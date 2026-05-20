<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    dollarPrice: Number,
    isAdmin: Boolean,
    adminShares: Array,
    fruits: Array,
    developmentStates: Array,
    varieties: Array,
    income: Object,
    costs: Array,
    surfaces: Array,
});

// ── Filtros ──
const selectedFruitId = ref('');
const includeInvestments = ref(false);



// ── Estados de desarrollo (patrón OutflowsDashboard) ──
const normalize = (str) => str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

const incluirAdmin = ref(true);
const selectedExtraStates = ref({});

// Producción = siempre incluido
const prodState = computed(() => props.developmentStates.find(s => normalize(s.label).includes('produccion')));
const adminState = computed(() => props.developmentStates.find(s => normalize(s.label).includes('administracion')));
const extraStates = computed(() => props.developmentStates.filter(s => {
    const n = normalize(s.label);
    return !n.includes('produccion') && !n.includes('administracion');
}));

// IDs activos para filtrar
const activeDevStateIds = computed(() => {
    const ids = [];
    if (prodState.value) ids.push(prodState.value.value);
    if (incluirAdmin.value && adminState.value) ids.push(adminState.value.value);
    extraStates.value.forEach(s => {
        if (selectedExtraStates.value[s.value]) ids.push(s.value);
    });
    return ids;
});

// ── Toggle moneda (mismo patrón que InvestmentDashboard) ──
const divisor = ref(props.dollarPrice);
const divisorMin = 800;
const divisorMax = 1300;
const dividir = ref(true);
const savingDollar = ref(false);

const showUSD = computed(() => dividir.value);
const convertIncome = (usdVal) => dividir.value ? usdVal : usdVal * divisor.value;
const convertCost = (clpVal) => dividir.value ? clpVal / divisor.value : clpVal;
const currencyLabel = computed(() => dividir.value ? 'USD' : 'CLP');
const currencyPrefix = computed(() => dividir.value ? 'USD ' : '$ ');

const formatMoney = (val) => {
    const abs = Math.abs(val);
    const formatted = abs.toLocaleString('es-CL', {
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    });
    return val < 0 ? `-${formatted}` : formatted;
};

const saveDollarPrice = async () => {
    if (!props.isAdmin) return;
    savingDollar.value = true;
    try {
        await axios.patch(route('api.dollar-price.update'), { dollar_price: divisor.value });
        Swal.fire({ icon: 'success', title: 'Precio dólar actualizado', showConfirmButton: false, timer: 1000 });
    } catch {
        Swal.fire({ icon: 'error', title: 'Error al guardar' });
    } finally {
        savingDollar.value = false;
    }
};

// ── Datos filtrados por estado de desarrollo (String() para compatibilidad MySQL producción) ──
const filteredSurfaces = computed(() => {
    return props.surfaces.filter(s => activeDevStateIds.value.map(String).includes(String(s.development_state_id)));
});

const filteredCosts = computed(() => {
    return props.costs.filter(c => {
        if (c.is_admin && (!incluirAdmin.value || !adminState.value)) return false;
        return activeDevStateIds.value.map(String).includes(String(c.development_state_id));
    });
});

// ── Construir filas por variedad (TODAS las frutas) ──
const allRows = computed(() => {
    const surfaceMap = {};
    filteredSurfaces.value.forEach(s => {
        if (!surfaceMap[s.variety_id]) surfaceMap[s.variety_id] = 0;
        surfaceMap[s.variety_id] += s.surface;
    });

    const costMap = {};
    filteredCosts.value.forEach(c => {
        if (!costMap[c.variety_id]) costMap[c.variety_id] = { cost_total: 0, cost_no_inv: 0 };
        costMap[c.variety_id].cost_total += c.cost_total;
        costMap[c.variety_id].cost_no_inv += c.cost_no_inv;
    });

    // Variedades visibles: solo las que tienen superficie en los dev states activos
    const visibleVarIds = new Set();
    filteredSurfaces.value.forEach(s => visibleVarIds.add(String(s.variety_id)));

    return props.varieties
        .filter(v => visibleVarIds.has(String(v.id)))
        .map(v => {
            const vId = String(v.id);
            const inc = props.income[vId] || props.income[v.id] || {};
            const surface = surfaceMap[vId] || surfaceMap[v.id] || 0;
            const costClp = includeInvestments.value
                ? (costMap[vId]?.cost_total || costMap[v.id]?.cost_total || 0)
                : (costMap[vId]?.cost_no_inv || costMap[v.id]?.cost_no_inv || 0);

            const incomeUsd = inc.income_usd || 0;
            const commercialCostUsd = inc.commercial_cost_usd || 0;
            const displayInc = convertIncome(incomeUsd);
            const displayCommercial = convertIncome(commercialCostUsd);
            const displayCst = convertCost(costClp);
            const profit = displayInc - displayCommercial - displayCst;
            const margin = displayInc > 0 ? (profit / displayInc) * 100 : (profit < 0 ? -100 : 0);

            return {
                variety_id: v.id,
                variety_name: v.name,
                fruit_id: v.fruit_id,
                fruit_name: v.fruit_name,
                surface,
                kg_harvested: inc.kg_harvested || 0,
                kg_exported: inc.kg_exported || 0,
                commercial_kg: inc.commercial_kg || 0,
                income_usd: incomeUsd,
                commercial_cost_usd: commercialCostUsd,
                cost_clp: costClp,
                income: displayInc,
                commercial_cost: displayCommercial,
                cost: displayCst,
                profit,
                margin,
            };
        })
        .sort((a, b) => b.profit - a.profit);
});

// ── Resumen por especie (macro) ──
const fruitSummary = computed(() => {
    const map = {};
    allRows.value.forEach(r => {
        const key = r.fruit_id;
        if (!map[key]) {
            map[key] = {
                fruit_id: r.fruit_id,
                fruit_name: r.fruit_name,
                surface: 0, kg_harvested: 0, kg_exported: 0,
                income: 0, commercial_cost: 0, cost: 0,
            };
        }
        map[key].surface += r.surface;
        map[key].kg_harvested += r.kg_harvested;
        map[key].kg_exported += r.kg_exported;
        map[key].income += r.income;
        map[key].commercial_cost += r.commercial_cost;
        map[key].cost += r.cost;
    });
    return Object.values(map).map(f => ({
        ...f,
        profit: f.income - f.commercial_cost - f.cost,
        margin: f.income > 0 ? ((f.income - f.commercial_cost - f.cost) / f.income) * 100 : 0,
    })).sort((a, b) => b.profit - a.profit);
});

// ── Detalle por variedad (filtrado por fruta) ──
const detailRows = computed(() => allRows.value);

// ── KPIs (total de TODAS las frutas) ──
const totalIncome = computed(() => allRows.value.reduce((s, r) => s + r.income, 0));
const totalCost = computed(() => allRows.value.reduce((s, r) => s + r.cost, 0));
const totalProfit = computed(() => totalIncome.value - totalCommercialCost.value - totalCost.value);
const totalMargin = computed(() => totalIncome.value > 0 ? (totalProfit.value / totalIncome.value) * 100 : 0);
const totalSurface = computed(() => allRows.value.reduce((s, r) => s + r.surface, 0));
const plMargin = computed(() => totalIncome.value > 0 ? (plProfit.value / totalIncome.value) * 100 : 0);

// ── Resumen P&L: costos desglosados por tipo de dev state (solo variedades visibles) ──
const visibleVarietyIds = computed(() => new Set(allRows.value.map(r => String(r.variety_id))));

const costByDevType = (devStateId, includeAdmin = false) => {
    const filtered = props.costs.filter(c =>
        String(c.development_state_id) === String(devStateId)
        && visibleVarietyIds.value.has(String(c.variety_id))
        && (includeAdmin || !c.is_admin)
    );
    let total = 0;
    filtered.forEach(c => {
        total += includeInvestments.value ? c.cost_total : c.cost_no_inv;
    });
    return total;
};

const totalCostProduccion = computed(() => {
    if (!prodState.value) return 0;
    return convertCost(costByDevType(prodState.value.value));
});

const totalCostAdmin = computed(() => {
    if (!adminState.value || !incluirAdmin.value || !props.adminShares?.length) return 0;
    // Sumar admin_shares de los dev_states activos (sin admin mismo)
    const activeNonAdmin = activeDevStateIds.value.filter(id => String(id) !== String(adminState.value.value));
    let total = 0;
    props.adminShares.forEach(s => {
        if (activeNonAdmin.map(String).includes(String(s.development_state_id))) {
            total += s.admin_share;
        }
    });
    return convertCost(total);
});

const totalCostExtras = computed(() => {
    let sum = 0;
    extraStates.value.forEach(s => {
        if (selectedExtraStates.value[s.value]) {
            sum += costByDevType(s.value);
        }
    });
    return convertCost(sum);
});

const totalKgExported = computed(() => allRows.value.reduce((s, r) => s + r.kg_exported, 0));
const totalKgHarvested = computed(() => allRows.value.reduce((s, r) => s + r.kg_harvested, 0));
const totalCommercialCost = computed(() => allRows.value.reduce((s, r) => s + r.commercial_cost, 0));
const plIncome = computed(() => totalIncome.value);
const plTotalCost = computed(() => totalCostProduccion.value + totalCostAdmin.value + totalCostExtras.value);
const plNetAfterCommercial = computed(() => plIncome.value - totalCommercialCost.value);
const plProfit = computed(() => plIncome.value - totalCommercialCost.value - plTotalCost.value);
const plCostPerKg = computed(() => totalKgHarvested.value > 0 ? (totalCostProduccion.value + totalCostAdmin.value) / totalKgHarvested.value : 0);
const plIncomePerKg = computed(() => totalKgHarvested.value > 0 ? plIncome.value / totalKgHarvested.value : 0);
const plCommercialPerKg = computed(() => totalKgHarvested.value > 0 ? totalCommercialCost.value / totalKgHarvested.value : 0);
const plProfitPerKg = computed(() => plIncomePerKg.value - plCommercialPerKg.value - plCostPerKg.value);

// ── Excel ──
const excelHeadersFruit = computed(() => [
    { label: 'Especie', key: 'fruit_name' },
    { label: 'Superficie (ha)', key: 'surface', type: 'number' },
    { label: 'Kg Cosechados', key: 'kg_harvested', type: 'number' },
    { label: 'Kg Exportados', key: 'kg_exported', type: 'number' },
    { label: `Ingreso (${currencyLabel.value})`, key: 'income', type: 'number' },
    { label: `Costo (${currencyLabel.value})`, key: 'cost', type: 'number' },
    { label: `Utilidad (${currencyLabel.value})`, key: 'profit', type: 'number' },
    { label: 'Margen %', key: 'margin', type: 'number' },
]);

const excelDataFruit = computed(() => fruitSummary.value.map(r => ({
    fruit_name: r.fruit_name,
    surface: r.surface,
    kg_harvested: r.kg_harvested,
    kg_exported: r.kg_exported,
    income: Math.round(r.income),
    cost: Math.round(r.cost),
    profit: Math.round(r.profit),
    margin: Math.round(r.margin * 10) / 10,
})));

const excelHeadersDetail = computed(() => [
    { label: 'Variedad', key: 'variety_name' },
    { label: 'Especie', key: 'fruit_name' },
    { label: 'Superficie (ha)', key: 'surface', type: 'number' },
    { label: 'Kg Cosechados', key: 'kg_harvested', type: 'number' },
    { label: 'Kg Exportados', key: 'kg_exported', type: 'number' },
    { label: `Ingreso (${currencyLabel.value})`, key: 'income', type: 'number' },
    { label: `Costo (${currencyLabel.value})`, key: 'cost', type: 'number' },
    { label: `Utilidad (${currencyLabel.value})`, key: 'profit', type: 'number' },
    { label: 'Margen %', key: 'margin', type: 'number' },
]);

const excelDataDetail = computed(() => detailRows.value.map(r => ({
    variety_name: r.variety_name,
    fruit_name: r.fruit_name,
    surface: r.surface,
    kg_harvested: r.kg_harvested,
    kg_exported: r.kg_exported,
    income: Math.round(r.income),
    cost: Math.round(r.cost),
    profit: Math.round(r.profit),
    margin: Math.round(r.margin * 10) / 10,
})));
// Chevron rotation for collapse
const setupCollapseChevron = () => {
    nextTick(() => {
        const el = document.getElementById('detailVarietyCollapse');
        const chevron = document.getElementById('detailChevron');
        if (el && chevron) {
            el.addEventListener('show.bs.collapse', () => { chevron.style.transform = 'rotate(90deg)'; });
            el.addEventListener('hide.bs.collapse', () => { chevron.style.transform = 'rotate(0deg)'; });
        }
    });
};
onMounted(setupCollapseChevron);
watch(allRows, () => setupCollapseChevron());
</script>

<template>
    <AppLayout title="Análisis de Utilidad / Pérdida">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-balance-scale me-2"></i>Análisis de Utilidad / Pérdida
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <!-- Toggle USD -->
                            <div class="form-check form-switch mb-0 d-flex align-items-center">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="dividir-switch"
                                    v-model="dividir"
                                >
                                <label class="form-check-label ms-2 mt-0 mb-0 small" for="dividir-switch">Ver en USD</label>
                            </div>
                            <!-- Slider divisor (visible solo cuando dividir está activo) -->
                            <div v-if="dividir" class="d-flex align-items-center gap-2">
                                <div class="vr d-none d-md-block" style="height: 24px;"></div>
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <span class="fw-bold small"><i class="fas fa-layer-group me-1"></i>Producción</span>

                    <!-- Checkbox Administración -->
                    <label v-if="adminState" class="d-flex align-items-center gap-1 mb-0 small text-muted border rounded px-2 py-0"
                           style="cursor:pointer;background:#f8f9fa;font-size:0.75rem;" title="Incluir costos de Administración">
                        <input class="form-check-input m-0" type="checkbox" v-model="incluirAdmin" style="cursor:pointer;">
                        + Admin
                    </label>

                    <!-- Checkboxes estados extra (Año 1, Año 2, etc.) -->
                    <label v-for="state in extraStates" :key="state.value"
                           class="d-flex align-items-center gap-1 mb-0 small text-muted border rounded px-2 py-0"
                           style="cursor:pointer;background:#f8f9fa;font-size:0.75rem;">
                        <input class="form-check-input m-0" type="checkbox"
                               v-model="selectedExtraStates[state.value]" style="cursor:pointer;">
                        + {{ state.label }}
                    </label>

                    <!-- Separador -->
                    <div class="vr" style="height: 20px;"></div>

                    <!-- Incluir inversiones -->
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="toggleInvestments" v-model="includeInvestments">
                        <label class="form-check-label small" for="toggleInvestments">Incluir inversiones</label>
                    </div>
                    </div>

                <!-- KPIs -->
                <div class="row mb-3 g-2">
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Superficie Total</div>
                                <div class="fs-7 fw-bold">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }} ha</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-success">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Total Ingresos</div>
                                <div class="fs-7 fw-bold text-success">{{ currencyPrefix }}{{ formatMoney(totalIncome) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-danger">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Costo Comercial</div>
                                <div class="fs-7 fw-bold text-danger">{{ currencyPrefix }}{{ formatMoney(totalCommercialCost) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border" :class="plNetAfterCommercial >= 0 ? 'border-success' : 'border-warning'">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Ingreso - Costo Comercial</div>
                                <div class="fs-7 fw-bold" :class="plNetAfterCommercial >= 0 ? 'text-success' : 'text-warning'">{{ currencyPrefix }}{{ formatMoney(plNetAfterCommercial) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-danger">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Costos Productivos
                                    <small class="text-muted">(Prod.{{ incluirAdmin && adminState ? ' + Admin' : '' }}{{ extraStates.filter(s => selectedExtraStates[s.value]).map(s => ' + ' + s.label).join('') }})</small>
                                </div>
                                <div class="fs-7 fw-bold text-danger">{{ currencyPrefix }}{{ formatMoney(plTotalCost) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1" :class="plProfit >= 0 ? 'border border-primary' : 'border border-warning'">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Utilidad / Pérdida</div>
                                <div class="fs-7 fw-bold" :class="plProfit >= 0 ? 'text-primary' : 'text-warning'">
                                    {{ currencyPrefix }}{{ formatMoney(plProfit) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Margen</div>
                                <div class="fs-7 fw-bold" :class="plMargin >= 0 ? 'text-success' : 'text-danger'">
                                    {{ plMargin.toFixed(1) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla resumen por especie (MACRO) -->
                <div v-if="fruitSummary.length > 0" class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-seedling me-2"></i>Resumen por Especie</h6>
                        <ExportExcelButton
                            :data="excelDataFruit"
                            :headers="excelHeadersFruit"
                            filename="utilidad_por_especie.xlsx"
                            class="btn btn-falcon-default btn-sm"
                        />
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Especie</th>
                                    <th class="text-end" style="width:110px">Sup. (ha)</th>
                                    <th class="text-end" style="width:120px">Kg Cosechados</th>
                                    <th class="text-end" style="width:120px">Kg Exportados</th>
                                    <th class="text-end" style="width:140px">Ingreso ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Costo Comerc. ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Costo ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Utilidad ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:90px">Margen %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in fruitSummary" :key="row.fruit_id"
                                    style="cursor: pointer;"
                                    @click="selectedFruitId = row.fruit_id"
                                    :class="{ 'table-active': String(selectedFruitId) === String(row.fruit_id) }">
                                    <td class="fw-semibold">
                                        <i class="fas fa-chevron-right fa-xs me-1 text-muted"></i>{{ row.fruit_name }}
                                    </td>
                                    <td class="text-end">{{ row.surface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }}</td>
                                    <td class="text-end">{{ row.kg_harvested.toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ row.kg_exported.toLocaleString('es-CL') }}</td>
                                    <td class="text-end text-success fw-bold">{{ currencyPrefix }}{{ formatMoney(row.income) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(row.commercial_cost) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(row.cost) }}</td>
                                    <td class="text-end fw-bold" :class="row.profit >= 0 ? 'text-primary' : 'text-warning'">
                                        {{ currencyPrefix }}{{ formatMoney(row.profit) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="badge" :class="row.margin >= 0 ? 'bg-success' : 'bg-danger'">
                                            {{ row.margin.toFixed(1) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light fw-bold" v-show="fruitSummary.length > 1">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }}</td>
                                    <td class="text-end">{{ allRows.reduce((s,r) => s + r.kg_harvested, 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ allRows.reduce((s,r) => s + r.kg_exported, 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end text-success">{{ currencyPrefix }}{{ formatMoney(totalIncome) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(totalCommercialCost) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(plTotalCost) }}</td>
                                    <td class="text-end" :class="plProfit >= 0 ? 'text-primary' : 'text-warning'">
                                        {{ currencyPrefix }}{{ formatMoney(plProfit) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="badge" :class="plMargin >= 0 ? 'bg-success' : 'bg-danger'">
                                            {{ plMargin.toFixed(1) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <small class="text-muted"><i class="fas fa-mouse-pointer me-1"></i>Haz clic en una especie para ver el detalle por variedad.</small>
                </div>

                <!-- Tabla detalle por variedad (MICRO) - colapsable -->
                <div v-if="detailRows.length > 0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0" style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#detailVarietyCollapse">
                            <i class="fas fa-chevron-right me-2 fa-xs" id="detailChevron"></i>
                            <i class="fas fa-list me-2"></i>Detalle por Variedad
                        </h6>
                        <ExportExcelButton
                            :data="excelDataDetail"
                            :headers="excelHeadersDetail"
                            filename="utilidad_por_variedad.xlsx"
                            class="btn btn-falcon-default btn-sm"
                        />
                    </div>
                    <div class="collapse" id="detailVarietyCollapse">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variedad</th>
                                    <th class="text-end" style="width:100px">Sup. (ha)</th>
                                    <th class="text-end" style="width:100px">Kilos/ha</th>
                                    <th class="text-end" style="width:120px">Kg Cosechados</th>
                                    <th class="text-end" style="width:120px">Kg Exportados</th>
                                    <th class="text-end" style="width:110px">Kg Comerciales</th>
                                    <th class="text-end" style="width:140px">Ingreso ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Costo Comerc. ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Costo ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:140px">Utilidad ({{ currencyLabel }})</th>
                                    <th class="text-end" style="width:90px">Margen %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in detailRows" :key="row.variety_id">
                                    <td class="fw-semibold">{{ row.variety_name }}</td>
                                    <td class="text-end">{{ row.surface ? row.surface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) : '-' }}</td>
                                    <td class="text-end">{{ row.surface > 0 && row.kg_harvested > 0 ? Math.round(row.kg_harvested / row.surface).toLocaleString('es-CL') : '-' }}</td>
                                    <td class="text-end">{{ row.kg_harvested ? row.kg_harvested.toLocaleString('es-CL') : '-' }}</td>
                                    <td class="text-end">{{ row.kg_exported ? row.kg_exported.toLocaleString('es-CL') : '-' }}</td>
                                    <td class="text-end">{{ row.commercial_kg ? row.commercial_kg.toLocaleString('es-CL') : '-' }}</td>
                                    <td class="text-end text-success fw-bold">
                                        <span v-if="row.income">{{ currencyPrefix }}{{ formatMoney(row.income) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-end text-danger">
                                        <span v-if="row.commercial_cost">{{ currencyPrefix }}{{ formatMoney(row.commercial_cost) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-end text-danger">
                                        <span v-if="row.cost">{{ currencyPrefix }}{{ formatMoney(row.cost) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-end fw-bold" :class="row.profit >= 0 ? 'text-primary' : 'text-warning'">
                                        {{ currencyPrefix }}{{ formatMoney(row.profit) }}
                                    </td>
                                    <td class="text-end">
                                        <span v-if="row.income" class="badge" :class="row.margin >= 0 ? 'bg-success' : 'bg-danger'">
                                            {{ row.margin.toFixed(1) }}%
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end">{{ detailRows.reduce((s,r) => s + r.surface, 0).toLocaleString('es-CL', { minimumFractionDigits: 2 }) }}</td>
                                    <td class="text-end">{{ (() => { const s = detailRows.reduce((a,r) => a + r.surface, 0); const k = detailRows.reduce((a,r) => a + r.kg_harvested, 0); return s > 0 ? Math.round(k / s).toLocaleString('es-CL') : '-'; })() }}</td>
                                    <td class="text-end">{{ detailRows.reduce((s,r) => s + r.kg_harvested, 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ detailRows.reduce((s,r) => s + r.kg_exported, 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ detailRows.reduce((s,r) => s + r.commercial_kg, 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end text-success">{{ currencyPrefix }}{{ formatMoney(detailRows.reduce((s,r) => s + r.income, 0)) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(detailRows.reduce((s,r) => s + r.commercial_cost, 0)) }}</td>
                                    <td class="text-end text-danger">{{ currencyPrefix }}{{ formatMoney(detailRows.reduce((s,r) => s + r.cost, 0)) }}</td>
                                    <td class="text-end" :class="detailRows.reduce((s,r) => s + r.profit, 0) >= 0 ? 'text-primary' : 'text-warning'">
                                        {{ currencyPrefix }}{{ formatMoney(detailRows.reduce((s,r) => s + r.profit, 0)) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    </div>
                </div>
                <div v-if="allRows.length > 0" class="d-flex justify-content-end mt-4 mb-3">
                    <div class="card border shadow-sm" style="min-width: 420px; max-width: 520px;">
                        <div class="card-header bg-light py-2 text-center">
                            <h6 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Resumen de Resultados ({{ currencyLabel }})</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 fs-10">
                                <tbody>
                                    <tr class="table-success">
                                        <td class="ps-3 fw-bold">Ingresos <small class="text-muted">(retorno exportación)</small></td>
                                        <td class="text-end pe-3 fw-bold text-success">{{ currencyPrefix }}{{ formatMoney(plIncome) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">(-) Costo Comercial <small class="text-muted">(fruta no exportada)</small></td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ formatMoney(totalCommercialCost) }}</td>
                                    </tr>
                                    <tr class="border-top" :class="plNetAfterCommercial >= 0 ? 'table-success' : 'table-warning'">
                                        <td class="ps-3 fw-semibold">= Ingreso - Costo Comercial</td>
                                        <td class="text-end pe-3 fw-semibold" :class="plNetAfterCommercial >= 0 ? 'text-success' : 'text-warning'">{{ currencyPrefix }}{{ formatMoney(plNetAfterCommercial) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">(-) Costos de Producción</td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ formatMoney(totalCostProduccion) }}</td>
                                    </tr>
                                    <tr v-if="incluirAdmin && adminState">
                                        <td class="ps-3">(-) Costos de Administración</td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ formatMoney(totalCostAdmin) }}</td>
                                    </tr>
                                    <tr v-for="state in extraStates.filter(s => selectedExtraStates[s.value])" :key="'pl-'+state.value">
                                        <td class="ps-3">(-) Costos {{ state.label }}</td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ formatMoney(convertCost(costByDevType(state.value))) }}</td>
                                    </tr>
                                    <tr class="border-top border-2" :class="plProfit >= 0 ? 'table-primary' : 'table-warning'">
                                        <td class="ps-3 fw-bold">= Utilidad Neta</td>
                                        <td class="text-end pe-3 fw-bold" :class="plProfit >= 0 ? 'text-primary' : 'text-warning'">
                                            {{ currencyPrefix }}{{ formatMoney(plProfit) }}
                                        </td>
                                    </tr>
                                    <tr class="table-light"><td colspan="2" class="py-1"></td></tr>
                                    <tr>
                                        <td class="ps-3">Kg Producidos</td>
                                        <td class="text-end pe-3">{{ totalKgHarvested.toLocaleString('es-CL') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">Kg Exportados</td>
                                        <td class="text-end pe-3">{{ totalKgExported.toLocaleString('es-CL') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">Ingreso Neto / kg</td>
                                        <td class="text-end pe-3 text-success">{{ currencyPrefix }}{{ plIncomePerKg.toLocaleString(dividir ? 'en-US' : 'es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">Costo Comercial / kg</td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ plCommercialPerKg.toLocaleString(dividir ? 'en-US' : 'es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3">Costo Productivo / kg</td>
                                        <td class="text-end pe-3 text-danger">{{ currencyPrefix }}{{ plCostPerKg.toLocaleString(dividir ? 'en-US' : 'es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                    </tr>
                                    <tr class="border-top fw-bold" :class="plProfitPerKg >= 0 ? '' : 'text-warning'">
                                        <td class="ps-3">Utilidad / kg</td>
                                        <td class="text-end pe-3" :class="plProfitPerKg >= 0 ? 'text-primary' : 'text-warning'">
                                            {{ currencyPrefix }}{{ plProfitPerKg.toLocaleString(dividir ? 'en-US' : 'es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sin datos -->
                <div v-if="allRows.length === 0" class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No hay datos para la temporada actual. Asegúrate de tener costos y producción ingresados.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.multiselect-sm {
    --ms-font-size: 0.8rem;
    --ms-line-height: 1.2;
    --ms-py: 0.25rem;
    --ms-px: 0.5rem;
    --ms-tag-font-size: 0.75rem;
    --ms-tag-py: 0.1rem;
    --ms-tag-px: 0.4rem;
    --ms-option-font-size: 0.8rem;
    --ms-option-py: 0.3rem;
}
#detailChevron {
    transition: transform 0.2s ease;
}
#detailVarietyCollapse.show ~ #detailChevron,
.collapsed #detailChevron {
    transform: rotate(0deg);
}
</style>


