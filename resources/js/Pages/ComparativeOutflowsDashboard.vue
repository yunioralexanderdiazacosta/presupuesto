<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

Chart.register(...registerables);

const props = defineProps({
    summary: Object,
    monthlyComparison: Object,
    cumulativeComparison: Object,
    comparisonByLevel1: Array,
    comparisonByLevel2: Array,
    detailedTable: Array,
    months: Array,
    seasonStartMonth: Number,
});

let monthlyChart = null;
let cumulativeChart = null;

// Toggle ÚNICO para incluir/excluir inversiones en TODO el dashboard
const includeInvestments = ref(false);

// Valores reactivos del presupuesto según el toggle
const displayedBudget = computed(() => 
    includeInvestments.value ? props.summary.budget_total_with_investments : props.summary.budget_total
);

const displayedBudgetPerHectare = computed(() => 
    includeInvestments.value ? props.summary.budget_per_hectare_with_investments : props.summary.budget_per_hectare
);

// Valores reactivos del consumido según el MISMO toggle
const displayedConsumed = computed(() => 
    includeInvestments.value ? props.summary.consumed_total_with_investments : props.summary.consumed_total
);

const displayedConsumedPerHectare = computed(() => 
    includeInvestments.value ? props.summary.consumed_per_hectare_with_investments : props.summary.consumed_per_hectare
);

// Facturado: cuando NO incluye inversiones, restar las inversiones CONSUMIDAS del facturado
const displayedInvoiced = computed(() => 
    includeInvestments.value ? props.summary.invoiced_total : (props.summary.invoiced_total - props.summary.consumed_investments_total)
);

const displayedInvoicedPerHectare = computed(() => {
    if (includeInvestments.value) {
        return props.summary.invoiced_per_hectare;
    }
    const investmentsPerHa = props.summary.total_surface > 0 ? (props.summary.consumed_investments_total / props.summary.total_surface) : 0;
    return props.summary.invoiced_per_hectare - investmentsPerHa;
});

const displayedDifference = computed(() => 
    displayedBudget.value - displayedInvoiced.value
);

const displayedPercentageExecution = computed(() => 
    displayedBudget.value > 0 ? (displayedInvoiced.value / displayedBudget.value) * 100 : 0
);

// Diferencia acumulada hasta el mes anterior al actual
const differenceToCurrentMonth = computed(() => {
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // 1-12
    
    // Presupuesto: usar según el toggle
    const budgetData = includeInvestments.value 
        ? props.cumulativeComparison.budget_with_investments_cumulative 
        : props.cumulativeComparison.budget_cumulative;
    
    // Facturado acumulado (sin ajustes)
    const realData = props.cumulativeComparison.real_cumulative;
    
    // Encontrar el índice del mes actual en el array de meses de la temporada
    let currentMonthIndex = -1;
    for (let i = 0; i < props.months.length; i++) {
        if (props.months[i].id === currentMonth) {
            currentMonthIndex = i;
            break;
        }
    }
    
    if (currentMonthIndex <= 0) {
        // Si no encontramos el mes o es el primer mes de la temporada, no hay mes anterior
        return null;
    }
    
    // Obtener datos del mes anterior (índice actual - 1)
    const previousMonthIndex = currentMonthIndex - 1;
    
    if (previousMonthIndex >= 0 && previousMonthIndex < budgetData.length) {
        const budgetUpToMonth = budgetData[previousMonthIndex] || 0;
        
        // Buscar el último valor real disponible hasta el mes anterior (puede ser null)
        let realUpToMonth = realData[previousMonthIndex];
        
        // Si es null, buscar el último valor no-null anterior
        if (realUpToMonth === null) {
            for (let i = previousMonthIndex - 1; i >= 0; i--) {
                if (realData[i] !== null) {
                    realUpToMonth = realData[i];
                    break;
                }
            }
        }
        
        realUpToMonth = realUpToMonth || 0;
        
        // Ajustar facturado si NO incluye inversiones
        // Restar directamente el total de inversiones consumidas
        let adjustedReal = realUpToMonth;
        if (!includeInvestments.value && props.summary.consumed_investments_total > 0) {
            adjustedReal = realUpToMonth - props.summary.consumed_investments_total;
        }
        
        return {
            difference: budgetUpToMonth - adjustedReal,
            monthName: props.months[previousMonthIndex]?.name || 'Mes anterior',
            budget: budgetUpToMonth,
            real: adjustedReal
        };
    }
    
    return null;
});

// Formatear números en formato chileno
const formatCLP = (value) => {
    if (!value && value !== 0) return '$0';
    return '$' + parseFloat(value).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
};

// Formatear porcentajes
const formatPercent = (value) => {
    if (!value && value !== 0) return '0%';
    return parseFloat(value).toFixed(1) + '%';
};

// Obtener clase de badge según variación
const getVarianceClass = (variance) => {
    if (variance > 10) return 'bg-danger';
    if (variance > 5) return 'bg-warning text-dark';
    if (variance > 0) return 'bg-info';
    return 'bg-success';
};

// Obtener icono según estado
const getStatusIcon = (status) => {
    if (status === 'over_budget' || status === 'over') return '⚠️';
    return '✅';
};

// Datos para exportar a Excel
const excelData = computed(() => {
    return props.detailedTable.map(item => ({
        'Categoría': item.category,
        'Presupuestado': item.budget,
        'Facturado': item.real,
        'Diferencia': item.difference,
        'Variación %': item.variance.toFixed(2),
    }));
});

onMounted(() => {
    console.log('Cumulative data:', props.cumulativeComparison);
    createMonthlyChart();
    createCumulativeChart();
});

// Watch para actualizar gráficos cuando cambie el toggle
watch(includeInvestments, () => {
    createMonthlyChart();
    createCumulativeChart();
});

// Gráfico de Barras Mensuales
function createMonthlyChart() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;

    if (monthlyChart) {
        monthlyChart.destroy();
    }

    // Usar datos según el toggle
    const budgetData = includeInvestments.value 
        ? props.monthlyComparison.budget_with_investments 
        : props.monthlyComparison.budget;

    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.monthlyComparison.labels,
            datasets: [
                {
                    label: includeInvestments.value ? 'Presupuestado (con inversiones)' : 'Presupuestado',
                    data: budgetData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Facturado',
                    data: props.monthlyComparison.real,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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
                    text: 'Comparativo Mensual: Presupuesto vs Facturado',
                    font: { size: 16 }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '$' + context.parsed.y.toLocaleString('es-CL');
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
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                }
            }
        }
    });
}

// Gráfico de Línea Acumulado
function createCumulativeChart() {
    const ctx = document.getElementById('cumulativeChart');
    if (!ctx) return;

    if (cumulativeChart) {
        cumulativeChart.destroy();
    }

    // Usar datos según el toggle
    const budgetCumulativeData = includeInvestments.value 
        ? props.cumulativeComparison.budget_with_investments_cumulative 
        : props.cumulativeComparison.budget_cumulative;

    const consumedCumulativeData = includeInvestments.value 
        ? props.cumulativeComparison.consumed_with_investments_cumulative 
        : props.cumulativeComparison.consumed_cumulative;

    cumulativeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.cumulativeComparison.labels,
            datasets: [
                {
                    label: includeInvestments.value 
                        ? 'Acumulado Presupuesto con Inversiones (Proyección completa)' 
                        : 'Acumulado Presupuesto (Proyección completa)',
                    data: budgetCumulativeData,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Acumulado Facturado (Real)',
                    data: props.cumulativeComparison.real_cumulative,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    spanGaps: false // No conectar puntos null
                },
                {
                    label: includeInvestments.value 
                        ? 'Acumulado Consumido con Inversiones (Real)' 
                        : 'Acumulado Consumido (Real)',
                    data: consumedCumulativeData,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    spanGaps: false // No conectar puntos null
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Evolución Acumulada - Real vs Proyección',
                    font: { size: 16 }
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.parsed.y === null) return null;
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '$' + context.parsed.y.toLocaleString('es-CL');
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
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            }
        }
    });
}
</script>

<template>
    <AppLayout title="Dashboard Comparativo - Presupuesto vs Facturado">
        <div class="my-3">
            <!-- Header -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row flex-between-center">
                        <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                <i class="fas fa-chart-line me-2"></i>Dashboard Comparativo
                            </h5>
                        </div>
                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                            <div class="d-flex align-items-center gap-2">
                                <!-- Toggle maestro para inversiones -->
                                <div class="form-check form-switch mb-0">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="includeInvestmentsToggle"
                                        v-model="includeInvestments"
                                    >
                                    <label class="form-check-label text-muted small" for="includeInvestmentsToggle">
                                        <span v-if="includeInvestments">
                                            <i class="fas fa-check-circle text-success"></i> Con inversiones
                                        </span>
                                        <span v-else>
                                            <i class="fas fa-times-circle text-secondary"></i> Sin inversiones
                                        </span>
                                    </label>
                                </div>
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
                                <h6 class="mb-0 text-muted">Presupuestado</h6>
                                <i class="fas fa-calculator text-primary fa-lg"></i>
                            </div>
                            <h3 class="mb-0 text-primary">{{ formatCLP(displayedBudget) }}</h3>
                            <small class="text-muted">{{ formatCLP(displayedBudgetPerHectare) }}/ha</small>
                            
                            <!-- Indicador de inversiones presupuestadas -->
                            <div v-if="includeInvestments && summary.total_investments > 0" class="mt-3">
                                <small class="text-info">
                                    <i class="fas fa-info-circle"></i>
                                    Inversiones presupuestadas: {{ formatCLP(summary.total_investments) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">Facturado</h6>
                                <i class="fas fa-file-invoice-dollar text-success fa-lg"></i>
                            </div>
                            <h3 class="mb-0 text-success">{{ formatCLP(displayedInvoiced) }}</h3>
                            <small class="text-muted">{{ formatCLP(displayedInvoicedPerHectare) }}/ha</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">Consumido</h6>
                                <i class="fas fa-boxes text-warning fa-lg"></i>
                            </div>
                            <h3 class="mb-0 text-warning">{{ formatCLP(displayedConsumed) }}</h3>
                            <small class="text-muted">{{ formatCLP(displayedConsumedPerHectare) }}/ha</small>
                            
                            <!-- Indicador de inversiones consumidas -->
                            <div v-if="includeInvestments && summary.consumed_investments_total > 0" class="mt-3">
                                <small class="text-info">
                                    <i class="fas fa-info-circle"></i>
                                    Inversiones consumidas: {{ formatCLP(summary.consumed_investments_total) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted small">Diferencia</h6>
                                <i :class="['fas', displayedDifference >= 0 ? 'fa-arrow-down text-success' : 'fa-arrow-up text-danger']"></i>
                            </div>
                            <h4 class="mb-1" :class="displayedDifference >= 0 ? 'text-success' : 'text-danger'">
                                {{ formatCLP(Math.abs(displayedDifference)) }}
                            </h4>
                            <small style="font-size: 0.7rem;">
                                <span :class="['badge', displayedDifference >= 0 ? 'bg-success' : 'bg-danger']">
                                    {{ displayedDifference >= 0 ? '✅ Bajo Presupuesto' : '⚠️ Sobrepresupuesto' }}
                                </span>
                            </small>
                            
                            <!-- Diferencia hasta el mes anterior -->
                            <div v-if="differenceToCurrentMonth" class="mt-3 pt-2 border-top">
                                <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">
                                    <strong>Hasta {{ differenceToCurrentMonth.monthName }}:</strong>
                                </small>
                                <h5 class="mb-0" :class="differenceToCurrentMonth.difference >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1rem;">
                                    {{ formatCLP(Math.abs(differenceToCurrentMonth.difference)) }}
                                </h5>
                                <small :class="differenceToCurrentMonth.difference >= 0 ? 'text-success' : 'text-danger'" style="font-size: 0.65rem;">
                                    {{ differenceToCurrentMonth.difference >= 0 ? '✅' : '⚠️' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">% Ejecución</h6>
                                <i class="fas fa-percent text-info fa-lg"></i>
                            </div>
                            <h3 class="mb-0 text-info">{{ formatPercent(displayedPercentageExecution) }}</h3>
                            <small class="text-muted">
                                Variación: 
                                <span :class="displayedDifference < 0 ? 'text-danger' : 'text-success'">
                                    {{ displayedDifference < 0 ? '+' : '' }}{{ formatPercent((displayedDifference / displayedBudget) * 100) }}
                                </span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Principales -->
            <div class="row g-3 mb-3">
                <!-- Gráfico Mensual -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico Acumulado -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="cumulativeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico por Categorías -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="height: 400px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla Detallada -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2"></i>Detalle por Categoría
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nivel 1</th>
                                            <th>Nivel 2</th>
                                            <th class="text-end">Presupuestado</th>
                                            <th class="text-end">Facturado</th>
                                            <th class="text-end">Consumido</th>
                                            <th class="text-end">Diferencia</th>
                                            <th class="text-end">Variación %</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in comparisonByLevel1" :key="item.category">
                                            <td class="fw-semibold text-muted small">{{ item.level1 }}</td>
                                            <td class="fw-bold">{{ item.level2 }}</td>
                                            <td class="text-end">{{ formatCLP(item.budget) }}</td>
                                            <td class="text-end">{{ formatCLP(item.invoiced) }}</td>
                                            <td class="text-end">{{ formatCLP(item.consumed) }}</td>
                                            <td class="text-end" :class="item.difference > 0 ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs(item.difference)) }}
                                            </td>
                                            <td class="text-end">
                                                <span :class="item.variance > 0 ? 'text-danger' : 'text-success'">
                                                    {{ item.variance > 0 ? '+' : '' }}{{ formatPercent(item.variance) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="getVarianceClass(Math.abs(item.variance))">
                                                    {{ getStatusIcon(item.status) }}
                                                    {{ Math.abs(item.variance) > 10 ? 'Atención' : Math.abs(item.variance) > 5 ? 'Revisión' : 'OK' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td colspan="2">TOTAL</td>
                                            <td class="text-end">{{ formatCLP(displayedBudget) }}</td>
                                            <td class="text-end">{{ formatCLP(summary.invoiced_total) }}</td>
                                            <td class="text-end">{{ formatCLP(displayedConsumed) }}</td>
                                            <td class="text-end" :class="displayedDifference > 0 ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs(displayedDifference)) }}
                                            </td>
                                            <td class="text-end">
                                                <span :class="displayedDifference < 0 ? 'text-danger' : 'text-success'">
                                                    {{ displayedDifference < 0 ? '+' : '' }}{{ formatPercent((displayedDifference / displayedBudget) * 100) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="displayedDifference >= 0 ? 'bg-success' : 'bg-danger'">
                                                    {{ displayedDifference >= 0 ? '✅' : '⚠️' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Torta Comparativos -->
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="pieChartBudget"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="pieChartReal"></canvas>
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
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-body {
    padding: 1.25rem;
}

h3 {
    font-size: 1.75rem;
    font-weight: 600;
}

.table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.badge {
    font-size: 0.8rem;
    padding: 0.35em 0.65em;
}
</style>
