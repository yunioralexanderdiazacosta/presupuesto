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
    months: Array,
});

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
        'Mes': item.month_name,
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
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
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
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.real }}</h6>
                                <i class="fas fa-receipt text-danger fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-danger text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(summary.real_total) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(summary.real_per_hectare) }}/ha</small>
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
                                        { label: 'Mes', key: 'Mes' },
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
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-striped mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ t.name }}</th>
                                            <th class="text-center">{{ t.month }}</th>
                                            <th class="text-center">{{ t.status }}</th>
                                            <th class="text-end">{{ t.budgeted }}</th>
                                            <th class="text-end">{{ t.real }}</th>
                                            <th class="text-end">{{ t.difference }}</th>
                                            <th class="text-end">{{ t.execution }}</th>
                                            <th>{{ t.costCenters }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in investmentDetails" :key="item.id">
                                            <td class="fw-semibold">{{ item.name }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark">{{ item.month_name }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="getEstadoBadge(item.estado)">{{ item.estado }}</span>
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
                                        <tr class="table-dark fw-bold">
                                            <td colspan="3">TOTAL</td>
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


        </div>
    </AppLayout>
</template>
