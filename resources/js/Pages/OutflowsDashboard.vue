<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import { computed } from 'vue';

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
    }
});

const title = 'Dashboard de Outflows';

const links = [
    { title: 'Gestión' },
    { title: 'Dashboard Outflows', active: true }
];

// Formatear números con separador de miles
const formatNumber = (number) => {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat('es-CL').format(number);
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
        <Breadcrumb :links="links" />
        
        <div class="card mb-3 mt-2">
            <div class="card-header py-2">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h6 class="mb-0 text-nowrap">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Dashboard de Análisis de Consumos y Facturación.
                        </h6>
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
                                            {{ formatCurrency(summary?.total_amount || 0) }}
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
                                            {{ formatCurrency(investments?.total || 0) }}
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
                                            {{ formatCurrency(expenses?.total || 0) }}
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
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Facturas</small>
                                        <h4 class="mb-0 text-success fw-bold">
                                            {{ formatCurrency(invoices?.total || 0) }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(invoices?.count || 0) }} facturas
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Notas de Crédito Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Notas de Crédito</small>
                                        <h4 class="mb-0 text-success fw-bold">
                                            {{ formatCurrency(creditNotes?.total || 0) }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(creditNotes?.count || 0) }} notas
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-minus-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Notas de Débito Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Notas de Débito</small>
                                        <h4 class="mb-0 text-success fw-bold">
                                            {{ formatCurrency(debitNotes?.total || 0) }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(debitNotes?.count || 0) }} notas
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-plus-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Compras Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">Total Compras</small>
                                        <h4 class="mb-0 text-success fw-bold">
                                            {{ formatCurrency(totalCompras) }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            Facturas + Débito - Crédito
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área para gráficos y análisis -->
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar text-primary me-2"></i>
                                    Total de Salidas por Clasificación Nivel 1
                                </h6>
                            </div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="byLevel1.labels && byLevel1.labels.length > 0"
                                    :barLabels="byLevel1.labels"
                                    :barData="byLevel1.data"
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
