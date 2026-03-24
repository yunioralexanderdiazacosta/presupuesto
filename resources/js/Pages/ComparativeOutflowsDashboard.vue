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

// Toggle idioma ES/EN
const isEnglish = ref(false);
const t = computed(() => isEnglish.value ? {
    budgeted: 'Budget',
    invoiced: 'Invoiced',
    consumed: 'Consumed',
    difference: 'Difference',
    execution: '% Execution',
    budgetMinusInvoiced: 'Budget - Invoiced',
    underBudget: '✅ Under Budget',
    overBudget: '⚠️ Over Budget',
    variance: 'Variance',
    withInvestments: 'With investments',
    withoutInvestments: 'Without investments',
    investmentsBudgeted: 'Budgeted investments',
    investmentsConsumed: 'Consumed investments',
    // Gráfico mensual
    monthlyTitle: 'Monthly Comparison: Budget vs Invoiced',
    budgetedLabel: 'Budget',
    budgetedWithInv: 'Budget (with investments)',
    invoicedLabel: 'Invoiced',
    // Gráfico acumulado
    cumulativeTitle: 'Cumulative Evolution - Actual vs Projection',
    cumBudget: 'Cumulative Budget (Full projection)',
    cumBudgetWithInv: 'Cumulative Budget with Investments (Full projection)',
    cumInvoiced: 'Cumulative Invoiced (Actual)',
    cumConsumed: 'Cumulative Consumed (Actual)',
    cumConsumedWithInv: 'Cumulative Consumed with Investments (Actual)',
    cumulativeTableTitle: 'Cumulative Evolution - Monthly Data',
    dashboardTitle: 'Comparative Dashboard',
} : {
    budgeted: 'Presupuestado',
    invoiced: 'Facturado',
    consumed: 'Consumido',
    difference: 'Diferencia',
    execution: '% Ejecución',
    budgetMinusInvoiced: 'Presupuesto - Facturado',
    underBudget: '✅ Bajo Presupuesto',
    overBudget: '⚠️ Sobrepresupuesto',
    variance: 'Variación',
    withInvestments: 'Con inversiones',
    withoutInvestments: 'Sin inversiones',
    investmentsBudgeted: 'Inversiones presupuestadas',
    investmentsConsumed: 'Inversiones consumidas',
    // Gráfico mensual
    monthlyTitle: 'Comparativo Mensual: Presupuesto vs Facturado',
    budgetedLabel: 'Presupuestado',
    budgetedWithInv: 'Presupuestado (con inversiones)',
    invoicedLabel: 'Facturado',
    // Gráfico acumulado
    cumulativeTitle: 'Evolución Acumulada - Real vs Proyección',
    cumBudget: 'Acumulado Presupuesto (Proyección completa)',
    cumBudgetWithInv: 'Acumulado Presupuesto con Inversiones (Proyección completa)',
    cumInvoiced: 'Acumulado Facturado (Real)',
    cumConsumed: 'Acumulado Consumido (Real)',
    cumConsumedWithInv: 'Acumulado Consumido con Inversiones (Real)',
    cumulativeTableTitle: 'Evolución Acumulada - Datos Mensuales',
    dashboardTitle: 'Dashboard Comparativo',
});

// Variables para conversión USD
const divisor = ref(props.dollarPrice);
const divisorMin = 800;
const divisorMax = 1300;
const dividir = ref(false); // Por defecto desactivado
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

// Mes seleccionado para el card de diferencia (inicializar con mes anterior al actual)
const selectedMonthIndex = ref(null);

// Estado para selección de filas en la tabla de categorías
const selectedRows = ref([]);

// Estado para grupos creados
const customGroups = ref([]);

// Estado para controlar qué grupos están expandidos
const expandedGroups = ref([]);

// Computed para saber si todas las filas están seleccionadas
const allRowsSelected = computed({
    get: () => props.comparisonByLevel1?.length > 0 && selectedRows.value.length === props.comparisonByLevel1.length,
    set: (value) => {
        if (value) {
            selectedRows.value = props.comparisonByLevel1.map((item, index) => index);
        } else {
            selectedRows.value = [];
        }
    }
});

// Función para toggle de una fila
const toggleRow = (index) => {
    const idx = selectedRows.value.indexOf(index);
    if (idx > -1) {
        selectedRows.value.splice(idx, 1);
    } else {
        selectedRows.value.push(index);
    }
};

// Función temporal para agrupar (Paso 3)
const groupSelected = () => {
    if (selectedRows.value.length === 0) return;
    
    // Obtener las filas seleccionadas con sus índices originales
    const selectedItems = selectedRows.value.map(idx => ({
        ...props.comparisonByLevel1[idx],
        originalIndex: idx
    }));
    
    // Calcular totales del grupo
    const totals = selectedItems.reduce((acc, item) => {
        acc.budget += item.budget || 0;
        acc.invoiced += item.invoiced || 0;
        acc.consumed += item.consumed || 0;
        return acc;
    }, { budget: 0, invoiced: 0, consumed: 0 });
    
    totals.difference = totals.budget - totals.invoiced;
    totals.variance = totals.budget > 0 ? ((totals.invoiced - totals.budget) / totals.budget) * 100 : 0;
    
    // Crear el grupo guardando los índices originales
    const newGroup = {
        id: Date.now(),
        name: `Grupo ${customGroups.value.length + 1}`,
        items: selectedItems,
        hiddenIndices: [...selectedRows.value], // Guardar índices para ocultar
        totals: totals,
        expanded: true
    };
    
    customGroups.value.push(newGroup);
    expandedGroups.value.push(newGroup.id);
    
    // Limpiar selección
    selectedRows.value = [];
};

// Toggle expandir/colapsar grupo
const toggleGroup = (groupId) => {
    const idx = expandedGroups.value.indexOf(groupId);
    if (idx > -1) {
        expandedGroups.value.splice(idx, 1);
    } else {
        expandedGroups.value.push(groupId);
    }
};

// Eliminar un grupo (las filas vuelven a la lista principal)
const removeGroup = (groupId) => {
    const groupIdx = customGroups.value.findIndex(g => g.id === groupId);
    if (groupIdx > -1) {
        // Eliminar el grupo
        customGroups.value.splice(groupIdx, 1);
        
        // Eliminar de expandidos
        const expIdx = expandedGroups.value.indexOf(groupId);
        if (expIdx > -1) {
            expandedGroups.value.splice(expIdx, 1);
        }
        
        // Las filas se mostrarán automáticamente porque ya no están en ningún grupo
        // El computed hiddenRowIndices se recalcula automáticamente
    }
};

// Agrupar automáticamente por Nivel 1
const groupByLevel1 = () => {
    // Si ya hay grupos, desagrupar todo
    if (customGroups.value.length > 0) {
        customGroups.value = [];
        expandedGroups.value = [];
        selectedRows.value = [];
        return;
    }
    
    // Obtener niveles 1 únicos que aún están visibles
    const level1Groups = {};
    
    props.comparisonByLevel1.forEach((item, index) => {
        // Solo agrupar filas que no estén ya en un grupo
        if (isRowVisible(index)) {
            if (!level1Groups[item.level1]) {
                level1Groups[item.level1] = [];
            }
            level1Groups[item.level1].push({ ...item, originalIndex: index });
        }
    });
    
    // Crear un grupo por cada Nivel 1
    Object.keys(level1Groups).forEach(level1Name => {
        const items = level1Groups[level1Name];
        
        // Calcular totales del grupo
        const totals = items.reduce((acc, item) => {
            acc.budget += item.budget || 0;
            acc.invoiced += item.invoiced || 0;
            acc.consumed += item.consumed || 0;
            return acc;
        }, { budget: 0, invoiced: 0, consumed: 0 });
        
        totals.difference = totals.budget - totals.invoiced;
        totals.variance = totals.budget > 0 ? ((totals.invoiced - totals.budget) / totals.budget) * 100 : 0;
        
        // Crear el grupo
        const newGroup = {
            id: Date.now() + Math.random(), // Asegurar ID único
            name: level1Name,
            items: items,
            hiddenIndices: items.map(item => item.originalIndex),
            totals: totals,
            expanded: false // Iniciar colapsado
        };
        
        customGroups.value.push(newGroup);
    });
    
    // Limpiar selección
    selectedRows.value = [];
};

// Computed para obtener los índices que están ocultos por grupos
const hiddenRowIndices = computed(() => {
    const hidden = new Set();
    customGroups.value.forEach(group => {
        group.hiddenIndices.forEach(idx => hidden.add(idx));
    });
    return hidden;
});

// Computed para verificar si una fila debe mostrarse
const isRowVisible = (index) => {
    return !hiddenRowIndices.value.has(index);
};

// Inicializar mes seleccionado en onMounted
onMounted(() => {
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // 1-12
    
    // Encontrar el índice del mes actual
    let currentMonthIndex = -1;
    for (let i = 0; i < props.months.length; i++) {
        if (props.months[i].id === currentMonth) {
            currentMonthIndex = i;
            break;
        }
    }
    
    // Inicializar con el mes anterior al actual (o el último mes disponible)
    if (currentMonthIndex > 0) {
        selectedMonthIndex.value = currentMonthIndex - 1;
    } else if (props.months.length > 0) {
        selectedMonthIndex.value = props.months.length - 1;
    }
    
    createMonthlyChart();
    createCumulativeChart();
});

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

// Diferencia acumulada hasta el mes seleccionado
const differenceToSelectedMonth = computed(() => {
    if (selectedMonthIndex.value === null || selectedMonthIndex.value < 0) {
        return null;
    }
    
    // Presupuesto: usar según el toggle
    const budgetData = includeInvestments.value 
        ? props.cumulativeComparison.budget_with_investments_cumulative 
        : props.cumulativeComparison.budget_cumulative;
    
    // Facturado acumulado (sin ajustes)
    const realData = props.cumulativeComparison.real_cumulative;
    
    const monthIndex = selectedMonthIndex.value;
    
    if (monthIndex >= 0 && monthIndex < budgetData.length) {
        const budgetUpToMonth = budgetData[monthIndex] || 0;
        
        // Buscar el último valor real disponible hasta el mes seleccionado (puede ser null)
        let realUpToMonth = realData[monthIndex];
        
        // Si es null, buscar el último valor no-null anterior
        if (realUpToMonth === null) {
            for (let i = monthIndex - 1; i >= 0; i--) {
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
            monthName: props.months[monthIndex]?.name || 'Mes',
            budget: budgetUpToMonth,
            real: adjustedReal
        };
    }
    
    return null;
});

// Formatear números en formato chileno (con conversión USD opcional)
const formatCLP = (value) => {
    if (!value && value !== 0) return dividir.value ? '$0 USD' : '$0';
    const convertedValue = dividir.value && divisor.value ? value / divisor.value : value;
    return '$' + parseFloat(convertedValue).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + (dividir.value ? ' USD' : '');
};

// Formatear números sin símbolo de moneda (para uso interno en gráficos)
const formatNumber = (value) => {
    if (!value && value !== 0) return 0;
    const convertedValue = dividir.value && divisor.value ? value / divisor.value : value;
    return convertedValue;
};

// Formatear porcentajes
const formatPercent = (value) => {
    if (!value && value !== 0) return '0%';
    return parseFloat(value).toFixed(1) + '%';
};

// Obtener clase de badge según variación
const getVarianceClass = (variance, isOverBudget = false) => {
    // Si es ahorro (variance negativa o difference positiva), siempre verde
    if (!isOverBudget) return 'bg-success';
    
    // Solo aplicar alertas si hay sobregasto
    const absVariance = Math.abs(variance);
    if (absVariance > 10) return 'bg-danger';
    if (absVariance > 5) return 'bg-warning text-dark';
    return 'bg-success';
};

// Obtener icono según estado
const getStatusIcon = (status) => {
    if (status === 'over_budget' || status === 'over') return '⚠️';
    return '✅';
};

// Datos para exportar a Excel - Evolución Acumulada
const cumulativeTableData = computed(() => {
    return props.cumulativeComparison.labels.map((month, index) => {
        const budget = includeInvestments.value 
            ? props.cumulativeComparison.budget_with_investments_cumulative[index] 
            : props.cumulativeComparison.budget_cumulative[index];
        const invoiced = props.cumulativeComparison.real_cumulative[index];
        const consumed = includeInvestments.value 
            ? props.cumulativeComparison.consumed_with_investments_cumulative[index] 
            : props.cumulativeComparison.consumed_cumulative[index];

        // Facturado mensual (no acumulado) — null para meses futuros
        const invoicedMonthly = index <= props.cumulativeComparison.last_month_with_data
            ? (props.monthlyComparison.real[index] ?? null)
            : null;
        
        // Aplicar conversión USD si está activada
        const convertedBudget = dividir.value && divisor.value ? budget / divisor.value : budget;
        const convertedInvoiced = invoiced !== null && dividir.value && divisor.value ? invoiced / divisor.value : invoiced;
        const convertedConsumed = consumed !== null && dividir.value && divisor.value ? consumed / divisor.value : consumed;
        const convertedInvoicedMonthly = invoicedMonthly !== null && dividir.value && divisor.value ? invoicedMonthly / divisor.value : invoicedMonthly;
        
        const difference = convertedInvoiced !== null ? convertedBudget - convertedInvoiced : null;
        const differenceConsumed = convertedConsumed !== null ? convertedBudget - convertedConsumed : null;
        const variance = convertedInvoiced !== null && convertedBudget > 0 ? ((convertedInvoiced / convertedBudget) * 100 - 100) : null;
        const varianceConsumed = convertedConsumed !== null && convertedBudget > 0 ? ((convertedConsumed / convertedBudget) * 100 - 100) : null;
        
        return {
            month: month,
            invoiced_monthly: convertedInvoicedMonthly !== null ? convertedInvoicedMonthly : 0,
            budget: convertedBudget || 0,
            invoiced: convertedInvoiced || 0,
            consumed: convertedConsumed || 0,
            difference: difference !== null ? difference : 0,
            differenceConsumed: differenceConsumed !== null ? differenceConsumed : 0,
            variance: variance !== null ? variance.toFixed(2) : 0,
            varianceConsumed: varianceConsumed !== null ? varianceConsumed.toFixed(2) : 0
        };
    });
});

// Datos para exportar a Excel
const excelData = computed(() => {
    return props.detailedTable.map(item => {
        // Aplicar conversión USD si está activada
        const budget = dividir.value && divisor.value ? item.budget / divisor.value : item.budget;
        const real = dividir.value && divisor.value ? item.real / divisor.value : item.real;
        const difference = dividir.value && divisor.value ? item.difference / divisor.value : item.difference;
        
        return {
            'Categoría': item.category,
            'Presupuestado': budget,
            'Facturado': real,
            'Diferencia': difference,
            'Variación %': item.variance.toFixed(2),
        };
    });
});

// Watch para actualizar gráficos cuando cambie el toggle o la conversión USD
watch([includeInvestments, dividir, divisor, isEnglish], () => {
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

    // Aplicar conversión USD si está activada
    const convertedBudgetData = dividir.value && divisor.value 
        ? budgetData.map(v => v / divisor.value)
        : budgetData;
    
    const convertedRealData = dividir.value && divisor.value
        ? props.monthlyComparison.real.map(v => v / divisor.value)
        : props.monthlyComparison.real;

    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.monthlyComparison.labels,
            datasets: [
                {
                    label: includeInvestments.value ? t.value.budgetedWithInv : t.value.budgetedLabel,
                    data: convertedBudgetData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: t.value.invoicedLabel,
                    data: convertedRealData,
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
                    text: t.value.monthlyTitle + (dividir.value ? ' (USD)' : ' (CLP)'),
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

    // Aplicar conversión USD si está activada
    const convertedBudgetCumulative = dividir.value && divisor.value
        ? budgetCumulativeData.map(v => v / divisor.value)
        : budgetCumulativeData;
    
    const convertedRealCumulative = dividir.value && divisor.value
        ? props.cumulativeComparison.real_cumulative.map(v => v !== null ? v / divisor.value : null)
        : props.cumulativeComparison.real_cumulative;
    
    const convertedConsumedCumulative = dividir.value && divisor.value
        ? consumedCumulativeData.map(v => v !== null ? v / divisor.value : null)
        : consumedCumulativeData;

    cumulativeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.cumulativeComparison.labels,
            datasets: [
                {
                    label: includeInvestments.value 
                        ? t.value.cumBudgetWithInv 
                        : t.value.cumBudget,
                    data: convertedBudgetCumulative,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: t.value.cumInvoiced,
                    data: convertedRealCumulative,
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
                        ? t.value.cumConsumedWithInv 
                        : t.value.cumConsumed,
                    data: convertedConsumedCumulative,
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
                    text: t.value.cumulativeTitle + (dividir.value ? ' (USD)' : ' (CLP)'),
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
                                <i class="fas fa-chart-line me-2"></i>{{ t.dashboardTitle }}
                            </h5>
                        </div>
                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                <!-- Toggle maestro para inversiones -->
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input 
                                        class="form-check-input me-2" 
                                        type="checkbox" 
                                        role="switch"
                                        id="includeInvestmentsToggle"
                                        v-model="includeInvestments"
                                        style="cursor: pointer;"
                                    >
                                    <label class="form-check-label text-muted small mb-0" for="includeInvestmentsToggle" style="cursor: pointer;">
                                        <span v-if="includeInvestments">
                                            <i class="fas fa-check-circle text-success"></i> {{ t.withInvestments }}
                                        </span>
                                        <span v-else>
                                            <i class="fas fa-times-circle text-secondary"></i> {{ t.withoutInvestments }}
                                        </span>
                                    </label>
                                </div>
                                
                                <!-- Separador -->
                                <div class="vr d-none d-md-block" style="height: 24px;"></div>
                                
                                <!-- Toggle para conversión USD -->
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="dividir-switch" 
                                        v-model="dividir"
                                    >
                                    <label class="form-check-label ms-2 mt-0 mb-0 small" for="dividir-switch">Ver en USD</label>
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
                                        <label for="divisor-slider" class="form-label mb-0 me-2 small">Divisor:</label>
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
                            <h4 class="mb-0 text-primary text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(displayedBudget) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(displayedBudgetPerHectare) }}/ha</small>
                            
                            <!-- Indicador de inversiones presupuestadas -->
                            <div v-if="includeInvestments && summary.total_investments > 0" class="mt-3">
                                <small class="text-info">
                                    <i class="fas fa-info-circle"></i>
                                    {{ t.investmentsBudgeted }}: {{ formatCLP(summary.total_investments) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.invoiced }}</h6>
                                <i class="fas fa-file-invoice-dollar text-success fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-success text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(displayedInvoiced) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(displayedInvoicedPerHectare) }}/ha</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.consumed }}</h6>
                                <i class="fas fa-boxes text-warning fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-warning text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(displayedConsumed) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ formatCLP(displayedConsumedPerHectare) }}/ha</small>
                            
                            <!-- Indicador de inversiones consumidas -->
                            <div v-if="includeInvestments && summary.consumed_investments_total > 0" class="mt-3">
                                <small class="text-info">
                                    <i class="fas fa-info-circle"></i>
                                    {{ t.investmentsConsumed }}: {{ formatCLP(summary.consumed_investments_total) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.difference }}</h6>
                                <i :class="['fas', displayedDifference >= 0 ? 'fa-arrow-down text-success' : 'fa-arrow-up text-danger']"></i>
                            </div>
                            <h4 class="mb-0 text-nowrap" style="font-size: 1.15rem;" :class="displayedDifference >= 0 ? 'text-success' : 'text-danger'">
                                {{ formatCLP(Math.abs(displayedDifference)) }}
                            </h4>
                            <small class="text-muted d-block mb-2">{{ t.budgetMinusInvoiced }}</small>
                            <span :class="['badge', displayedDifference >= 0 ? 'bg-success' : 'bg-danger']">
                                {{ displayedDifference >= 0 ? t.underBudget : t.overBudget }}
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
                            <h4 class="mb-0 text-info text-nowrap" style="font-size: 1.15rem;">{{ formatPercent(displayedPercentageExecution) }}</h4>
                            <small class="text-muted">
                                {{ t.variance }}: 
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

            <!-- Tabla Mensual: Presupuesto vs Costos -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2"></i>Resumen Mensual: Presupuesto vs Costos
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 110px;">Concepto</th>
                                            <th
                                                v-for="(label, i) in monthlyComparison.labels"
                                                :key="i"
                                                class="text-end"
                                                style="min-width: 90px;"
                                            >{{ label }}</th>
                                            <th class="text-end" style="min-width: 100px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Fila: Presupuesto mensual -->
                                        <tr>
                                            <td class="fw-semibold text-primary">
                                                <i class="fas fa-calculator fa-xs me-1"></i>{{ t.budgeted }}
                                            </td>
                                            <td
                                                v-for="(val, i) in (includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget)"
                                                :key="i"
                                                class="text-end"
                                            >{{ formatCLP(val) }}</td>
                                            <td class="text-end fw-bold text-primary">
                                                {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a, b) => a + (b || 0), 0)) }}
                                            </td>
                                        </tr>
                                        <!-- Fila: Costos (Facturado) mensual -->
                                        <tr>
                                            <td class="fw-semibold text-success">
                                                <i class="fas fa-file-invoice-dollar fa-xs me-1"></i>{{ t.invoiced }}
                                            </td>
                                            <td
                                                v-for="(val, i) in monthlyComparison.real"
                                                :key="i"
                                                class="text-end"
                                            >
                                                <span v-if="val > 0">{{ formatCLP(val) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                {{ formatCLP(monthlyComparison.real.reduce((a, b) => a + (b || 0), 0)) }}
                                            </td>
                                        </tr>
                                        <!-- Fila: Diferencia (Presupuesto - Facturado) -->
                                        <tr class="table-light">
                                            <td class="fw-semibold">
                                                <i class="fas fa-balance-scale fa-xs me-1"></i>{{ t.difference }}
                                            </td>
                                            <td
                                                v-for="(val, i) in monthlyComparison.real"
                                                :key="i"
                                                class="text-end fw-bold"
                                                :class="{
                                                    'text-danger': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val) < 0,
                                                    'text-success': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val) >= 0 && (val > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0),
                                                    'text-muted': val === 0 && (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) === 0
                                                }"
                                            >
                                                <template v-if="val > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0">
                                                    {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val) }}
                                                </template>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold"
                                                :class="((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - monthlyComparison.real.reduce((a,b)=>a+(b||0),0)) < 0 ? 'text-danger' : 'text-success'">
                                                {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - monthlyComparison.real.reduce((a,b)=>a+(b||0),0)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Evolución Acumulada -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>{{ t.cumulativeTableTitle }}
                                </h6>
                                <ExportExcelButton
                                    :data="cumulativeTableData"
                                    :headers="[
                                        { label: 'Mes', key: 'month' },
                                        { label: 'Facturado Mensual', key: 'invoiced_monthly' },
                                        { label: 'Presupuesto Acumulado', key: 'budget' },
                                        { label: 'Facturado Acumulado', key: 'invoiced' },
                                        { label: 'Consumido Acumulado', key: 'consumed' },
                                        { label: 'Diferencia (Presup. - Fact.)', key: 'difference' },
                                        { label: 'Diferencia (Presup. - Cons.)', key: 'differenceConsumed' },
                                        { label: 'Variación % (Fact.)', key: 'variance' },
                                        { label: 'Variación % (Cons.)', key: 'varianceConsumed' }
                                    ]"
                                    filename="evolucion_acumulada.xlsx"
                                    class="btn btn-sm btn-light-primary"
                                >
                                    <i class="fas fa-file-excel me-1"></i>
                                    Exportar
                                </ExportExcelButton>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 9%;">Mes</th>
                                            <th class="text-end" style="width: 12%;">Facturado<br>Mensual</th>
                                            <th class="text-end" style="width: 13%;">Presupuesto<br>Acumulado</th>
                                            <th class="text-end" style="width: 13%;">Facturado<br>Acumulado</th>
                                            <th class="text-end" style="width: 13%;">Consumido<br>Acumulado</th>
                                            <th class="text-end" style="width: 10%;">Dif. (P-F)</th>
                                            <th class="text-end" style="width: 10%;">Dif. (P-C)</th>
                                            <th class="text-end" style="width: 10%;">Var. % (F)</th>
                                            <th class="text-end" style="width: 10%;">Var. % (C)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(month, index) in cumulativeComparison.labels" :key="index">
                                            <td class="fw-semibold">{{ month }}</td>
                                            <td class="text-end">
                                                <span v-if="index <= cumulativeComparison.last_month_with_data">
                                                    {{ formatCLP(monthlyComparison.real[index]) }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end">
                                                {{ formatCLP(includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) }}
                                            </td>
                                            <td class="text-end">
                                                <span v-if="cumulativeComparison.real_cumulative[index] !== null">
                                                    {{ formatCLP(cumulativeComparison.real_cumulative[index]) }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end text-warning">
                                                <span v-if="(includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) !== null">
                                                    {{ formatCLP(includeInvestments 
                                                        ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                        : cumulativeComparison.consumed_cumulative[index]) }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold" 
                                                v-if="cumulativeComparison.real_cumulative[index] !== null"
                                                :class="((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index]) >= 0 
                                                    ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index])) }}
                                                <i :class="['fas', 'fa-xs', 'ms-1', 
                                                    ((includeInvestments 
                                                        ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                        : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index]) >= 0 
                                                        ? 'fa-arrow-down' : 'fa-arrow-up']"></i>
                                            </td>
                                            <td class="text-end" v-else>
                                                <span class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold" 
                                                v-if="(includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) !== null"
                                                :class="((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - (includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index])) >= 0 
                                                    ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - (includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]))) }}
                                                <i :class="['fas', 'fa-xs', 'ms-1', 
                                                    ((includeInvestments 
                                                        ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                        : cumulativeComparison.budget_cumulative[index]) - (includeInvestments 
                                                        ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                        : cumulativeComparison.consumed_cumulative[index])) >= 0 
                                                        ? 'fa-arrow-down' : 'fa-arrow-up']"></i>
                                            </td>
                                            <td class="text-end" v-else>
                                                <span class="text-muted">-</span>
                                            </td>
                                            <td class="text-end"
                                                v-if="cumulativeComparison.real_cumulative[index] !== null"
                                                :class="((cumulativeComparison.real_cumulative[index] / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) > 0 
                                                    ? 'text-danger' : 'text-success'">
                                                {{ ((cumulativeComparison.real_cumulative[index] / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) > 0 ? '+' : '' }}{{ 
                                                    formatPercent((cumulativeComparison.real_cumulative[index] / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) }}
                                            </td>
                                            <td class="text-end" v-else>
                                                <span class="text-muted">-</span>
                                            </td>
                                            <td class="text-end"
                                                v-if="(includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) !== null"
                                                :class="(((includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) > 0 
                                                    ? 'text-danger' : 'text-success'">
                                                {{ (((includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) > 0 ? '+' : '' }}{{ 
                                                    formatPercent(((includeInvestments 
                                                    ? cumulativeComparison.consumed_with_investments_cumulative[index] 
                                                    : cumulativeComparison.consumed_cumulative[index]) / (includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index])) * 100 - 100) }}
                                            </td>
                                            <td class="text-end" v-else>
                                                <span class="text-muted">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">
                                    <i class="fas fa-table me-2"></i>Detalle por Categoría
                                </h6>
                                <button 
                                    class="btn btn-sm"
                                    :class="customGroups.length > 0 ? 'btn-warning' : 'btn-outline-primary'"
                                    @click="groupByLevel1"
                                    :title="customGroups.length > 0 ? 'Desagrupar y mostrar vista normal' : 'Agrupar todas las categorías por Nivel 1'">
                                    <i class="fas me-1" :class="customGroups.length > 0 ? 'fa-list' : 'fa-folder-tree'"></i>
                                    {{ customGroups.length > 0 ? 'Desagrupar Todo' : 'Agrupar por Nivel 1' }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Barra de controles (se muestra solo cuando hay selección) -->
                        <div v-if="selectedRows.length > 0" class="card-body bg-light border-bottom py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-primary">
                                        {{ selectedRows.length }} fila{{ selectedRows.length !== 1 ? 's' : '' }} seleccionada{{ selectedRows.length !== 1 ? 's' : '' }}
                                    </span>
                                    <button 
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="selectedRows = []"
                                        title="Limpiar selección">
                                        <i class="fas fa-times me-1"></i>
                                        Limpiar selección
                                    </button>
                                </div>
                                <button 
                                    class="btn btn-sm btn-primary"
                                    @click="groupSelected"
                                    title="Agrupar filas seleccionadas">
                                    <i class="fas fa-layer-group me-1"></i>
                                    Agrupar seleccionados
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm" style="font-size: 0.8rem;">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input" 
                                                    v-model="allRowsSelected"
                                                    title="Seleccionar todas"
                                                />
                                            </th>
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
                                        <!-- Grupos personalizados -->
                                        <template v-for="group in customGroups" :key="'group-' + group.id">
                                            <!-- Fila del grupo (colapsable) -->
                                            <tr class="table-info cursor-pointer" @click="toggleGroup(group.id)">
                                                <td>
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-link text-danger p-0"
                                                        @click.stop="removeGroup(group.id)"
                                                        title="Eliminar grupo">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                                <td colspan="2" class="fw-bold">
                                                    <i class="fas" :class="expandedGroups.includes(group.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                    {{ group.name }} ({{ group.items.length }} categorías)
                                                </td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.budget) }}</td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.invoiced) }}</td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.consumed) }}</td>
                                                <td class="text-end fw-bold" :class="group.totals.difference > 0 ? 'text-success' : 'text-danger'">
                                                    {{ formatCLP(Math.abs(group.totals.difference)) }}
                                                </td>
                                                <td class="text-end fw-bold">
                                                    <span :class="group.totals.variance > 0 ? 'text-danger' : 'text-success'">
                                                        {{ group.totals.variance > 0 ? '+' : '' }}{{ formatPercent(group.totals.variance) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge" :class="getVarianceClass(group.totals.variance, group.totals.variance > 0)">
                                                        {{ getStatusIcon(group.totals.variance > 0 ? 'over' : 'ok') }}
                                                        {{ group.totals.variance > 0 ? (Math.abs(group.totals.variance) > 10 ? 'Alerta' : Math.abs(group.totals.variance) > 5 ? 'Revisión' : 'OK') : 'OK' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            
                                            <!-- Items del grupo (se muestran cuando está expandido) -->
                                            <template v-if="expandedGroups.includes(group.id)">
                                                <tr v-for="(item, idx) in group.items" :key="'group-item-' + group.id + '-' + idx" class="table-light">
                                                    <td></td>
                                                    <td class="fw-semibold text-muted small ps-4">└ {{ item.level1 }}</td>
                                                    <td class="fw-normal">{{ item.level2 }}</td>
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
                                                        <span class="badge" :class="getVarianceClass(item.variance, item.variance > 0)">
                                                            {{ getStatusIcon(item.status) }}
                                                            {{ item.variance > 0 ? (Math.abs(item.variance) > 10 ? 'Alerta' : Math.abs(item.variance) > 5 ? 'Revisión' : 'OK') : 'OK' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>
                                        
                                        <!-- Filas normales (solo las que no están en grupos) -->
                                        <tr v-for="(item, index) in comparisonByLevel1" :key="item.category" v-show="isRowVisible(index)">
                                            <td>
                                                <input 
                                                    type="checkbox" 
                                                    class="form-check-input"
                                                    :checked="selectedRows.includes(index)"
                                                    @change="toggleRow(index)"
                                                />
                                            </td>
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
                                                <span class="badge" :class="getVarianceClass(item.variance, item.variance > 0)">
                                                    {{ getStatusIcon(item.status) }}
                                                    {{ item.variance > 0 ? (Math.abs(item.variance) > 10 ? 'Alerta' : Math.abs(item.variance) > 5 ? 'Revisión' : 'OK') : 'OK' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr class="fw-bold">
                                            <td></td>
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

h4 {
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

.cursor-pointer {
    cursor: pointer;
}

.table-info {
    background-color: #d1ecf1 !important;
}

.table-info:hover {
    background-color: #bee5eb !important;
}
</style>
