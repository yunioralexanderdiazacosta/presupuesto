<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import FalconPieChart from '@/Components/FalconPieChart.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_amount: 0,
            total_count: 0,
            avg_per_outflow: 0
        })
    },
    investments: {
        type: Object,
        default: () => ({
            total: 0,
            count: 0
        })
    },
    expenses: {
        type: Object,
        default: () => ({
            total: 0,
            count: 0
        })
    },
    invoices: {
        type: Object,
        default: () => ({
            total: 0,
            count: 0
        })
    },
    creditNotes: {
        type: Object,
        default: () => ({
            total: 0,
            count: 0
        })
    },
    debitNotes: {
        type: Object,
        default: () => ({
            total: 0,
            count: 0
        })
    },
    byLevel1: {
        type: Object,
        default: () => ({
            labels: [],
            data: []
        })
    },
    byProject: {
        type: Object,
        default: () => ({
            labels: [],
            data: []
        })
    },
    byDevelopmentState: {
        type: Array,
        default: () => []
    },
    byDevelopmentStateWithoutInvestments: {
        type: Array,
        default: () => []
    },
    costoKiloAcumulado: {
        type: Object,
        default: () => ({
            totalProduccion: 0,
            totalKilos: 0,
            costoKilo: 0
        })
    }
});

const title = 'Dashboard de Outflows';

const links = [
    { title: 'Gestión' },
    { title: 'Dashboard Outflows', active: true }
];

// Variables para conversión USD
const divisor = ref(970);
const divisorMin = 800;
const divisorMax = 1100;
const dividir = ref(false); // Por defecto desactivado

// Formatear números con separador de miles (sin decimales)
const formatNumber = (number) => {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.round(number));
};

// Formatear costo kilo con 1 decimal
const formatCostoKilo = (number) => {
    if (number === null || number === undefined) return '0,0';
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    }).format(number);
};

// Formatear moneda
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '$0';
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Preparar datos para el gráfico de torta (pie chart)
const pieChartData = computed(() => {
    if (!props.byProject || !props.byProject.labels || !props.byProject.data) {
        return { labels: [], datasets: [] };
    }
    // Aplicar conversión si está activada
    const convertedData = dividir.value && divisor.value 
        ? props.byProject.data.map(value => value / divisor.value)
        : props.byProject.data;
    
    return {
        labels: props.byProject.labels,
        datasets: [{
            data: convertedData
        }]
    };
});

// Preparar datos para el gráfico de torta de Level1
const pieChartLevel1Data = computed(() => {
    if (!props.byLevel1 || !props.byLevel1.labels || !props.byLevel1.data) {
        return { labels: [], datasets: [] };
    }
    // Aplicar conversión si está activada
    const convertedData = dividir.value && divisor.value 
        ? props.byLevel1.data.map(value => value / divisor.value)
        : props.byLevel1.data;
    
    return {
        labels: props.byLevel1.labels,
        datasets: [{
            data: convertedData
        }]
    };
});

// Datos convertidos para gráfico de barras de Project
const convertedProjectData = computed(() => {
    if (!props.byProject || !props.byProject.data) return [];
    return dividir.value && divisor.value 
        ? props.byProject.data.map(value => value / divisor.value)
        : props.byProject.data;
});

// Datos convertidos para gráfico de barras de Level1
const convertedLevel1Data = computed(() => {
    if (!props.byLevel1 || !props.byLevel1.data) return [];
    return dividir.value && divisor.value 
        ? props.byLevel1.data.map(value => value / divisor.value)
        : props.byLevel1.data;
});

// Calcular total de compras: Facturas + Crédito - Débito
const totalCompras = computed(() => {
    const facturas = props.invoices?.total || 0;
    const credito = props.creditNotes?.total || 0;
    const debito = props.debitNotes?.total || 0;
    return facturas + debito - credito;
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
       
        
        <div class="card mb-3 mt-2">
            <div class="card-header py-2">
                <div class="row flex-between-end align-items-center">
                    <div class="col-auto align-self-center">
                        <h6 class="mb-0 text-nowrap">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Dashboard de Análisis de Consumos y Facturación.
                        </h6>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="form-check form-switch d-flex align-items-center mb-0">
                                <input class="form-check-input" type="checkbox" id="dividir-switch" v-model="dividir">
                                <label class="form-check-label ms-2 mt-0 mb-0 small" for="dividir-switch">Ver en USD</label>
                            </div>
                            <template v-if="dividir">
                                <div class="d-flex align-items-center" style="min-width:220px;">
                                    <label for="divisor-slider" class="form-label mb-0 me-2 small">Divisor:</label>
                                    <input id="divisor-slider" type="range" class="form-range" 
                                           v-model.number="divisor" :min="divisorMin" :max="divisorMax" :step="1" 
                                           style="max-width:150px;" />
                                    <span class="text-muted small ms-2"><b>{{ divisor }}</b></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary py-3">
                <!-- Título Sección Consumos -->
                <h6 class="text-secondary mb-2 d-flex align-items-center">
                    <i class="fas fa-chart-line me-2 fs-8"></i>
                    <span>Análisis de Consumos</span>
                </h6>

                <!-- KPI Cards Fila 1: Consumos -->
                <div class="row g-2 mb-2">
                    <!-- Total Outflows Card -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Consumido</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (summary?.total_amount || 0) / divisor : (summary?.total_amount || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(summary?.total_count || 0) }} registros
                                        </small>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Inversiones Card -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Inversiones</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (investments?.total || 0) / divisor : (investments?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(investments?.count || 0) }} registros
                                        </small>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Gastos Card -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Gastos</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (expenses?.total || 0) / divisor : (expenses?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(expenses?.count || 0) }} registros
                                        </small>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-receipt fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <div class="row mb-2">
                    <div class="col-12">
                        <hr class="my-1 opacity-25">
                    </div>
                </div>

                <!-- Título Sección Compras -->
                <h6 class="text-secondary mb-2 d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2 fs-8"></i>
                    <span>Detalle de Compras</span>
                </h6>

                <!-- KPI Cards Fila 2: Compras -->
                <div class="row g-2 mb-3">
                    <!-- Total Facturas Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-3" style="border-color: #6FB550 !important;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Facturas</small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (invoices?.total || 0) / divisor : (invoices?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(invoices?.count || 0) }} facturas
                                        </small>
                                    </div>
                                    <div style="color: #6FB550;">
                                        <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Notas de Débito Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-3" style="border-color: #6FB550 !important;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Notas de Débito</small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (debitNotes?.total || 0) / divisor : (debitNotes?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(debitNotes?.count || 0) }} notas
                                        </small>
                                    </div>
                                    <div style="color: #6FB550;">
                                        <i class="fas fa-plus-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Notas de Crédito Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-3" style="border-color: #6FB550 !important;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Notas de Crédito</small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (creditNotes?.total || 0) / divisor : (creditNotes?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(creditNotes?.count || 0) }} notas
                                        </small>
                                    </div>
                                    <div style="color: #6FB550;">
                                        <i class="fas fa-minus-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Compras Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-3" style="border-color: #60A145 !important;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Compras</small>
                                        <h4 class="mb-0 fw-bold" style="color: #60A145;">
                                            {{ formatNumber(dividir && divisor ? totalCompras / divisor : totalCompras) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            Facturas + Débito - Crédito
                                        </small>
                                    </div>
                                    <div style="color: #60A145;">
                                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <div class="row mb-2 mt-3">
                    <div class="col-12">
                        <hr class="my-1 opacity-25">
                    </div>
                </div>

                <!-- Título Sección Estados de Desarrollo -->
                <h6 class="text-secondary mb-2 d-flex align-items-center">
                    <i class="fas fa-layer-group me-2 fs-8"></i>
                    <span>Consumos por Estado de Desarrollo</span>
                </h6>

                <!-- Card Totales por Estado de Desarrollo -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="card border-start border-info border-3">
                            <div class="card-header bg-transparent py-2">
                                <h6 class="mb-0 text-info">
                                    <i class="fas fa-seedling me-2"></i>
                                    Resumen con Inversiones
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="byDevelopmentState && byDevelopmentState.length > 0" class="list-group list-group-flush">
                                    <div 
                                        v-for="state in byDevelopmentState" 
                                        :key="state.id"
                                        class="list-group-item d-flex justify-content-between align-items-center py-2 px-3"
                                    >
                                        <span class="fw-medium">
                                            <i class="fas fa-circle text-info me-2" style="font-size: 8px;"></i>
                                            {{ state.name }}
                                        </span>
                                        <span class="fs-8">
                                            {{ formatNumber(dividir && divisor ? state.total / divisor : state.total) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No hay datos de estados de desarrollo disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nuevo Card: Sin Inversiones -->
                    <div class="col-md-6">
                        <div class="card border-start border-warning border-3">
                            <div class="card-header bg-transparent py-2">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-filter me-2"></i>
                                    Sin Inversiones
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="byDevelopmentStateWithoutInvestments && byDevelopmentStateWithoutInvestments.length > 0" class="list-group list-group-flush">
                                    <div 
                                        v-for="state in byDevelopmentStateWithoutInvestments" 
                                        :key="state.id"
                                        class="list-group-item d-flex justify-content-between align-items-center py-2 px-3"
                                    >
                                        <span class="fw-medium">
                                            <i class="fas fa-circle text-warning me-2" style="font-size: 8px;"></i>
                                            {{ state.name }}
                                        </span>
                                        <span class="fs-8">
                                            {{ formatNumber(dividir && divisor ? state.total / divisor : state.total) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No hay datos disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Costo Kilo Acumulado -->
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="card border-start border-success border-3 shadow-sm">
                            <div class="card-header bg-transparent py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 text-success">
                                        <i class="fas fa-calculator me-2"></i>
                                        Costo Kilo Acumulado
                                    </h6>
                                    <small class="text-muted">Producción / Kilos Estimados</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Total Producción -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                Total Costos Producción
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(dividir && divisor ? costoKiloAcumulado.totalProduccion / divisor : costoKiloAcumulado.totalProduccion) }} <small class="text-secondary">{{ dividir ? 'USD' : 'CLP' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Kilos -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                Total Kilos Estimados
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(costoKiloAcumulado.totalKilos) }} <small class="text-secondary">Kg</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Costo por Kilo -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded border border-success">
                                            <small class="text-uppercase text-success d-block mb-2" style="font-size: 0.75rem; font-weight: 700;">
                                                <i class="fas fa-star me-1"></i> Costo / Kilo Acumulado
                                            </small>
                                            <div class="fs-7 text-success">
                                                {{ formatCostoKilo(dividir && divisor ? costoKiloAcumulado.costoKilo / divisor : costoKiloAcumulado.costoKilo) }} <small class="text-success fw-medium">{{ dividir ? 'USD' : 'CLP' }}/Kg</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mensaje si no hay datos -->
                                <div v-if="!costoKiloAcumulado.totalKilos || !costoKiloAcumulado.totalProduccion" class="alert alert-warning mt-3 mb-0 py-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>
                                        <span v-if="!costoKiloAcumulado.totalKilos">No hay estimaciones de kilos registradas. </span>
                                        <span v-if="!costoKiloAcumulado.totalProduccion">No hay gastos de producción registrados.</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área para gráficos y análisis -->
                <div class="row g-3">
                    <div class="col-12">
                        <div class="row g-3">
                            <!-- Gráfico de Barras -->
                            <div class="col-lg-7">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-bar text-success me-2"></i>
                                            Monto Total Gastado por Proyecto
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <FalconBarChart
                                            v-if="byProject.labels && byProject.labels.length > 0"
                                            :barLabels="byProject.labels"
                                            :barData="convertedProjectData"
                                            :height="350"
                                            :color="['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#6366f1']"
                                        />
                                        <div v-else class="text-center py-5">
                                            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay datos disponibles</h5>
                                            <p class="text-muted mb-0">
                                                Aún no hay gastos por proyecto registrados
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Torta (Porcentaje) -->
                            <div class="col-lg-5">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-pie text-info me-2"></i>
                                            Distribución Porcentual por Proyecto
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <FalconPieChart
                                            v-if="pieChartData.labels && pieChartData.labels.length > 0"
                                            :pieLabels="pieChartData.labels"
                                            :pieDatasets="pieChartData.datasets"
                                            :showPercentage="true"
                                        />
                                        <div v-else class="text-center py-5">
                                            <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay datos disponibles</h5>
                                            <p class="text-muted mb-0">
                                                Aún no hay gastos por proyecto registrados
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <!-- Gráfico de Barras Level1 -->
                            <div class="col-lg-7">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-bar text-primary me-2"></i>
                                            Clasificación por producto y Nivel 1
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <FalconBarChart
                                            v-if="byLevel1.labels && byLevel1.labels.length > 0"
                                            :barLabels="byLevel1.labels"
                                            :barData="convertedLevel1Data"
                                            :height="350"
                                            :color="['#60a5fa', '#34d399', '#fbbf24', '#fb7185', '#a78bfa', '#2dd4bf', '#f472b6', '#818cf8']"
                                        />
                                        <div v-else class="text-center py-5">
                                            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay datos disponibles</h5>
                                            <p class="text-muted mb-0">
                                                Aún no hay salidas registradas para mostrar en el gráfico
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Torta Level1 (Porcentaje) -->
                            <div class="col-lg-5">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-pie text-primary me-2"></i>
                                            Distribución Porcentual por Nivel 1
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <FalconPieChart
                                            v-if="pieChartLevel1Data.labels && pieChartLevel1Data.labels.length > 0"
                                            :pieLabels="pieChartLevel1Data.labels"
                                            :pieDatasets="pieChartLevel1Data.datasets"
                                            :showPercentage="true"
                                        />
                                        <div v-else class="text-center py-5">
                                            <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay datos disponibles</h5>
                                            <p class="text-muted mb-0">
                                                Aún no hay salidas registradas
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.opacity-50 {
    opacity: 0.5;
}
</style>
