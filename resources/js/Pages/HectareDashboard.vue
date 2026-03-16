<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import FalconPieChart from '@/Components/FalconPieChart.vue';
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, LineController, LineElement, PointElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Filler } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const props = defineProps({
    surfaceByDevelopmentState: { type: Array, default: () => [] },
    surfaceByFruit: { type: Array, default: () => [] },
    costPerHaByDevState: { type: Array, default: () => [] },
    costPerHaByFruit: { type: Array, default: () => [] },
    costPerHaByFruitDevState: { type: Array, default: () => [] },
    costPerHaByLevel1: { type: Object, default: () => ({ total_surface: 0, data: [] }) },
    costPerHaByLevel2: { type: Array, default: () => [] },
    monthlyCostPerHa: { type: Object, default: () => ({ labels: [], monthly_costs: [], cumulative_costs: [], cumulative_per_ha: [], total_surface: 0 }) },
    surfaceByVariety: { type: Array, default: () => [] },
    costPerHaByVariety: { type: Array, default: () => [] },
    costByVarietyLevel2: { type: Array, default: () => [] },
    varietyDevStates: { type: Array, default: () => [] },
    costPerHaByCC: { type: Array, default: () => [] },
    costByCCLevel2: { type: Array, default: () => [] },
});

// Formato números
const fmt = (n) => new Intl.NumberFormat('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(n ?? 0));
const fmtDec = (n) => new Intl.NumberFormat('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n ?? 0);
const fmtCurrency = (n) => '$' + fmt(n);

// Tab activa para cruce Frutal × Estado
const activeTab = ref('table');

// Colores para gráficos
const chartColors = ['#2c7be5', '#00d97e', '#e63757', '#f5803e', '#6b5eae', '#39afd1', '#fd7e14', '#02a8b5', '#727cf5', '#6c757d'];

// Highlight de fila al hacer click en barra
const highlightDevState = ref(null);
const highlightFruit = ref(null);
const highlightLevel1 = ref(null);
const onBarClickDevState = (e) => { highlightDevState.value = e.name; };
const onBarClickFruit = (e) => { highlightFruit.value = e.name; };
const onBarClickLevel1 = (e) => { highlightLevel1.value = e.name; };
const showLevel2Subcat = ref(false);
const showLevel2Level3 = ref(false);

// Level2 filtrado por Level1 seleccionado y por estado
const filteredLevel2 = computed(() => {
    let items = props.costPerHaByLevel2;
    if (highlightLevel1.value) {
        items = items.filter(l => l.level1 === highlightLevel1.value);
    }
    if (selectedVarietyState.value) {
        items = items.filter(l => Number(l.state_id) === Number(selectedVarietyState.value));
    }
    // Agrupar por level1 + subcategoría + level3 (sumar estados si es "Todos")
    const grouped = {};
    items.forEach(l => {
        const key = l.level1 + '||' + l.name + '||' + (l.level3 || '');
        if (!grouped[key]) grouped[key] = { level1: l.level1, name: l.name, level3: l.level3 || '', total_cost: 0 };
        grouped[key].total_cost += l.total_cost;
    });
    const surface = level1Surface.value;
    return Object.values(grouped).map(g => ({
        ...g, cost_per_ha: surface > 0 ? g.total_cost / surface : 0,
    })).sort((a, b) => a.level1.localeCompare(b.level1) || a.name.localeCompare(b.name) || b.total_cost - a.total_cost);
});

// Datos colapsados nivel 2: agrupa por level1×level2 sumando level3
const collapsedLevel2SubcatOnly = computed(() => {
    const map = {};
    filteredLevel2.value.forEach(item => {
        const key = item.level1 + '||' + item.name;
        if (!map[key]) map[key] = { ...item, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map).sort((a, b) => a.level1.localeCompare(b.level1) || b.total_cost - a.total_cost);
});

// Datos colapsados nivel 1: agrupa por level1 sumando subcategorías
const collapsedLevel2CatOnly = computed(() => {
    const map = {};
    filteredLevel2.value.forEach(item => {
        const key = item.level1;
        if (!map[key]) map[key] = { ...item, name: item.level1, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map).sort((a, b) => b.total_cost - a.total_cost);
});

// Rowspan para Level2
const groupedLevel2Rows = computed(() => {
    const rows = [];
    let lastCat = null;
    let lastSub = null;
    const items = !showLevel2Subcat.value ? collapsedLevel2CatOnly.value : (showLevel2Level3.value ? filteredLevel2.value : collapsedLevel2SubcatOnly.value);
    items.forEach((item) => {
        const isFirstCat = item.level1 !== lastCat;
        const isFirstSub = (item.level1 + '||' + item.name) !== (lastCat + '||' + lastSub);
        const catSpan = isFirstCat ? items.filter(i => i.level1 === item.level1).length : 0;
        const subSpan = isFirstSub ? items.filter(i => i.level1 === item.level1 && i.name === item.name).length : 0;
        rows.push({ ...item, isFirstOfCategory: isFirstCat, rowspan: catSpan, isFirstOfSub: isFirstSub, subRowspan: subSpan });
        lastCat = item.level1;
        lastSub = item.name;
    });
    return rows;
});

// ── KPI resumen global ──
const productiveSurface = computed(() => props.costPerHaByDevState.filter(s => s.surface > 0).reduce((sum, s) => sum + s.surface, 0));
const totalSurface = computed(() => props.surfaceByDevelopmentState.reduce((sum, s) => sum + s.surface, 0));
const totalCostAllStates = computed(() => props.costPerHaByDevState.reduce((sum, s) => sum + s.total_cost, 0));
const globalCostPerHa = computed(() => productiveSurface.value > 0 ? totalCostAllStates.value / productiveSurface.value : 0);

// ── Toggle: incluir administración en KPIs globales ──
const includeAdmin = ref(true);

// Detectar estado de administración (surface = 0)
const adminCost = computed(() => {
    const admin = props.costPerHaByDevState.find(s => s.surface === 0);
    return admin?.total_cost ?? 0;
});

// KPI ajustados según toggle
const adjustedTotalCost = computed(() => includeAdmin.value ? totalCostAllStates.value : totalCostAllStates.value - adminCost.value);
const adjustedGlobalCostPerHa = computed(() => productiveSurface.value > 0 ? adjustedTotalCost.value / productiveSurface.value : 0);

// ── Filtrar estados según toggle (ocultar admin si OFF) ──
const filteredDevStates = computed(() => {
    const states = includeAdmin.value ? props.costPerHaByDevState : props.costPerHaByDevState.filter(s => s.surface > 0);
    return states.map(s => ({
        ...s,
        cost_per_ha_display: s.surface > 0 ? s.cost_per_ha : (productiveSurface.value > 0 ? s.total_cost / productiveSurface.value : 0),
    }));
});

// ── Datos para gráfico de barras: Costo/ha por Estado de Desarrollo ──
const devStateBarLabels = computed(() => filteredDevStates.value.map(s => s.name));
const devStateBarData = computed(() => filteredDevStates.value.map(s => Math.round(s.cost_per_ha_display)));

// ── Datos para gráfico de barras: Costo/ha por Frutal ──
const fruitBarLabels = computed(() => props.costPerHaByFruit.map(f => f.name));
const fruitBarData = computed(() => props.costPerHaByFruit.map(f => Math.round(f.cost_per_ha)));

// ── Pie chart superficie por frutal ──
const surfacePieLabels = computed(() => props.surfaceByFruit.map(f => f.name));
const surfacePieData = computed(() => props.surfaceByFruit.map(f => f.surface));

// ── Filtro de estado de desarrollo compartido (Level1, Level2, Variedades) ──
const selectedVarietyState = ref(null); // null = Todos

// ── Superficie para cálculo $/ha de Level1 (según estado seleccionado) ──
const level1Surface = computed(() => {
    if (!selectedVarietyState.value) return productiveSurface.value;
    const state = props.costPerHaByDevState.find(s => Number(s.state_id) === Number(selectedVarietyState.value));
    return state?.surface ?? 0;
});

// ── Level1 filtrado por estado ──
const filteredCostByLevel1 = computed(() => {
    const data = props.costPerHaByLevel1.data;
    const surface = level1Surface.value;
    if (!selectedVarietyState.value) {
        const grouped = {};
        data.forEach(d => {
            if (!grouped[d.name]) grouped[d.name] = { name: d.name, total_cost: 0 };
            grouped[d.name].total_cost += d.total_cost;
        });
        return Object.values(grouped).map(g => ({
            ...g, cost_per_ha: surface > 0 ? g.total_cost / surface : 0,
        })).sort((a, b) => b.total_cost - a.total_cost);
    }
    const filtered = data.filter(d => Number(d.state_id) === Number(selectedVarietyState.value));
    const grouped = {};
    filtered.forEach(d => {
        if (!grouped[d.name]) grouped[d.name] = { name: d.name, total_cost: 0 };
        grouped[d.name].total_cost += d.total_cost;
    });
    return Object.values(grouped).map(g => ({
        ...g, cost_per_ha: surface > 0 ? g.total_cost / surface : 0,
    })).sort((a, b) => b.total_cost - a.total_cost);
});

// ── Datos para gráfico barras: Costo/ha por Level 1 ──
const level1BarLabels = computed(() => filteredCostByLevel1.value.map(d => d.name));
const level1BarData = computed(() => filteredCostByLevel1.value.map(d => Math.round(d.cost_per_ha)));
const filteredLevel1Total = computed(() => filteredCostByLevel1.value.reduce((s, d) => s + d.total_cost, 0));
const filteredLevel1CostPerHa = computed(() => {
    const surface = level1Surface.value;
    return surface > 0 ? filteredLevel1Total.value / surface : 0;
});

// ── Datos filtrados de variedad según estado seleccionado ──
const filteredCostByVariety = computed(() => {
    const data = props.costPerHaByVariety;
    if (!selectedVarietyState.value) {
        // Agregar por variedad (todos los estados)
        const grouped = {};
        data.forEach(item => {
            if (!grouped[item.name]) {
                grouped[item.name] = { name: item.name, total_cost: 0, surface: 0 };
            }
            grouped[item.name].total_cost += item.total_cost;
            grouped[item.name].surface += item.surface;
        });
        return Object.values(grouped).map(g => ({
            ...g,
            cost_per_ha: g.surface > 0 ? g.total_cost / g.surface : 0,
        })).sort((a, b) => b.total_cost - a.total_cost);
    }
    return data.filter(d => Number(d.state_id) === Number(selectedVarietyState.value))
        .sort((a, b) => b.total_cost - a.total_cost);
});

const filteredSurfaceByVariety = computed(() => {
    const data = props.surfaceByVariety;
    if (!selectedVarietyState.value) {
        const grouped = {};
        data.forEach(item => {
            if (!grouped[item.name]) {
                grouped[item.name] = { name: item.name, surface: 0, count: 0 };
            }
            grouped[item.name].surface += item.surface;
            grouped[item.name].count += item.count;
        });
        return Object.values(grouped).sort((a, b) => b.surface - a.surface);
    }
    return data.filter(d => Number(d.state_id) === Number(selectedVarietyState.value))
        .sort((a, b) => b.surface - a.surface);
});

const totalCostVariety = computed(() => filteredCostByVariety.value.reduce((s, v) => s + v.total_cost, 0));

// ── Datos para gráfico de barras: Costo/ha por Variedad (filtrado) ──
const varietyBarLabels = computed(() => filteredCostByVariety.value.map(v => v.name));
const varietyBarData = computed(() => filteredCostByVariety.value.map(v => Math.round(v.cost_per_ha)));

// ── Click en barra de variedad: detalle por Level 2 ──
const highlightVariety = ref(null);
const onBarClickVariety = (e) => { highlightVariety.value = e.name; };
const showVarietySubcat = ref(false);
const showVarietyLevel3 = ref(false);

const highlightVarietySurface = computed(() => {
    if (!highlightVariety.value) return 0;
    const v = filteredCostByVariety.value.find(v => v.name === highlightVariety.value);
    return v?.surface ?? 0;
});
const filteredVarietyDetail = computed(() => {
    let items = props.costByVarietyLevel2;
    if (highlightVariety.value) {
        items = items.filter(v => v.variety === highlightVariety.value);
    }
    if (selectedVarietyState.value) {
        items = items.filter(v => Number(v.state_id) === Number(selectedVarietyState.value));
    } else {
        // Agrupar por variety+level2+level3 sumando todos los estados
        const grouped = {};
        items.forEach(v => {
            const key = v.variety + '||' + v.category + '||' + v.name + '||' + (v.level3 || '');
            if (!grouped[key]) grouped[key] = { variety: v.variety, category: v.category, name: v.name, level3: v.level3 || '', total_cost: 0 };
            grouped[key].total_cost += v.total_cost;
        });
        items = Object.values(grouped);
    }
    const surface = highlightVariety.value ? highlightVarietySurface.value : filteredCostByVariety.value.reduce((s, v) => s + v.surface, 0);
    return items
        .map(v => ({ ...v, cost_per_ha: surface > 0 ? v.total_cost / surface : 0 }))
        .sort((a, b) => a.variety.localeCompare(b.variety) || a.category.localeCompare(b.category) || a.name.localeCompare(b.name) || b.total_cost - a.total_cost);
});

// Datos colapsados nivel 2: agrupa por variedad×categoría×subcategoría sumando level3
const collapsedVarietySubcatOnly = computed(() => {
    const map = {};
    filteredVarietyDetail.value.forEach(item => {
        const key = item.variety + '||' + item.category + '||' + item.name;
        if (!map[key]) map[key] = { ...item, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map).sort((a, b) => a.variety.localeCompare(b.variety) || a.category.localeCompare(b.category) || b.total_cost - a.total_cost);
});

// Datos colapsados nivel 1: agrupa por variedad×categoría sumando subcategorías
const collapsedVarietyDetail = computed(() => {
    const map = {};
    filteredVarietyDetail.value.forEach(item => {
        const key = item.variety + '||' + item.category;
        if (!map[key]) map[key] = { ...item, name: item.category, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map).sort((a, b) => a.variety.localeCompare(b.variety) || b.total_cost - a.total_cost);
});

// Filas con rowspan calculado para agrupar por variedad y categoría
const groupedVarietyRows = computed(() => {
    const rows = [];
    let lastVar = null;
    let lastCat = null;
    let lastSub = null;
    const items = !showVarietySubcat.value ? collapsedVarietyDetail.value : (showVarietyLevel3.value ? filteredVarietyDetail.value : collapsedVarietySubcatOnly.value);
    items.forEach((item) => {
        const isFirstVar = item.variety !== lastVar;
        const isFirstCat = (item.variety + '||' + item.category) !== (lastVar + '||' + lastCat);
        const isFirstSub = (item.variety + '||' + item.category + '||' + item.name) !== (lastVar + '||' + lastCat + '||' + lastSub);
        const varSpan = isFirstVar ? items.filter(i => i.variety === item.variety).length : 0;
        const catSpan = isFirstCat ? items.filter(i => i.variety === item.variety && i.category === item.category).length : 0;
        const subSpan = isFirstSub ? items.filter(i => i.variety === item.variety && i.category === item.category && i.name === item.name).length : 0;
        rows.push({ ...item, isFirstOfVariety: isFirstVar, varRowspan: varSpan, isFirstOfCategory: isFirstCat, rowspan: catSpan, isFirstOfSub: isFirstSub, subRowspan: subSpan });
        lastVar = item.variety;
        lastCat = item.category;
        lastSub = item.name;
    });
    return rows;
});

// ── Cruce Frutal × Estado: obtener frutas y estados únicos ──
const crossFruits = computed(() => [...new Set(props.costPerHaByFruitDevState.map(r => r.fruit))]);
const crossStates = computed(() => [...new Set(props.costPerHaByFruitDevState.map(r => r.state))]);
const crossLookup = computed(() => {
    const map = {};
    props.costPerHaByFruitDevState.forEach(r => { map[r.fruit + '||' + r.state] = r; });
    return map;
});
const getCrossData = (fruit, state) => crossLookup.value[fruit + '||' + state] || null;
const getCrossCostPerHa = (d) => {
    if (!d) return 0;
    if (d.surface > 0) return d.cost_per_ha;
    return productiveSurface.value > 0 ? d.total_cost / productiveSurface.value : 0;
};

// ── Costo/ha por Centro de Costo (filtrado por estado) ──
const filteredCostByCC = computed(() => {
    let data = props.costPerHaByCC;
    if (selectedVarietyState.value) {
        data = data.filter(d => Number(d.state_id) === Number(selectedVarietyState.value));
    }
    return data.filter(d => d.surface > 0).sort((a, b) => b.cost_per_ha - a.cost_per_ha);
});

const ccBarLabels = computed(() => filteredCostByCC.value.map(d => d.name));
const ccBarData = computed(() => filteredCostByCC.value.map(d => Math.round(d.cost_per_ha)));

// ── Click en barra de CC: detalle por Level2 ──
const highlightCC = ref(null);
const onBarClickCC = (e) => { highlightCC.value = e.name; };
const showCCLevel3 = ref(false);
const showCCSubcat = ref(false);

const highlightCCSurface = computed(() => {
    if (!highlightCC.value) return filteredCostByCC.value.reduce((s, c) => s + c.surface, 0);
    const cc = filteredCostByCC.value.find(c => c.name === highlightCC.value);
    return cc?.surface ?? 0;
});

const filteredCCDetail = computed(() => {
    let items = props.costByCCLevel2;
    if (highlightCC.value) {
        const cc = filteredCostByCC.value.find(c => c.name === highlightCC.value);
        if (!cc) return [];
        items = items.filter(v => v.cc_id === cc.cc_id);
    }
    // Filtrar por estado si aplica
    if (selectedVarietyState.value) {
        // costByCCLevel2 no tiene state_id, filtrar via cc_ids del estado
        const validCCIds = filteredCostByCC.value.map(c => c.cc_id);
        items = items.filter(v => validCCIds.includes(v.cc_id));
    }
    const surface = highlightCCSurface.value;
    return items
        .map(v => {
            const ccName = highlightCC.value || (filteredCostByCC.value.find(c => c.cc_id === v.cc_id)?.name ?? '');
            return { ...v, cc_name: ccName, cost_per_ha: surface > 0 ? v.total_cost / surface : 0 };
        })
        .sort((a, b) => a.cc_name.localeCompare(b.cc_name) || a.category.localeCompare(b.category) || a.name.localeCompare(b.name) || b.total_cost - a.total_cost);
});

// Datos colapsados nivel 3: agrupa por CC×Level1×Level2 sumando Level3
const collapsedCCLevel3 = computed(() => {
    const map = {};
    filteredCCDetail.value.forEach(item => {
        const key = (item.cc_name || '') + '||' + item.category + '||' + item.name;
        if (!map[key]) map[key] = { ...item, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map);
});

// Datos colapsados nivel 2: agrupa por CC×Level1 sumando subcategorías
const collapsedCCSubcat = computed(() => {
    const map = {};
    filteredCCDetail.value.forEach(item => {
        const key = (item.cc_name || '') + '||' + item.category;
        if (!map[key]) map[key] = { ...item, name: item.category, total_cost: 0, cost_per_ha: 0 };
        map[key].total_cost += item.total_cost;
        map[key].cost_per_ha += item.cost_per_ha;
    });
    return Object.values(map).sort((a, b) => a.cc_name.localeCompare(b.cc_name) || b.total_cost - a.total_cost);
});

const groupedCCRows = computed(() => {
    const rows = [];
    let lastCC = null;
    let lastCat = null;
    let lastSub = null;
    const items = !showCCSubcat.value ? collapsedCCSubcat.value : (showCCLevel3.value ? filteredCCDetail.value : collapsedCCLevel3.value);
    items.forEach((item) => {
        const isFirstCC = item.cc_name !== lastCC;
        const isFirstCat = (item.cc_name + '||' + item.category) !== (lastCC + '||' + lastCat);
        const isFirstSub = (item.cc_name + '||' + item.category + '||' + item.name) !== (lastCC + '||' + lastCat + '||' + lastSub);
        const ccSpan = isFirstCC ? items.filter(i => i.cc_name === item.cc_name).length : 0;
        const catSpan = isFirstCat ? items.filter(i => i.cc_name === item.cc_name && i.category === item.category).length : 0;
        const subSpan = isFirstSub ? items.filter(i => i.cc_name === item.cc_name && i.category === item.category && i.name === item.name).length : 0;
        rows.push({ ...item, isFirstOfCC: isFirstCC, ccRowspan: ccSpan, isFirstOfCategory: isFirstCat, rowspan: catSpan, isFirstOfSub: isFirstSub, subRowspan: subSpan });
        lastCC = item.cc_name;
        lastCat = item.category;
        lastSub = item.name;
    });
    return rows;
});

// Reset CC al cambiar estado
watch(selectedVarietyState, () => {
    highlightLevel1.value = null;
    highlightVariety.value = null;
    highlightCC.value = null;
});

// ── Gráfico mensual (Chart.js) ──
const monthlyChartRef = ref(null);
let monthlyChart = null;

const createMonthlyChart = () => {
    if (!monthlyChartRef.value || !props.monthlyCostPerHa.labels.length) return;
    if (monthlyChart) monthlyChart.destroy();

    const ctx = monthlyChartRef.value.getContext('2d');
    monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.monthlyCostPerHa.labels,
            datasets: [
                {
                    label: 'Costo Mensual ($)',
                    data: props.monthlyCostPerHa.monthly_costs,
                    backgroundColor: 'rgba(44, 123, 229, 0.5)',
                    borderColor: '#2c7be5',
                    borderWidth: 1,
                    borderRadius: 4,
                    order: 2,
                    yAxisID: 'y',
                },
                {
                    label: 'Costo/Ha Acumulado ($)',
                    data: props.monthlyCostPerHa.cumulative_per_ha,
                    type: 'line',
                    borderColor: '#e63757',
                    backgroundColor: 'rgba(230, 55, 87, 0.1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#e63757',
                    tension: 0.3,
                    fill: true,
                    order: 1,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.dataset.label + ': $' + fmt(ctx.parsed.y),
                    },
                },
                legend: { position: 'top' },
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Costo Mensual ($)' },
                    ticks: { callback: (v) => '$' + fmt(v) },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Costo/Ha Acumulado ($)' },
                    ticks: { callback: (v) => '$' + fmt(v) },
                    grid: { drawOnChartArea: false },
                },
            },
        },
    });
};

onMounted(() => { nextTick(() => createMonthlyChart()); });
onUnmounted(() => { if (monthlyChart) monthlyChart.destroy(); });

// Recrear gráfico cuando el canvas se monte (por v-if tardío o reubicación)
watch(monthlyChartRef, (val) => {
    if (val) nextTick(() => createMonthlyChart());
});
</script>

<template>
    <AppLayout title="Dashboard Gestión por Hectárea">
        <Head title="Dashboard Gestión por Hectárea" />

        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-seedling me-2"></i>Dashboard Gestión por Hectárea
                        </h5>
                    </div>
                    <div class="col-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small mb-0 text-nowrap">Incluir Administracion en analisis:</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" v-model="includeAdmin" id="toggleAdmin">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- ═══════════ KPI GLOBALES ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase text-600 mb-1"><i class="fas fa-expand me-1"></i> Superficie Total</h6>
                                <h3 class="mb-0 text-primary">{{ fmtDec(totalSurface) }} <small class="fs-9 text-600">ha</small></h3>
                                <small class="text-500">{{ props.surfaceByDevelopmentState.reduce((s, i) => s + i.count, 0) }} centros de costo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase text-600 mb-1"><i class="fas fa-dollar-sign me-1"></i> Costo Total (sin Inv.)</h6>
                                <h3 class="mb-0 text-success">{{ fmtCurrency(adjustedTotalCost) }}</h3>
                                <small class="text-500">{{ includeAdmin ? 'Incluye admin prorrateada' : 'Sin gastos admin' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-danger border-3">
                            <div class="card-body py-3">
                                <h6 class="text-uppercase text-600 mb-1"><i class="fas fa-chart-bar me-1"></i> Costo / Hectárea Global</h6>
                                <h3 class="mb-0 text-danger">{{ fmtCurrency(adjustedGlobalCostPerHa) }} <small class="fs-9 text-600">/ha</small></h3>
                                <small class="text-500">{{ includeAdmin ? 'Incluye admin prorrateada' : 'Solo estados productivos' }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ SECCIÓN: ESTADO DE DESARROLLO ═══════════ -->
                <div class="card mb-4 shadow-none" style="border-left: 3px solid #b3d1ec;">
                    <div class="card-header py-2" style="background-color: #dce9f5;">
                        <h5 class="mb-0 fs-9" style="color: #2c5282;"><i class="fas fa-layer-group me-2"></i>Estado de Desarrollo</h5>
                    </div>
                    <div class="card-body">

                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header"><h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Costo / Hectárea por Estado de Desarrollo</h6></div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="devStateBarLabels.length"
                                    :barLabels="devStateBarLabels"
                                    :barData="devStateBarData"
                                    :height="300"
                                    :color="['#2c7be5', '#00d97e', '#e63757', '#f5803e', '#6b5eae']"
                                    @bar-click="onBarClickDevState"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos de estados de desarrollo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header"><h6 class="mb-0">Detalle por Estado</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Estado</th>
                                                <th class="text-end">Superficie</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in filteredDevStates" :key="item.name"
                                                :class="{ 'table-primary': highlightDevState === item.name }"
                                                style="cursor: pointer; transition: background 0.2s"
                                                @click="highlightDevState = highlightDevState === item.name ? null : item.name">
                                                <td class="fw-semi-bold">{{ item.name }}</td>
                                                <td class="text-end">{{ fmtDec(item.surface) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha_display) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light fw-bold">
                                            <tr>
                                                <td>Total</td>
                                                <td class="text-end">{{ fmtDec(productiveSurface) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(adjustedTotalCost) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    </div>
                </div>
                <!-- /SECCIÓN: ESTADO DE DESARROLLO -->

                <!-- ═══════════ SECCIÓN: ANÁLISIS POR FRUTAL ═══════════ -->
                <div class="card mb-4 shadow-none" style="border-left: 3px solid #b3d1ec;">
                    <div class="card-header py-2" style="background-color: #dce9f5;">
                        <h5 class="mb-0 fs-9" style="color: #2c5282;"><i class="fas fa-apple-alt me-2"></i>Análisis por Frutal</h5>
                    </div>
                    <div class="card-body">

                <div class="row g-3 mb-4">
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header"><h6 class="mb-0"><i class="fas fa-apple-alt me-2"></i>Costo / Hectárea por Frutal</h6></div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="fruitBarLabels.length"
                                    :barLabels="fruitBarLabels"
                                    :barData="fruitBarData"
                                    :height="300"
                                    :color="['#00d97e', '#2c7be5', '#e63757', '#f5803e', '#6b5eae', '#39afd1']"
                                    @bar-click="onBarClickFruit"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos de frutales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header"><h6 class="mb-0">Distribución de Superficie</h6></div>
                            <div class="card-body">
                                <FalconPieChart
                                    v-if="surfacePieLabels.length"
                                    :pieLabels="surfacePieLabels"
                                    :pieDatasets="surfacePieData"
                                    :showPercentage="true"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ DETALLE POR FRUTAL (tabla) ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Detalle Costo/ha por Frutal</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Frutal</th>
                                                <th class="text-end">N° CC</th>
                                                <th class="text-end">Superficie</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">% del Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in costPerHaByFruit" :key="item.name"
                                                :class="{ 'table-primary': highlightFruit === item.name }"
                                                style="cursor: pointer; transition: background 0.2s"
                                                @click="highlightFruit = highlightFruit === item.name ? null : item.name">
                                                <td class="fw-semi-bold">{{ item.name }}</td>
                                                <td class="text-end">{{ surfaceByFruit.find(f => f.name === item.name)?.count ?? '-' }}</td>
                                                <td class="text-end">{{ fmtDec(item.surface) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ totalCostAllStates > 0 ? fmtDec(item.total_cost / totalCostAllStates * 100) : '0' }}%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ CRUCE FRUTAL × ESTADO DE DESARROLLO ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-th me-2"></i>Costo / Hectárea: Frutal × Estado de Desarrollo</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" v-if="costPerHaByFruitDevState.length">
                                    <table class="table table-sm table-bordered table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Frutal</th>
                                                <th v-for="state in crossStates" :key="state" class="text-center">
                                                    {{ state }}<br><small class="text-500">$/ha</small>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="fruit in crossFruits" :key="fruit">
                                                <td class="fw-semi-bold">{{ fruit }}</td>
                                                <td v-for="state in crossStates" :key="state" class="text-center">
                                                    <template v-if="getCrossData(fruit, state)">
                                                        <span class="fw-bold text-primary">{{ fmtCurrency(getCrossCostPerHa(getCrossData(fruit, state))) }}</span>
                                                        <br>
                                                        <small class="text-500">{{ fmtDec(getCrossData(fruit, state).surface) }} ha · {{ fmtCurrency(getCrossData(fruit, state).total_cost) }}</small>
                                                    </template>
                                                    <span v-else class="text-300">—</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-center text-muted py-4">Sin datos para cruce Frutal × Estado</p>
                            </div>
                        </div>
                    </div>
                </div>

                    </div>
                </div>
                <!-- /SECCIÓN: ANÁLISIS POR FRUTAL -->

                <!-- ═══════════ SECCIÓN: ANÁLISIS POR CATEGORÍA ═══════════ -->
                <div class="card mb-4 shadow-none" style="border-left: 3px solid #b3d1ec;">
                    <div class="card-header py-2" style="background-color: #dce9f5;">
                        <h5 class="mb-0 fs-9" style="color: #2c5282;"><i class="fas fa-sitemap me-2"></i>Análisis por Categoría (Nivel 1)</h5>
                    </div>
                    <div class="card-body">

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtro por Estado de Desarrollo</h6>
                            <div class="d-flex align-items-center gap-2">
                                <select v-model="selectedVarietyState" class="form-select form-select-sm" style="width: 200px;">
                                    <option :value="null">Todos los estados</option>
                                    <option v-for="s in varietyDevStates" :key="s.value" :value="s.value">{{ s.label }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ COSTO/HA POR CATEGORÍA (LEVEL 1) ═══════════ -->
                <div class="row g-3 mb-4">
                    <div :class="(showLevel2Subcat && showLevel2Level3) ? 'col-lg-4' : (showLevel2Subcat ? 'col-lg-5' : 'col-lg-7')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i>Costo / Hectárea por Categoría (Nivel 1)
                                    <small v-if="selectedVarietyState" class="text-500 ms-1">({{ varietyDevStates.find(s => Number(s.value) === Number(selectedVarietyState))?.label }})</small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="level1BarLabels.length"
                                    :barLabels="level1BarLabels"
                                    :barData="level1BarData"
                                    :height="300"
                                    :color="chartColors"
                                    @bar-click="onBarClickLevel1"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos por nivel 1</p>
                            </div>
                        </div>
                    </div>
                    <div :class="(showLevel2Subcat && showLevel2Level3) ? 'col-lg-8' : (showLevel2Subcat ? 'col-lg-7' : 'col-lg-5')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    <template v-if="highlightLevel1">Detalle: {{ highlightLevel1 }}</template>
                                    <template v-else>Detalle por Categoría</template>
                                </h6>
                                <div v-if="filteredLevel2.length" class="d-flex gap-1">
                                    <button type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showLevel2Subcat = !showLevel2Subcat; if (!showLevel2Subcat) showLevel2Level3 = false;" v-tooltip="showLevel2Subcat ? 'Ocultar subcategorías' : 'Ver subcategorías'">
                                        <i class="fas fa-xs" :class="showLevel2Subcat ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showLevel2Subcat ? 'Colapsar' : 'Subcategorías' }}</span>
                                    </button>
                                    <button v-if="showLevel2Subcat" type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showLevel2Level3 = !showLevel2Level3" v-tooltip="showLevel2Level3 ? 'Ocultar productos' : 'Ver productos'">
                                        <i class="fas fa-xs" :class="showLevel2Level3 ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showLevel2Level3 ? 'Colapsar' : 'Productos' }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="filteredLevel2.length" class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th v-if="!highlightLevel1">Categoría</th>
                                                <th v-if="showLevel2Subcat">Subcategoría</th>
                                                <th v-if="showLevel2Subcat && showLevel2Level3">Producto</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in groupedLevel2Rows" :key="item.level1 + '-' + item.name + '-' + (item.level3 || idx)">
                                                <td v-if="!highlightLevel1 && item.isFirstOfCategory" :rowspan="item.rowspan" class="align-middle fw-semi-bold bg-body-tertiary border-end" style="vertical-align: middle;">{{ item.level1 }}</td>
                                                <td v-if="showLevel2Subcat && item.isFirstOfSub" :rowspan="item.subRowspan" class="align-middle fw-semi-bold border-end" style="vertical-align: middle;">{{ item.name }}</td>
                                                <td v-if="showLevel2Subcat && showLevel2Level3">{{ item.level3 }}</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ filteredLevel2.reduce((s, i) => s + i.total_cost, 0) > 0 ? fmtDec(item.total_cost / filteredLevel2.reduce((s, i) => s + i.total_cost, 0) * 100) : '0' }}%</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light fw-bold">
                                            <tr>
                                                <td :colspan="(highlightLevel1 ? 0 : 1) + (showLevel2Subcat ? (showLevel2Level3 ? 2 : 1) : 0)">Total</td>
                                                <td class="text-end">{{ fmtCurrency(filteredLevel2.reduce((s, i) => s + i.total_cost, 0)) }}</td>
                                                <td class="text-end text-danger">{{ fmtCurrency(filteredLevel2.reduce((s, i) => s + i.cost_per_ha, 0)) }}</td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div v-else class="d-flex align-items-center justify-content-center h-100 py-5">
                                    <p class="text-muted mb-0 fs-9"><i class="fas fa-hand-point-left fa-lg me-2"></i>Click en una barra del gráfico</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    </div>
                </div>
                <!-- /SECCIÓN: ANÁLISIS POR CATEGORÍA -->

                <!-- ═══════════ SECCIÓN: ANÁLISIS POR VARIEDAD ═══════════ -->
                <div class="card mb-4 shadow-none" style="border-left: 3px solid #b3d1ec;">
                    <div class="card-header py-2" style="background-color: #dce9f5;">
                        <h5 class="mb-0 fs-9" style="color: #2c5282;"><i class="fas fa-seedling me-2"></i>Análisis por Variedad</h5>
                    </div>
                    <div class="card-body">

                <div class="row g-3 mb-4">
                    <div :class="(showVarietySubcat && showVarietyLevel3) ? 'col-lg-4' : (showVarietySubcat ? 'col-lg-5' : 'col-lg-7')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Costo / Hectárea por Variedad
                                    <small v-if="selectedVarietyState" class="text-500 ms-1">({{ varietyDevStates.find(s => Number(s.value) === Number(selectedVarietyState))?.label }})</small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="varietyBarLabels.length"
                                    :barLabels="varietyBarLabels"
                                    :barData="varietyBarData"
                                    :height="300"
                                    :color="['#727cf5', '#00d97e', '#e63757', '#f5803e', '#2c7be5', '#39afd1', '#6b5eae', '#fd7e14', '#02a8b5', '#6c757d']"
                                    @bar-click="onBarClickVariety"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos de variedades</p>
                            </div>
                        </div>
                    </div>
                    <div :class="(showVarietySubcat && showVarietyLevel3) ? 'col-lg-8' : (showVarietySubcat ? 'col-lg-7' : 'col-lg-5')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    <template v-if="highlightVariety">Detalle: {{ highlightVariety }}</template>
                                    <template v-else>Detalle por Categoría</template>
                                </h6>
                                <div v-if="filteredVarietyDetail.length" class="d-flex gap-1">
                                    <button type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showVarietySubcat = !showVarietySubcat; if (!showVarietySubcat) showVarietyLevel3 = false;" v-tooltip="showVarietySubcat ? 'Ocultar subcategorías' : 'Ver subcategorías'">
                                        <i class="fas fa-xs" :class="showVarietySubcat ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showVarietySubcat ? 'Colapsar' : 'Subcategorías' }}</span>
                                    </button>
                                    <button v-if="showVarietySubcat" type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showVarietyLevel3 = !showVarietyLevel3" v-tooltip="showVarietyLevel3 ? 'Ocultar productos' : 'Ver productos'">
                                        <i class="fas fa-xs" :class="showVarietyLevel3 ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showVarietyLevel3 ? 'Colapsar' : 'Productos' }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="filteredVarietyDetail.length" class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th v-if="!highlightVariety">Variedad</th>
                                                <th>Categoría</th>
                                                <th v-if="showVarietySubcat">Subcategoría</th>
                                                <th v-if="showVarietySubcat && showVarietyLevel3">Producto</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in groupedVarietyRows" :key="item.variety + '-' + item.category + '-' + item.name + '-' + (item.level3 || idx)">
                                                <td v-if="!highlightVariety && item.isFirstOfVariety" :rowspan="item.varRowspan" class="align-middle fw-semi-bold bg-body-tertiary border-end" style="vertical-align: middle;">{{ item.variety }}</td>
                                                <td v-if="item.isFirstOfCategory" :rowspan="item.rowspan" class="align-middle fw-semi-bold border-end" style="vertical-align: middle;">{{ item.category }}</td>
                                                <td v-if="showVarietySubcat && item.isFirstOfSub" :rowspan="item.subRowspan" class="align-middle border-end" style="vertical-align: middle;">{{ item.name }}</td>
                                                <td v-if="showVarietySubcat && showVarietyLevel3">{{ item.level3 }}</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ filteredVarietyDetail.reduce((s, i) => s + i.total_cost, 0) > 0 ? fmtDec(item.total_cost / filteredVarietyDetail.reduce((s, i) => s + i.total_cost, 0) * 100) : '0' }}%</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light fw-bold">
                                            <tr>
                                                <td :colspan="(highlightVariety ? 0 : 1) + (showVarietySubcat ? (showVarietyLevel3 ? 3 : 2) : 1)">Total</td>
                                                <td class="text-end">{{ fmtCurrency(filteredVarietyDetail.reduce((s, i) => s + i.total_cost, 0)) }}</td>
                                                <td class="text-end text-danger">{{ fmtCurrency(filteredVarietyDetail.reduce((s, i) => s + i.cost_per_ha, 0)) }}</td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div v-else class="d-flex align-items-center justify-content-center h-100 py-5">
                                    <p class="text-muted mb-0 fs-9"><i class="fas fa-hand-point-left fa-lg me-2"></i>Click en una barra del gráfico</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ DETALLE POR VARIEDAD (tabla) ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Detalle Costo/ha por Variedad
                                    <small v-if="selectedVarietyState" class="text-500 ms-1">({{ varietyDevStates.find(s => Number(s.value) === Number(selectedVarietyState))?.label }})</small>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Variedad</th>
                                                <th v-if="!selectedVarietyState" class="text-end">N° Registros</th>
                                                <th class="text-end">Superficie</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">% del Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in filteredCostByVariety" :key="item.name">
                                                <td class="fw-semi-bold">{{ item.name }}</td>
                                                <td v-if="!selectedVarietyState" class="text-end">{{ filteredSurfaceByVariety.find(v => v.name === item.name)?.count ?? '-' }}</td>
                                                <td class="text-end">{{ fmtDec(item.surface) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ totalCostVariety > 0 ? fmtDec(item.total_cost / totalCostVariety * 100) : '0' }}%</td>
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
                <!-- /SECCIÓN: ANÁLISIS POR VARIEDAD -->

                <!-- ═══════════ SECCIÓN: CENTRO DE COSTO ═══════════ -->
                <div class="card mb-4 shadow-none" style="border-left: 3px solid #b3d1ec;">
                    <div class="card-header py-2" style="background-color: #dce9f5;">
                        <h5 class="mb-0 fs-9" style="color: #2c5282;"><i class="fas fa-map-marker-alt me-2"></i>Análisis por Centro de Costo</h5>
                    </div>
                    <div class="card-body">

                <div class="row g-3 mb-4">
                    <div :class="(showCCSubcat && showCCLevel3) ? 'col-lg-4' : (showCCSubcat ? 'col-lg-5' : 'col-lg-7')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Costo / Hectárea por Centro de Costo
                                    <small v-if="selectedVarietyState" class="text-500 ms-1">({{ varietyDevStates.find(s => Number(s.value) === Number(selectedVarietyState))?.label }})</small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <FalconBarChart
                                    v-if="ccBarLabels.length"
                                    :barLabels="ccBarLabels"
                                    :barData="ccBarData"
                                    :height="300"
                                    :color="['#2c7be5', '#00d97e', '#e63757', '#f5803e', '#727cf5', '#39afd1', '#6b5eae', '#fd7e14', '#02a8b5', '#6c757d']"
                                    @bar-click="onBarClickCC"
                                />
                                <p v-else class="text-center text-muted py-5">Sin datos de centros de costo</p>
                            </div>
                        </div>
                    </div>
                    <div :class="(showCCSubcat && showCCLevel3) ? 'col-lg-8' : (showCCSubcat ? 'col-lg-7' : 'col-lg-5')" style="transition: all 0.3s ease;">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    <template v-if="highlightCC">Detalle: {{ highlightCC }}</template>
                                    <template v-else>Detalle por Categoría</template>
                                </h6>
                                <div v-if="filteredCCDetail.length" class="d-flex gap-1">
                                    <button type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showCCSubcat = !showCCSubcat; if (!showCCSubcat) showCCLevel3 = false;" v-tooltip="showCCSubcat ? 'Ocultar subcategorías' : 'Ver subcategorías'">
                                        <i class="fas fa-xs" :class="showCCSubcat ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showCCSubcat ? 'Colapsar' : 'Subcategorías' }}</span>
                                    </button>
                                    <button v-if="showCCSubcat" type="button" class="btn btn-falcon-default btn-sm py-0 px-2" @click="showCCLevel3 = !showCCLevel3" v-tooltip="showCCLevel3 ? 'Ocultar productos' : 'Ver productos'">
                                        <i class="fas fa-xs" :class="showCCLevel3 ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                        <span class="ms-1 d-none d-xl-inline" style="font-size: 0.75rem;">{{ showCCLevel3 ? 'Colapsar' : 'Productos' }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="filteredCCDetail.length" class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th v-if="!highlightCC">Centro de Costo</th>
                                                <th>Categoría</th>
                                                <th v-if="showCCSubcat">Subcategoría</th>
                                                <th v-if="showCCSubcat && showCCLevel3">Producto</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in groupedCCRows" :key="(item.cc_name || '') + '-' + item.category + '-' + item.name + '-' + (item.level3 || idx)">
                                                <td v-if="!highlightCC && item.isFirstOfCC" :rowspan="item.ccRowspan" class="align-middle fw-semi-bold bg-body-tertiary border-end" style="vertical-align: middle;">{{ item.cc_name }}</td>
                                                <td v-if="item.isFirstOfCategory" :rowspan="item.rowspan" class="align-middle fw-semi-bold border-end" style="vertical-align: middle;">{{ item.category }}</td>
                                                <td v-if="showCCSubcat && item.isFirstOfSub" :rowspan="item.subRowspan" class="align-middle border-end" style="vertical-align: middle;">{{ item.name }}</td>
                                                <td v-if="showCCSubcat && showCCLevel3">{{ item.level3 }}</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ filteredCCDetail.reduce((s, i) => s + i.total_cost, 0) > 0 ? fmtDec(item.total_cost / filteredCCDetail.reduce((s, i) => s + i.total_cost, 0) * 100) : '0' }}%</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light fw-bold">
                                            <tr>
                                                <td :colspan="(highlightCC ? 0 : 1) + (showCCSubcat ? (showCCLevel3 ? 3 : 2) : 1)">Total</td>
                                                <td class="text-end">{{ fmtCurrency(filteredCCDetail.reduce((s, i) => s + i.total_cost, 0)) }}</td>
                                                <td class="text-end text-danger">{{ fmtCurrency(filteredCCDetail.reduce((s, i) => s + i.cost_per_ha, 0)) }}</td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div v-else class="d-flex align-items-center justify-content-center h-100 py-5">
                                    <p class="text-muted mb-0 fs-9"><i class="fas fa-hand-point-left fa-lg me-2"></i>Click en una barra del gráfico</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ DETALLE POR CC (tabla) ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Detalle Costo/ha por Centro de Costo
                                    <small v-if="selectedVarietyState" class="text-500 ms-1">({{ varietyDevStates.find(s => Number(s.value) === Number(selectedVarietyState))?.label }})</small>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Centro de Costo</th>
                                                <th>Frutal</th>
                                                <th class="text-end">Superficie</th>
                                                <th class="text-end">Costo Total</th>
                                                <th class="text-end">$/ha</th>
                                                <th class="text-end">% del Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in filteredCostByCC" :key="item.cc_id"
                                                :class="{ 'table-primary': highlightCC === item.name }"
                                                style="cursor: pointer; transition: background 0.2s"
                                                @click="highlightCC = item.name">
                                                <td class="fw-semi-bold">{{ item.name }}</td>
                                                <td>{{ item.fruit }}</td>
                                                <td class="text-end">{{ fmtDec(item.surface) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(item.total_cost) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ fmtCurrency(item.cost_per_ha) }}</td>
                                                <td class="text-end">{{ filteredCostByCC.reduce((s, i) => s + i.total_cost, 0) > 0 ? fmtDec(item.total_cost / filteredCostByCC.reduce((s, i) => s + i.total_cost, 0) * 100) : '0' }}%</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-light fw-bold" v-if="filteredCostByCC.length">
                                            <tr>
                                                <td colspan="2">TOTAL</td>
                                                <td class="text-end">{{ fmtDec(filteredCostByCC.reduce((s, i) => s + i.surface, 0)) }} ha</td>
                                                <td class="text-end">{{ fmtCurrency(filteredCostByCC.reduce((s, i) => s + i.total_cost, 0)) }}</td>
                                                <td class="text-end text-danger">{{ fmtCurrency(filteredCostByCC.reduce((s, i) => s + i.total_cost, 0) / filteredCostByCC.reduce((s, i) => s + i.surface, 0)) }}</td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    </div>
                </div>
                <!-- /SECCIÓN: CENTRO DE COSTO -->

                <!-- ═══════════ EVOLUCIÓN MENSUAL ═══════════ -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-area me-2"></i>Evolución Mensual — Costo y Costo/ha Acumulado
                                    <small class="text-500 ms-2">(Superficie: {{ fmtDec(monthlyCostPerHa.total_surface) }} ha)</small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div v-if="monthlyCostPerHa.labels.length" style="height: 350px;">
                                    <canvas ref="monthlyChartRef"></canvas>
                                </div>
                                <p v-else class="text-center text-muted py-5">Sin datos mensuales disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
