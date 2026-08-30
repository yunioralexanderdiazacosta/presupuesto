<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

Chart.register(...registerables);

const props = defineProps({
    summary: Object,
    dollarPrice: { type: Number, default: 970 },
    isAdmin:     { type: Boolean, default: false },
    monthlyComparison: Object,
    investmentDetails: Array,
    byLevel3: { type: Array, default: () => [] },
    months: Array,
});

const byLevel3Total = computed(() =>
    (props.byLevel3 || []).reduce((sum, r) => sum + r.total, 0)
);

// Expand/collapse tabla inversiones por level3
const expandedGroups = ref(new Set());
const toggleInvGroup = (key) => {
    if (expandedGroups.value.has(key)) {
        expandedGroups.value.delete(key);
    } else {
        expandedGroups.value.add(key);
    }
    expandedGroups.value = new Set(expandedGroups.value);
};
const expandAllInv = () => {
    const keys = [];
    (props.byLevel3 || []).forEach((inv) => {
        keys.push('inv-' + inv.investment_id);
        (inv.level3s || []).forEach((l3) => {
            keys.push('l3-' + inv.investment_id + '-' + l3.level3_id);
            (l3.level2s || []).forEach((l2) => {
                keys.push('l2-' + inv.investment_id + '-' + l3.level3_id + '-' + l2.level2_name);
            });
        });
    });
    expandedGroups.value = new Set(keys);
};
const collapseAllInv = () => {
    expandedGroups.value = new Set();
};

let monthlyChart = null;
let comparisonBarChart = null;

// Toggle idioma ES/EN
const isEnglish = ref(false);
const t = computed(() => isEnglish.value ? {
    dashboardTitle: 'Investment Dashboard',
    budgeted: 'Budgeted',
    real: 'Actual (Consumed)',
    difference: 'Difference',
    execution: '% Execution',
    underBudget: '✅ Under Budget',
    overBudget: '⚠️ Over Budget',
    totalInvestments: 'Total Investments',
    executed: 'Executed',
    monthlyTitle: 'Monthly: Budgeted vs Actual',
    budgetedLabel: 'Budgeted',
    realLabel: 'Actual',
    detailTitle: 'Investment Detail',
    byCCTitle: 'By Cost Center',
    name: 'Name',
    month: 'Month',
    status: 'Status',
    costCenters: 'Cost Centers',
    observations: 'Observations',
    costCenter: 'Cost Center',
    variance: 'Variance',
    viewInUSD: 'View in USD',
    divisor: 'Divisor',
    comparisonTitle: 'Top Investments: Budgeted vs Actual',
} : {
    dashboardTitle: 'Dashboard de Inversiones',
    budgeted: 'Presupuestado',
    real: 'Real (Consumido)',
    difference: 'Diferencia',
    execution: '% Ejecución',
    underBudget: '✅ Bajo Presupuesto',
    overBudget: '⚠️ Sobrepresupuesto',
    totalInvestments: 'Total Inversiones',
    executed: 'Ejecutadas',
    monthlyTitle: 'Mensual: Presupuestado vs Real',
    budgetedLabel: 'Presupuestado',
    realLabel: 'Real',
    detailTitle: 'Detalle por Inversión',
    byCCTitle: 'Por Centro de Costo',
    name: 'Nombre',
    month: 'Mes',
    status: 'Estado',
    costCenters: 'Centros de Costo',
    observations: 'Observaciones',
    costCenter: 'Centro de Costo',
    variance: 'Variación',
    viewInUSD: 'Ver en USD',
    divisor: 'Divisor',
    comparisonTitle: 'Top Inversiones: Presupuestado vs Real',
});

// Variables para conversión USD
const divisor = ref(props.dollarPrice);
const divisorMin = 800;
const divisorMax = 1300;
const dividir = ref(false);
const savingDollar = ref(false);

const saveDollarPrice = async () => {
    if (!props.isAdmin) return;
    savingDollar.value = true;
    try {
        await axios.patch(route('api.dollar-price.update'), { dollar_price: divisor.value });
    } catch (e) {
        console.error('Error guardando tipo de cambio', e);
    } finally {
        savingDollar.value = false;
    }
};

// Formatear números en formato chileno (con conversión USD opcional)
const formatCLP = (value) => {
    if (!value && value !== 0) return dividir.value ? '$0 USD' : '$0';
    const convertedValue = dividir.value && divisor.value ? value / divisor.value : value;
    return '$' + parseFloat(convertedValue).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + (dividir.value ? ' USD' : '');
};

const formatPercent = (value) => {
    if (!value && value !== 0) return '0%';
    return parseFloat(value).toFixed(1) + '%';
};

const getStatusBadgeClass = (execution) => {
    if (execution > 110) return 'bg-danger';
    if (execution > 100) return 'bg-warning text-dark';
    if (execution > 75) return 'bg-info';
    return 'bg-success';
};

const getEstadoBadge = (estado) => {
    const map = {
        'planificada': 'bg-secondary',
        'en proceso': 'bg-info',
        'ejecutada': 'bg-success',
        'cancelada': 'bg-danger',
    };
    return map[estado?.toLowerCase()] || 'bg-secondary';
};

// Datos para exportar a Excel - Detalle por inversión
const excelDetailData = computed(() => {
    return props.investmentDetails.map(item => ({
        'Nombre': item.name,
        'Estado': item.estado,
        'Presupuestado': dividir.value && divisor.value ? item.budgeted / divisor.value : item.budgeted,
        'Real': dividir.value && divisor.value ? item.real / divisor.value : item.real,
        'Diferencia': dividir.value && divisor.value ? item.difference / divisor.value : item.difference,
        'Ejecución %': item.execution?.toFixed(1),
        'Centros de Costo': item.cost_centers,
    }));
});

onMounted(() => {
    createMonthlyChart();
    createComparisonBarChart();
});

watch([dividir, divisor, isEnglish], () => {
    createMonthlyChart();
    createComparisonBarChart();
});

function createMonthlyChart() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;

    if (monthlyChart) monthlyChart.destroy();

    const convertedBudget = dividir.value && divisor.value
        ? props.monthlyComparison.budgeted.map(v => v / divisor.value)
        : props.monthlyComparison.budgeted;

    const convertedReal = dividir.value && divisor.value
        ? props.monthlyComparison.real.map(v => v / divisor.value)
        : props.monthlyComparison.real;

    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.monthlyComparison.labels,
            datasets: [
                {
                    label: t.value.budgetedLabel,
                    data: convertedBudget,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: t.value.realLabel,
                    data: convertedReal,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: t.value.monthlyTitle + (dividir.value ? ' (USD)' : ' (CLP)'),
                    font: { size: 16 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            label += '$' + context.parsed.y.toLocaleString('es-CL') + (dividir.value ? ' USD' : '');
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M' + (dividir.value ? ' USD' : '');
                        }
                    }
                }
            }
        }
    });
}

function createComparisonBarChart() {
    const ctx = document.getElementById('comparisonBarChart');
    if (!ctx) return;

    if (comparisonBarChart) comparisonBarChart.destroy();

    // Top 10 inversiones con mayor presupuesto
    const sorted = [...props.investmentDetails]
        .sort((a, b) => b.budgeted - a.budgeted)
        .slice(0, 10);

    const labels = sorted.map(i => i.name.length > 20 ? i.name.substring(0, 20) + '...' : i.name);

    const convertedBudget = dividir.value && divisor.value
        ? sorted.map(i => i.budgeted / divisor.value)
        : sorted.map(i => i.budgeted);

    const convertedReal = dividir.value && divisor.value
        ? sorted.map(i => i.real / divisor.value)
        : sorted.map(i => i.real);

    comparisonBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: t.value.budgetedLabel,
                    data: convertedBudget,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: t.value.realLabel,
                    data: convertedReal,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: { size: 13 }
                    }
                },
                title: {
                    display: true,
                    text: t.value.comparisonTitle + (dividir.value ? ' (USD)' : ' (CLP)'),
                    font: { size: 16 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            label += '$' + context.parsed.x.toLocaleString('es-CL') + (dividir.value ? ' USD' : '');
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                },
                y: {
                    ticks: {
                        font: { size: 13 }
                    }
                }
            }
        }
    });
}
</script>

<template>
    <AppLayout title="Dashboard de Inversiones">
        <div class="my-3">
            <!-- Header -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row flex-between-center">
                        <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                <i class="fas fa-chart-pie me-2"></i>{{ t.dashboardTitle }}
                            </h5>
                        </div>
                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                <!-- Toggle para conversión USD -->
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="dividir-switch" 
                                        v-model="dividir"
                                    >
                                    <label class="form-check-label ms-2 mt-0 mb-0 small" for="dividir-switch">{{ t.viewInUSD }}</label>
                                </div>

                                <!-- Separador -->
                                <div class="vr d-none d-md-block" style="height: 24px;"></div>

                                <!-- Toggle idioma ES/EN -->
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="lang-switch" 
                                        v-model="isEnglish"
                                    >
                                    <label class="form-check-label ms-2 mt-0 mb-0 small" for="lang-switch">
                                        <span v-if="isEnglish">🇺🇸 EN</span>
                                        <span v-else>🇨🇱 ES</span>
                                    </label>
                                </div>
                                
                                <!-- Slider de divisor (solo visible cuando dividir está activo) -->
                                <template v-if="dividir">
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="divisor-slider" class="form-label mb-0 me-2 small">{{ t.divisor }}:</label>
                                        <input
                                            id="divisor-slider"
                                            type="range"
                                            class="form-range"
                                            v-model.number="divisor"
                                            :min="divisorMin"
                                            :max="divisorMax"
                                            :step="1"
                                            style="width:220px; flex-shrink:0;"
                                        />
                                        <span class="text-muted small ms-1"><b>{{ divisor }}</b></span>
                                        <button v-if="isAdmin" @click="saveDollarPrice" :disabled="savingDollar"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2"
                                            title="Guardar como valor predeterminado para el equipo">
                                            <i class="fas fa-save fa-xs" :class="{'fa-spin fa-circle-notch': savingDollar}"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row g-3 mb-3">
                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.budgeted }}</h6>
                                <i class="fas fa-calculator text-primary fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-primary text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(summary.budgeted_total) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(summary.budgeted_per_hectare) }}/ha</small>
                            <div v-if="summary.unassigned_budgeted > 0" class="small text-warning mt-1" style="font-size: 0.7rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ formatCLP(summary.unassigned_budgeted) }} sin inversión asignada
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">Real Inversión</h6>
                                <i class="fas fa-receipt text-danger fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-danger text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(summary.real_total) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(summary.real_per_hectare) }}/ha</small>
                            <div v-if="summary.unassigned_real > 0" class="small text-warning mt-1" style="font-size: 0.7rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ formatCLP(summary.unassigned_real) }} sin inversión asignada
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.difference }}</h6>
                                <i :class="['fas', summary.difference >= 0 ? 'fa-arrow-down text-success' : 'fa-arrow-up text-danger']"></i>
                            </div>
                            <h4 class="mb-0 text-nowrap" style="font-size: 1.15rem;" :class="summary.difference >= 0 ? 'text-success' : 'text-danger'">
                                {{ formatCLP(Math.abs(summary.difference)) }}
                            </h4>
                            <span :class="['badge', summary.difference >= 0 ? 'bg-success' : 'bg-danger']">
                                {{ summary.difference >= 0 ? t.underBudget : t.overBudget }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.execution }}</h6>
                                <i class="fas fa-percent text-info fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-info text-nowrap" style="font-size: 1.15rem;">{{ formatPercent(summary.percentage_execution) }}</h4>
                            <small class="text-muted">{{ summary.executed_count }}/{{ summary.total_count }} {{ t.executed }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="comparisonBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla Detalle por Inversión -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>{{ t.detailTitle }}
                                </h6>
                                <ExportExcelButton
                                    :data="excelDetailData"
                                    :headers="[
                                        { label: 'Nombre', key: 'Nombre' },
                                        { label: 'Estado', key: 'Estado' },
                                        { label: 'Presupuestado', key: 'Presupuestado' },
                                        { label: 'Real', key: 'Real' },
                                        { label: 'Diferencia', key: 'Diferencia' },
                                        { label: 'Ejecución %', key: 'Ejecución %' },
                                        { label: 'Centros de Costo', key: 'Centros de Costo' },
                                    ]"
                                    filename="inversiones_detalle.xlsx"
                                    class="btn btn-falcon-default btn-sm"
                                >
                                    <i class="fas fa-file-excel me-1"></i>Exportar
                                </ExportExcelButton>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="border-0 py-2"><span class="text-uppercase fw-bold">{{ t.name }}</span></th>
                                            <th class="border-0 py-2 text-center"><span class="text-uppercase fw-bold">{{ t.status }}</span></th>
                                            <th class="border-0 py-2 text-end"><span class="text-uppercase fw-bold">{{ t.budgeted }}</span></th>
                                            <th class="border-0 py-2 text-end"><span class="text-uppercase fw-bold">Real Inversión</span></th>
                                            <th class="border-0 py-2 text-end"><span class="text-uppercase fw-bold">{{ t.difference }}</span></th>
                                            <th class="border-0 py-2 text-end"><span class="text-uppercase fw-bold">{{ t.execution }}</span></th>
                                            <th class="border-0 py-2"><span class="text-uppercase fw-bold">{{ t.costCenters }}</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in investmentDetails" :key="item.id" :class="{ 'table-warning': item.unassigned }">
                                            <td class="fw-semibold">
                                                {{ item.name }}
                                                <i v-if="item.unassigned" class="fas fa-exclamation-triangle text-warning ms-1" v-tooltip="item.observations"></i>
                                            </td>
                                            <td class="text-center">
                                                <span v-if="item.estado" class="badge" :class="getEstadoBadge(item.estado)">{{ item.estado }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end">{{ formatCLP(item.budgeted) }}</td>
                                            <td class="text-end">{{ formatCLP(item.real) }}</td>
                                            <td class="text-end fw-bold" :class="item.difference >= 0 ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs(item.difference)) }}
                                                <i :class="['fas', 'fa-xs', 'ms-1', item.difference >= 0 ? 'fa-arrow-down' : 'fa-arrow-up']"></i>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge" :class="getStatusBadgeClass(item.execution)">
                                                    {{ formatPercent(item.execution) }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ item.cost_centers || '-' }}</td>
                                        </tr>
                                        <!-- Fila de totales -->
                                        <tr class="table-primary fw-bold">
                                            <td colspan="2">TOTAL</td>
                                            <td class="text-end">{{ formatCLP(summary.budgeted_total) }}</td>
                                            <td class="text-end">{{ formatCLP(summary.real_total) }}</td>
                                            <td class="text-end" :class="summary.difference >= 0 ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs(summary.difference)) }}
                                            </td>
                                            <td class="text-end">
                                                <span class="badge" :class="getStatusBadgeClass(summary.percentage_execution)">
                                                    {{ formatPercent(summary.percentage_execution) }}
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        <!-- begin: Tabla por Level3 -->
        <div class="card my-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-table text-info me-2"></i>Salidas de inversión por Nivel 3
                </h6>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllInv" v-tooltip="'Expandir todo'">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllInv" v-tooltip="'Colapsar todo'">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div v-if="!byLevel3 || byLevel3.length === 0" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>Sin salidas de inversión registradas
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 py-2" style="width:55%;">
                                    <span class="text-uppercase fw-bold">Inversión / Nivel 3 / Nivel 2 / Producto</span>
                                </th>
                                <th class="border-0 py-2 text-end" style="width:30%;">
                                    <span class="text-uppercase fw-bold">Costo Total</span>
                                </th>
                                <th class="border-0 py-2 text-end" style="width:15%;">
                                    <span class="text-uppercase fw-bold">%</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="inv in byLevel3" :key="inv.investment_id">
                                <!-- Nivel 1: Inversión -->
                                <tr :class="inv.investment_id === 0 ? 'table-warning' : 'table-light'" style="cursor:pointer;" @click="toggleInvGroup('inv-' + inv.investment_id)">
                                    <td class="py-2 fw-bold text-primary">
                                        <i class="fas me-2" :class="expandedGroups.has('inv-' + inv.investment_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ inv.investment_name }}
                                        <i v-if="inv.investment_id === 0" class="fas fa-exclamation-triangle text-warning ms-1" v-tooltip="'Salidas clasificadas como Inversión sin vincular a una inversión específica'"></i>
                                        <small class="text-muted ms-1">({{ inv.level3s.length }})</small>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-primary">{{ formatCLP(inv.total) }}</td>
                                    <td class="py-2 text-end">
                                        <span class="badge bg-primary">{{ byLevel3Total > 0 ? (inv.total / byLevel3Total * 100).toFixed(1) : '0.0' }}%</span>
                                    </td>
                                </tr>
                                <template v-if="expandedGroups.has('inv-' + inv.investment_id)">
                                    <!-- Nivel 2: Level3 -->
                                    <template v-for="l3 in inv.level3s" :key="'l3-' + inv.investment_id + '-' + l3.level3_id">
                                        <tr style="cursor:pointer;" @click="toggleInvGroup('l3-' + inv.investment_id + '-' + l3.level3_id)">
                                            <td class="py-2 ps-4 fw-semibold text-dark">
                                                <i class="fas me-2 text-muted" :class="expandedGroups.has('l3-' + inv.investment_id + '-' + l3.level3_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                <i class="fas fa-layer-group fa-xs me-1 text-muted"></i>{{ l3.level3_name }}
                                            </td>
                                            <td class="py-2 text-end">{{ formatCLP(l3.total) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-secondary">{{ inv.total > 0 ? (l3.total / inv.total * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                        <template v-if="expandedGroups.has('l3-' + inv.investment_id + '-' + l3.level3_id)">
                                            <!-- Nivel 3: Level2 -->
                                            <template v-for="l2 in l3.level2s" :key="'l2-' + inv.investment_id + '-' + l3.level3_id + '-' + l2.level2_name">
                                                <tr style="cursor:pointer;" @click="toggleInvGroup('l2-' + inv.investment_id + '-' + l3.level3_id + '-' + l2.level2_name)">
                                                    <td class="py-2 ps-5 text-muted">
                                                        <i class="fas me-2 text-muted" :class="expandedGroups.has('l2-' + inv.investment_id + '-' + l3.level3_id + '-' + l2.level2_name) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                        <i class="fas fa-folder fa-xs me-1 text-warning"></i>{{ l2.level2_name }}
                                                    </td>
                                                    <td class="py-2 text-end">{{ formatCLP(l2.total) }}</td>
                                                    <td class="py-2 text-end">
                                                        <span class="badge bg-secondary" style="opacity:0.7;">{{ l3.total > 0 ? (l2.total / l3.total * 100).toFixed(1) : '0.0' }}%</span>
                                                    </td>
                                                </tr>
                                                <!-- Nivel 4: Producto -->
                                                <template v-if="expandedGroups.has('l2-' + inv.investment_id + '-' + l3.level3_id + '-' + l2.level2_name)">
                                                    <tr v-for="prod in l2.products" :key="prod.name">
                                                        <td class="py-1 text-muted" style="padding-left:4.5rem;">
                                                            <i class="fas fa-box fa-xs me-2"></i>{{ prod.name }}
                                                        </td>
                                                        <td class="py-1 text-end text-muted">{{ formatCLP(prod.total) }}</td>
                                                        <td class="py-1 text-end text-muted" style="font-size:0.8rem;">
                                                            {{ l2.total > 0 ? (prod.total / l2.total * 100).toFixed(1) : '0.0' }}%
                                                        </td>
                                                    </tr>
                                                </template>
                                            </template>
                                        </template>
                                    </template>
                                </template>
                            </template>
                            <!-- Fila total -->
                            <tr class="table-primary fw-bold">
                                <td class="py-2">TOTAL</td>
                                <td class="py-2 text-end">{{ formatCLP(byLevel3Total) }}</td>
                                <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- end: Tabla por Level3 -->

        </div>
    </AppLayout>
</template>
