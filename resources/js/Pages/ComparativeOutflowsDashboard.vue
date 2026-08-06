<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';
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
    payrollSummary: {
        type: Object,
        default: () => ({ total: 0, workdays: 0 })
    },
    payrollMonthly: {
        type: Array,
        default: () => Array(12).fill(0)
    },
    payrollByLevel2: {
        type: Object,
        default: () => ({})
    },
    comparisonByLevel1Monthly: {
        type: Array,
        default: () => []
    },
    companyReasons:         { type: Array,  default: () => [] },
    activeCompanyReasonIds: { type: Array,  default: () => [] },
});

let monthlyChart = null;
let cumulativeChart = null;

// Toggle ÚNICO para incluir/excluir inversiones en TODO el dashboard
const includeInvestments = ref(false);

// Filtro Razón Social
const selectedCompanyReasons = ref(props.activeCompanyReasonIds ?? []);
const applyCompanyReasonFilter = () => {
    router.get(
        route('comparative.dashboard'),
        selectedCompanyReasons.value.length > 0 ? { company_reason_ids: selectedCompanyReasons.value } : {},
        { preserveScroll: false }
    );
};

// Toggles para mostrar/ocultar series en gráficos
const showBudget = ref(true);
const showInvoiced = ref(true);
const showConsumed = ref(false);
const showPayroll = ref(true);

// Toggle idioma ES/EN
const isEnglish = ref(false);
const t = computed(() => isEnglish.value ? {
    budgeted: 'Budget',
    invoiced: 'Invoiced',
    consumed: 'Consumed',
    difference: 'Difference',
    execution: '% Execution',
    budgetMinusInvoiced: 'Budget - Invoiced - Payroll',
    underBudget: '✅ Under Budget',
    overBudget: '⚠️ Over Budget',
    variance: 'Variance',
    withInvestments: 'With investments',
    withoutInvestments: 'Without investments',
    investmentsBudgeted: 'Budgeted investments',
    investmentsConsumed: 'Consumed investments',
    payroll: 'Payroll',
    payrollWorkdays: 'workdays',
    // Gráfico mensual
    monthlyTitle: 'Monthly Comparison: Budget vs Invoiced vs Outflows',
    budgetedLabel: 'Budget',
    budgetedWithInv: 'Budget (with investments)',
    invoicedLabel: 'Invoiced',
    consumedLabel: 'Outflows',
    consumedWithInvLabel: 'Outflows (with investments)',
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
    budgetMinusInvoiced: 'Presupuesto - Facturado - Remun.',
    underBudget: '✅ Bajo Presupuesto',
    overBudget: '⚠️ Sobrepresupuesto',
    variance: 'Variación',
    withInvestments: 'Con inversiones',
    withoutInvestments: 'Sin inversiones',
    investmentsBudgeted: 'Inversiones presupuestadas',
    investmentsConsumed: 'Inversiones consumidas',
    payroll: 'Remuneraciones',
    payrollWorkdays: 'jornadas',
    // Gráfico mensual
    monthlyTitle: 'Comparativo Mensual: Presupuesto vs Facturado vs Consumos',
    budgetedLabel: 'Presupuestado',
    budgetedWithInv: 'Presupuestado (con inversiones)',
    invoicedLabel: 'Facturado',
    consumedLabel: 'Consumos',
    consumedWithInvLabel: 'Consumos (con inversiones)',
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
const LS_DIVISOR = 'comparative_divisor';
const LS_DIVIDIR = 'comparative_dividir';
const _savedDivisor = parseFloat(localStorage.getItem(LS_DIVISOR));
const divisor = ref(!isNaN(_savedDivisor) ? _savedDivisor : props.dollarPrice);
const divisorMin = 800;
const divisorMax = 1300;
const dividir = ref(localStorage.getItem(LS_DIVIDIR) === '1');
watch(divisor, v => localStorage.setItem(LS_DIVISOR, v));
watch(dividir, v => localStorage.setItem(LS_DIVIDIR, v ? '1' : '0'));
const savingDollar = ref(false);

const saveDollarPrice = async () => {
    if (!props.isAdmin) return;
    savingDollar.value = true;
    try {
        const response = await axios.patch(route('api.dollar-price.update'), { dollar_price: divisor.value });
        divisor.value = response.data.dollar_price;
        Swal.fire({ icon: 'success', title: 'Guardado', text: `Tipo de cambio: $${Number(response.data.dollar_price).toLocaleString('es-CL')}`, timer: 1800, showConfirmButton: false });
    } catch (e) {
        console.error('Error guardando tipo de cambio', e);
        Swal.fire({ icon: 'error', title: 'Error al guardar', text: e?.response?.data?.errors?.dollar_price?.[0] || e?.response?.data?.message || 'No se pudo guardar el tipo de cambio.' });
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

// Controla si el bloque de remuneraciones está expandido en la tabla detalle
const payrollExpanded = ref(true);

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
        acc.budget   += item.budget   || 0;
        acc.invoiced += item.invoiced || 0;
        acc.consumed += item.consumed || 0;
        acc.payroll  += item.payroll  || 0;
        return acc;
    }, { budget: 0, invoiced: 0, consumed: 0, payroll: 0 });
    
    totals.difference = totals.budget - totals.invoiced - totals.payroll;
    totals.variance = totals.budget > 0 ? ((totals.invoiced + totals.payroll - totals.budget) / totals.budget) * 100 : 0;
    
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
    
    // Construir mapa L1 → L2 → items
    const level1Map = {};
    props.comparisonByLevel1.forEach((item, index) => {
        if (isRowVisible(index)) {
            if (!level1Map[item.level1]) level1Map[item.level1] = {};
            if (!level1Map[item.level1][item.level2]) level1Map[item.level1][item.level2] = [];
            level1Map[item.level1][item.level2].push({ ...item, originalIndex: index });
        }
    });
    
    Object.keys(level1Map).forEach(level1Name => {
        const level2Map = level1Map[level1Name];

        // Crear subgrupos de Nivel 2
        const level2Groups = Object.keys(level2Map).sort().map(level2Name => {
            const items = level2Map[level2Name];
            const totals = items.reduce((acc, item) => {
                acc.budget   += item.budget   || 0;
                acc.invoiced += item.invoiced || 0;
                acc.consumed += item.consumed || 0;
                acc.payroll  += item.payroll  || 0;
                return acc;
            }, { budget: 0, invoiced: 0, consumed: 0, payroll: 0 });
            totals.difference = totals.budget - totals.invoiced - totals.payroll;
            totals.variance = totals.budget > 0 ? ((totals.invoiced + totals.payroll - totals.budget) / totals.budget) * 100 : 0;
            return {
                id: Date.now() + Math.random(),
                name: level2Name,
                items,
                totals,
            };
        });

        // Totales del Nivel 1 = suma de subgrupos
        const totals = level2Groups.reduce((acc, l2) => {
            acc.budget   += l2.totals.budget;
            acc.invoiced += l2.totals.invoiced;
            acc.consumed += l2.totals.consumed;
            acc.payroll  += l2.totals.payroll;
            return acc;
        }, { budget: 0, invoiced: 0, consumed: 0, payroll: 0 });
        totals.difference = totals.budget - totals.invoiced - totals.payroll;
        totals.variance = totals.budget > 0 ? ((totals.invoiced + totals.payroll - totals.budget) / totals.budget) * 100 : 0;

        const allIndices = Object.values(level2Map).flat().map(item => item.originalIndex);

        customGroups.value.push({
            id: Date.now() + Math.random(),
            name: level1Name,
            level2Groups,
            hiddenIndices: allIndices,
            totals,
        });
    });
    
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

// Estado para detalle mensual (multi-selección estilo Power BI)
// selectedBars: array de barras activas [{ key, datasetIndex, barIndex, monthId, monthName, column }]
const selectedBars = ref([]);
const monthlyDetailCache = ref({}); // { [monthId]: responseData }
const monthlyDetailLoading = ref(false);
const monthlyDetailColumn = ref('invoiced'); // 'invoiced' | 'consumed'
const monthlyDetailExpandedGroups = ref([]);

// Nombres de meses seleccionados para el título
const monthlyDetailMonthNames = computed(() =>
    selectedBars.value.map(b => b.monthName).join(' + ')
);

// Colores base de cada dataset
const DATASET_COLORS = {
    budget:   { active: 'rgba(54, 162, 235, 0.9)',  dim: 'rgba(54, 162, 235, 0.15)' },
    invoiced: { active: 'rgba(75, 192, 192, 0.9)',  dim: 'rgba(75, 192, 192, 0.15)' },
    consumed: { active: 'rgba(255, 159, 64, 0.9)',  dim: 'rgba(255, 159, 64, 0.15)' },
    payroll:  { active: 'rgba(40, 167, 69, 0.9)',   dim: 'rgba(40, 167, 69, 0.15)' },
};

function applyMultiBarHighlight() {
    if (!monthlyChart) return;
    const n = props.monthlyComparison.labels.length;
    const activeSet = new Set(selectedBars.value.map(b => b.key));
    monthlyChart.data.datasets.forEach((ds, dsIdx) => {
        const type = ds._type;
        const colors = DATASET_COLORS[type] || DATASET_COLORS.budget;
        ds.backgroundColor = Array.from({ length: n }, (_, i) =>
            activeSet.has(`${dsIdx}:${i}`) ? colors.active : colors.dim
        );
        ds.borderColor = Array.from({ length: n }, (_, i) =>
            activeSet.has(`${dsIdx}:${i}`) ? colors.active : colors.dim
        );
    });
    monthlyChart.update();
}

function clearBarHighlight() {
    if (!monthlyChart) return;
    monthlyChart.data.datasets.forEach(ds => {
        const type = ds._type;
        const colors = DATASET_COLORS[type] || DATASET_COLORS.budget;
        ds.backgroundColor = colors.active;
        ds.borderColor = colors.active;
    });
    monthlyChart.update();
}

// Caché y agrupación dedicadas para el detalle de Remuneraciones (Nivel1/Nivel2, sin producto)
const payrollDetailCache = ref({}); // { [monthId]: { rows: [{level1, level2, total_payroll}] } }

const payrollDetailGrouped = computed(() => {
    const selectedMonthIds = selectedBars.value.map(b => b.monthId);
    if (selectedMonthIds.length === 0) return [];

    const l2Map = {};
    for (const monthId of selectedMonthIds) {
        const data = payrollDetailCache.value[monthId];
        if (!data?.rows) continue;
        for (const row of data.rows) {
            if (!row.total_payroll || row.total_payroll <= 0) continue;
            const key = `${row.level1}||${row.level2}`;
            if (!l2Map[key]) l2Map[key] = { level1: row.level1, level2: row.level2, total: 0 };
            l2Map[key].total += row.total_payroll;
        }
    }

    const l1Map = {};
    for (const item of Object.values(l2Map)) {
        if (item.total <= 0) continue;
        if (!l1Map[item.level1]) l1Map[item.level1] = { level1: item.level1, rows: [], subtotal: 0 };
        l1Map[item.level1].rows.push(item);
        l1Map[item.level1].subtotal += item.total;
    }
    return Object.values(l1Map).sort((a, b) => a.level1.localeCompare(b.level1));
});

const payrollDetailTotals = computed(() => {
    const selectedMonthIds = selectedBars.value.map(b => b.monthId);
    return selectedMonthIds.reduce((acc, monthId) => {
        const data = payrollDetailCache.value[monthId];
        if (!data?.rows) return acc;
        data.rows.forEach(row => { acc += row.total_payroll || 0; });
        return acc;
    }, 0);
});

// Grupo activo según el tipo de columna seleccionada (para botones expandir/colapsar y estado vacío)
const activeDetailGrouped = computed(() =>
    monthlyDetailColumn.value === 'payroll' ? payrollDetailGrouped.value : monthlyDetailGrouped.value
);

// Agrupar y acumular filas de TODOS los meses seleccionados por nivel 1
const monthlyDetailGrouped = computed(() => {
    const col = monthlyDetailColumn.value;
    const selectedMonthIds = selectedBars.value.map(b => b.monthId);
    if (selectedMonthIds.length === 0) return [];

    // Merge: acumular por (level1, level2, level3, product_name) entre meses
    const productMap = {};
    for (const monthId of selectedMonthIds) {
        const data = monthlyDetailCache.value[monthId];
        if (!data?.rows) continue;
        for (const row of data.rows) {
            const val = col === 'invoiced' ? row.total_invoiced : row.total_consumed;
            if (!val || val <= 0) continue;
            const l1 = row.level1 || 'Sin clasificar';
            const productKey = `${l1}||${row.level2}||${row.level3}||${row.product_name}`;
            if (!productMap[productKey]) {
                productMap[productKey] = { ...row, total_invoiced: 0, total_consumed: 0, _l1: l1 };
            }
            productMap[productKey].total_invoiced += row.total_invoiced || 0;
            productMap[productKey].total_consumed += row.total_consumed || 0;
        }
    }

    // Reagrupar por level1
    const l1Map = {};
    for (const item of Object.values(productMap)) {
        const val = col === 'invoiced' ? item.total_invoiced : item.total_consumed;
        if (val <= 0) continue;
        const key = item._l1;
        if (!l1Map[key]) l1Map[key] = { level1: key, rows: [], subtotal: 0 };
        l1Map[key].rows.push(item);
        l1Map[key].subtotal += val;
    }
    return Object.values(l1Map).sort((a, b) => a.level1.localeCompare(b.level1));
});

// Seleccionar/deseleccionar barra con soporte Ctrl+Clic multi-selección
const toggleBarSelection = async (event, datasetIndex, barIndex, month, clickedType) => {
    if (monthlyDetailLoading.value) return;
    const key = `${datasetIndex}:${barIndex}`;
    const isCtrl = event.ctrlKey || event.metaKey;

    // Si se cambia de columna (invoiced ↔ consumed ↔ payroll), limpiar todo y empezar de cero
    if (selectedBars.value.length > 0 && clickedType !== monthlyDetailColumn.value) {
        selectedBars.value = [];
        monthlyDetailCache.value = {};
        payrollDetailCache.value = {};
        monthlyDetailExpandedGroups.value = [];
    }
    monthlyDetailColumn.value = clickedType;

    if (isCtrl) {
        // Ctrl+Clic: toggle esta barra en la selección múltiple
        const idx = selectedBars.value.findIndex(b => b.key === key);
        if (idx > -1) {
            selectedBars.value.splice(idx, 1);
        } else {
            selectedBars.value.push({ key, datasetIndex, barIndex, monthId: month.id, monthName: month.name, column: clickedType });
        }
    } else {
        // Clic normal: si ya es la única seleccionada, deseleccionar; sino seleccionar solo esta
        const alreadyAlone = selectedBars.value.length === 1 && selectedBars.value[0].key === key;
        if (alreadyAlone) {
            selectedBars.value = [];
        } else {
            selectedBars.value = [{ key, datasetIndex, barIndex, monthId: month.id, monthName: month.name, column: clickedType }];
        }
    }

    // Actualizar highlight
    if (selectedBars.value.length === 0) {
        clearBarHighlight();
        return;
    }
    applyMultiBarHighlight();

    // Cargar datos de meses que no están en caché
    const cache = clickedType === 'payroll' ? payrollDetailCache : monthlyDetailCache;
    const toFetch = selectedBars.value.filter(b => !cache.value[b.monthId]);
    if (toFetch.length > 0) {
        monthlyDetailLoading.value = true;
        try {
            await Promise.all(toFetch.map(async (bar) => {
                if (clickedType === 'payroll') {
                    const response = await axios.get(route('api.comparative.payroll-monthly-detail'), {
                        params: {
                            month_id: bar.monthId,
                            ...(selectedCompanyReasons.value.length > 0 ? { company_reason_ids: selectedCompanyReasons.value } : {})
                        }
                    });
                    payrollDetailCache.value = { ...payrollDetailCache.value, [bar.monthId]: response.data };
                } else {
                    const response = await axios.get(route('api.comparative.monthly-detail'), {
                        params: {
                            month_id: bar.monthId,
                            include_investments: includeInvestments.value ? 1 : 0,
                            ...(selectedCompanyReasons.value.length > 0 ? { company_reason_ids: selectedCompanyReasons.value } : {})
                        }
                    });
                    monthlyDetailCache.value = { ...monthlyDetailCache.value, [bar.monthId]: response.data };
                }
            }));
        } catch (error) {
            console.error('Error recargando detalles:', error);
        } finally {
            monthlyDetailLoading.value = false;
        }
    }
};

// Al cambiar toggle de inversiones, limpiar caché y recargar meses seleccionados (no aplica a Remuneraciones)
watch(includeInvestments, async () => {
    const barsSnapshot = selectedBars.value.filter(b => b.column !== 'payroll');
    if (barsSnapshot.length > 0) {
        monthlyDetailCache.value = {};
        monthlyDetailLoading.value = true;
        try {
            await Promise.all(barsSnapshot.map(async (bar) => {
                const response = await axios.get(route('api.comparative.monthly-detail'), {
                    params: {
                        month_id: bar.monthId,
                        include_investments: includeInvestments.value ? 1 : 0,
                        ...(selectedCompanyReasons.value.length > 0 ? { company_reason_ids: selectedCompanyReasons.value } : {})
                    }
                });
                monthlyDetailCache.value = { ...monthlyDetailCache.value, [bar.monthId]: response.data };
            }));
        } catch (error) {
            console.error('Error recargando detalles:', error);
        } finally {
            monthlyDetailLoading.value = false;
        }
    }
});

// Totales acumulados de todos los meses seleccionados
const monthlyDetailTotals = computed(() => {
    const selectedMonthIds = selectedBars.value.map(b => b.monthId);
    if (selectedMonthIds.length === 0) return { invoiced: 0, consumed: 0 };
    return selectedMonthIds.reduce((acc, monthId) => {
        const data = monthlyDetailCache.value[monthId];
        if (!data?.rows) return acc;
        data.rows.forEach(row => {
            acc.invoiced += row.total_invoiced || 0;
            acc.consumed += row.total_consumed || 0;
        });
        return acc;
    }, { invoiced: 0, consumed: 0 });
});

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

    // La tabla "Detalle por Categoría" aparece agrupada por Nivel 1 por defecto
    groupByLevel1();
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
    displayedBudget.value - displayedInvoiced.value - (props.payrollSummary?.total || 0)
);

const displayedPercentageExecution = computed(() => 
    displayedBudget.value > 0
        ? ((displayedInvoiced.value + (props.payrollSummary?.total || 0)) / displayedBudget.value) * 100
        : 0
);

// Filas de remuneraciones para la tabla Detalle por Categoría
const payrollRows = computed(() => {
    if (!(props.payrollSummary?.total > 0)) return [];
    const byLevel2 = props.payrollByLevel2 || {};
    return Object.entries(byLevel2)
        .filter(([, data]) => data.total > 0)
        .map(([level2, data]) => ({
            level1: data.level1 || 'Remuneraciones',
            level2: level2,
            budget: 0,
            invoiced: 0,
            consumed: 0,
            payroll: data.total,
            isPayroll: true
        }))
        .sort((a, b) => a.level1.localeCompare(b.level1) || a.level2.localeCompare(b.level2));
});

// Remuneraciones acumuladas por mes (running sum, null para meses futuros)
const payrollCumulative = computed(() => {
    const lastMonth = props.cumulativeComparison?.last_month_with_data ?? -1;
    const monthly = props.payrollMonthly || Array(12).fill(0);
    let cumSum = 0;
    return monthly.map((v, i) => {
        if (i > lastMonth) return null;
        cumSum += v || 0;
        return cumSum;
    });
});

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

// ────────────────────────────────────────────────────────────────
// Tabla "Detalle Mensual por Categoría" (Presupuesto vs Real vs Diferencia)
// Real = Facturado + Remuneraciones. El filtro de meses se aplica 100% en frontend
// (los datos de los 12 meses ya vienen calculados en comparisonByLevel1Monthly).
//
// Selector "Real: Facturado | Consumido" — Facturado viene precalculado en el
// payload inicial; Consumido se pide bajo demanda (es una vuelta pesada por
// todos los outflows de la temporada) y se cachea en el frontend hasta que
// cambien los filtros (inversiones / razón social aplicada).
// ────────────────────────────────────────────────────────────────

const realSourceMode = ref('facturado'); // 'facturado' | 'consumido'
const consumedCategoryRows = ref(null); // null = aún no cargado
const loadingConsumedByCategory = ref(false);
const realColumnLabel = computed(() => realSourceMode.value === 'consumido' ? 'Consumido' : 'Real');

// Convierte el objeto {month_id: monto} del backend a un array de 12 posiciones
// respetando el orden de meses de la temporada (props.months)
const monthlyByIdToArray = (monthlyById) => (props.months || []).map(m => Number(monthlyById?.[m.id] || 0));

const fetchConsumedByCategory = async () => {
    loadingConsumedByCategory.value = true;
    try {
        const response = await axios.get(route('api.comparative.consumed-by-category'), {
            params: {
                include_investments: includeInvestments.value ? 1 : 0,
                ...(selectedCompanyReasons.value.length > 0 ? { company_reason_ids: selectedCompanyReasons.value } : {})
            }
        });
        consumedCategoryRows.value = response.data.rows || [];
    } catch (error) {
        console.error('Error cargando consumido por categoría:', error);
        consumedCategoryRows.value = [];
    } finally {
        loadingConsumedByCategory.value = false;
    }
};

// Al activar "Consumido" por primera vez, se carga desde el backend
watch(realSourceMode, (val) => {
    if (val === 'consumido' && consumedCategoryRows.value === null) {
        fetchConsumedByCategory();
    }
});

// Si cambian inversiones o la razón social aplicada, invalidar caché de consumido
watch([includeInvestments, () => props.activeCompanyReasonIds], () => {
    if (realSourceMode.value === 'consumido') {
        consumedCategoryRows.value = null;
        fetchConsumedByCategory();
    }
});

// Fuente de datos activa para la tabla: Facturado (prop tal cual) o Consumido
// (presupuesto de comparisonByLevel1Monthly + consumido fusionado por categoría).
// El Real de Consumido también suma Remuneraciones (payroll_monthly), igual que
// el Real de Facturado, para que ambos modos sean comparables contra Presupuesto.
// Nota: la clasificación de Consumido es la propia del outflow, por lo que puede
// haber categorías que no existan en Facturado/Presupuesto (se agregan al final).
const activeMonthlyDetailItems = computed(() => {
    const budgetItems = props.comparisonByLevel1Monthly || [];
    if (realSourceMode.value !== 'consumido') return budgetItems;

    const consumedMap = new Map();
    for (const row of (consumedCategoryRows.value || [])) {
        const key = row.level1 + '||' + row.level2 + '||' + row.level3;
        consumedMap.set(key, monthlyByIdToArray(row.monthly));
    }

    const merged = budgetItems.map(item => {
        const key = item.level1 + '||' + item.level2 + '||' + item.level3;
        const consumedArr = consumedMap.get(key) || Array(12).fill(0);
        consumedMap.delete(key);
        const real_monthly = consumedArr.map((v, i) => v + (item.payroll_monthly?.[i] || 0));
        return {
            ...item,
            real_monthly,
            difference_monthly: item.budget_monthly.map((b, i) => b - real_monthly[i]),
        };
    });

    // Categorías que solo existen en Consumido (sin presupuesto ni facturado asociado)
    for (const [key, real_monthly] of consumedMap.entries()) {
        const [level1, level2, level3] = key.split('||');
        merged.push({
            level1, level2, level3,
            budget_monthly: Array(12).fill(0),
            real_monthly,
            difference_monthly: real_monthly.map(v => -v),
        });
    }

    return merged;
});

// Opciones del multiselect: un item por cada mes de la temporada
const monthlyDetailMonthOptions = computed(() =>
    (props.months || []).map((m, index) => ({ value: index, label: m.name }))
);

// Meses seleccionados (índices 0-11) — por defecto, todos los meses con datos
const selectedMonthlyDetailMonths = ref([]);
onMounted(() => {
    const lastMonth = props.cumulativeComparison?.last_month_with_data ?? -1;
    selectedMonthlyDetailMonths.value = monthlyDetailMonthOptions.value
        .filter(opt => opt.value <= lastMonth)
        .map(opt => opt.value);
});

// Meses seleccionados, ordenados cronológicamente (el multiselect no garantiza el orden de selección)
const orderedSelectedMonths = computed(() =>
    [...selectedMonthlyDetailMonths.value].sort((a, b) => a - b)
);

// Árbol expandible: Nivel 1 cerrado por defecto → expande Nivel 2 → expande Nivel 3
const expandedMonthlyL1 = ref(new Set());
const expandedMonthlyL2 = ref(new Set());
const toggleMonthlyL1 = (level1) => {
    const s = new Set(expandedMonthlyL1.value);
    s.has(level1) ? s.delete(level1) : s.add(level1);
    expandedMonthlyL1.value = s;
};
const toggleMonthlyL2 = (key) => {
    const s = new Set(expandedMonthlyL2.value);
    s.has(key) ? s.delete(key) : s.add(key);
    expandedMonthlyL2.value = s;
};

const sumMonthlyArray = (items, field) => {    const arr = Array(12).fill(0);
    for (const it of items) {
        for (let i = 0; i < 12; i++) arr[i] += (it[field]?.[i] || 0);
    }
    return arr;
};

// Árbol Nivel1 → Nivel2 → Nivel3, con totales agregados por mes en cada nivel
const monthlyDetailTree = computed(() => {
    const l1Map = new Map();
    for (const item of activeMonthlyDetailItems.value) {
        if (!l1Map.has(item.level1)) l1Map.set(item.level1, new Map());
        const l2Map = l1Map.get(item.level1);
        if (!l2Map.has(item.level2)) l2Map.set(item.level2, []);
        l2Map.get(item.level2).push(item);
    }

    const normalize = (s) => (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const level1Order = { 'costos directos': 1, 'administracion': 2, 'generales campo': 3, 'cosecha': 4, 'sin clasificar': 5 };

    const tree = Array.from(l1Map.entries()).map(([level1Name, l2Map]) => {
        const level2Groups = Array.from(l2Map.entries()).map(([level2Name, items]) => {
            const budget_monthly = sumMonthlyArray(items, 'budget_monthly');
            const real_monthly = sumMonthlyArray(items, 'real_monthly');
            return {
                name: level2Name,
                key: level1Name + '||' + level2Name,
                items,
                budget_monthly,
                real_monthly,
                difference_monthly: budget_monthly.map((b, i) => b - real_monthly[i]),
            };
        }).sort((a, b) => a.name.localeCompare(b.name));

        const allItems = level2Groups.flatMap(g => g.items);
        const budget_monthly = sumMonthlyArray(allItems, 'budget_monthly');
        const real_monthly = sumMonthlyArray(allItems, 'real_monthly');

        return {
            name: level1Name,
            level2Groups,
            budget_monthly,
            real_monthly,
            difference_monthly: budget_monthly.map((b, i) => b - real_monthly[i]),
        };
    });

    tree.sort((a, b) => {
        const oa = level1Order[normalize(a.name)] ?? 99;
        const ob = level1Order[normalize(b.name)] ?? 99;
        return oa !== ob ? oa - ob : a.name.localeCompare(b.name);
    });

    return tree;
});

// ¿Están todas las categorías (Nivel 1 y Nivel 2) expandidas?
const isMonthlyDetailAllExpanded = computed(() => {
    const tree = monthlyDetailTree.value;
    if (tree.length === 0) return false;
    return tree.every(l1 =>
        expandedMonthlyL1.value.has(l1.name) &&
        l1.level2Groups.every(l2 => expandedMonthlyL2.value.has(l2.key))
    );
});

// Expande o colapsa de golpe todas las categorías de la tabla mensual
const toggleMonthlyExpandAll = () => {
    if (isMonthlyDetailAllExpanded.value) {
        expandedMonthlyL1.value = new Set();
        expandedMonthlyL2.value = new Set();
        return;
    }
    const l1Set = new Set();
    const l2Set = new Set();
    monthlyDetailTree.value.forEach(l1 => {
        l1Set.add(l1.name);
        l1.level2Groups.forEach(l2 => l2Set.add(l2.key));
    });
    expandedMonthlyL1.value = l1Set;
    expandedMonthlyL2.value = l2Set;
};

// Suma de un array de 12 posiciones, restringida a los meses seleccionados
const sumSelectedMonths = (arr) => orderedSelectedMonths.value.reduce((acc, i) => acc + (arr?.[i] || 0), 0);

// Total general (fila TOTAL de la tabla), por mes
const monthlyDetailGrandTotal = computed(() => {
    const items = activeMonthlyDetailItems.value;
    const budget_monthly = sumMonthlyArray(items, 'budget_monthly');
    const real_monthly = sumMonthlyArray(items, 'real_monthly');
    return { budget_monthly, real_monthly, difference_monthly: budget_monthly.map((b, i) => b - real_monthly[i]) };
});

// Color del borde de la zona "Total selección", según el signo de la diferencia total
const totalSelectionZoneColor = computed(() => {
    const diff = sumSelectedMonths(monthlyDetailGrandTotal.value.difference_monthly);
    return diff >= 0 ? '#4caf50' : '#dc3545';
});

// Grosor del borde de la zona "Total selección" (más grueso cuando es positivo/verde)
const totalSelectionZoneWidth = computed(() => {
    const diff = sumSelectedMonths(monthlyDetailGrandTotal.value.difference_monthly);
    return diff >= 0 ? '3px' : '2px';
});

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

        // Remuneraciones mensual y acumulado — null para meses futuros
        const payrollMonth = index <= props.cumulativeComparison.last_month_with_data
            ? (props.payrollMonthly[index] || 0)
            : null;
        const payrollCum = payrollCumulative.value[index]; // null para meses futuros
        
        // Aplicar conversión USD si está activada
        const convertedBudget = dividir.value && divisor.value ? budget / divisor.value : budget;
        const convertedInvoiced = invoiced !== null && dividir.value && divisor.value ? invoiced / divisor.value : invoiced;
        const convertedConsumed = consumed !== null && dividir.value && divisor.value ? consumed / divisor.value : consumed;
        const convertedInvoicedMonthly = invoicedMonthly !== null && dividir.value && divisor.value ? invoicedMonthly / divisor.value : invoicedMonthly;
        const convertedPayrollMonth = payrollMonth !== null && dividir.value && divisor.value ? payrollMonth / divisor.value : payrollMonth;
        const convertedPayrollCum = payrollCum !== null && dividir.value && divisor.value ? payrollCum / divisor.value : payrollCum;
        
        const difference = convertedInvoiced !== null ? convertedBudget - convertedInvoiced - (convertedPayrollCum || 0) : null;
        const differenceConsumed = convertedConsumed !== null ? convertedBudget - convertedConsumed : null;
        const variance = convertedInvoiced !== null && convertedBudget > 0 ? ((convertedInvoiced / convertedBudget) * 100 - 100) : null;
        const varianceConsumed = convertedConsumed !== null && convertedBudget > 0 ? ((convertedConsumed / convertedBudget) * 100 - 100) : null;
        
        return {
            month: month,
            invoiced_monthly: convertedInvoicedMonthly !== null ? convertedInvoicedMonthly : 0,
            payroll_monthly: convertedPayrollMonth !== null ? convertedPayrollMonth : 0,
            payroll_cumulative: convertedPayrollCum !== null ? convertedPayrollCum : 0,
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

// Datos para exportar la tabla "Detalle por Categoría"
// Genera filas planas respetando la jerarquía activa:
//   - Agrupado (L1 > L2 > L3)
//   - Vista plana (L1, L2, L3, valores)
const detailCategoryExcelData = computed(() => {
    const div = dividir.value && divisor.value ? divisor.value : 1;
    const conv = (v) => (v || 0) / div;

    const row = (nivel1, nivel2, nivel3, item, indent = '') => ({
        'Nivel 1': nivel1,
        'Nivel 2': nivel2,
        'Nivel 3': nivel3,
        'Presupuestado': conv(item.budget),
        'Facturado': conv(item.invoiced),
        'Consumido': conv(item.consumed),
        'Remuneraciones': conv(item.payroll),
        'Diferencia': conv(item.difference),
        'Variación %': item.budget > 0
            ? (((item.invoiced - item.budget) / item.budget) * 100).toFixed(2)
            : '0.00',
    });

    if (customGroups.value.length > 0) {
        // Modo agrupado jerárquico
        const rows = [];
        for (const group of customGroups.value) {
            if (group.level2Groups) {
                // Fila totalizadora L1
                rows.push({
                    'Nivel 1': group.name,
                    'Nivel 2': '',
                    'Nivel 3': '',
                    'Presupuestado': conv(group.totals.budget),
                    'Facturado': conv(group.totals.invoiced),
                    'Consumido': conv(group.totals.consumed),
                    'Remuneraciones': conv(group.totals.payroll),
                    'Diferencia': conv(group.totals.difference),
                    'Variación %': group.totals.budget > 0
                        ? (((group.totals.invoiced - group.totals.budget) / group.totals.budget) * 100).toFixed(2)
                        : '0.00',
                });
                for (const l2 of group.level2Groups) {
                    // Fila totalizadora L2
                    rows.push({
                        'Nivel 1': '',
                        'Nivel 2': l2.name,
                        'Nivel 3': '',
                        'Presupuestado': conv(l2.totals.budget),
                        'Facturado': conv(l2.totals.invoiced),
                        'Consumido': conv(l2.totals.consumed),
                        'Remuneraciones': conv(l2.totals.payroll),
                        'Diferencia': conv(l2.totals.difference),
                        'Variación %': l2.totals.budget > 0
                            ? (((l2.totals.invoiced - l2.totals.budget) / l2.totals.budget) * 100).toFixed(2)
                            : '0.00',
                    });
                    for (const item of l2.items) {
                        rows.push(row('', '', item.level3, item));
                    }
                }
            } else {
                // Grupo manual plano
                rows.push({
                    'Nivel 1': group.name,
                    'Nivel 2': '(grupo)',
                    'Nivel 3': '',
                    'Presupuestado': conv(group.totals.budget),
                    'Facturado': conv(group.totals.invoiced),
                    'Consumido': conv(group.totals.consumed),
                    'Remuneraciones': conv(group.totals.payroll),
                    'Diferencia': conv(group.totals.difference),
                    'Variación %': group.totals.budget > 0
                        ? (((group.totals.invoiced - group.totals.budget) / group.totals.budget) * 100).toFixed(2)
                        : '0.00',
                });
                for (const item of (group.items || [])) {
                    rows.push(row(item.level1, item.level2, item.level3, item));
                }
            }
        }
        return rows;
    }

    // Vista plana normal
    return props.comparisonByLevel1.map(item => row(item.level1, item.level2, item.level3, item));
});

// Watch para actualizar gráficos cuando cambie el toggle o la conversión USD
watch([includeInvestments, dividir, divisor, isEnglish, showBudget, showInvoiced, showConsumed, showPayroll], () => {
    selectedBars.value = [];
    monthlyDetailCache.value = {};
    payrollDetailCache.value = {};
    clearBarHighlight();
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

    const consumedData = includeInvestments.value 
        ? props.monthlyComparison.consumed_with_investments 
        : props.monthlyComparison.consumed;

    // Aplicar conversión USD si está activada
    const convertedBudgetData = dividir.value && divisor.value 
        ? budgetData.map(v => v / divisor.value)
        : budgetData;
    
    const convertedRealData = dividir.value && divisor.value
        ? props.monthlyComparison.real.map(v => v / divisor.value)
        : props.monthlyComparison.real;

    const convertedConsumedData = dividir.value && divisor.value
        ? consumedData.map(v => v / divisor.value)
        : consumedData;

    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.monthlyComparison.labels,
            datasets: [
                ...(showBudget.value ? [{
                    _type: 'budget',
                    label: includeInvestments.value ? t.value.budgetedWithInv : t.value.budgetedLabel,
                    data: convertedBudgetData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }] : []),
                ...(showInvoiced.value ? [{
                    _type: 'invoiced',
                    label: t.value.invoicedLabel,
                    data: convertedRealData,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }] : []),
                ...(showConsumed.value ? [{
                    _type: 'consumed',
                    label: includeInvestments.value ? t.value.consumedWithInvLabel : t.value.consumedLabel,
                    data: convertedConsumedData,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }] : []),
                ...(showPayroll.value && (props.payrollSummary?.total || 0) > 0 ? [{
                    _type: 'payroll',
                    label: t.value.payroll,
                    data: dividir.value && divisor.value
                        ? props.payrollMonthly.map(v => v / divisor.value)
                        : props.payrollMonthly,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }] : [])
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const datasetIndex = elements[0].datasetIndex;
                    const month = props.months[index];
                    if (month && monthlyChart) {
                        const clickedType = monthlyChart.data.datasets[datasetIndex]?._type;
                        if (!clickedType || clickedType === 'budget') return;
                        toggleBarSelection(event.native, datasetIndex, index, month, clickedType);
                    }
                }
            },
            plugins: {
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
                },
                datalabels: {
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] > 0;
                    },
                    anchor: 'end',
                    align: 'end',
                    rotation: -45,
                    font: { size: 10, weight: 'bold' },
                    color: '#333',
                    formatter: function(value) {
                        if (value >= 1000000000) {
                            return (value / 1000000000).toFixed(1) + ' MM';
                        } else if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + ' M';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(0) + ' K';
                        }
                        return value;
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
        },
        plugins: [ChartDataLabels]
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
                ...(showBudget.value ? [{
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
                    pointHoverRadius: 6,
                    datalabels: {
                        align: 'top',
                        offset: 10,
                        color: 'rgb(54, 162, 235)',
                    }
                }] : []),
                ...(showInvoiced.value ? [{
                    label: t.value.cumInvoiced,
                    data: convertedRealCumulative,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    spanGaps: false,
                    datalabels: {
                        align: 'bottom',
                        offset: 10,
                        color: 'rgb(75, 192, 192)',
                    }
                }] : []),
                ...(showConsumed.value ? [{
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
                    spanGaps: false,
                    datalabels: {
                        align: 'right',
                        offset: 10,
                        color: 'rgb(255, 159, 64)',
                    }
                }] : []),
                ...(showPayroll.value && (props.payrollSummary?.total || 0) > 0 ? [{
                    label: t.value.payroll,
                    data: dividir.value && divisor.value
                        ? payrollCumulative.value.map(v => v !== null ? v / divisor.value : null)
                        : payrollCumulative.value,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    spanGaps: false,
                    datalabels: {
                        align: 'top',
                        offset: 5,
                        color: 'rgb(40, 167, 69)',
                    }
                }] : [])
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
                },
                datalabels: {
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] !== null && context.dataset.data[context.dataIndex] > 0;
                    },
                    anchor: 'end',
                    font: { size: 11, weight: 'bold' },
                    formatter: function(value) {
                        if (value === null) return '';
                        if (value >= 1000000000) {
                            return (value / 1000000000).toFixed(1) + ' MM';
                        } else if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + ' M';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(0) + ' K';
                        }
                        return value;
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
        },
        plugins: [ChartDataLabels]
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
                                        <input type="number" v-model.number="divisor"
                                               min="1" step="0.0001"
                                               class="form-control form-control-sm text-end"
                                               style="width:110px; flex-shrink:0;"
                                               title="Ingresa el tipo de cambio manualmente (hasta 4 decimales)" />
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
                <!-- Filtro Razón Social -->
                <div class="row mt-0 mb-2 ms-1 align-items-center g-1" v-if="companyReasons?.length > 0">
                    <div class="col-auto">
                        <label class="form-label mb-0 small fw-semibold text-muted">
                            <i class="fas fa-building me-1"></i>Razón Social
                        </label>
                    </div>
                    <div class="col" style="max-width: 460px;">
                        <Multiselect
                            v-model="selectedCompanyReasons"
                            :options="companyReasons"
                            mode="multiple"
                            :searchable="true"
                            :close-on-select="false"
                            :hide-selected="false"
                            :multipleLabel="(vals) => vals.length ? vals.map(v => v.label).join(', ') : 'Todas las razones sociales'"
                            placeholder="Todas las razones sociales"
                            no-options-text="Sin opciones"
                            no-results-text="Sin resultados"
                            class="multiselect-sm multiselect-company-reason"
                            :style="{'--ms-min-h': '1.9rem', '--ms-py': '0.25rem', '--ms-font-size': '0.78rem'}"
                        />
                    </div>
                    <div class="col-auto ps-1">
                        <button
                            type="button"
                            class="btn btn-falcon-primary btn-sm"
                            @click="applyCompanyReasonFilter"
                        >
                            <i class="fas fa-filter fa-xs me-1"></i>Aplicar
                        </button>
                        <span v-if="props.activeCompanyReasonIds && props.activeCompanyReasonIds.length > 0" class="btn btn-sm btn-primary ms-1 pe-none" style="font-size:0.75rem;">
                            {{ props.activeCompanyReasonIds.length }} filtradas
                        </span>
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

                <!-- Remuneraciones Card -->
                <div v-if="(payrollSummary?.total || 0) > 0" class="col-lg col-md-6">
                    <div class="card h-100 border-start border-success border-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">{{ t.payroll }}</h6>
                                <i class="fas fa-users text-success fa-lg"></i>
                            </div>
                            <h4 class="mb-0 text-success text-nowrap" style="font-size: 1.15rem;">{{ formatCLP(payrollSummary?.total || 0) }}</h4>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                {{ new Intl.NumberFormat('es-CL').format(Math.round(payrollSummary?.workdays || 0)) }} {{ t.payrollWorkdays }}
                            </small>
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

            <!-- Toggles para series de gráficos -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center gap-4">
                                <small class="text-muted fw-semibold"><i class="fas fa-eye me-1"></i>{{ isEnglish ? 'Show in charts' : 'Mostrar en gráficos' }}:</small>
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" role="switch" id="showBudgetToggle" v-model="showBudget" style="cursor: pointer;">
                                    <label class="form-check-label small mb-0" for="showBudgetToggle" style="cursor: pointer;">
                                        <span :class="showBudget ? 'text-primary' : 'text-secondary'">
                                            <i :class="showBudget ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                            {{ isEnglish ? 'Budget' : 'Presupuesto' }}
                                        </span>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" role="switch" id="showInvoicedToggle" v-model="showInvoiced" style="cursor: pointer;">
                                    <label class="form-check-label small mb-0" for="showInvoicedToggle" style="cursor: pointer;">
                                        <span :class="showInvoiced ? 'text-success' : 'text-secondary'">
                                            <i :class="showInvoiced ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                            {{ isEnglish ? 'Invoiced' : 'Facturado' }}
                                        </span>
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" role="switch" id="showConsumedToggle" v-model="showConsumed" style="cursor: pointer;">
                                    <label class="form-check-label small mb-0" for="showConsumedToggle" style="cursor: pointer;">
                                        <span :class="showConsumed ? 'text-warning' : 'text-secondary'">
                                            <i :class="showConsumed ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                            {{ isEnglish ? 'Outflows' : 'Consumos' }}
                                        </span>
                                    </label>
                                </div>
                                <div v-if="(payrollSummary?.total || 0) > 0" class="form-check form-switch mb-0 d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" role="switch" id="showPayrollToggle" v-model="showPayroll" style="cursor: pointer;">
                                    <label class="form-check-label small mb-0" for="showPayrollToggle" style="cursor: pointer;">
                                        <span :class="showPayroll ? 'text-success' : 'text-secondary'">
                                            <i :class="showPayroll ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                            {{ isEnglish ? 'Payroll' : 'Remuneraciones' }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico Mensual -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="height: 400px; cursor: pointer;" title="Haz clic en una barra para ver el detalle">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>Clic en barra de <strong>Facturado</strong>, <strong>Consumos</strong> o <strong>Remuneraciones</strong> para ver detalle &nbsp;·&nbsp; <kbd>Ctrl</kbd>+Clic para seleccionar múltiples meses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Detalle Mensual (aparece al hacer clic en el gráfico) -->
            <div v-if="selectedBars.length > 0 || monthlyDetailLoading" class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row flex-between-center">
                                <div class="col-auto d-flex align-items-center gap-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-list me-2"></i>
                                        Detalle de <span
                                            :class="monthlyDetailColumn === 'invoiced' ? 'text-success' : ''"
                                            :style="monthlyDetailColumn === 'consumed' ? 'color: rgb(255, 159, 64)' : (monthlyDetailColumn === 'payroll' ? 'color: rgb(40, 167, 69)' : '')"
                                        >{{ monthlyDetailColumn === 'invoiced' ? 'Facturado' : (monthlyDetailColumn === 'payroll' ? 'Remuneraciones' : 'Consumos') }}</span>
                                        — <span class="text-primary">{{ monthlyDetailMonthNames }}</span>
                                        <span v-if="selectedBars.length > 1" class="badge bg-primary ms-1" style="font-size:0.68rem;">{{ selectedBars.length }} meses</span>
                                    </h6>
                                    <!-- Badge inversiones (no aplica a Remuneraciones) -->
                                    <span
                                        v-if="monthlyDetailColumn !== 'payroll'"
                                        class="badge rounded-pill"
                                        :class="includeInvestments ? 'bg-warning text-dark' : 'bg-secondary'"
                                        style="font-size: 0.7rem; font-weight: 500;"
                                        v-tooltip="includeInvestments ? 'Incluye inversiones' : 'Sin inversiones'"
                                    >
                                        <i class="fas fa-tractor fa-xs me-1"></i>{{ includeInvestments ? 'Con inv.' : 'Sin inv.' }}
                                    </span>
                                    <!-- Botones expandir/colapsar todo -->
                                    <template v-if="activeDetailGrouped.length > 0">
                                        <button
                                            @click="monthlyDetailExpandedGroups = activeDetailGrouped.map(g => g.level1)"
                                            class="btn btn-sm btn-falcon-default py-0 px-2"
                                            style="font-size:0.72rem"
                                            v-tooltip="'Expandir todo'"
                                        ><i class="fas fa-expand-alt fa-xs"></i></button>
                                        <button
                                            @click="monthlyDetailExpandedGroups = []"
                                            class="btn btn-sm btn-falcon-default py-0 px-2"
                                            style="font-size:0.72rem"
                                            v-tooltip="'Colapsar todo'"
                                        ><i class="fas fa-compress-alt fa-xs"></i></button>
                                    </template>
                                </div>
                                <div class="col-auto">
                                    <button
                                        @click="selectedBars = []; monthlyDetailCache = {}; payrollDetailCache = {}; monthlyDetailColumn = 'invoiced'; monthlyDetailExpandedGroups = []; clearBarHighlight()"
                                        class="btn btn-sm btn-falcon-default"
                                    >
                                        <i class="fas fa-times fa-xs me-1"></i>Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Loading -->
                            <div v-if="monthlyDetailLoading" class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-lg text-primary"></i>
                                <p class="text-muted mt-2 mb-0">Cargando detalle...</p>
                            </div>
                            <!-- Tabla de Remuneraciones agrupada por Nivel 1 (Nivel 2, sin producto) -->
                            <div v-else-if="monthlyDetailColumn === 'payroll' && activeDetailGrouped.length > 0" class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nivel 2</th>
                                            <th class="text-end">Remuneraciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="group in activeDetailGrouped" :key="group.level1">
                                            <tr class="table-info cursor-pointer" @click="monthlyDetailExpandedGroups.includes(group.level1) ? monthlyDetailExpandedGroups.splice(monthlyDetailExpandedGroups.indexOf(group.level1), 1) : monthlyDetailExpandedGroups.push(group.level1)">
                                                <td class="fw-bold">
                                                    <i class="fas fa-xs me-2"
                                                        :class="monthlyDetailExpandedGroups.includes(group.level1) ? 'fa-chevron-down' : 'fa-chevron-right'"
                                                    ></i>
                                                    {{ group.level1 }}
                                                </td>
                                                <td class="text-end fw-bold" style="color: rgb(40, 167, 69)">{{ formatCLP(group.subtotal) }}</td>
                                            </tr>
                                            <template v-if="monthlyDetailExpandedGroups.includes(group.level1)">
                                                <tr v-for="(row, i) in group.rows" :key="i">
                                                    <td class="ps-4 fw-semibold">{{ row.level2 }}</td>
                                                    <td class="text-end">{{ formatCLP(row.total) }}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td class="text-end fw-bold">Total</td>
                                            <td class="text-end" style="color: rgb(40, 167, 69)">{{ formatCLP(payrollDetailTotals) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- Tabla de Facturado/Consumos agrupada por Nivel 1 -->
                            <div v-else-if="monthlyDetailColumn !== 'payroll' && monthlyDetailGrouped.length > 0" class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nivel 2</th>
                                            <th>Nivel 3</th>
                                            <th>Producto</th>
                                            <th class="text-end">
                                                {{ monthlyDetailColumn === 'invoiced' ? 'Facturado' : 'Consumos' }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="group in monthlyDetailGrouped" :key="group.level1">
                                            <!-- Fila cabecera Nivel 1 -->
                                            <tr class="table-info cursor-pointer" @click="monthlyDetailExpandedGroups.includes(group.level1) ? monthlyDetailExpandedGroups.splice(monthlyDetailExpandedGroups.indexOf(group.level1), 1) : monthlyDetailExpandedGroups.push(group.level1)">
                                                <td colspan="3" class="fw-bold">
                                                    <i class="fas fa-xs me-2"
                                                        :class="monthlyDetailExpandedGroups.includes(group.level1) ? 'fa-chevron-down' : 'fa-chevron-right'"
                                                    ></i>
                                                    {{ group.level1 }}
                                                </td>
                                                <td class="text-end fw-bold"
                                                    :class="monthlyDetailColumn === 'invoiced' ? 'text-success' : ''"
                                                    :style="monthlyDetailColumn === 'consumed' ? 'color: rgb(255, 159, 64)' : ''"
                                                >{{ formatCLP(group.subtotal) }}</td>
                                            </tr>
                                            <!-- Filas de detalle (expandidas) -->
                                            <template v-if="monthlyDetailExpandedGroups.includes(group.level1)">
                                                <tr v-for="(row, i) in group.rows" :key="i">
                                                    <td class="ps-4 text-muted" style="font-size: 0.75rem;">{{ row.level2 }}</td>
                                                    <td class="text-muted" style="font-size: 0.75rem;">{{ row.level3 }}</td>
                                                    <td class="fw-semibold">{{ row.product_name }}</td>
                                                    <td class="text-end">
                                                        {{ formatCLP(monthlyDetailColumn === 'invoiced' ? row.total_invoiced : row.total_consumed) }}
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total</td>
                                            <td class="text-end"
                                                :class="monthlyDetailColumn === 'invoiced' ? 'text-success' : ''"
                                                :style="monthlyDetailColumn === 'consumed' ? 'color: rgb(255, 159, 64)' : ''"
                                            >
                                                {{ formatCLP(monthlyDetailColumn === 'invoiced' ? monthlyDetailTotals.invoiced : monthlyDetailTotals.consumed) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <!-- Sin datos -->
                            <div v-else class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-lg"></i>
                                <p class="mt-2 mb-0">Sin movimientos en el período seleccionado</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico Acumulado -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="height: 400px;">
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
                                        <tr v-if="showBudget">
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
                                        <tr v-if="showInvoiced">
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
                                        <!-- Fila: Consumos mensual -->
                                        <tr v-if="showConsumed">
                                            <td class="fw-semibold" style="color: rgb(255, 159, 64);">
                                                <i class="fas fa-arrow-circle-down fa-xs me-1"></i>{{ t.consumed }}
                                            </td>
                                            <td
                                                v-for="(val, i) in (includeInvestments ? monthlyComparison.consumed_with_investments : monthlyComparison.consumed)"
                                                :key="i"
                                                class="text-end"
                                            >
                                                <span v-if="val > 0">{{ formatCLP(val) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold" style="color: rgb(255, 159, 64);">
                                                {{ formatCLP((includeInvestments ? monthlyComparison.consumed_with_investments : monthlyComparison.consumed).reduce((a, b) => a + (b || 0), 0)) }}
                                            </td>
                                        </tr>
                                        <!-- Fila: Remuneraciones mensual -->
                                        <tr v-if="showPayroll && (payrollSummary?.total || 0) > 0">
                                            <td class="fw-semibold text-success">
                                                <i class="fas fa-users fa-xs me-1"></i>{{ t.payroll }}
                                            </td>
                                            <td
                                                v-for="(val, i) in payrollMonthly"
                                                :key="i"
                                                class="text-end"
                                            >
                                                <span v-if="val > 0">{{ formatCLP(val) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                {{ formatCLP(payrollMonthly.reduce((a, b) => a + (b || 0), 0)) }}
                                            </td>
                                        </tr>
                                        <!-- Fila: Diferencia (Presupuesto - Facturado) -->
                                        <tr class="table-light" v-if="showInvoiced">
                                            <td class="fw-semibold">
                                                <i class="fas fa-balance-scale fa-xs me-1"></i>{{ t.difference }} ({{ t.invoiced }})
                                            </td>
                                            <td
                                                v-for="(val, i) in monthlyComparison.real"
                                                :key="i"
                                                class="text-end fw-bold"
                                                :class="{
                                                    'text-danger': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) < 0,
                                                    'text-success': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) >= 0 && (val > 0 || (payrollMonthly[i] || 0) > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0),
                                                    'text-muted': val === 0 && (payrollMonthly[i] || 0) === 0 && (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) === 0
                                                }"
                                            >
                                                <template v-if="val > 0 || (payrollMonthly[i] || 0) > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0">
                                                    {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) }}
                                                </template>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold"
                                                :class="((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - monthlyComparison.real.reduce((a,b)=>a+(b||0),0) - payrollMonthly.reduce((a,b)=>a+(b||0),0)) < 0 ? 'text-danger' : 'text-success'">
                                                {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - monthlyComparison.real.reduce((a,b)=>a+(b||0),0) - payrollMonthly.reduce((a,b)=>a+(b||0),0)) }}
                                            </td>
                                        </tr>
                                        <!-- Fila: Diferencia (Presupuesto - Consumido) -->
                                        <tr class="table-light" v-if="showConsumed">
                                            <td class="fw-semibold">
                                                <i class="fas fa-balance-scale fa-xs me-1"></i>{{ t.difference }} ({{ t.consumed }})
                                            </td>
                                            <td
                                                v-for="(val, i) in (includeInvestments ? monthlyComparison.consumed_with_investments : monthlyComparison.consumed)"
                                                :key="i"
                                                class="text-end fw-bold"
                                                :class="{
                                                    'text-danger': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) < 0,
                                                    'text-success': ((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) >= 0 && (val > 0 || (payrollMonthly[i] || 0) > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0),
                                                    'text-muted': val === 0 && (payrollMonthly[i] || 0) === 0 && (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) === 0
                                                }"
                                            >
                                                <template v-if="val > 0 || (payrollMonthly[i] || 0) > 0 || (includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) > 0">
                                                    {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments[i] : monthlyComparison.budget[i]) - val - (payrollMonthly[i] || 0)) }}
                                                </template>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold"
                                                :class="((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - (includeInvestments ? monthlyComparison.consumed_with_investments : monthlyComparison.consumed).reduce((a,b)=>a+(b||0),0) - payrollMonthly.reduce((a,b)=>a+(b||0),0)) < 0 ? 'text-danger' : 'text-success'">
                                                {{ formatCLP((includeInvestments ? monthlyComparison.budget_with_investments : monthlyComparison.budget).reduce((a,b)=>a+(b||0),0) - (includeInvestments ? monthlyComparison.consumed_with_investments : monthlyComparison.consumed).reduce((a,b)=>a+(b||0),0) - payrollMonthly.reduce((a,b)=>a+(b||0),0)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalle Mensual por Categoría -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Detalle Mensual por Categoría
                                </h6>
                                <button
                                    class="btn btn-sm"
                                    :class="isMonthlyDetailAllExpanded ? 'btn-warning' : 'btn-outline-primary'"
                                    @click="toggleMonthlyExpandAll"
                                    :title="isMonthlyDetailAllExpanded ? 'Colapsar todas las categorías' : 'Expandir todas las categorías'">
                                    <i class="fas me-1" :class="isMonthlyDetailAllExpanded ? 'fa-list' : 'fa-folder-tree'"></i>
                                    {{ isMonthlyDetailAllExpanded ? 'Colapsar Todo' : 'Expandir Todo' }}
                                </button>
                            </div>
                            <div class="row align-items-center g-1 mb-2">
                                <div class="col-auto">
                                    <label class="form-label mb-0 small fw-semibold text-muted">
                                        <i class="fas fa-calendar me-1"></i>Meses
                                    </label>
                                </div>
                                <div class="col" style="max-width: 420px;">
                                    <Multiselect
                                        v-model="selectedMonthlyDetailMonths"
                                        :options="monthlyDetailMonthOptions"
                                        mode="multiple"
                                        :searchable="true"
                                        :close-on-select="false"
                                        :hide-selected="false"
                                        :multipleLabel="(vals) => vals.length ? vals.map(v => v.label).join(', ') : 'Selecciona meses'"
                                        placeholder="Selecciona meses"
                                        no-options-text="Sin opciones"
                                        no-results-text="Sin resultados"
                                        class="multiselect-sm multiselect-company-reason"
                                        :style="{'--ms-min-h': '1.9rem', '--ms-py': '0.25rem', '--ms-font-size': '0.78rem'}"
                                    />
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-0 small fw-semibold text-muted">
                                        <i class="fas fa-filter me-1"></i>Real
                                    </label>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button
                                            type="button"
                                            class="btn"
                                            :class="realSourceMode === 'facturado' ? 'btn-primary' : 'btn-outline-primary'"
                                            @click="realSourceMode = 'facturado'"
                                        >Facturado</button>
                                        <button
                                            type="button"
                                            class="btn"
                                            :class="realSourceMode === 'consumido' ? 'btn-primary' : 'btn-outline-primary'"
                                            @click="realSourceMode = 'consumido'"
                                        >Consumido</button>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i
                                        class="fas fa-circle-info text-muted"
                                        style="cursor: help;"
                                        v-tooltip="'Facturado: facturas del mes + remuneraciones, clasificado por el producto. Consumido: salidas de bodega (outflows) + remuneraciones, clasificado según la categoría propia del outflow y prorrateado por superficie de centro de costo. Al ser criterios distintos, las categorías mostradas pueden no coincidir exactamente entre ambas vistas.'"
                                    ></i>
                                </div>
                                <div v-if="loadingConsumedByCategory" class="col-auto">
                                    <i class="fas fa-circle-notch fa-spin text-muted"></i>
                                    <span class="small text-muted ms-1">Cargando consumido...</span>
                                </div>
                            </div>
                            <div v-if="realSourceMode === 'consumido'" class="alert alert-warning py-1 px-2 mb-0 small">
                                <i class="fas fa-triangle-exclamation me-1"></i>
                                Estás viendo el <strong>Consumido</strong> (salidas de bodega). Su clasificación por categoría y su reparto por razón social son distintos a los de Facturado, por lo que algunas categorías pueden variar o aparecer solo en esta vista.
                            </div>
                        </div>
                        <div class="card-body">
                            <div v-if="orderedSelectedMonths.length === 0" class="text-center py-4 text-muted">
                                <i class="fas fa-calendar-times fa-lg"></i>
                                <p class="mt-2 mb-0">Selecciona al menos un mes para ver el detalle</p>
                            </div>
                            <div v-else class="table-responsive" style="max-height: 640px;">
                                <table class="table table-sm table-hover mb-0 monthly-detail-table" style="font-size: 0.78rem;" :style="{'--zone-color': totalSelectionZoneColor, '--zone-width': totalSelectionZoneWidth}">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sticky-col text-truncate" rowspan="2" style="width: 160px; max-width: 160px; background-color:#f8f9fa; border: 2px solid #dee2e6;" title="Categoría">Categoría</th>
                                            <th v-for="(mIdx, mi) in orderedSelectedMonths" :key="'h-' + mIdx" colspan="3" class="text-center">
                                                {{ months[mIdx]?.name }}
                                            </th>
                                            <th v-if="orderedSelectedMonths.length > 1" colspan="3" class="text-center total-zone-top total-zone-left total-zone-right total-zone-tl total-zone-tr">
                                                Total selección
                                            </th>
                                        </tr>
                                        <tr>
                                            <template v-for="(mIdx, mi) in orderedSelectedMonths" :key="'sh-' + mIdx">
                                                <th class="text-end month-divider" style="min-width: 95px;">Presup.</th>
                                                <th class="text-end" style="min-width: 95px;" :title="realSourceMode === 'consumido' ? 'Consumido' : 'Facturado + Remuneraciones'">{{ realColumnLabel }}</th>
                                                <th class="text-end" style="min-width: 95px;">Dif.</th>
                                            </template>
                                            <template v-if="orderedSelectedMonths.length > 1">
                                                <th class="text-end total-zone-left" style="min-width: 95px;">Presup.</th>
                                                <th class="text-end" style="min-width: 95px;">{{ realColumnLabel }}</th>
                                                <th class="text-end total-zone-right" style="min-width: 95px;">Dif.</th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="l1 in monthlyDetailTree" :key="'ml1-' + l1.name">
                                            <!-- Fila Nivel 1 -->
                                            <tr class="table-info cursor-pointer" @click="toggleMonthlyL1(l1.name)">
                                                <td class="sticky-col fw-bold text-truncate" style="background-color:#d1ecf1;" :title="l1.name">
                                                    <i class="fas me-1" :class="expandedMonthlyL1.has(l1.name) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                    {{ l1.name }}
                                                </td>
                                                <template v-for="(mIdx, mi) in orderedSelectedMonths" :key="'l1v-' + l1.name + '-' + mIdx">
                                                    <td class="text-end month-divider">{{ formatCLP(l1.budget_monthly[mIdx]) }}</td>
                                                    <td class="text-end">{{ formatCLP(l1.real_monthly[mIdx]) }}</td>
                                                    <td class="text-end" :class="l1.difference_monthly[mIdx] >= 0 ? 'text-success' : 'text-danger'">
                                                        {{ formatCLP(l1.difference_monthly[mIdx]) }}
                                                    </td>
                                                </template>
                                                <template v-if="orderedSelectedMonths.length > 1">
                                                    <td class="text-end fw-bold total-col total-zone-left">{{ formatCLP(sumSelectedMonths(l1.budget_monthly)) }}</td>
                                                    <td class="text-end fw-bold total-col">{{ formatCLP(sumSelectedMonths(l1.real_monthly)) }}</td>
                                                    <td class="text-end fw-bold total-col total-zone-right" :class="sumSelectedMonths(l1.difference_monthly) >= 0 ? 'text-success' : 'text-danger'">
                                                        {{ formatCLP(sumSelectedMonths(l1.difference_monthly)) }}
                                                    </td>
                                                </template>
                                            </tr>

                                            <template v-if="expandedMonthlyL1.has(l1.name)">
                                                <template v-for="l2 in l1.level2Groups" :key="'ml2-' + l2.key">
                                                    <!-- Fila Nivel 2 -->
                                                    <tr class="cursor-pointer" @click="toggleMonthlyL2(l2.key)">
                                                        <td class="sticky-col fw-semibold ps-3 text-truncate" style="background-color:#dde5f0;" :title="l2.name">
                                                            <i class="fas fa-xs me-1" :class="expandedMonthlyL2.has(l2.key) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                            └ {{ l2.name }}
                                                        </td>
                                                        <template v-for="(mIdx, mi) in orderedSelectedMonths" :key="'l2v-' + l2.key + '-' + mIdx">
                                                            <td class="text-end month-divider" style="background-color:#dde5f0;">{{ formatCLP(l2.budget_monthly[mIdx]) }}</td>
                                                            <td class="text-end" style="background-color:#dde5f0;">{{ formatCLP(l2.real_monthly[mIdx]) }}</td>
                                                            <td class="text-end" style="background-color:#dde5f0;" :class="l2.difference_monthly[mIdx] >= 0 ? 'text-success' : 'text-danger'">
                                                                {{ formatCLP(l2.difference_monthly[mIdx]) }}
                                                            </td>
                                                        </template>
                                                        <template v-if="orderedSelectedMonths.length > 1">
                                                            <td class="text-end fw-semibold total-col total-zone-left">{{ formatCLP(sumSelectedMonths(l2.budget_monthly)) }}</td>
                                                            <td class="text-end fw-semibold total-col">{{ formatCLP(sumSelectedMonths(l2.real_monthly)) }}</td>
                                                            <td class="text-end fw-semibold total-col total-zone-right" :class="sumSelectedMonths(l2.difference_monthly) >= 0 ? 'text-success' : 'text-danger'">
                                                                {{ formatCLP(sumSelectedMonths(l2.difference_monthly)) }}
                                                            </td>
                                                        </template>
                                                    </tr>

                                                    <!-- Filas Nivel 3 -->
                                                    <template v-if="expandedMonthlyL2.has(l2.key)">
                                                        <tr v-for="item in l2.items" :key="'ml3-' + l2.key + '-' + item.level3" class="table-light">
                                                            <td class="sticky-col small ps-4 text-truncate" style="background-color:#f8f9fa;" :title="item.level3">└ {{ item.level3 }}</td>
                                                            <template v-for="(mIdx, mi) in orderedSelectedMonths" :key="'l3v-' + l2.key + '-' + item.level3 + '-' + mIdx">
                                                                <td class="text-end month-divider">{{ formatCLP(item.budget_monthly[mIdx]) }}</td>
                                                                <td class="text-end">{{ formatCLP(item.real_monthly[mIdx]) }}</td>
                                                                <td class="text-end" :class="item.difference_monthly[mIdx] >= 0 ? 'text-success' : 'text-danger'">
                                                                    {{ formatCLP(item.difference_monthly[mIdx]) }}
                                                                </td>
                                                            </template>
                                                            <template v-if="orderedSelectedMonths.length > 1">
                                                                <td class="text-end total-col total-zone-left">{{ formatCLP(sumSelectedMonths(item.budget_monthly)) }}</td>
                                                                <td class="text-end total-col">{{ formatCLP(sumSelectedMonths(item.real_monthly)) }}</td>
                                                                <td class="text-end total-col total-zone-right" :class="sumSelectedMonths(item.difference_monthly) >= 0 ? 'text-success' : 'text-danger'">
                                                                    {{ formatCLP(sumSelectedMonths(item.difference_monthly)) }}
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </template>
                                            </template>
                                        </template>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td class="sticky-col fw-bold" style="background-color:#f8f9fa;">TOTAL</td>
                                            <template v-for="(mIdx, mi) in orderedSelectedMonths" :key="'tv-' + mIdx">
                                                <td class="text-end month-divider">{{ formatCLP(monthlyDetailGrandTotal.budget_monthly[mIdx]) }}</td>
                                                <td class="text-end">{{ formatCLP(monthlyDetailGrandTotal.real_monthly[mIdx]) }}</td>
                                                <td class="text-end" :class="monthlyDetailGrandTotal.difference_monthly[mIdx] >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ formatCLP(monthlyDetailGrandTotal.difference_monthly[mIdx]) }}
                                                </td>
                                            </template>
                                            <template v-if="orderedSelectedMonths.length > 1">
                                                <td class="text-end fw-bold total-zone-left total-zone-bottom total-zone-bl">{{ formatCLP(sumSelectedMonths(monthlyDetailGrandTotal.budget_monthly)) }}</td>
                                                <td class="text-end fw-bold total-zone-bottom">{{ formatCLP(sumSelectedMonths(monthlyDetailGrandTotal.real_monthly)) }}</td>
                                                <td class="text-end fw-bold total-zone-right total-zone-bottom total-zone-br" :class="sumSelectedMonths(monthlyDetailGrandTotal.difference_monthly) >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ formatCLP(sumSelectedMonths(monthlyDetailGrandTotal.difference_monthly)) }}
                                                </td>
                                            </template>
                                        </tr>
                                    </tfoot>
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
                                        { label: 'Remun. Mensual', key: 'payroll_monthly' },
                                        { label: 'Presupuesto Acumulado', key: 'budget' },
                                        { label: 'Facturado Acumulado', key: 'invoiced' },
                                        { label: 'Consumido Acumulado', key: 'consumed' },
                                        { label: 'Remun. Acumulado', key: 'payroll_cumulative' },
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
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">Mes</th>
                                            <th class="text-end" style="width: 8%;">Fact.<br>Mensual</th>
                                            <th class="text-end" style="width: 8%;">Remun.<br>Mensual</th>
                                            <th class="text-end" style="width: 10%;">Presup.<br>Acumulado</th>
                                            <th class="text-end" style="width: 10%;">Fact.<br>Acumulado</th>
                                            <th class="text-end" style="width: 10%;">Consumido<br>Acumulado</th>
                                            <th class="text-end" style="width: 10%;">Remun.<br>Acumulado</th>
                                            <th class="text-end" style="width: 10%;">Dif. (P-F)</th>
                                            <th class="text-end" style="width: 10%;">Dif. (P-C)</th>
                                            <th class="text-end" style="width: 9%;">Var. % (F)</th>
                                            <th class="text-end" style="width: 9%;">Var. % (C)</th>
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
                                            <td class="text-end text-success">
                                                <span v-if="index <= cumulativeComparison.last_month_with_data && (payrollMonthly[index] || 0) > 0">
                                                    {{ formatCLP(payrollMonthly[index]) }}
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
                                            <td class="text-end text-success fw-semibold">
                                                <span v-if="payrollCumulative[index] !== null && payrollCumulative[index] > 0">
                                                    {{ formatCLP(payrollCumulative[index]) }}
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-bold" 
                                                v-if="cumulativeComparison.real_cumulative[index] !== null"
                                                :class="((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index] - (payrollCumulative[index] || 0)) >= 0 
                                                    ? 'text-success' : 'text-danger'">
                                                {{ formatCLP(Math.abs((includeInvestments 
                                                    ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                    : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index] - (payrollCumulative[index] || 0))) }}
                                                <i :class="['fas', 'fa-xs', 'ms-1', 
                                                    ((includeInvestments 
                                                        ? cumulativeComparison.budget_with_investments_cumulative[index] 
                                                        : cumulativeComparison.budget_cumulative[index]) - cumulativeComparison.real_cumulative[index] - (payrollCumulative[index] || 0)) >= 0 
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
                                <div class="d-flex align-items-center gap-2">
                                    <ExportExcelButton
                                        :data="detailCategoryExcelData"
                                        :headers="[
                                            { label: 'Nivel 1',        key: 'Nivel 1' },
                                            { label: 'Nivel 2',        key: 'Nivel 2' },
                                            { label: 'Nivel 3',        key: 'Nivel 3' },
                                            { label: 'Presupuestado',  key: 'Presupuestado' },
                                            { label: 'Facturado',      key: 'Facturado' },
                                            { label: 'Consumido',      key: 'Consumido' },
                                            { label: 'Remuneraciones', key: 'Remuneraciones' },
                                            { label: 'Diferencia',     key: 'Diferencia' },
                                            { label: 'Variación %',    key: 'Variación %' },
                                        ]"
                                        filename="detalle_por_categoria.xlsx"
                                        class="btn btn-sm btn-light-primary"
                                    >
                                        <i class="fas fa-file-excel me-1"></i>
                                        Exportar
                                    </ExportExcelButton>
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
                                            <th>Nivel 3</th>
                                            <th class="text-end">Presupuestado</th>
                                            <th class="text-end">Facturado</th>
                                            <th class="text-end">Consumido</th>
                                            <th class="text-end">Remun.</th>
                                            <th class="text-end">Diferencia</th>
                                            <th class="text-end">Variación %</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Grupos personalizados -->
                                        <template v-for="group in customGroups" :key="'group-' + group.id">
                                            <!-- ── Fila Nivel 1 (cabecera del grupo) ── -->
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
                                                <td colspan="3" class="fw-bold">
                                                    <i class="fas me-1" :class="expandedGroups.includes(group.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                    {{ group.name }}
                                                    <span class="text-muted fw-normal small ms-1">
                                                        ({{ group.level2Groups ? group.level2Groups.length + ' subcategorías' : group.items.length + ' categorías' }})
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.budget) }}</td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.invoiced) }}</td>
                                                <td class="text-end fw-bold">{{ formatCLP(group.totals.consumed) }}</td>
                                                <td class="text-end fw-bold text-muted">{{ formatCLP(group.totals.payroll || 0) }}</td>
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

                                            <template v-if="expandedGroups.includes(group.id)">

                                                <!-- ── Modo jerárquico: subgrupos Nivel 2 ── -->
                                                <template v-if="group.level2Groups">
                                                    <template v-for="l2group in group.level2Groups" :key="'l2-' + l2group.id">
                                                        <!-- Fila Nivel 2 -->
                                                        <tr class="cursor-pointer" style="background-color:#dde5f0 !important;" @click="toggleGroup(l2group.id)">
                                                            <td></td>
                                                            <td></td>
                                                            <td colspan="2" class="fw-semibold ps-3">
                                                                <i class="fas fa-xs me-1" :class="expandedGroups.includes(l2group.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                                └ {{ l2group.name }}
                                                                <span class="text-muted fw-normal small ms-1">({{ l2group.items.length }})</span>
                                                            </td>
                                                            <td class="text-end fw-semibold">{{ formatCLP(l2group.totals.budget) }}</td>
                                                            <td class="text-end fw-semibold">{{ formatCLP(l2group.totals.invoiced) }}</td>
                                                            <td class="text-end fw-semibold">{{ formatCLP(l2group.totals.consumed) }}</td>
                                                            <td class="text-end fw-semibold text-muted">{{ formatCLP(l2group.totals.payroll || 0) }}</td>
                                                            <td class="text-end fw-semibold" :class="l2group.totals.difference > 0 ? 'text-success' : 'text-danger'">
                                                                {{ formatCLP(Math.abs(l2group.totals.difference)) }}
                                                            </td>
                                                            <td class="text-end fw-semibold">
                                                                <span :class="l2group.totals.variance > 0 ? 'text-danger' : 'text-success'">
                                                                    {{ l2group.totals.variance > 0 ? '+' : '' }}{{ formatPercent(l2group.totals.variance) }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge" :class="getVarianceClass(l2group.totals.variance, l2group.totals.variance > 0)">
                                                                    {{ getStatusIcon(l2group.totals.variance > 0 ? 'over' : 'ok') }}
                                                                    {{ l2group.totals.variance > 0 ? (Math.abs(l2group.totals.variance) > 10 ? 'Alerta' : Math.abs(l2group.totals.variance) > 5 ? 'Revisión' : 'OK') : 'OK' }}
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <!-- Filas Nivel 3 (cuando el subgrupo Nivel 2 está expandido) -->
                                                        <template v-if="expandedGroups.includes(l2group.id)">
                                                            <tr v-for="(item, idx) in l2group.items" :key="'l3-item-' + l2group.id + '-' + idx" class="table-light">
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                                <td class="small ps-4">└ {{ item.level3 }}</td>
                                                                <td class="text-end">{{ formatCLP(item.budget) }}</td>
                                                                <td class="text-end">{{ formatCLP(item.invoiced) }}</td>
                                                                <td class="text-end">{{ formatCLP(item.consumed) }}</td>
                                                                <td class="text-end">
                                                                    <span v-if="(item.payroll || 0) > 0" class="text-success">{{ formatCLP(item.payroll) }}</span>
                                                                    <span v-else class="text-muted">-</span>
                                                                </td>
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
                                                </template>

                                                <!-- ── Modo plano (grupos manuales) ── -->
                                                <template v-else>
                                                    <tr v-for="(item, idx) in group.items" :key="'group-item-' + group.id + '-' + idx" class="table-light">
                                                        <td></td>
                                                        <td class="fw-semibold text-muted small ps-4">└ {{ item.level1 }}</td>
                                                        <td class="fw-normal">{{ item.level2 }}</td>
                                                        <td class="fw-normal text-muted small">{{ item.level3 }}</td>
                                                        <td class="text-end">{{ formatCLP(item.budget) }}</td>
                                                        <td class="text-end">{{ formatCLP(item.invoiced) }}</td>
                                                        <td class="text-end">{{ formatCLP(item.consumed) }}</td>
                                                        <td class="text-end">
                                                            <span v-if="(item.payroll || 0) > 0" class="text-success">{{ formatCLP(item.payroll) }}</span>
                                                            <span v-else class="text-muted">-</span>
                                                        </td>
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
                                            <td class="text-muted small">{{ item.level3 }}</td>
                                            <td class="text-end">{{ formatCLP(item.budget) }}</td>
                                            <td class="text-end">{{ formatCLP(item.invoiced) }}</td>
                                            <td class="text-end">{{ formatCLP(item.consumed) }}</td>
                                            <td class="text-end">
                                                <span v-if="(item.payroll || 0) > 0" class="text-success">{{ formatCLP(item.payroll) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
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
                                            <td colspan="3">TOTAL</td>
                                            <td class="text-end">{{ formatCLP(displayedBudget) }}</td>
                                            <td class="text-end">{{ formatCLP(summary.invoiced_total) }}</td>
                                            <td class="text-end">{{ formatCLP(displayedConsumed) }}</td>
                                            <td class="text-end text-success">{{ formatCLP(payrollSummary?.total || 0) }}</td>
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

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 2;
}
thead .sticky-col {
    z-index: 3;
}
.sticky-col.text-truncate {
    max-width: 160px;
}

.total-col {
    background-color: #eef0f2 !important;
}

/* Línea vertical entre meses, mismo color que el borde del header (a partir de la fila de sub-encabezados) */
.month-divider {
    border-left: 2px solid #dee2e6 !important;
}

/* border-collapse: separate es necesario para que se vean las esquinas redondeadas de la zona "Total selección" */
.monthly-detail-table {
    border-collapse: separate;
    border-spacing: 0;
}

/* Borde de la zona "Total selección" (color y grosor según --zone-color/--zone-width, definidos en el <table>) */
.total-zone-left {
    border-left: var(--zone-width, 2px) solid var(--zone-color) !important;
}
.total-zone-right {
    border-right: var(--zone-width, 2px) solid var(--zone-color) !important;
}
.total-zone-top {
    border-top: var(--zone-width, 2px) solid var(--zone-color) !important;
}
.total-zone-bottom {
    border-bottom: var(--zone-width, 2px) solid var(--zone-color) !important;
}
.total-zone-tl {
    border-top-left-radius: 8px;
}
.total-zone-tr {
    border-top-right-radius: 8px;
}
.total-zone-bl {
    border-bottom-left-radius: 8px;
}
.total-zone-br {
    border-bottom-right-radius: 8px;
}

/* Multiselect razón social */
.multiselect-company-reason {
    font-size: 0.78rem;
}
.multiselect-company-reason .multiselect-multiple-label,
.multiselect-company-reason .multiselect-placeholder {
    font-size: 0.78rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 340px;
}
.multiselect-company-reason .multiselect-wrapper {
    min-height: 22px;
    height: 22px;
    padding-top: 0;
    padding-bottom: 0;
}
.multiselect-company-reason .multiselect-option {
    font-size: 0.78rem;
    padding: 4px 10px;
}
</style>
