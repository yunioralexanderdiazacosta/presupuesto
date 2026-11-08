<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import { Chart, registerables } from 'chart.js';
import '@/chartTheme';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

Chart.register(...registerables);

const props = defineProps({
    summary: { type: Object, required: true },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
});

const title = 'Resumen Diario de Cosecha';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

// ── Filtros: cada cambio recarga el resumen desde el servidor ──
const date = ref(props.filters.date);
const selectedBranchIds = ref([...props.filters.branch_ids]);
const selectedReasonIds = ref([...props.filters.reason_ids]);

const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })));
const reasonOptions = computed(() => props.companyReasons.map(c => ({ value: c.id, label: c.name })));

function queryParams() {
    return {
        date: date.value,
        branch_ids: selectedBranchIds.value,
        reason_ids: selectedReasonIds.value,
    };
}

function applyFilters() {
    if (!date.value) return;
    router.get(route('daily-harvest-summary.index'), queryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['summary', 'filters'],
    });
}

watch([date, selectedBranchIds, selectedReasonIds], applyFilters, { deep: true });

function shiftDay(days) {
    const d = new Date(date.value + 'T00:00:00');
    d.setDate(d.getDate() + days);
    date.value = [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
}

const pdfUrl = computed(() => route('daily-harvest-summary.pdf', queryParams()));

// ── Formato ──
function num(val) {
    const n = Number(val);
    return isNaN(n) ? 0 : n;
}

function fmt(val) {
    return num(val).toLocaleString('es-CL', { maximumFractionDigits: 0 });
}

const dateLabel = computed(() => new Date(props.summary.date + 'T00:00:00').toLocaleDateString('es-CL', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
}));

const kpis = computed(() => props.summary.kpis);
const hasData = computed(() => kpis.value.guides > 0);

const kpiCards = computed(() => [
    { label: 'Kg Despachados', value: fmt(kpis.value.kg), icon: 'fa-weight-hanging' },
    { label: 'Guías', value: fmt(kpis.value.guides), icon: 'fa-file-alt' },
    { label: 'Camiones', value: fmt(kpis.value.trucks), icon: 'fa-truck' },
    { label: 'Bins', value: fmt(kpis.value.bins), icon: 'fa-box' },
    { label: 'Cajas', value: fmt(kpis.value.boxes), icon: 'fa-boxes' },
    { label: 'Cuarteles', value: fmt(kpis.value.cost_centers), icon: 'fa-map-marker-alt' },
]);

// ── Gráficos ──
const pieCanvas = ref(null);
const trendCanvas = ref(null);
let pieChart = null;
let trendChart = null;

const palette = ['#2c7be5', '#00d27a', '#f5803e', '#e63757', '#6f42c1', '#27bcfd', '#f5c242', '#748194', '#d63384', '#198754'];

const hasTrendData = computed(() => props.summary.trend.some(t => t.kg > 0));

function renderCharts() {
    pieChart?.destroy();
    trendChart?.destroy();
    pieChart = trendChart = null;

    const varieties = props.summary.by_variety;
    if (pieCanvas.value && varieties.length > 0) {
        pieChart = new Chart(pieCanvas.value, {
            type: 'pie',
            data: {
                labels: varieties.map(v => v.name),
                datasets: [{
                    data: varieties.map(v => v.kg),
                    backgroundColor: varieties.map((_, i) => palette[i % palette.length]),
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

    if (trendCanvas.value && hasTrendData.value) {
        const trend = props.summary.trend;
        trendChart = new Chart(trendCanvas.value, {
            type: 'bar',
            data: {
                labels: trend.map(t => t.label),
                datasets: [{
                    label: 'Kg Despachados',
                    data: trend.map(t => t.kg),
                    // El día seleccionado se destaca sobre los anteriores
                    backgroundColor: trend.map(t => (t.date === props.summary.date ? '#2c7be5' : '#b6d0f7')),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `${fmt(ctx.parsed.y)} kg (${trend[ctx.dataIndex].guides} guías)` } },
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => fmt(v) } } },
            },
        });
    }
}

watch(() => props.summary, () => nextTick(renderCharts));
onMounted(() => nextTick(renderCharts));
onBeforeUnmount(() => {
    pieChart?.destroy();
    trendChart?.destroy();
});

// ── Exportar Excel (números puros) ──
const excelHeaders = [
    { label: 'Sección', key: 'section' },
    { label: 'Detalle', key: 'detail' },
    { label: 'Kg', key: 'kg', type: 'number' },
    { label: 'Guías', key: 'guides', type: 'number' },
    { label: 'Cantidad', key: 'qty', type: 'number' },
];

const excelData = computed(() => {
    const s = props.summary;
    const rows = [
        { section: 'Resumen', detail: 'Total del día', kg: s.kpis.kg, guides: s.kpis.guides, qty: '' },
        { section: 'Resumen', detail: 'Camiones', kg: '', guides: '', qty: s.kpis.trucks },
        { section: 'Resumen', detail: 'Bins', kg: '', guides: '', qty: s.kpis.bins },
        { section: 'Resumen', detail: 'Cajas', kg: '', guides: '', qty: s.kpis.boxes },
        { section: 'Resumen', detail: 'Cuarteles', kg: '', guides: '', qty: s.kpis.cost_centers },
    ];
    const add = (section, list, label = (r) => r.name) =>
        list.forEach(r => rows.push({ section, detail: label(r), kg: r.kg, guides: r.guides, qty: '' }));

    add('Variedad', s.by_variety);
    add('Cuartel', s.by_cost_center, r => (r.varieties ? `${r.name} (${r.varieties})` : r.name));
    add('Exportadora', s.by_exporter);
    add('Packing', s.by_packing_house);
    s.trend.forEach(t => rows.push({ section: 'Últimos 7 días', detail: t.date, kg: t.kg, guides: t.guides, qty: '' }));
    return rows;
});

const excelFilename = computed(() => `Resumen_Diario_Cosecha_${props.summary.date}.xlsx`);

const detailTables = computed(() => [
    { key: 'by_cost_center', title: 'Kilos por cuartel', icon: 'fa-map-marker-alt', first: 'Cuartel', rows: props.summary.by_cost_center, showVarieties: true },
    { key: 'by_exporter', title: 'Kilos por exportadora', icon: 'fa-building', first: 'Exportadora', rows: props.summary.by_exporter },
    { key: 'by_packing_house', title: 'Kilos por packing', icon: 'fa-warehouse', first: 'Packing', rows: props.summary.by_packing_house },
]);
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
                            <i class="fas fa-calendar-day me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                :filename="excelFilename"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <a :href="pdfUrl" target="_blank" rel="noopener" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-file-pdf" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">PDF</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-calendar-alt me-1"></i> Fecha</label>
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-falcon-default" title="Día anterior" @click="shiftDay(-1)"><i class="fas fa-chevron-left"></i></button>
                            <input type="date" v-model="date" class="form-control" />
                            <button type="button" class="btn btn-falcon-default" title="Día siguiente" @click="shiftDay(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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
                </div>

                <div class="text-muted text-capitalize mb-2"><i class="fas fa-calendar-check me-1"></i>{{ dateLabel }}</div>

                <!-- KPIs -->
                <div class="row g-2 mb-3">
                    <div v-for="card in kpiCards" :key="card.label" class="col-6 col-md-2">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small"><i class="fas me-1" :class="card.icon"></i>{{ card.label }}</div>
                                <div class="fs-7 fw-bold">{{ card.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!hasData" class="alert alert-info text-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>No hay guías despachadas en esta fecha con los filtros seleccionados.
                </div>

                <!-- Gráficos -->
                <div class="row g-2 mb-3">
                    <div class="col-lg-5">
                        <div class="card h-100 border">
                            <div class="card-header py-2">
                                <h6 class="mb-0 fs-10"><i class="fas fa-chart-pie me-1"></i>Kilos por variedad</h6>
                            </div>
                            <div class="card-body" style="height: 300px;">
                                <canvas v-if="summary.by_variety.length > 0" ref="pieCanvas"></canvas>
                                <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted small">Sin datos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card h-100 border">
                            <div class="card-header py-2">
                                <h6 class="mb-0 fs-10"><i class="fas fa-chart-bar me-1"></i>Kilos despachados, últimos 7 días</h6>
                            </div>
                            <div class="card-body" style="height: 300px;">
                                <canvas v-if="hasTrendData" ref="trendCanvas"></canvas>
                                <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted small">Sin datos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kilos por variedad (detalle) -->
                <div class="row g-2">
                    <div class="col-lg-6">
                        <div class="card border mb-2">
                            <div class="card-header py-2">
                                <h6 class="mb-0 fs-10"><i class="fas fa-seedling me-1"></i>Kilos por variedad</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered fs-10 mb-0">
                                    <thead class="table-light">
                                        <tr><th>Variedad</th><th class="text-end">Guías</th><th class="text-end">Kg</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in summary.by_variety" :key="r.name">
                                            <td>{{ r.name }}</td>
                                            <td class="text-end">{{ r.guides }}</td>
                                            <td class="text-end">{{ fmt(r.kg) }}</td>
                                        </tr>
                                        <tr v-if="summary.by_variety.length === 0"><td colspan="3" class="text-center text-muted">Sin guías</td></tr>
                                    </tbody>
                                    <tfoot v-if="summary.by_variety.length > 0">
                                        <tr class="table-dark fw-bold">
                                            <td>TOTAL</td>
                                            <td class="text-end">{{ fmt(kpis.guides) }}</td>
                                            <td class="text-end">{{ fmt(kpis.kg) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div v-for="t in detailTables" :key="t.key" class="col-lg-6">
                        <div class="card border mb-2">
                            <div class="card-header py-2">
                                <h6 class="mb-0 fs-10"><i class="fas me-1" :class="t.icon"></i>{{ t.title }}</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered fs-10 mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ t.first }}</th>
                                            <th v-if="t.showVarieties">Variedad</th>
                                            <th class="text-end">Guías</th>
                                            <th class="text-end">Kg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in t.rows" :key="r.name">
                                            <td>{{ r.name }}</td>
                                            <td v-if="t.showVarieties">{{ r.varieties || '-' }}</td>
                                            <td class="text-end">{{ r.guides }}</td>
                                            <td class="text-end">{{ fmt(r.kg) }}</td>
                                        </tr>
                                        <tr v-if="t.rows.length === 0"><td :colspan="t.showVarieties ? 4 : 3" class="text-center text-muted">Sin guías</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <small class="text-muted d-block mt-2">
                    Se consideran las guías cuya fecha de despacho es la fecha seleccionada. Camiones = patentes distintas (una guía sin patente cuenta como un camión).
                </small>
            </div>
        </div>
    </AppLayout>
</template>
