<script setup>
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import FalconPieChart from '@/Components/FalconPieChart.vue';
import { computed, ref } from 'vue';
import axios from 'axios';

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
    byLevel1: {
        type: Object,
        default: () => ({
            labels: [],
            data: []
        })
    },
    byLevel2: {
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
    },
    payrollSummary: {
        type: Object,
        default: () => ({ total: 0, workdays: 0 })
    },
    payrollByDevState: {
        type: Array,
        default: () => []
    },
    dollarPrice: { type: Number, default: 970 },
    isAdmin:     { type: Boolean, default: false },
    companyReasons:        { type: Array,  default: () => [] },
    activeCompanyReasonId: { type: Number, default: null },
});

const title = 'Dashboard de Outflows';

const links = [
    { title: 'Gestión' },
    { title: 'Dashboard Outflows', active: true }
];

// Toggle idioma ES/EN
const isEnglish = ref(false);

// Filtro Razón Social
const selectedCompanyReason = ref(props.activeCompanyReasonId ?? '');
const onCompanyReasonChange = (e) => {
    const value = e.target.value;
    router.get(
        route('outflows.dashboard'),
        value ? { company_reason_id: value } : {},
        { preserveScroll: false }
    );
};

const t = computed(() => isEnglish.value ? {
    dashboardTitle: 'Consumption & Billing Analysis Dashboard',
    viewInUSD: 'View in USD',
    divisor: 'Divisor',
    // Sección consumos
    sectionConsumed: 'Consumption Analysis',
    totalConsumed: 'Total Consumed',
    totalInvestments: 'Total Investments',
    totalExpenses: 'Total Expenses',
    totalPayroll: 'Payroll',
    payrollWorkdays: 'workdays',
    records: 'records',
    // Sección compras
    sectionPurchases: 'Purchase Detail',
    totalInvoices: 'Total Invoices',
    invoices: 'invoices',
    debitNotes: 'Debit Notes',
    creditNotes: 'Credit Notes',
    notes: 'notes',
    totalPurchases: 'Total Purchases',
    invoiceFormula: 'Invoices + Debit - Credit',
    // Sección estados desarrollo
    sectionDevStates: 'Consumption by Development State',
    withInvestments: 'Summary with Investments',
    withoutInvestments: 'Without Investments',
    noDevStateData: 'No development state data available',
    noData: 'No data available',
    noDataYet: 'No outflows recorded to display in chart',
    noExpensesByProject: 'No expenses by project recorded',
    noOutflows: 'No outflows recorded',
    // Card costo kilo
    costoKiloTitle: 'Accumulated Cost per Kilo',
    prodKilosLabel: 'Production / Estimated Kilos',
    incluirAdminTooltip: 'Include administration costs in cost/kg',
    totalCosts: 'Total Costs',
    totalCostsProdAdmin: '(Prod. + Admin)',
    totalCostsProd: 'Production',
    adminLabel: 'Admin',
    totalKilos: 'Total Estimated Kilos',
    costoKiloLabel: 'Accumulated Cost / Kilo',
    noKilos: 'No kilo estimates recorded.',
    noProduction: 'No production expenses recorded.',
    costoKiloCosechaTitle: 'Harvest Cost per Kilo',
    costoKiloCosechaLabel: 'Harvest Cost / Kilo',
    totalCosecha: 'Total Harvest',
    noCosecha: 'No harvest expenses recorded for this season.',
    costoKiloCosechaSubtitle: 'Harvest Level 1 / Estimated Kilos',
    // Gráficos
    chartLevel2Bar: 'Classification by Level 2',
    chartLevel2Table: 'Detailed Summary by Level 2',
    colLevel1: 'Level 1',
    colLevel2: 'Level 2',
    colTotal: 'Total Amount',
    colPct: '% of Total',
    colCategory: 'Category',
    colArea: 'Area',
    total: 'TOTAL',
    subtotal: 'Subtotal',
    viewByArea: 'By Area',
    viewByCategory: 'By Category',
    chartProjectBar: 'Total Amount Spent by Project',
    chartProjectPie: 'Percentage Distribution by Project',
    chartLevel1Bar: 'Classification by Product and Level 1',
    chartLevel1Pie: 'Percentage Distribution by Level 1',
} : {
    dashboardTitle: 'Dashboard de Análisis de Consumos y Facturación.',
    viewInUSD: 'Ver en USD',
    divisor: 'Divisor',
    // Sección consumos
    sectionConsumed: 'Análisis de Consumos',
    totalConsumed: 'Total Consumido',
    totalInvestments: 'Total Inversiones',
    totalExpenses: 'Total Gastos',
    totalPayroll: 'Remuneraciones',
    payrollWorkdays: 'jornadas',
    records: 'registros',
    // Sección compras
    sectionPurchases: 'Detalle de Compras',
    totalInvoices: 'Total Facturas',
    totalInvoicesTooltip: 'El precio unitario de las facturas ya incluye el descuento de las NC financieras aplicadas.',
    invoices: 'facturas',
    debitNotes: 'Notas de Débito',
    creditNotes: 'Notas de Crédito',
    creditNotesTooltip: 'Solo NC que afectan inventario. Las NC financieras (descuentos de precio) ya están descontadas directamente del precio unitario de la factura.',
    notes: 'notas',
    totalPurchases: 'Total Compras',
    totalPurchasesTooltip: 'Las facturas ya reflejan el descuento de las NC financieras en su precio unitario.',
    invoiceFormula: 'Facturas + Débito - Crédito',
    // Sección estados desarrollo
    sectionDevStates: 'Consumos por Estado de Desarrollo',
    withInvestments: 'Resumen con Inversiones',
    withoutInvestments: 'Sin Inversiones',
    noDevStateData: 'No hay datos de estados de desarrollo disponibles',
    noData: 'No hay datos disponibles',
    noDataYet: 'Aún no hay salidas registradas para mostrar en el gráfico',
    noExpensesByProject: 'Aún no hay gastos por proyecto registrados',
    noOutflows: 'Aún no hay salidas registradas',
    // Card costo kilo
    costoKiloTitle: 'Costo Kilo Acumulado',
    prodKilosLabel: 'Producción / Kilos Estimados',
    incluirAdminTooltip: 'Incluir gastos de administración en el costo/kg',
    totalCosts: 'Total Costos',
    totalCostsProdAdmin: '(Prod. + Admin)',
    totalCostsProd: 'Producción',
    adminLabel: 'Admin',
    totalKilos: 'Total Kilos Estimados',
    costoKiloLabel: 'Costo / Kilo Acumulado',
    noKilos: 'No hay estimaciones de kilos registradas.',
    noProduction: 'No hay gastos de producción registrados.',
    costoKiloCosechaTitle: 'Costo / Kilo Cosecha',
    costoKiloCosechaLabel: 'Costo / Kilo Cosecha',
    totalCosecha: 'Total Cosecha',
    noCosecha: 'No hay gastos de cosecha registrados para esta temporada.',
    costoKiloCosechaSubtitle: 'Level 1 Cosecha / Kilos Estimados',
    // Gráficos
    chartLevel2Bar: 'Clasificación por Nivel 2',
    chartLevel2Table: 'Resumen Detallado por Nivel 2',
    colLevel1: 'Nivel 1',
    colLevel2: 'Nivel 2',
    colTotal: 'Monto Total',
    colPct: '% del Total',
    colCategory: 'Categoría',
    colArea: 'Área',
    total: 'TOTAL',
    subtotal: 'Subtotal',
    viewByArea: 'Por Área',
    viewByCategory: 'Por Categoría',
    chartProjectBar: 'Monto Total Gastado por Proyecto',
    chartProjectPie: 'Distribución Porcentual por Proyecto',
    chartLevel1Bar: 'Clasificación por producto y Nivel 1',
    chartLevel1Pie: 'Distribución Porcentual por Nivel 1',
});

// Variables para conversión USD
const divisor = ref(props.dollarPrice);
const divisorMin = 800;
const divisorMax = 1300;
const dividir = ref(false);
const incluirAdmin = ref(false);
const selectedExtraStates = ref({});
const showDevStateBreakdown = ref(false); // switch para mostrar/ocultar Salidas y Remun. en cards de estados
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

// Select de estimación para Costo Kilo Acumulado
const selectedEstimateStatusId = ref(props.costoKiloAcumulado?.defaultEstimateStatusId ?? null);
const activeTotalKilos = computed(() => {
    const data = props.costoKiloAcumulado?.kilosByEstimate;
    if (data && selectedEstimateStatusId.value && data[selectedEstimateStatusId.value]) {
        return Object.values(data[selectedEstimateStatusId.value]).reduce((sum, k) => sum + Number(k), 0);
    }
    return props.costoKiloAcumulado?.totalKilos ?? 0;
});

// Admin prorrateado por hectáreas: suma admin_share de producción + extras activos
const normalize = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
// Estados adicionales: unión de outflows + payroll, excluyendo produccion y administracion
// Permite que estados con solo payroll (ej. Año 4 sin outflows) también aparezcan como checkboxes
const extraStates = computed(() => {
    const map = {};
    // Primero los estados de outflows (tienen total y admin_share)
    (props.byDevelopmentStateWithoutInvestments || []).forEach(s => {
        const n = normalize(s.name);
        if (!n.includes('produccion') && !n.includes('administracion')) {
            map[s.id] = { id: s.id, name: s.name, total: s.total, admin_share: s.admin_share ?? 0 };
        }
    });
    // Agregar estados de payroll que no estén ya en outflows
    (props.payrollByDevState || []).forEach(s => {
        const n = normalize(s.name);
        if (!n.includes('produccion') && !n.includes('administracion') && !map[s.id]) {
            map[s.id] = { id: s.id, name: s.name, total: 0, admin_share: 0 };
        }
    });
    return Object.values(map).sort((a, b) => a.name.localeCompare(b.name, 'es'));
});
const totalAdministracion = computed(() => {
    if (!props.byDevelopmentStateWithoutInvestments?.length) return 0;
    // Admin prorrateado a producción (siempre incluido como base)
    const prodState = props.byDevelopmentStateWithoutInvestments.find(s =>
        normalize(s.name).includes('produccion')
    );
    let total = prodState?.admin_share ?? 0;
    // Sumar admin_share de extras activos
    for (const s of extraStates.value) {
        if (selectedExtraStates.value[s.id]) {
            total += (s.admin_share ?? 0);
        }
    }
    return total;
});
const totalExtras = computed(() =>
    extraStates.value.reduce((sum, s) => sum + (selectedExtraStates.value[s.id] ? s.total : 0), 0)
);
// Etiqueta dinámica para el título del card según qué extras están activos
const costoLabelSuffix = computed(() => {
    const parts = [];
    if (incluirAdmin.value) parts.push('Admin');
    extraStates.value.filter(s => selectedExtraStates.value[s.id]).forEach(s => parts.push(s.name));
    return parts.length ? `(Prod. + ${parts.join(' + ')})` : null;
});
// Payroll por estado de desarrollo para Costo Kilo Acumulado
const payrollProduccion = computed(() => {
    if (!props.payrollByDevState?.length) return 0;
    const s = props.payrollByDevState.find(s => normalize(s.name).includes('produccion'));
    return s?.total ?? 0;
});
const payrollAdministracion = computed(() => {
    if (!props.payrollByDevState?.length) return 0;
    const s = props.payrollByDevState.find(s => normalize(s.name).includes('administracion'));
    return s?.total ?? 0;
});
const payrollExtrasTotal = computed(() =>
    extraStates.value.reduce((sum, s) =>
        sum + (selectedExtraStates.value[s.id] ? (payrollDevStateMap.value[s.id] ?? 0) : 0), 0)
);
// Suma de payroll incluida en el cálculo (para mostrar en desglose)
const totalPayrollInCosto = computed(() =>
    payrollProduccion.value
    + (incluirAdmin.value ? payrollAdministracion.value : 0)
    + payrollExtrasTotal.value
);
const totalProduccionEfectivo = computed(() =>
    props.costoKiloAcumulado.totalProduccion
    + payrollProduccion.value
    + (incluirAdmin.value ? totalAdministracion.value + payrollAdministracion.value : 0)
    + totalExtras.value
    + payrollExtrasTotal.value
);
const costoKiloEfectivo = computed(() =>
    activeTotalKilos.value > 0 ? totalProduccionEfectivo.value / activeTotalKilos.value : 0
);

// Total costos del Level1 "Cosecha" (case-insensitive, sin tildes)
const totalCosecha = computed(() => {
    if (!props.byLevel1?.labels?.length) return 0;
    const idx = props.byLevel1.labels.findIndex(l =>
        l.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim() === 'cosecha'
    );
    return idx !== -1 ? (props.byLevel1.data[idx] ?? 0) : 0;
});

const costoKiloCosechaEfectivo = computed(() =>
    activeTotalKilos.value > 0 ? totalCosecha.value / activeTotalKilos.value : 0
);

// Mapa de remuneraciones por dev_state_id para lookup rápido en template
const payrollDevStateMap = computed(() => {
    const map = {};
    (props.payrollByDevState || []).forEach(s => { map[s.id] = s.total; });
    return map;
});

// Estados de desarrollo (sin inversiones) merged con remuneraciones
// Asegura que aparezcan estados con payroll aunque no tengan outflows
const mergedDevStates = computed(() => {
    const map = {};
    (props.byDevelopmentStateWithoutInvestments || []).forEach(s => {
        map[s.id] = { id: s.id, name: s.name, outflows: s.total, admin_share: s.admin_share ?? 0, payroll: 0 };
    });
    (props.payrollByDevState || []).forEach(s => {
        if (map[s.id]) {
            map[s.id].payroll = s.total;
        } else {
            map[s.id] = { id: s.id, name: s.name, outflows: 0, admin_share: 0, payroll: s.total };
        }
    });
    return Object.values(map).sort((a, b) => (b.outflows + b.payroll) - (a.outflows + a.payroll));
});

// Estados de desarrollo (con inversiones) merged con remuneraciones
const mergedDevStatesWithInvestments = computed(() => {
    const map = {};
    (props.byDevelopmentState || []).forEach(s => {
        map[s.id] = { id: s.id, name: s.name, outflows: s.total, payroll: 0 };
    });
    (props.payrollByDevState || []).forEach(s => {
        if (map[s.id]) {
            map[s.id].payroll = s.total;
        } else {
            map[s.id] = { id: s.id, name: s.name, outflows: 0, payroll: s.total };
        }
    });
    return Object.values(map).sort((a, b) => (b.outflows + b.payroll) - (a.outflows + a.payroll));
});

// Formatear números con separador de miles (sin decimales)
const formatNumber = (number) => {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.round(number));
};

// Formatear costo kilo con 2 decimales
const formatCostoKilo = (number) => {
    if (number === null || number === undefined) return '0,00';
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
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

// Preparar datos para el gráfico de torta de Level2
const pieChartLevel2Data = computed(() => {
    if (!props.byLevel2 || !props.byLevel2.labels || !props.byLevel2.data) {
        return { labels: [], datasets: [] };
    }
    // Aplicar conversión si está activada
    const convertedData = dividir.value && divisor.value 
        ? props.byLevel2.data.map(value => value / divisor.value)
        : props.byLevel2.data;
    
    return {
        labels: props.byLevel2.labels,
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

// Datos convertidos para gráfico de barras de Level2
const convertedLevel2Data = computed(() => {
    if (!props.byLevel2 || !props.byLevel2.data) return [];
    return dividir.value && divisor.value 
        ? props.byLevel2.data.map(value => value / divisor.value)
        : props.byLevel2.data;
});

// Toggle para vista de tabla Level2
const level2ViewMode = ref('area'); // 'area' o 'category'

// Vista agrupada por Área (Level1)
const groupedByArea = computed(() => {
    if (!props.byLevel2 || !props.byLevel2.labels) return [];
    const totalAll = props.byLevel2.data.reduce((a, b) => a + b, 0);
    const groups = {};
    props.byLevel2.labels.forEach((label, i) => {
        const level1 = props.byLevel2.level1[i];
        if (!groups[level1]) groups[level1] = { name: level1, items: [], subtotal: 0 };
        groups[level1].items.push({
            label,
            amount: props.byLevel2.data[i],
            pct: totalAll > 0 ? (props.byLevel2.data[i] / totalAll * 100) : 0
        });
        groups[level1].subtotal += props.byLevel2.data[i];
    });
    // Ordenar grupos por subtotal desc, items dentro por monto desc
    return Object.values(groups)
        .sort((a, b) => b.subtotal - a.subtotal)
        .map(g => ({
            ...g,
            pct: totalAll > 0 ? (g.subtotal / totalAll * 100) : 0,
            items: g.items.sort((a, b) => b.amount - a.amount)
        }));
});

// Vista agrupada por Categoría (nombre base)
const groupedByCategory = computed(() => {
    if (!props.byLevel2 || !props.byLevel2.labels) return [];
    const totalAll = props.byLevel2.data.reduce((a, b) => a + b, 0);
    const groups = {};
    props.byLevel2.labels.forEach((label, i) => {
        // Extraer nombre base: quitar prefijos como "cos. ", "adm. ", "admin. ", etc.
        const baseName = label.replace(/^(cos\.?|adm\.?|admin\.?)\s*/i, '').trim().toLowerCase();
        const displayName = baseName.charAt(0).toUpperCase() + baseName.slice(1);
        if (!groups[baseName]) groups[baseName] = { name: displayName, items: [], subtotal: 0 };
        groups[baseName].items.push({
            level1: props.byLevel2.level1[i],
            originalLabel: label,
            amount: props.byLevel2.data[i],
            pct: totalAll > 0 ? (props.byLevel2.data[i] / totalAll * 100) : 0
        });
        groups[baseName].subtotal += props.byLevel2.data[i];
    });
    return Object.values(groups)
        .sort((a, b) => b.subtotal - a.subtotal)
        .map(g => ({
            ...g,
            pct: totalAll > 0 ? (g.subtotal / totalAll * 100) : 0,
            items: g.items.sort((a, b) => b.amount - a.amount)
        }));
});

// Control de grupos expandidos/colapsados
const expandedGroups = ref(new Set());
const toggleGroup = (key) => {
    if (expandedGroups.value.has(key)) {
        expandedGroups.value.delete(key);
    } else {
        expandedGroups.value.add(key);
    }
    // Forzar reactividad
    expandedGroups.value = new Set(expandedGroups.value);
};
const expandAll = () => {
    const groups = level2ViewMode.value === 'area' ? groupedByArea.value : groupedByCategory.value;
    expandedGroups.value = new Set(groups.map((_, i) => level2ViewMode.value + '-' + i));
};
const collapseAll = () => {
    expandedGroups.value = new Set();
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
       
        
        <div class="card my-3">
            <div class="card-header py-2">
                <div class="row flex-between-end align-items-center">
                    <div class="col-auto align-self-center">
                        <h6 class="mb-0 text-nowrap">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            {{ t.dashboardTitle }}
                        </h6>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="form-check form-switch d-flex align-items-center mb-0 me-1">
                                <input class="form-check-input" type="checkbox" id="lang-switch" v-model="isEnglish">
                                <label class="form-check-label ms-2 mt-0 mb-0 small fw-semibold" for="lang-switch" style="cursor:pointer;">EN</label>
                            </div>
                            <div class="form-check form-switch d-flex align-items-center mb-0">
                                <input class="form-check-input" type="checkbox" id="dividir-switch" v-model="dividir">
                                <label class="form-check-label ms-2 mt-0 mb-0 small" for="dividir-switch">{{ t.viewInUSD }}</label>
                            </div>
                            <template v-if="dividir">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="divisor-slider" class="form-label mb-0 me-2 small">{{ t.divisor }}:</label>
                                    <input id="divisor-slider" type="range" class="form-range"
                                           v-model.number="divisor" :min="divisorMin" :max="divisorMax" :step="1"
                                           style="width:220px; flex-shrink:0;" />
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
                <!-- Filtro Razón Social -->
                <div class="row mt-2 align-items-center" v-if="companyReasons?.length > 0">
                    <div class="col-auto">
                        <label class="form-label mb-0 small fw-semibold text-muted">
                            <i class="fas fa-building me-1"></i>Razón Social
                        </label>
                    </div>
                    <div class="col" style="max-width: 360px;">
                        <select
                            v-model="selectedCompanyReason"
                            class="form-select form-select-sm"
                            @change="onCompanyReasonChange"
                        >
                            <option value="">Todas las razones sociales</option>
                            <option v-for="rs in companyReasons" :key="rs.value" :value="rs.value">
                                {{ rs.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-auto" v-if="selectedCompanyReason && selectedCompanyReason !== ''">
                        <span class="badge bg-primary">Filtrado</span>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary py-3">
                <!-- Título Sección Compras -->
                <h6 class="text-secondary mb-2 d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2 fs-8"></i>
                    <span>{{ t.sectionPurchases }}</span>
                </h6>

                <!-- KPI Cards Fila 2: Compras -->
                <div class="row g-2 mb-2">
                    <!-- Total Facturas Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-3" style="border-color: #6FB550 !important;" v-tooltip="t.totalInvoicesTooltip">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalInvoices }} <i class="fas fa-info-circle fa-xs text-muted opacity-50"></i></small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (invoices?.total || 0) / divisor : (invoices?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(invoices?.count || 0) }} {{ t.invoices }}
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
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.debitNotes }}</small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (debitNotes?.total || 0) / divisor : (debitNotes?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(debitNotes?.count || 0) }} {{ t.notes }}
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
                        <div class="card h-100 border-start border-3" style="border-color: #6FB550 !important;" v-tooltip="t.creditNotesTooltip">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.creditNotes }} <i class="fas fa-info-circle fa-xs text-muted opacity-50"></i></small>
                                        <h4 class="mb-0 fw-bold" style="color: #6FB550;">
                                            {{ formatNumber(dividir && divisor ? (creditNotes?.total || 0) / divisor : (creditNotes?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(creditNotes?.count || 0) }} {{ t.notes }}
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
                        <div class="card h-100 border-start border-3" style="border-color: #60A145 !important;" v-tooltip="t.totalPurchasesTooltip">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalPurchases }} <i class="fas fa-info-circle fa-xs text-muted opacity-50"></i></small>
                                        <h4 class="mb-0 fw-bold" style="color: #60A145;">
                                            {{ formatNumber(dividir && divisor ? totalCompras / divisor : totalCompras) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ t.invoiceFormula }}
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
                <div class="row mb-2">
                    <div class="col-12">
                        <hr class="my-1 opacity-25">
                    </div>
                </div>

                <!-- Título Sección Consumos -->
                <h6 class="text-secondary mb-2 d-flex align-items-center">
                    <i class="fas fa-chart-line me-2 fs-8"></i>
                    <span>{{ t.sectionConsumed }}</span>
                </h6>

                <!-- KPI Cards Fila 1: Consumos -->
                <div class="row g-2 mb-2">
                    <!-- Total Outflows Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalConsumed }}</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (summary?.total_amount || 0) / divisor : (summary?.total_amount || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(summary?.total_count || 0) }} {{ t.records }}
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
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalInvestments }}</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (investments?.total || 0) / divisor : (investments?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(investments?.count || 0) }} {{ t.records }}
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
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalExpenses }}</small>
                                        <h4 class="mb-0 text-primary fw-bold">
                                            {{ formatNumber(dividir && divisor ? (expenses?.total || 0) / divisor : (expenses?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(expenses?.count || 0) }} {{ t.records }}
                                        </small>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-receipt fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remuneraciones Card -->
                    <div class="col-md-3">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted text-uppercase d-block mb-1">{{ t.totalPayroll }}</small>
                                        <h4 class="mb-0 text-success fw-bold">
                                            {{ formatNumber(dividir && divisor ? (payrollSummary?.total || 0) / divisor : (payrollSummary?.total || 0)) }} {{ dividir ? 'USD' : 'CLP' }}
                                        </h4>
                                        <small class="text-muted fs-10">
                                            {{ formatNumber(payrollSummary?.workdays || 0) }} {{ t.payrollWorkdays }}
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-users fa-2x opacity-50"></i>
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
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="text-secondary mb-0 d-flex align-items-center">
                        <i class="fas fa-layer-group me-2 fs-8"></i>
                        <span>{{ t.sectionDevStates }}</span>
                    </h6>
                    <label class="d-flex align-items-center gap-2 mb-0 small text-muted" style="cursor:pointer;font-size:0.75rem;">
                        <span>Desglose</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" v-model="showDevStateBreakdown" style="cursor:pointer;">
                        </div>
                    </label>
                </div>

                <!-- Card Totales por Estado de Desarrollo -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="card border-start border-info border-3">
                            <div class="card-header bg-transparent py-2">
                                <h6 class="mb-0 text-info">
                                    <i class="fas fa-seedling me-2"></i>
                                    {{ t.withInvestments }}
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="mergedDevStatesWithInvestments.length > 0" class="list-group list-group-flush">
                                    <div
                                        v-for="state in mergedDevStatesWithInvestments"
                                        :key="state.id"
                                        class="list-group-item py-2 px-3"
                                    >
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-medium">
                                                <i class="fas fa-circle text-info me-2" style="font-size: 8px;"></i>
                                                {{ state.name }}
                                            </span>
                                            <div class="d-flex gap-3 align-items-center text-end">
                                                <!-- Salidas -->
                                                <div v-if="showDevStateBreakdown">
                                                    <small class="text-muted d-block" style="font-size: 0.65rem;">Salidas</small>
                                                    <span class="fs-8">{{ formatNumber(dividir && divisor ? state.outflows / divisor : state.outflows) }}</span>
                                                </div>
                                                <!-- Remuneraciones -->
                                                <div v-if="showDevStateBreakdown && state.payroll > 0">
                                                    <small class="text-success d-block" style="font-size: 0.65rem;">Remun.</small>
                                                    <span class="fs-8 text-success">{{ formatNumber(dividir && divisor ? state.payroll / divisor : state.payroll) }}</span>
                                                </div>
                                                <!-- Total -->
                                                <div :class="showDevStateBreakdown ? 'border-start ps-3' : ''">
                                                    <small class="text-dark d-block fw-semibold" style="font-size: 0.65rem;">Total</small>
                                                    <span class="fs-8 fw-bold">{{ formatNumber(dividir && divisor ? (state.outflows + state.payroll) / divisor : (state.outflows + state.payroll)) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">{{ t.noDevStateData }}</p>
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
                                    {{ t.withoutInvestments }}
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="mergedDevStates.length > 0" class="list-group list-group-flush">
                                    <div
                                        v-for="state in mergedDevStates"
                                        :key="state.id"
                                        class="list-group-item py-2 px-3"
                                    >
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-medium">
                                                <i class="fas fa-circle text-warning me-2" style="font-size: 8px;"></i>
                                                {{ state.name }}
                                            </span>
                                            <div class="d-flex gap-3 align-items-center text-end">
                                                <!-- Salidas -->
                                                <div v-if="showDevStateBreakdown">
                                                    <small class="text-muted d-block" style="font-size: 0.65rem;">Salidas</small>
                                                    <span class="fs-8">{{ formatNumber(dividir && divisor ? state.outflows / divisor : state.outflows) }}</span>
                                                </div>
                                                <!-- Remuneraciones -->
                                                <div v-if="showDevStateBreakdown && state.payroll > 0">
                                                    <small class="text-success d-block" style="font-size: 0.65rem;">Remun.</small>
                                                    <span class="fs-8 text-success">{{ formatNumber(dividir && divisor ? state.payroll / divisor : state.payroll) }}</span>
                                                </div>
                                                <!-- Total -->
                                                <div :class="showDevStateBreakdown ? 'border-start ps-3' : ''">
                                                    <small class="text-dark d-block fw-semibold" style="font-size: 0.65rem;">Total</small>
                                                    <span class="fs-8 fw-bold">{{ formatNumber(dividir && divisor ? (state.outflows + state.payroll) / divisor : (state.outflows + state.payroll)) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">{{ t.noData }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Costo Kilo Acumulado -->
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="card border-start border-success border-3 shadow-sm">
                            <div class="card-header bg-transparent pt-2 pb-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 text-success">
                                            <i class="fas fa-calculator me-2"></i>
                                            {{ t.costoKiloTitle }}
                                        </h6>
                                        <select v-if="costoKiloAcumulado.estimateOptions && costoKiloAcumulado.estimateOptions.length" v-model="selectedEstimateStatusId" class="form-select form-select-sm py-0" style="width:auto;max-width:200px;font-size:0.75rem;">
                                            <option v-for="opt in costoKiloAcumulado.estimateOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                                        </select>
                                        <label class="d-flex align-items-center gap-1 mb-0 small text-muted border rounded px-2 py-0" :title="t.incluirAdminTooltip" style="cursor:pointer;background:#f8f9fa;font-size:0.75rem;">
                                            <input class="form-check-input m-0" type="checkbox" id="toggleAdmin" v-model="incluirAdmin" style="cursor:pointer;">
                                            + Admin
                                        </label>
                                        <label v-for="state in extraStates" :key="state.id" class="d-flex align-items-center gap-1 mb-0 small text-muted border rounded px-2 py-0" style="cursor:pointer;background:#f8f9fa;font-size:0.75rem;">
                                            <input class="form-check-input m-0" type="checkbox" v-model="selectedExtraStates[state.id]" style="cursor:pointer;">
                                            + {{ state.name }}
                                        </label>
                                    </div>
                                    <small class="text-muted mb-0" style="font-size:0.75rem;">{{ t.prodKilosLabel }}</small>
                                </div>
                            </div>
                            <div class="card-body pt-2 pb-3">
                                <div class="row g-2">
                                    <!-- Total Producción -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                {{ t.totalCosts }} {{ costoLabelSuffix ?? t.totalCostsProd }}
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(dividir && divisor ? totalProduccionEfectivo / divisor : totalProduccionEfectivo) }} <small class="text-secondary">{{ dividir ? 'USD' : 'CLP' }}</small>
                                            </div>
                                            <small v-if="incluirAdmin" class="text-muted d-block" style="font-size:0.7rem;">
                                                {{ t.adminLabel }}: {{ formatNumber(dividir && divisor ? totalAdministracion / divisor : totalAdministracion) }}
                                            </small>
                                            <template v-for="state in extraStates" :key="state.id">
                                                <small v-if="selectedExtraStates[state.id]" class="text-muted d-block" style="font-size:0.7rem;">
                                                    {{ state.name }}: {{ formatNumber(dividir && divisor ? state.total / divisor : state.total) }}
                                                </small>
                                            </template>
                                            <small v-if="totalPayrollInCosto > 0" class="text-success d-block mt-1" style="font-size:0.7rem;">
                                                <i class="fas fa-users me-1"></i>Remun. incluidas: {{ formatNumber(dividir && divisor ? totalPayrollInCosto / divisor : totalPayrollInCosto) }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Total Kilos -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                {{ t.totalKilos }}
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(activeTotalKilos) }} <small class="text-secondary">Kg</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Costo por Kilo -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-success bg-opacity-10 rounded border border-success">
                                            <small class="text-uppercase text-success d-block mb-2" style="font-size: 0.75rem; font-weight: 700;">
                                                <i class="fas fa-star me-1"></i> {{ t.costoKiloLabel }}
                                            </small>
                                            <div class="fs-7 text-success">
                                                {{ formatCostoKilo(dividir && divisor ? costoKiloEfectivo / divisor : costoKiloEfectivo) }} <small class="text-success fw-medium">{{ dividir ? 'USD' : 'CLP' }}/Kg</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mensaje si no hay datos -->
                                <div v-if="!activeTotalKilos || totalProduccionEfectivo <= 0" class="alert alert-warning mt-3 mb-0 py-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>
                                        <span v-if="!activeTotalKilos">{{ t.noKilos }} </span>
                                        <span v-if="totalProduccionEfectivo <= 0">{{ t.noProduction }}</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Costo Kilo Cosecha -->
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="card border-start border-warning border-3 shadow-sm">
                            <div class="card-header bg-transparent pt-2 pb-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 text-warning">
                                        <i class="fas fa-wheat-awn me-2"></i>
                                        {{ t.costoKiloCosechaTitle }}
                                    </h6>
                                    <small class="text-muted mb-0" style="font-size:0.75rem;">{{ t.costoKiloCosechaSubtitle }}</small>
                                </div>
                            </div>
                            <div class="card-body pt-2 pb-3">
                                <div class="row g-2">
                                    <!-- Total Cosecha -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                {{ t.totalCosecha }}
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(dividir && divisor ? totalCosecha / divisor : totalCosecha) }} <small class="text-secondary">{{ dividir ? 'USD' : 'CLP' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Kilos -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <small class="text-uppercase text-muted d-block mb-2" style="font-size: 0.75rem; font-weight: 600;">
                                                {{ t.totalKilos }}
                                            </small>
                                            <div class="fs-7">
                                                {{ formatNumber(activeTotalKilos) }} <small class="text-secondary">Kg</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Costo por Kilo Cosecha -->
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                            <small class="text-uppercase text-warning d-block mb-2" style="font-size: 0.75rem; font-weight: 700;">
                                                <i class="fas fa-star me-1"></i> {{ t.costoKiloCosechaLabel }}
                                            </small>
                                            <div class="fs-7 text-warning">
                                                {{ formatCostoKilo(dividir && divisor ? costoKiloCosechaEfectivo / divisor : costoKiloCosechaEfectivo) }} <small class="text-warning fw-medium">{{ dividir ? 'USD' : 'CLP' }}/Kg</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mensaje si no hay datos -->
                                <div v-if="!totalCosecha" class="alert alert-warning mt-3 mb-0 py-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <small>{{ t.noCosecha }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área para gráficos y análisis -->
                <div class="row g-3">
                    <!-- Level2 Charts -->
                    <div class="col-12">
                        <div class="row g-3">
                            <!-- Gráfico de Barras Level2 -->
                            <div class="col-12">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-bar text-info me-2"></i>
                                            {{ t.chartLevel2Bar }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <FalconBarChart
                                            v-if="byLevel2.labels && byLevel2.labels.length > 0"
                                            :barLabels="byLevel2.labels"
                                            :barData="convertedLevel2Data"
                                            :height="350"
                                            :color="['#3b82f6', '#60a5fa', '#93c5fd', '#2563eb', '#1d4ed8', '#1e40af', '#1e3a8a', '#06b6d4']"
                                        />
                                        <div v-else class="text-center py-5">
                                            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">{{ t.noData }}</h5>
                                            <p class="text-muted mb-0">
                                                {{ t.noDataYet }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla resumen Level2 -->
                    <div class="col-12" v-if="byLevel2.labels && byLevel2.labels.length > 0">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-table text-info me-2"></i>
                                    {{ t.chartLevel2Table }}
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAll" v-tooltip="isEnglish ? 'Expand all' : 'Expandir todo'">
                                            <i class="fas fa-expand-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAll" v-tooltip="isEnglish ? 'Collapse all' : 'Colapsar todo'">
                                            <i class="fas fa-compress-alt"></i>
                                        </button>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button 
                                            type="button" 
                                            class="btn" 
                                            :class="level2ViewMode === 'area' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="level2ViewMode = 'area'"
                                        >
                                            <i class="fas fa-sitemap me-1"></i>{{ t.viewByArea }}
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn" 
                                            :class="level2ViewMode === 'category' ? 'btn-primary' : 'btn-outline-secondary'"
                                            @click="level2ViewMode = 'category'"
                                        >
                                            <i class="fas fa-tags me-1"></i>{{ t.viewByCategory }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <!-- Vista Por Área (Level1) -->
                                    <table v-if="level2ViewMode === 'area'" class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 py-2" style="width: 55%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colArea }} / {{ t.colLevel2 }}</span>
                                                </th>
                                                <th class="border-0 py-2 text-end" style="width: 30%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colTotal }}</span>
                                                </th>
                                                <th class="border-0 py-2 text-end" style="width: 15%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colPct }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-for="(group, gi) in groupedByArea" :key="'area-'+gi">
                                                <!-- Fila grupo Level1 -->
                                                <tr class="table-light" style="cursor: pointer;" @click="toggleGroup('area-'+gi)">
                                                    <td class="py-2 fw-bold text-primary">
                                                        <i class="fas me-2" :class="expandedGroups.has('area-'+gi) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>{{ group.name }}
                                                        <small class="text-muted ms-1">({{ group.items.length }})</small>
                                                    </td>
                                                    <td class="py-2 text-end fw-bold text-primary">
                                                        {{ formatNumber(dividir && divisor ? group.subtotal / divisor : group.subtotal) }}
                                                        <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        <span class="badge bg-primary">{{ group.pct.toFixed(1) }}%</span>
                                                    </td>
                                                </tr>
                                                <!-- Filas detalle Level2 -->
                                                <tr v-if="expandedGroups.has('area-'+gi)" v-for="(item, ii) in group.items" :key="'area-item-'+gi+'-'+ii">
                                                    <td class="py-2 ps-5">
                                                        {{ item.label }}
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        {{ formatNumber(dividir && divisor ? item.amount / divisor : item.amount) }}
                                                        <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        <span class="badge bg-secondary">{{ item.pct.toFixed(1) }}%</span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <!-- Fila total -->
                                            <tr class="table-primary fw-bold">
                                                <td class="py-2">{{ t.total }}</td>
                                                <td class="py-2 text-end">
                                                    {{ formatNumber(dividir && divisor ? byLevel2.data.reduce((a, b) => a + b, 0) / divisor : byLevel2.data.reduce((a, b) => a + b, 0)) }}
                                                    <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                </td>
                                                <td class="py-2 text-end">
                                                    <span class="badge bg-primary">100%</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Vista Por Categoría (nombre base) -->
                                    <table v-else class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 py-2" style="width: 55%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colCategory }} / {{ t.colArea }}</span>
                                                </th>
                                                <th class="border-0 py-2 text-end" style="width: 30%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colTotal }}</span>
                                                </th>
                                                <th class="border-0 py-2 text-end" style="width: 15%;">
                                                    <span class="text-uppercase fw-bold">{{ t.colPct }}</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-for="(group, gi) in groupedByCategory" :key="'cat-'+gi">
                                                <!-- Fila grupo Categoría -->
                                                <tr class="table-light" style="cursor: pointer;" @click="toggleGroup('category-'+gi)">
                                                    <td class="py-2 fw-bold text-info">
                                                        <i class="fas me-2" :class="expandedGroups.has('category-'+gi) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>{{ group.name }}
                                                        <span v-if="group.items.length > 1" class="text-muted ms-1">({{ group.items.length }} áreas)</span>
                                                    </td>
                                                    <td class="py-2 text-end fw-bold text-info">
                                                        {{ formatNumber(dividir && divisor ? group.subtotal / divisor : group.subtotal) }}
                                                        <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        <span class="badge bg-info">{{ group.pct.toFixed(1) }}%</span>
                                                    </td>
                                                </tr>
                                                <!-- Filas detalle por área -->
                                                <tr v-if="expandedGroups.has('category-'+gi)" v-for="(item, ii) in group.items" :key="'cat-item-'+gi+'-'+ii">
                                                    <td class="py-2 ps-5">
                                                        <span class="text-muted">{{ item.level1 }}</span>
                                                        <span class="text-muted ms-1">({{ item.originalLabel }})</span>
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        {{ formatNumber(dividir && divisor ? item.amount / divisor : item.amount) }}
                                                        <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                    </td>
                                                    <td class="py-2 text-end">
                                                        <span class="badge bg-secondary">{{ item.pct.toFixed(1) }}%</span>
                                                    </td>
                                                </tr>
                                            </template>
                                            <!-- Fila total -->
                                            <tr class="table-primary fw-bold">
                                                <td class="py-2">{{ t.total }}</td>
                                                <td class="py-2 text-end">
                                                    {{ formatNumber(dividir && divisor ? byLevel2.data.reduce((a, b) => a + b, 0) / divisor : byLevel2.data.reduce((a, b) => a + b, 0)) }}
                                                    <span class="text-secondary ms-1">{{ dividir ? 'USD' : 'CLP' }}</span>
                                                </td>
                                                <td class="py-2 text-end">
                                                    <span class="badge bg-primary">100%</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
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
