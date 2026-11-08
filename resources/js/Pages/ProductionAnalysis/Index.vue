<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import { Chart, registerables } from 'chart.js';
import '@/chartTheme';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

Chart.register(...registerables);

const props = defineProps({
    fruits: { type: Array, default: () => [] },
    estimateStatuses: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const title = 'Análisis de Producción';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

// Sucursal y Razón Social son filtros independientes (multi-selección); vacío = todos, y se combinan con AND
const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })));
const reasonOptions = computed(() => props.companyReasons.map(c => ({ value: c.id, label: c.name })));
const selectedBranchIds = ref([]);
const selectedReasonIds = ref([]);

const orgFilteredRows = computed(() => {
    const branchIds = selectedBranchIds.value.map(String);
    const reasonIds = selectedReasonIds.value.map(String);
    return props.rows.filter(r =>
        (branchIds.length === 0 || branchIds.includes(String(r.branch_id))) &&
        (reasonIds.length === 0 || reasonIds.includes(String(r.company_reason_id)))
    );
});

// Solo especies que tienen variedades por cuartel con los filtros aplicados
const fruitOptions = computed(() => {
    const used = new Set(orgFilteredRows.value.map(r => String(r.fruit_id)));
    return props.fruits
        .filter(f => used.has(String(f.id)))
        .map(f => ({ value: f.id, label: f.name }));
});

const selectedFruitId = ref(fruitOptions.value.length ? fruitOptions.value[0].value : '');

// Si la especie elegida deja de existir al filtrar, se pasa a la primera disponible
watch(fruitOptions, (opts) => {
    if (!opts.some(f => String(f.value) === String(selectedFruitId.value))) {
        selectedFruitId.value = opts.length ? opts[0].value : '';
    }
});

const statusOptions = computed(() =>
    props.estimateStatuses
        .filter(s => String(s.fruit_id) === String(selectedFruitId.value))
        .map(s => ({ value: s.id, label: s.name }))
);

const selectedStatusId = ref(statusOptions.value.length ? statusOptions.value[0].value : '');

watch(selectedFruitId, () => {
    selectedStatusId.value = statusOptions.value.length ? statusOptions.value[0].value : '';
});

function num(val) {
    const n = Number(val);
    return isNaN(n) ? 0 : n;
}

function fmt(val) {
    return num(val).toLocaleString('es-CL', { maximumFractionDigits: 0 });
}

function fmtSurface(val) {
    return num(val).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// % de avance contra la estimación; null cuando no hay estimación con la que comparar
function pct(kg, estimated) {
    return estimated > 0 ? (num(kg) / estimated) * 100 : null;
}

function fmtPct(val) {
    return val === null ? '-' : val.toLocaleString('es-CL', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
}

const isOver = (val) => val !== null && val > 100.0001;

// Kg por hectárea; null cuando el cuartel no tiene superficie
function perHa(kg, surface) {
    return surface > 0 ? num(kg) / surface : null;
}

function fmtKgHa(val) {
    return val === null ? '-' : fmt(val);
}

const enrichedRows = computed(() =>
    orgFilteredRows.value
        .filter(r => String(r.fruit_id) === String(selectedFruitId.value))
        .map(r => {
            const estimated = num(r.estimates?.[selectedStatusId.value]);
            return {
                ...r,
                kg_estimated: estimated,
                pctDispatched: pct(r.kg_dispatched, estimated),
                pctExported: pct(r.kg_exported, estimated),
                pctExportOfProcess: pct(r.kg_exported, num(r.kg_received)),
                kgHaEstimated: perHa(estimated, num(r.surface)),
                kgHaReceived: perHa(r.kg_received, num(r.surface)),
            };
        })
);

function sumRows(list) {
    const t = list.reduce((acc, r) => {
        acc.surface += num(r.surface);
        acc.estimated += r.kg_estimated;
        acc.dispatched += num(r.kg_dispatched);
        acc.received += num(r.kg_received);
        acc.exported += num(r.kg_exported);
        return acc;
    }, { surface: 0, estimated: 0, dispatched: 0, received: 0, exported: 0 });
    t.pctDispatched = pct(t.dispatched, t.estimated);
    t.pctExported = pct(t.exported, t.estimated);
    // % de lo vaciado a proceso que terminó como exportación
    t.pctExportOfProcess = pct(t.exported, t.received);
    // Kg/ha de variedades y total = kg totales ÷ superficie total (promedio ponderado)
    t.kgHaEstimated = perHa(t.estimated, t.surface);
    t.kgHaReceived = perHa(t.received, t.surface);
    return t;
}

// Cuarteles agrupados por variedad, con subtotal por variedad
const groups = computed(() => {
    const map = new Map();
    enrichedRows.value.forEach(r => {
        const key = String(r.variety_id);
        if (!map.has(key)) map.set(key, { key, name: r.variety_name, rows: [] });
        map.get(key).rows.push(r);
    });
    return [...map.values()]
        .map(g => ({
            ...g,
            rows: g.rows.sort((a, b) => a.cost_center_name.localeCompare(b.cost_center_name)),
            totals: sumRows(g.rows),
            hasOver: g.rows.some(r => isOver(r.pctDispatched) || isOver(r.pctExported)),
        }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

// Variedades expandidas (por defecto todas contraídas)
const expanded = ref(new Set());

function isExpanded(key) {
    return expanded.value.has(key);
}

function toggleGroup(key) {
    const next = new Set(expanded.value);
    next.has(key) ? next.delete(key) : next.add(key);
    expanded.value = next;
}

function expandAll() {
    expanded.value = new Set(groups.value.map(g => g.key));
}

function collapseAll() {
    expanded.value = new Set();
}

const allExpanded = computed(() => groups.value.length > 0 && groups.value.every(g => expanded.value.has(g.key)));

// Mismo comportamiento de expandir/contraer para la tabla de Kg/ha (estado independiente)
const expandedHa = ref(new Set());

function isExpandedHa(key) {
    return expandedHa.value.has(key);
}

function toggleGroupHa(key) {
    const next = new Set(expandedHa.value);
    next.has(key) ? next.delete(key) : next.add(key);
    expandedHa.value = next;
}

function expandAllHa() {
    expandedHa.value = new Set(groups.value.map(g => g.key));
}

function collapseAllHa() {
    expandedHa.value = new Set();
}

const allExpandedHa = computed(() => groups.value.length > 0 && groups.value.every(g => expandedHa.value.has(g.key)));

watch(selectedFruitId, () => {
    collapseAll();
    collapseAllHa();
});

const grandTotals = computed(() => sumRows(enrichedRows.value));

// Cuarteles que ya superaron la estimación (guía o exportado)
const overRows = computed(() =>
    enrichedRows.value.filter(r => isOver(r.pctDispatched) || isOver(r.pctExported))
);

// ── Gráficos ──
const pieCanvas = ref(null);
const barCanvas = ref(null);
let pieChart = null;
let barChart = null;

const palette = ['#2c7be5', '#00d27a', '#f5803e', '#e63757', '#6f42c1', '#27bcfd', '#f5c242', '#748194', '#d63384', '#198754'];

const hasChartData = computed(() => groups.value.some(g => g.totals.dispatched > 0 || g.totals.received > 0 || g.totals.exported > 0));

function renderCharts() {
    pieChart?.destroy();
    barChart?.destroy();
    pieChart = barChart = null;
    if (!hasChartData.value) return;

    const labels = groups.value.map(g => g.name);

    if (pieCanvas.value) {
        pieChart = new Chart(pieCanvas.value, {
            type: 'pie',
            data: {
                labels,
                datasets: [{
                    data: groups.value.map(g => g.totals.dispatched),
                    backgroundColor: groups.value.map((_, i) => palette[i % palette.length]),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const share = total > 0 ? (ctx.parsed / total) * 100 : 0;
                                return `${ctx.label}: ${fmt(ctx.parsed)} kg (${share.toFixed(1)}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    if (barCanvas.value) {
        barChart = new Chart(barCanvas.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Kg Vaciados a Proceso', data: groups.value.map(g => g.totals.received), backgroundColor: '#2c7be5' },
                    { label: 'Kg Exportados', data: groups.value.map(g => g.totals.exported), backgroundColor: '#00d27a' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${fmt(ctx.parsed.y)} kg` } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => fmt(v) } },
                },
            },
        });
    }
}

watch(groups, () => nextTick(renderCharts));
onMounted(() => nextTick(renderCharts));
onBeforeUnmount(() => {
    pieChart?.destroy();
    barChart?.destroy();
});

const selectedFruitLabel = computed(() =>
    fruitOptions.value.find(f => String(f.value) === String(selectedFruitId.value))?.label || ''
);
const selectedStatusLabel = computed(() =>
    statusOptions.value.find(s => String(s.value) === String(selectedStatusId.value))?.label || ''
);

// ── Exportar Excel (números puros) ──
const excelHeaders = [
    { label: 'Variedad', key: 'variety' },
    { label: 'Cuartel', key: 'costCenter' },
    { label: 'Superficie (ha)', key: 'surface', type: 'number' },
    { label: 'Kg Estimados', key: 'estimated', type: 'number' },
    { label: 'Kg Guía', key: 'dispatched', type: 'number' },
    { label: '% Avance Guía', key: 'pctDispatched', type: 'number' },
    { label: 'Kg Vaciados a Proceso', key: 'received', type: 'number' },
    { label: 'Kg Exportados', key: 'exported', type: 'number' },
    { label: '% Exportado s/ Proceso', key: 'pctExportOfProcess', type: 'number' },
    { label: '% Avance Exportado', key: 'pctExported', type: 'number' },
    { label: 'Kg/ha Estimado', key: 'kgHaEstimated', type: 'number' },
    { label: 'Kg/ha a Proceso', key: 'kgHaReceived', type: 'number' },
];

const round1 = (v) => (v === null ? '' : Math.round(v * 10) / 10);

const excelData = computed(() => {
    const data = [];
    groups.value.forEach(g => {
        g.rows.forEach(r => data.push({
            variety: g.name,
            costCenter: r.cost_center_name,
            surface: num(r.surface),
            estimated: r.kg_estimated,
            dispatched: num(r.kg_dispatched),
            pctDispatched: round1(r.pctDispatched),
            received: num(r.kg_received),
            exported: num(r.kg_exported),
            pctExportOfProcess: round1(r.pctExportOfProcess),
            pctExported: round1(r.pctExported),
            kgHaEstimated: round1(r.kgHaEstimated),
            kgHaReceived: round1(r.kgHaReceived),
        }));
        data.push({
            variety: `Subtotal ${g.name}`,
            costCenter: '',
            surface: g.totals.surface,
            estimated: g.totals.estimated,
            dispatched: g.totals.dispatched,
            pctDispatched: round1(g.totals.pctDispatched),
            received: g.totals.received,
            exported: g.totals.exported,
            pctExportOfProcess: round1(g.totals.pctExportOfProcess),
            pctExported: round1(g.totals.pctExported),
            kgHaEstimated: round1(g.totals.kgHaEstimated),
            kgHaReceived: round1(g.totals.kgHaReceived),
        });
    });
    data.push({
        variety: 'TOTAL',
        costCenter: '',
        surface: grandTotals.value.surface,
        estimated: grandTotals.value.estimated,
        dispatched: grandTotals.value.dispatched,
        pctDispatched: round1(grandTotals.value.pctDispatched),
        received: grandTotals.value.received,
        exported: grandTotals.value.exported,
        pctExportOfProcess: round1(grandTotals.value.pctExportOfProcess),
        pctExported: round1(grandTotals.value.pctExported),
        kgHaEstimated: round1(grandTotals.value.kgHaEstimated),
        kgHaReceived: round1(grandTotals.value.kgHaReceived),
    });
    return data;
});

const excelFilename = computed(() =>
    `Analisis_Produccion_${selectedFruitLabel.value}${selectedStatusLabel.value ? '_' + selectedStatusLabel.value : ''}.xlsx`
);
</script>

<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-chart-bar me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <Link :href="route('production-analysis.classifications')" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-sort-amount-down" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Clasificación</span>
                            </Link>
                            <ExportExcelButton
                                v-if="enrichedRows.length > 0"
                                :data="excelData"
                                :headers="excelHeaders"
                                :filename="excelFilename"
                                class="btn btn-falcon-default btn-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-store me-1"></i> Sucursal</label>
                        <Multiselect
                            v-model="selectedBranchIds"
                            mode="tags"
                            :options="branchOptions"
                            placeholder="Todas las sucursales"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-building me-1"></i> Razón Social</label>
                        <Multiselect
                            v-model="selectedReasonIds"
                            mode="tags"
                            :options="reasonOptions"
                            placeholder="Todas las razones sociales"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-seedling me-1"></i> Especie</label>
                        <Multiselect
                            v-model="selectedFruitId"
                            :options="fruitOptions"
                            placeholder="Seleccione especie..."
                            :searchable="true"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-clipboard-list me-1"></i> Nombre Estimación</label>
                        <Multiselect
                            v-model="selectedStatusId"
                            :options="statusOptions"
                            placeholder="Seleccione estimación..."
                            :searchable="true"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                </div>

                <div v-if="fruitOptions.length === 0" class="alert alert-info text-center mb-0">
                    <i class="fas fa-info-circle me-2"></i>No hay variedades por cuartel para los filtros seleccionados.
                </div>

                <template v-else>
                    <!-- KPIs -->
                    <div class="row mb-3 g-2">
                        <div class="col-6 col-md-3">
                            <div class="card h-100 p-1 border">
                                <div class="card-body py-2 px-3 text-center">
                                    <div class="text-muted small">Kg Estimados</div>
                                    <div class="fs-7 fw-bold">{{ fmt(grandTotals.estimated) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100 p-1 border">
                                <div class="card-body py-2 px-3 text-center">
                                    <div class="text-muted small">Kg Guía</div>
                                    <div class="fs-7 fw-bold">{{ fmt(grandTotals.dispatched) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100 p-1 border">
                                <div class="card-body py-2 px-3 text-center">
                                    <div class="text-muted small">Kg Vaciados a Proceso</div>
                                    <div class="fs-7 fw-bold">{{ fmt(grandTotals.received) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100 p-1 border">
                                <div class="card-body py-2 px-3 text-center">
                                    <div class="text-muted small">Kg Exportados</div>
                                    <div class="fs-7 fw-bold">{{ fmt(grandTotals.exported) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="enrichedRows.length === 0" class="alert alert-warning text-center mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>No hay variedades por cuartel para esta especie.
                    </div>

                    <template v-else>
                    <!-- Alerta: cuarteles que superan la estimación -->
                    <div v-if="overRows.length > 0" class="alert alert-danger py-2 mb-3">
                        <div class="fw-bold">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ overRows.length }} {{ overRows.length === 1 ? 'cuartel superó' : 'cuarteles superaron' }} la estimación "{{ selectedStatusLabel }}"
                        </div>
                        <ul class="mb-0 ps-3 fs-10">
                            <li v-for="r in overRows" :key="r.ccv_id">
                                <strong>{{ r.cost_center_name }}</strong> ({{ r.variety_name }}):
                                estimado {{ fmt(r.kg_estimated) }} kg —
                                <span v-if="isOver(r.pctDispatched)">guía {{ fmt(r.kg_dispatched) }} kg ({{ fmtPct(r.pctDispatched) }})</span>
                                <span v-if="isOver(r.pctDispatched) && isOver(r.pctExported)"> · </span>
                                <span v-if="isOver(r.pctExported)">exportado {{ fmt(r.kg_exported) }} kg ({{ fmtPct(r.pctExported) }})</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Gráficos por variedad -->
                    <div v-if="hasChartData" class="row g-2 mb-3">
                        <div class="col-lg-5">
                            <div class="card h-100 border">
                                <div class="card-header py-2">
                                    <h6 class="mb-0 fs-10"><i class="fas fa-chart-pie me-1"></i>Kg Guía por variedad</h6>
                                </div>
                                <div class="card-body" style="height: 320px;">
                                    <canvas ref="pieCanvas"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100 border">
                                <div class="card-header py-2">
                                    <h6 class="mb-0 fs-10"><i class="fas fa-chart-bar me-1"></i>Kg Vaciados a Proceso vs Kg Exportados por variedad</h6>
                                </div>
                                <div class="card-body" style="height: 320px;">
                                    <canvas ref="barCanvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="allExpanded ? collapseAll() : expandAll()">
                            <span :class="allExpanded ? 'fas fa-compress-alt' : 'fas fa-expand-alt'" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">{{ allExpanded ? 'Contraer todo' : 'Expandir todo' }}</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variedad / Cuartel</th>
                                    <th class="text-end">Superficie (ha)</th>
                                    <th class="text-end">Kg Estimados</th>
                                    <th class="text-end">Kg Guía</th>
                                    <th class="text-end">% Avance Guía</th>
                                    <th class="text-end">Kg Vaciados a Proceso</th>
                                    <th class="text-end">Kg Exportados</th>
                                    <th class="text-end">% Exportado s/ Proceso</th>
                                    <th class="text-end">% Avance Exportado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="g in groups" :key="g.key">
                                    <tr class="table-secondary fw-bold" style="cursor: pointer;" @click="toggleGroup(g.key)">
                                        <td class="text-nowrap">
                                            <i class="fas fa-chevron-right fa-xs me-2" :class="{ 'fa-rotate-90': isExpanded(g.key) }"></i>{{ g.name }}
                                            <span class="badge bg-light text-dark border ms-1 fw-normal">{{ g.rows.length }} {{ g.rows.length === 1 ? 'cuartel' : 'cuarteles' }}</span>
                                            <i v-if="g.hasOver && !isExpanded(g.key)" class="fas fa-exclamation-triangle text-danger ms-1" title="Hay cuarteles que superan la estimación"></i>
                                        </td>
                                        <td class="text-end">{{ fmtSurface(g.totals.surface) }}</td>
                                        <td class="text-end">{{ fmt(g.totals.estimated) }}</td>
                                        <td class="text-end">{{ fmt(g.totals.dispatched) }}</td>
                                        <td class="text-end">
                                            <span v-if="isOver(g.totals.pctDispatched)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(g.totals.pctDispatched) }}</span>
                                            <span v-else>{{ fmtPct(g.totals.pctDispatched) }}</span>
                                        </td>
                                        <td class="text-end">{{ fmt(g.totals.received) }}</td>
                                        <td class="text-end">{{ fmt(g.totals.exported) }}</td>
                                        <td class="text-end">{{ fmtPct(g.totals.pctExportOfProcess) }}</td>
                                        <td class="text-end">
                                            <span v-if="isOver(g.totals.pctExported)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(g.totals.pctExported) }}</span>
                                            <span v-else>{{ fmtPct(g.totals.pctExported) }}</span>
                                        </td>
                                    </tr>
                                    <template v-if="isExpanded(g.key)">
                                        <tr v-for="r in g.rows" :key="r.ccv_id" :class="{ 'table-danger': isOver(r.pctDispatched) || isOver(r.pctExported) }">
                                            <td class="ps-5">{{ r.cost_center_name }}</td>
                                            <td class="text-end">{{ fmtSurface(r.surface) }}</td>
                                            <td class="text-end">{{ r.kg_estimated ? fmt(r.kg_estimated) : '-' }}</td>
                                            <td class="text-end">{{ r.kg_dispatched ? fmt(r.kg_dispatched) : '-' }}</td>
                                            <td class="text-end">
                                                <span v-if="isOver(r.pctDispatched)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(r.pctDispatched) }}</span>
                                                <span v-else>{{ fmtPct(r.pctDispatched) }}</span>
                                            </td>
                                            <td class="text-end">{{ r.kg_received ? fmt(r.kg_received) : '-' }}</td>
                                            <td class="text-end">{{ r.kg_exported ? fmt(r.kg_exported) : '-' }}</td>
                                            <td class="text-end">{{ fmtPct(r.pctExportOfProcess) }}</td>
                                            <td class="text-end">
                                                <span v-if="isOver(r.pctExported)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(r.pctExported) }}</span>
                                                <span v-else>{{ fmtPct(r.pctExported) }}</span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end">{{ fmtSurface(grandTotals.surface) }}</td>
                                    <td class="text-end">{{ fmt(grandTotals.estimated) }}</td>
                                    <td class="text-end">{{ fmt(grandTotals.dispatched) }}</td>
                                    <td class="text-end">
                                        <span v-if="isOver(grandTotals.pctDispatched)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(grandTotals.pctDispatched) }}</span>
                                        <span v-else>{{ fmtPct(grandTotals.pctDispatched) }}</span>
                                    </td>
                                    <td class="text-end">{{ fmt(grandTotals.received) }}</td>
                                    <td class="text-end">{{ fmt(grandTotals.exported) }}</td>
                                    <td class="text-end">{{ fmtPct(grandTotals.pctExportOfProcess) }}</td>
                                    <td class="text-end">
                                        <span v-if="isOver(grandTotals.pctExported)" class="badge bg-danger" title="Supera la estimación"><i class="fas fa-exclamation-triangle me-1"></i>{{ fmtPct(grandTotals.pctExported) }}</span>
                                        <span v-else>{{ fmtPct(grandTotals.pctExported) }}</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Kg Estimados = kilos/ha × superficie. % Exportado s/ Proceso = Kg Exportados ÷ Kg Vaciados a Proceso. % Avance = Kg Guía (o Kg Exportados) ÷ Kg Estimados; se marca en rojo si supera el 100%. Kg Guía incluye todas las guías despachadas; Kg Vaciados a Proceso y Kg Exportados solo consideran guías procesadas.
                    </small>

                    <!-- Kilos por hectárea -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <h6 class="mb-0"><i class="fas fa-ruler-combined me-2"></i>Kilos por hectárea</h6>
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="allExpandedHa ? collapseAllHa() : expandAllHa()">
                            <span :class="allExpandedHa ? 'fas fa-compress-alt' : 'fas fa-expand-alt'" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">{{ allExpandedHa ? 'Contraer todo' : 'Expandir todo' }}</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variedad / Cuartel</th>
                                    <th class="text-end">Superficie (ha)</th>
                                    <th class="text-end">Kg/ha Estimado</th>
                                    <th class="text-end">Kg/ha a Proceso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="g in groups" :key="g.key">
                                    <tr class="table-secondary fw-bold" style="cursor: pointer;" @click="toggleGroupHa(g.key)">
                                        <td class="text-nowrap">
                                            <i class="fas fa-chevron-right fa-xs me-2" :class="{ 'fa-rotate-90': isExpandedHa(g.key) }"></i>{{ g.name }}
                                            <span class="badge bg-light text-dark border ms-1 fw-normal">{{ g.rows.length }} {{ g.rows.length === 1 ? 'cuartel' : 'cuarteles' }}</span>
                                        </td>
                                        <td class="text-end">{{ fmtSurface(g.totals.surface) }}</td>
                                        <td class="text-end">{{ fmtKgHa(g.totals.kgHaEstimated) }}</td>
                                        <td class="text-end">{{ fmtKgHa(g.totals.kgHaReceived) }}</td>
                                    </tr>
                                    <template v-if="isExpandedHa(g.key)">
                                        <tr v-for="r in g.rows" :key="r.ccv_id">
                                            <td class="ps-5">{{ r.cost_center_name }}</td>
                                            <td class="text-end">{{ fmtSurface(r.surface) }}</td>
                                            <td class="text-end">{{ r.kg_estimated ? fmtKgHa(r.kgHaEstimated) : '-' }}</td>
                                            <td class="text-end">{{ r.kg_received ? fmtKgHa(r.kgHaReceived) : '-' }}</td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end">{{ fmtSurface(grandTotals.surface) }}</td>
                                    <td class="text-end">{{ fmtKgHa(grandTotals.kgHaEstimated) }}</td>
                                    <td class="text-end">{{ fmtKgHa(grandTotals.kgHaReceived) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Kg/ha = kilos ÷ superficie. En variedades y total se calcula con los kilos y la superficie acumulados (promedio ponderado), no promediando los cuarteles. Kg/ha a Proceso usa los kilos vaciados a proceso de guías procesadas.
                    </small>
                    </template>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
