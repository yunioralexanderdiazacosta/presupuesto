<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    consumoPorSucursal: { type: Array, default: () => [] },
    stockValorizado: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    consumoPorHectarea: { type: Array, default: () => [] },
    superficiePorSucursal: { type: Array, default: () => [] },
    developmentStates: { type: Array, default: () => [] },
});

const title = 'Detalle de Salidas por Sucursal';

// Normaliza el stock valorizado para usar el mismo campo 'amount' que el consumo
const normalizedStock = computed(() => props.stockValorizado.map(r => ({ ...r, amount: r.valor })));

const formatNumber = (value) => new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(value || 0));
const formatHa = (value) => new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value || 0);

// Cards apiladas: Consumo + Stock Valorizado por sucursal
const branchTotals = computed(() => {
    const map = {};
    props.consumoPorSucursal.forEach(r => {
        const name = r.branch_name || 'Sin sucursal';
        if (!map[name]) map[name] = { name, consumo: 0, stock: 0 };
        map[name].consumo += Number(r.amount || 0);
    });
    props.stockValorizado.forEach(r => {
        const name = r.branch_name || 'Sin sucursal';
        if (!map[name]) map[name] = { name, consumo: 0, stock: 0 };
        map[name].stock += Number(r.valor || 0);
    });
    return Object.values(map)
        .map(b => ({ ...b, total: b.consumo + b.stock }))
        .sort((a, b) => b.total - a.total);
});

const totalConsumo = computed(() => props.consumoPorSucursal.reduce((sum, r) => sum + Number(r.amount || 0), 0));
const totalStock = computed(() => props.stockValorizado.reduce((sum, r) => sum + Number(r.valor || 0), 0));
const totalGeneral = computed(() => totalConsumo.value + totalStock.value);

// Filtro de sucursal (local, ahora MÚLTIPLE) y selector de indicador para la tabla por nivel
const selectedBranches = ref([]); // array de nombres de sucursal
const tableMode = ref('consumo'); // 'consumo' | 'stock'

// Opciones del multiselect (usa el nombre como value, ver nota de filtrado por nombre más abajo)
// La sucursal viene del centro de costo asociado al consumo (no de la línea de factura).
const branchMultiselectOptions = computed(() => props.branches.map(b => ({ value: b.label, label: b.label })));

// Cuando hay sucursales seleccionadas, cada una se muestra como columna propia (no acumulada).
// Sin selección, se muestra una única columna "Total" con todas las sucursales sumadas.
const activeBranchColumns = computed(() => selectedBranches.value.length > 0 ? [...selectedBranches.value] : ['Total']);
// Solo se agrega una columna extra de Total agregado cuando hay 2+ sucursales elegidas (con 1 sola sería redundante).
const showAggregateTotal = computed(() => selectedBranches.value.length > 1);
const colSpanCount = computed(() => 2 + activeBranchColumns.value.length + (showAggregateTotal.value ? 1 : 0));

// Árbol Nivel1 -> Nivel2 -> Nivel3 según el indicador y sucursales seleccionadas, con monto por sucursal (byBranch)
const levelTree = computed(() => {
    const source = tableMode.value === 'consumo' ? props.consumoPorSucursal : normalizedStock.value;
    const multi = selectedBranches.value.length > 0;
    // Se filtra por nombre de sucursal (no por id) porque algunas salidas antiguas quedaron
    // asociadas al id de la sucursal de una temporada previa con el mismo nombre.
    const rows = multi
        ? source.filter(r => selectedBranches.value.includes(r.branch_name || 'Sin sucursal'))
        : source;

    const l1Map = {};
    rows.forEach(r => {
        const amount = Number(r.amount || 0);
        const branchCol = multi ? (r.branch_name || 'Sin sucursal') : 'Total';

        const l1Key = r.level1_id ?? 'null';
        if (!l1Map[l1Key]) {
            l1Map[l1Key] = { level1_id: r.level1_id, level1_name: r.level1_name || 'Sin Clasificar', total: 0, byBranch: {}, level2s: {} };
        }
        l1Map[l1Key].total += amount;
        l1Map[l1Key].byBranch[branchCol] = (l1Map[l1Key].byBranch[branchCol] || 0) + amount;

        const l2Key = r.level2_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key]) {
            l1Map[l1Key].level2s[l2Key] = { level2_id: r.level2_id, level2_name: r.level2_name || 'Sin Clasificar', total: 0, byBranch: {}, level3s: {} };
        }
        l1Map[l1Key].level2s[l2Key].total += amount;
        l1Map[l1Key].level2s[l2Key].byBranch[branchCol] = (l1Map[l1Key].level2s[l2Key].byBranch[branchCol] || 0) + amount;

        const l3Key = r.level3_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key].level3s[l3Key]) {
            l1Map[l1Key].level2s[l2Key].level3s[l3Key] = { level3_id: r.level3_id, level3_name: r.level3_name || 'Sin Clasificar', total: 0, byBranch: {} };
        }
        l1Map[l1Key].level2s[l2Key].level3s[l3Key].total += amount;
        l1Map[l1Key].level2s[l2Key].level3s[l3Key].byBranch[branchCol] = (l1Map[l1Key].level2s[l2Key].level3s[l3Key].byBranch[branchCol] || 0) + amount;
    });

    return Object.values(l1Map).map(g => ({
        ...g,
        level2s: Object.values(g.level2s).map(l2 => ({
            ...l2,
            level3s: Object.values(l2.level3s).sort((a, b) => b.total - a.total),
        })).sort((a, b) => b.total - a.total),
    })).sort((a, b) => b.total - a.total);
});

const levelTreeGrandTotal = computed(() => levelTree.value.reduce((sum, g) => sum + g.total, 0));

// Totales por columna de sucursal para la fila de Total (tfoot)
const levelTreeBranchTotals = computed(() => {
    const totals = {};
    activeBranchColumns.value.forEach(col => { totals[col] = 0; });
    levelTree.value.forEach(g => {
        activeBranchColumns.value.forEach(col => {
            totals[col] += (g.byBranch[col] || 0);
        });
    });
    return totals;
});

// Control de expandir/colapsar (Nivel1 -> Nivel2 y Nivel2 -> Nivel3)
const expandedL1 = ref(new Set());
const expandedL2 = ref(new Set());

const toggleL1 = (key) => {
    if (expandedL1.value.has(key)) expandedL1.value.delete(key);
    else expandedL1.value.add(key);
    expandedL1.value = new Set(expandedL1.value);
};
const toggleL2 = (key) => {
    if (expandedL2.value.has(key)) expandedL2.value.delete(key);
    else expandedL2.value.add(key);
    expandedL2.value = new Set(expandedL2.value);
};
const expandAllLevels = () => {
    expandedL1.value = new Set(levelTree.value.map(g => 'l1-' + g.level1_id));
    const l2Keys = [];
    levelTree.value.forEach(g => g.level2s.forEach(l2 => l2Keys.push('l2-' + g.level1_id + '-' + l2.level2_id)));
    expandedL2.value = new Set(l2Keys);
};
const collapseAllLevels = () => {
    expandedL1.value = new Set();
    expandedL2.value = new Set();
};

// ── Consumo por Hectárea (misma sucursal del centro de costo que la tabla de montos) ──
const selectedBranchesHa = ref([]); // nombres de sucursal
const selectedDevStates = ref([]); // ids de estado de desarrollo

const branchMultiselectOptionsHa = computed(() => props.branches.map(b => ({ value: b.label, label: b.label })));
const devStateMultiselectOptions = computed(() => props.developmentStates);

const activeBranchColumnsHa = computed(() => selectedBranchesHa.value.length > 0 ? [...selectedBranchesHa.value] : ['Total']);
const showAggregateTotalHa = computed(() => selectedBranchesHa.value.length > 1);
const colSpanCountHa = computed(() => 2 + activeBranchColumnsHa.value.length + (showAggregateTotalHa.value ? 1 : 0));

// Filtra las filas de consumo según sucursal(es) y estado(s) de desarrollo elegidos
const haFilteredAmountRows = computed(() => {
    let rows = props.consumoPorHectarea;
    if (selectedDevStates.value.length > 0) {
        const selected = selectedDevStates.value.map(String);
        rows = rows.filter(r => selected.includes(String(r.development_state_id)));
    }
    if (selectedBranchesHa.value.length > 0) {
        rows = rows.filter(r => selectedBranchesHa.value.includes(r.branch_name || 'Sin sucursal'));
    }
    return rows;
});

// Superficie (denominador del $/ha) por columna de sucursal, con los mismos filtros aplicados
const haSurfaceByBranch = computed(() => {
    let rows = props.superficiePorSucursal;
    if (selectedDevStates.value.length > 0) {
        const selected = selectedDevStates.value.map(String);
        rows = rows.filter(r => selected.includes(String(r.development_state_id)));
    }
    const multi = selectedBranchesHa.value.length > 0;
    if (multi) {
        rows = rows.filter(r => selectedBranchesHa.value.includes(r.branch_name || 'Sin sucursal'));
    }
    const map = {};
    rows.forEach(r => {
        const col = multi ? (r.branch_name || 'Sin sucursal') : 'Total';
        map[col] = (map[col] || 0) + Number(r.surface || 0);
    });
    return map;
});

// Superficie total (suma de las columnas visibles), usada para la columna agregada "Total"
const haSurfaceGrandTotal = computed(() => Object.values(haSurfaceByBranch.value).reduce((sum, v) => sum + v, 0));

// $/ha para una columna dada (evita división por cero)
const perHa = (amount, col) => {
    const surface = haSurfaceByBranch.value[col] || 0;
    return surface > 0 ? amount / surface : 0;
};

// $/ha para la columna agregada "Total" (divide por la superficie de todas las sucursales visibles)
const perHaTotal = (amount) => {
    return haSurfaceGrandTotal.value > 0 ? amount / haSurfaceGrandTotal.value : 0;
};

// Árbol Nivel1 -> Nivel2 -> Nivel3 con monto (aún en $) por sucursal; la división por ha se hace al renderizar
const levelTreeHa = computed(() => {
    const multi = selectedBranchesHa.value.length > 0;
    const l1Map = {};
    haFilteredAmountRows.value.forEach(r => {
        const amount = Number(r.amount || 0);
        const branchCol = multi ? (r.branch_name || 'Sin sucursal') : 'Total';

        const l1Key = r.level1_id ?? 'null';
        if (!l1Map[l1Key]) {
            l1Map[l1Key] = { level1_id: r.level1_id, level1_name: r.level1_name || 'Sin Clasificar', total: 0, byBranch: {}, level2s: {} };
        }
        l1Map[l1Key].total += amount;
        l1Map[l1Key].byBranch[branchCol] = (l1Map[l1Key].byBranch[branchCol] || 0) + amount;

        const l2Key = r.level2_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key]) {
            l1Map[l1Key].level2s[l2Key] = { level2_id: r.level2_id, level2_name: r.level2_name || 'Sin Clasificar', total: 0, byBranch: {}, level3s: {} };
        }
        l1Map[l1Key].level2s[l2Key].total += amount;
        l1Map[l1Key].level2s[l2Key].byBranch[branchCol] = (l1Map[l1Key].level2s[l2Key].byBranch[branchCol] || 0) + amount;

        const l3Key = r.level3_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key].level3s[l3Key]) {
            l1Map[l1Key].level2s[l2Key].level3s[l3Key] = { level3_id: r.level3_id, level3_name: r.level3_name || 'Sin Clasificar', total: 0, byBranch: {} };
        }
        l1Map[l1Key].level2s[l2Key].level3s[l3Key].total += amount;
        l1Map[l1Key].level2s[l2Key].level3s[l3Key].byBranch[branchCol] = (l1Map[l1Key].level2s[l2Key].level3s[l3Key].byBranch[branchCol] || 0) + amount;
    });

    return Object.values(l1Map).map(g => ({
        ...g,
        level2s: Object.values(g.level2s).map(l2 => ({
            ...l2,
            level3s: Object.values(l2.level3s).sort((a, b) => b.total - a.total),
        })).sort((a, b) => b.total - a.total),
    })).sort((a, b) => b.total - a.total);
});

const levelTreeHaGrandTotal = computed(() => levelTreeHa.value.reduce((sum, g) => sum + g.total, 0));

const levelTreeHaBranchTotals = computed(() => {
    const totals = {};
    activeBranchColumnsHa.value.forEach(col => { totals[col] = 0; });
    levelTreeHa.value.forEach(g => {
        activeBranchColumnsHa.value.forEach(col => {
            totals[col] += (g.byBranch[col] || 0);
        });
    });
    return totals;
});

// Control de expandir/colapsar independiente del de la tabla de sucursales
const expandedL1Ha = ref(new Set());
const expandedL2Ha = ref(new Set());

const toggleL1Ha = (key) => {
    if (expandedL1Ha.value.has(key)) expandedL1Ha.value.delete(key);
    else expandedL1Ha.value.add(key);
    expandedL1Ha.value = new Set(expandedL1Ha.value);
};
const toggleL2Ha = (key) => {
    if (expandedL2Ha.value.has(key)) expandedL2Ha.value.delete(key);
    else expandedL2Ha.value.add(key);
    expandedL2Ha.value = new Set(expandedL2Ha.value);
};
const expandAllLevelsHa = () => {
    expandedL1Ha.value = new Set(levelTreeHa.value.map(g => 'l1-' + g.level1_id));
    const l2Keys = [];
    levelTreeHa.value.forEach(g => g.level2s.forEach(l2 => l2Keys.push('l2-' + g.level1_id + '-' + l2.level2_id)));
    expandedL2Ha.value = new Set(l2Keys);
};
const collapseAllLevelsHa = () => {
    expandedL1Ha.value = new Set();
    expandedL2Ha.value = new Set();
};
</script>

<template>
    <Head :title="title" />
    <AppLayout title="Detalle de Salidas por Sucursal">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-code-branch me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Cards apiladas por sucursal: Consumo + Stock Valorizado -->
                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3" v-for="b in branchTotals" :key="b.name">
                        <div class="card h-100 border border-primary">
                            <div class="card-header py-2 bg-primary bg-opacity-10">
                                <h6 class="mb-0 text-primary fw-bold fs-10 text-nowrap"><i class="fas fa-code-branch me-1"></i>{{ b.name }}</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <small class="text-muted">Consumo</small>
                                    <strong>{{ formatNumber(b.consumo) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted">Stock Valorizado</small>
                                    <strong>{{ formatNumber(b.stock) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted fw-semibold">Total</small>
                                    <strong class="text-primary">{{ formatNumber(b.total) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                        <div class="card h-100 border border-success">
                            <div class="card-header py-2 bg-success bg-opacity-10">
                                <h6 class="mb-0 text-success fw-bold fs-10 text-nowrap"><i class="fas fa-layer-group me-1"></i>Totales Generales</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <small class="text-muted">Total Consumo</small>
                                    <strong>{{ formatNumber(totalConsumo) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted">Total Stock Valorizado</small>
                                    <strong>{{ formatNumber(totalStock) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted fw-semibold">Total General</small>
                                    <strong class="text-success">{{ formatNumber(totalGeneral) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros para la tabla por nivel -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <label class="form-label mb-0 small fw-semibold text-muted">Sucursales:</label>
                    <div style="min-width: 300px; max-width: 480px; flex: 1 1 350px;">
                        <Multiselect
                            v-model="selectedBranches"
                            :options="branchMultiselectOptions"
                            mode="multiple"
                            :searchable="true"
                            :close-on-select="false"
                            :hide-selected="false"
                            :multipleLabel="(vals) => vals.length ? vals.map(v => v.label).join(', ') : 'Todas las sucursales (acumulado)'"
                            placeholder="Todas las sucursales (acumulado)"
                            no-options-text="Sin opciones"
                            no-results-text="Sin resultados"
                            class="multiselect-sm"
                            :style="{'--ms-min-h': '1.9rem', '--ms-py': '0.25rem', '--ms-font-size': '0.78rem'}"
                        />
                    </div>

                    <div class="segmented-control ms-2">
                        <button type="button" class="segmented-option" :class="{ active: tableMode === 'consumo' }" @click="tableMode = 'consumo'">
                            <i class="fas fa-arrow-circle-down me-1"></i>Consumo
                        </button>
                        <button type="button" class="segmented-option" :class="{ active: tableMode === 'stock' }" @click="tableMode = 'stock'">
                            <i class="fas fa-boxes-stacked me-1"></i>Stock Valorizado
                        </button>
                    </div>

                    <div class="btn-group btn-group-sm ms-auto" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllLevels" v-tooltip="'Expandir todo'">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllLevels" v-tooltip="'Colapsar todo'">
                            <i class="fas fa-compress-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabla Nivel 1 / Nivel 2 / Nivel 3 -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-primary">
                            <tr>
                                <th class="border-0 py-2">Nivel 1 / Nivel 2 / Nivel 3</th>
                                <th class="border-0 py-2 text-end" v-for="col in activeBranchColumns" :key="'head-' + col">{{ col }}</th>
                                <th class="border-0 py-2 text-end" v-if="showAggregateTotal">Total</th>
                                <th class="border-0 py-2 text-end">% Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!levelTree.length">
                                <td :colspan="colSpanCount" class="text-center py-4 text-muted">No hay datos para mostrar</td>
                            </tr>
                            <template v-for="g in levelTree" :key="'l1-' + g.level1_id">
                                <tr class="table-light" style="cursor:pointer;" @click="toggleL1('l1-' + g.level1_id)">
                                    <td class="py-2 fw-bold text-primary">
                                        <i class="fas me-2" :class="expandedL1.has('l1-' + g.level1_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ g.level1_name }}
                                        <small class="text-muted ms-1">({{ g.level2s.length }})</small>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-primary" v-for="col in activeBranchColumns" :key="'l1c-' + g.level1_id + '-' + col">{{ formatNumber(g.byBranch[col] || 0) }}</td>
                                    <td class="py-2 text-end fw-bold text-primary" v-if="showAggregateTotal">{{ formatNumber(g.total) }}</td>
                                    <td class="py-2 text-end">
                                        <span class="badge bg-primary">{{ levelTreeGrandTotal > 0 ? ((g.total / levelTreeGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                                    </td>
                                </tr>
                                <template v-if="expandedL1.has('l1-' + g.level1_id)">
                                    <template v-for="l2 in g.level2s" :key="'l2-' + g.level1_id + '-' + l2.level2_id">
                                        <tr style="cursor:pointer;" @click="toggleL2('l2-' + g.level1_id + '-' + l2.level2_id)">
                                            <td class="py-2 ps-5">
                                                <i class="fas me-2" :class="expandedL2.has('l2-' + g.level1_id + '-' + l2.level2_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                {{ l2.level2_name }}
                                                <small class="text-muted ms-1">({{ l2.level3s.length }})</small>
                                            </td>
                                            <td class="py-2 text-end" v-for="col in activeBranchColumns" :key="'l2c-' + g.level1_id + '-' + l2.level2_id + '-' + col">{{ formatNumber(l2.byBranch[col] || 0) }}</td>
                                            <td class="py-2 text-end" v-if="showAggregateTotal">{{ formatNumber(l2.total) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-secondary">{{ g.total > 0 ? ((l2.total / g.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                        <tr v-if="expandedL2.has('l2-' + g.level1_id + '-' + l2.level2_id)" v-for="l3 in l2.level3s" :key="'l3-' + g.level1_id + '-' + l2.level2_id + '-' + l3.level3_id">
                                            <td class="py-2 ps-7">{{ l3.level3_name }}</td>
                                            <td class="py-2 text-end" v-for="col in activeBranchColumns" :key="'l3c-' + g.level1_id + '-' + l2.level2_id + '-' + l3.level3_id + '-' + col">{{ formatNumber(l3.byBranch[col] || 0) }}</td>
                                            <td class="py-2 text-end" v-if="showAggregateTotal">{{ formatNumber(l3.total) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-light text-dark">{{ l2.total > 0 ? ((l3.total / l2.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                        <tfoot v-if="levelTree.length">
                            <tr class="table-primary fw-bold">
                                <td class="py-2">Total</td>
                                <td class="py-2 text-end" v-for="col in activeBranchColumns" :key="'footc-' + col">{{ formatNumber(levelTreeBranchTotals[col] || 0) }}</td>
                                <td class="py-2 text-end" v-if="showAggregateTotal">{{ formatNumber(levelTreeGrandTotal) }}</td>
                                <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Consumo por Hectárea: misma sucursal del centro de costo que la tabla de montos, con filtro de estado de desarrollo -->
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-12 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-ruler-combined me-2"></i>Consumo por Hectárea
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <label class="form-label mb-0 small fw-semibold text-muted">Sucursales:</label>
                    <div style="min-width: 260px; max-width: 420px; flex: 1 1 300px;">
                        <Multiselect
                            v-model="selectedBranchesHa"
                            :options="branchMultiselectOptionsHa"
                            mode="multiple"
                            :searchable="true"
                            :close-on-select="false"
                            :hide-selected="false"
                            :multipleLabel="(vals) => vals.length ? vals.map(v => v.label).join(', ') : 'Todas las sucursales (acumulado)'"
                            placeholder="Todas las sucursales (acumulado)"
                            no-options-text="Sin opciones"
                            no-results-text="Sin resultados"
                            class="multiselect-sm"
                            :style="{'--ms-min-h': '1.9rem', '--ms-py': '0.25rem', '--ms-font-size': '0.78rem'}"
                        />
                    </div>

                    <label class="form-label mb-0 small fw-semibold text-muted">Estado de desarrollo:</label>
                    <div style="min-width: 220px; max-width: 380px; flex: 1 1 260px;">
                        <Multiselect
                            v-model="selectedDevStates"
                            :options="devStateMultiselectOptions"
                            mode="multiple"
                            :searchable="true"
                            :close-on-select="false"
                            :hide-selected="false"
                            :multipleLabel="(vals) => vals.length ? vals.map(v => v.label).join(', ') : 'Todos los estados'"
                            placeholder="Todos los estados"
                            no-options-text="Sin opciones"
                            no-results-text="Sin resultados"
                            class="multiselect-sm"
                            :style="{'--ms-min-h': '1.9rem', '--ms-py': '0.25rem', '--ms-font-size': '0.78rem'}"
                        />
                    </div>

                    <div class="btn-group btn-group-sm ms-auto" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllLevelsHa" v-tooltip="'Expandir todo'">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllLevelsHa" v-tooltip="'Colapsar todo'">
                            <i class="fas fa-compress-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Fila de superficie base (denominador) por columna -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small text-muted">
                    <span class="fw-semibold">Superficie considerada:</span>
                    <span v-for="col in activeBranchColumnsHa" :key="'ha-surf-' + col">
                        {{ col }}: <strong>{{ formatHa(haSurfaceByBranch[col] || 0) }} ha</strong>
                    </span>
                </div>

                <!-- Tabla Nivel 1 / Nivel 2 / Nivel 3 en $/ha -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-primary">
                            <tr>
                                <th class="border-0 py-2">Nivel 1 / Nivel 2 / Nivel 3</th>
                                <th class="border-0 py-2 text-end" v-for="col in activeBranchColumnsHa" :key="'ha-head-' + col">{{ col === 'Total' ? 'Promedio' : col }}</th>
                                <th class="border-0 py-2 text-end" v-if="showAggregateTotalHa" v-tooltip="'Promedio ponderado: monto total \u00f7 superficie total de las sucursales seleccionadas'">Promedio</th>
                                <th class="border-0 py-2 text-end">% Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!levelTreeHa.length">
                                <td :colspan="colSpanCountHa" class="text-center py-4 text-muted">No hay datos para mostrar</td>
                            </tr>
                            <template v-for="g in levelTreeHa" :key="'ha-l1-' + g.level1_id">
                                <tr class="table-light" style="cursor:pointer;" @click="toggleL1Ha('l1-' + g.level1_id)">
                                    <td class="py-2 fw-bold text-primary">
                                        <i class="fas me-2" :class="expandedL1Ha.has('l1-' + g.level1_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ g.level1_name }}
                                        <small class="text-muted ms-1">({{ g.level2s.length }})</small>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-primary" v-for="col in activeBranchColumnsHa" :key="'ha-l1c-' + g.level1_id + '-' + col">{{ formatNumber(perHa(g.byBranch[col] || 0, col)) }}</td>
                                    <td class="py-2 text-end fw-bold text-primary" v-if="showAggregateTotalHa">{{ formatNumber(perHaTotal(g.total)) }}</td>
                                    <td class="py-2 text-end">
                                        <span class="badge bg-primary">{{ levelTreeHaGrandTotal > 0 ? ((g.total / levelTreeHaGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                                    </td>
                                </tr>
                                <template v-if="expandedL1Ha.has('l1-' + g.level1_id)">
                                    <template v-for="l2 in g.level2s" :key="'ha-l2-' + g.level1_id + '-' + l2.level2_id">
                                        <tr style="cursor:pointer;" @click="toggleL2Ha('l2-' + g.level1_id + '-' + l2.level2_id)">
                                            <td class="py-2 ps-5">
                                                <i class="fas me-2" :class="expandedL2Ha.has('l2-' + g.level1_id + '-' + l2.level2_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                {{ l2.level2_name }}
                                                <small class="text-muted ms-1">({{ l2.level3s.length }})</small>
                                            </td>
                                            <td class="py-2 text-end" v-for="col in activeBranchColumnsHa" :key="'ha-l2c-' + g.level1_id + '-' + l2.level2_id + '-' + col">{{ formatNumber(perHa(l2.byBranch[col] || 0, col)) }}</td>
                                            <td class="py-2 text-end" v-if="showAggregateTotalHa">{{ formatNumber(perHaTotal(l2.total)) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-secondary">{{ g.total > 0 ? ((l2.total / g.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                        <tr v-if="expandedL2Ha.has('l2-' + g.level1_id + '-' + l2.level2_id)" v-for="l3 in l2.level3s" :key="'ha-l3-' + g.level1_id + '-' + l2.level2_id + '-' + l3.level3_id">
                                            <td class="py-2 ps-7">{{ l3.level3_name }}</td>
                                            <td class="py-2 text-end" v-for="col in activeBranchColumnsHa" :key="'ha-l3c-' + g.level1_id + '-' + l2.level2_id + '-' + l3.level3_id + '-' + col">{{ formatNumber(perHa(l3.byBranch[col] || 0, col)) }}</td>
                                            <td class="py-2 text-end" v-if="showAggregateTotalHa">{{ formatNumber(perHaTotal(l3.total)) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-light text-dark">{{ l2.total > 0 ? ((l3.total / l2.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                        <tfoot v-if="levelTreeHa.length">
                            <tr class="table-primary fw-bold">
                                <td class="py-2">Total</td>
                                <td class="py-2 text-end" v-for="col in activeBranchColumnsHa" :key="'ha-footc-' + col">{{ formatNumber(perHa(levelTreeHaBranchTotals[col] || 0, col)) }}</td>
                                <td class="py-2 text-end" v-if="showAggregateTotalHa">{{ formatNumber(perHaTotal(levelTreeHaGrandTotal)) }}</td>
                                <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <small class="text-muted d-block mt-1">Valores en $ por hectárea. La sucursal considerada es la del centro de costo (cuartel), la misma que usa la tabla de montos.</small>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.segmented-control {
    display: inline-flex;
    background: var(--bs-tertiary-bg, #edf2f9);
    border: 1px solid var(--bs-border-color, #e3e6ed);
    border-radius: 0.5rem;
    padding: 3px;
    gap: 2px;
}
.segmented-option {
    display: inline-flex;
    align-items: center;
    border: none;
    background: transparent;
    padding: 0.3rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 500;
    line-height: 1;
    color: var(--bs-secondary-color, #6c757d);
    border-radius: 0.4rem;
    white-space: nowrap;
    transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}
.segmented-option:hover:not(.active) {
    color: var(--bs-emphasis-color, #212529);
    background: rgba(0, 0, 0, 0.06);
}
.segmented-option.active {
    background: var(--bs-body-bg, #fff);
    color: var(--bs-primary, #2c7be5);
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
</style>
