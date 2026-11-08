<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import Swal from 'sweetalert2';
import { Chart, registerables } from 'chart.js';
import '@/chartTheme';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import Modal from '@/Components/Modal.vue';

Chart.register(...registerables);

const props = defineProps({
    fruits: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    catalog: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});

const title = 'Análisis de Clasificación';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const TYPE_LABELS = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };
const TYPE_ORDER = ['caliber', 'color', 'quality'];
const typeLabel = (t) => TYPE_LABELS[t] || (t ? t.charAt(0).toUpperCase() + t.slice(1) : '');

// Los valores del catálogo y los guardados en los procesos pueden diferir en mayúsculas ("j" vs "J")
const norm = (v) => String(v ?? '').trim().toLowerCase();

function num(val) {
    const n = Number(val);
    return isNaN(n) ? 0 : n;
}
const fmt = (val) => num(val).toLocaleString('es-CL', { maximumFractionDigits: 0 });
const fmtPct = (val) => (val === null ? '-' : val.toLocaleString('es-CL', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%');

// ── Filtros superiores (mismos que Análisis de Cosecha) ──
const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })));
const reasonOptions = computed(() => props.companyReasons.map(c => ({ value: c.id, label: c.name })));
const selectedBranchIds = ref([]);
const selectedReasonIds = ref([]);

const orgFilteredRows = computed(() => {
    const branchIds = selectedBranchIds.value.map(String);
    const reasonIds = selectedReasonIds.value.map(String);
    return props.rows.filter(r =>
        num(r.guides_processed) > 0 &&
        (branchIds.length === 0 || branchIds.includes(String(r.branch_id))) &&
        (reasonIds.length === 0 || reasonIds.includes(String(r.company_reason_id)))
    );
});

const fruitOptions = computed(() => {
    const used = new Set(orgFilteredRows.value.map(r => String(r.fruit_id)));
    return props.fruits
        .filter(f => used.has(String(f.id)))
        .map(f => ({ value: f.id, label: f.name }));
});

const selectedFruitId = ref(fruitOptions.value.length ? fruitOptions.value[0].value : '');

watch(fruitOptions, (opts) => {
    if (!opts.some(f => String(f.value) === String(selectedFruitId.value))) {
        selectedFruitId.value = opts.length ? opts[0].value : '';
    }
});

const fruitRows = computed(() =>
    orgFilteredRows.value.filter(r => String(r.fruit_id) === String(selectedFruitId.value))
);

// Variedades de la especie elegida; vacío = todas
const varietyOptions = computed(() => {
    const map = new Map();
    fruitRows.value.forEach(r => map.set(String(r.variety_id), r.variety_name));
    return [...map.entries()]
        .map(([value, label]) => ({ value, label }))
        .sort((a, b) => a.label.localeCompare(b.label));
});
const selectedVarietyIds = ref([]);

watch(varietyOptions, (opts) => {
    const valid = new Set(opts.map(o => o.value));
    selectedVarietyIds.value = selectedVarietyIds.value.filter(id => valid.has(String(id)));
});

const visibleRows = computed(() => {
    const ids = selectedVarietyIds.value.map(String);
    return ids.length === 0 ? fruitRows.value : fruitRows.value.filter(r => ids.includes(String(r.variety_id)));
});

// Tipos de clasificación: los del catálogo del frutal y los que aparezcan en los procesos
const typeOptions = computed(() => {
    const set = new Set();
    props.catalog
        .filter(c => String(c.fruit_id) === String(selectedFruitId.value))
        .forEach(c => set.add(c.type));
    fruitRows.value.forEach(r => Object.keys(r.classifications || {}).forEach(t => set.add(t)));
    return [...set]
        .sort((a, b) => {
            const ia = TYPE_ORDER.indexOf(a);
            const ib = TYPE_ORDER.indexOf(b);
            return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib) || a.localeCompare(b);
        })
        .map(t => ({ value: t, label: typeLabel(t) }));
});

const selectedType = ref(typeOptions.value.length ? typeOptions.value[0].value : '');

watch(typeOptions, (opts) => {
    if (!opts.some(t => t.value === selectedType.value)) {
        selectedType.value = opts.length ? opts[0].value : '';
    }
});

// ── Columnas: valores del catálogo o grupos definidos por el usuario ──
const baseColumns = computed(() => {
    const cols = [];
    const seen = new Set();
    props.catalog
        .filter(c => String(c.fruit_id) === String(selectedFruitId.value) && c.type === selectedType.value)
        .forEach(c => {
            const key = norm(c.value);
            if (!seen.has(key)) {
                seen.add(key);
                cols.push({ key, label: c.value });
            }
        });

    // Valores que aparecen en los procesos pero ya no están en el catálogo
    const extras = new Map();
    fruitRows.value.forEach(r => {
        (r.classifications?.[selectedType.value] || []).forEach(item => {
            const key = norm(item.value);
            if (!seen.has(key) && !extras.has(key)) extras.set(key, item.value);
        });
    });
    [...extras.entries()]
        .sort((a, b) => a[1].localeCompare(b[1]))
        .forEach(([key, label]) => cols.push({ key, label }));

    return cols;
});

const typeGroups = computed(() =>
    props.groups.filter(g => String(g.fruit_id) === String(selectedFruitId.value) && g.classification_type === selectedType.value)
);

// Si hay grupos configurados para la especie y clasificación elegidas, se usan por defecto
const groupMode = ref(true);
const effectiveGroupMode = computed(() => groupMode.value && typeGroups.value.length > 0);

const columns = computed(() => {
    if (!effectiveGroupMode.value) {
        return baseColumns.value.map(b => ({ key: b.key, label: b.label, members: [b.key] }));
    }
    const cols = typeGroups.value.map(g => ({
        key: 'g' + g.id,
        label: g.name,
        members: (g.values || []).map(norm),
        detail: (g.values || []).join(', '),
    }));
    const grouped = new Set(cols.flatMap(c => c.members));
    const rest = baseColumns.value.filter(b => !grouped.has(b.key));
    if (rest.length) {
        cols.push({ key: 'rest', label: 'Sin grupo', members: rest.map(b => b.key), detail: rest.map(b => b.label).join(', ') });
    }
    return cols;
});

// Cada valor cuenta en un solo grupo (el primero que lo contenga)
const valueToColumn = computed(() => {
    const map = new Map();
    columns.value.forEach(c => c.members.forEach(k => { if (!map.has(k)) map.set(k, c.key); }));
    return map;
});

// ── Filas enriquecidas con kg por columna ──
const enrichedRows = computed(() =>
    visibleRows.value.map(r => {
        const cells = {};
        let classified = 0;
        (r.classifications?.[selectedType.value] || []).forEach(item => {
            const col = valueToColumn.value.get(norm(item.value));
            if (!col) return;
            const kg = num(item.kg);
            cells[col] = cells[col] || { kg: 0 };
            cells[col].kg += kg;
            classified += kg;
        });
        const exported = num(r.kg_exported);
        return {
            ...r,
            exported,
            cells,
            classified,
            unclassified: Math.max(0, exported - classified),
        };
    })
);

function sumRows(list) {
    const t = { exported: 0, classified: 0, unclassified: 0, cells: {} };
    list.forEach(r => {
        t.exported += r.exported;
        t.classified += r.classified;
        t.unclassified += r.unclassified;
        Object.entries(r.cells).forEach(([key, c]) => {
            t.cells[key] = t.cells[key] || { kg: 0 };
            t.cells[key].kg += c.kg;
        });
    });
    return t;
}

// ── Comparar por: cada variedad, sucursal o razón social se muestra por separado (sin sumar entre sí) ──
const compareBy = ref('variety');
const compareOptions = [
    { value: 'variety', label: 'Variedad' },
    { value: 'branch', label: 'Sucursal' },
    { value: 'reason', label: 'Razón social' },
];
const compareLabel = computed(() => compareOptions.find(o => o.value === compareBy.value)?.label || '');

function groupInfo(r) {
    if (compareBy.value === 'branch') {
        return { key: 'b' + (r.branch_id ?? 'none'), name: props.branches.find(b => String(b.id) === String(r.branch_id))?.name || 'Sin sucursal' };
    }
    if (compareBy.value === 'reason') {
        return { key: 'r' + (r.company_reason_id ?? 'none'), name: props.companyReasons.find(c => String(c.id) === String(r.company_reason_id))?.name || 'Sin razón social' };
    }
    return { key: 'v' + r.variety_id, name: r.variety_name };
}

// Al comparar por sucursal o razón social, cada cuartel indica su variedad
const rowLabel = (r) => (compareBy.value === 'variety' ? r.cost_center_name : `${r.cost_center_name} — ${r.variety_name}`);

const varietyGroups = computed(() => {
    const map = new Map();
    enrichedRows.value.forEach(r => {
        const { key, name } = groupInfo(r);
        if (!map.has(key)) map.set(key, { key, name, rows: [] });
        map.get(key).rows.push(r);
    });
    return [...map.values()]
        .map(g => ({
            ...g,
            rows: g.rows.sort((a, b) => rowLabel(a).localeCompare(rowLabel(b))),
            totals: sumRows(g.rows),
        }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

const grandTotals = computed(() => sumRows(enrichedRows.value));

// "Sin clasificar" = exportado que no se repartió en la clasificación elegida
const showUnclassified = computed(() => grandTotals.value.unclassified > 0.5);

// ── Kg y % de exportación de cada celda ──
const pctOf = (kg, exported) => (exported > 0 ? (kg / exported) * 100 : null);
const cellKg = (ent, key) => (ent.cells[key]?.kg ? fmt(ent.cells[key].kg) : '-');
const cellPct = (ent, key) => (ent.cells[key]?.kg ? fmtPct(pctOf(ent.cells[key].kg, ent.exported)) : '-');
const unclassifiedKg = (ent) => (ent.unclassified > 0.5 ? fmt(ent.unclassified) : '-');
const unclassifiedPct = (ent) => (ent.unclassified > 0.5 ? fmtPct(pctOf(ent.unclassified, ent.exported)) : '-');
const totalKg = (ent) => (ent.classified ? fmt(ent.classified) : '-');
const totalPct = (ent) => (ent.classified ? fmtPct(pctOf(ent.classified, ent.exported)) : '-');

// ── Expandir / contraer variedades ──
const expanded = ref(new Set());
const isExpanded = (key) => expanded.value.has(key);

function toggleGroup(key) {
    const next = new Set(expanded.value);
    next.has(key) ? next.delete(key) : next.add(key);
    expanded.value = next;
}
const expandAll = () => { expanded.value = new Set(varietyGroups.value.map(g => g.key)); };
const collapseAll = () => { expanded.value = new Set(); };
const allExpanded = computed(() => varietyGroups.value.length > 0 && varietyGroups.value.every(g => expanded.value.has(g.key)));

// Misma expansión para la tabla de porcentajes (estado independiente)
const expandedPct = ref(new Set());
const isExpandedPct = (key) => expandedPct.value.has(key);

function toggleGroupPct(key) {
    const next = new Set(expandedPct.value);
    next.has(key) ? next.delete(key) : next.add(key);
    expandedPct.value = next;
}
const expandAllPct = () => { expandedPct.value = new Set(varietyGroups.value.map(g => g.key)); };
const collapseAllPct = () => { expandedPct.value = new Set(); };
const allExpandedPct = computed(() => varietyGroups.value.length > 0 && varietyGroups.value.every(g => expandedPct.value.has(g.key)));

watch([selectedFruitId, selectedBranchIds, selectedReasonIds, selectedVarietyIds, compareBy], () => {
    collapseAll();
    collapseAllPct();
});

// ── Mapa de calor de la tabla de porcentajes ──
const showHeat = ref(true);
const heatMax = computed(() => {
    let max = 0;
    const consider = (ent) => columns.value.forEach(c => {
        const p = pctOf(ent.cells[c.key]?.kg || 0, ent.exported);
        if (p !== null && p > max) max = p;
    });
    varietyGroups.value.forEach(g => { consider(g.totals); g.rows.forEach(consider); });
    return max;
});
function heatStyle(ent, key) {
    const kg = ent.cells[key]?.kg || 0;
    if (!showHeat.value || !kg || heatMax.value <= 0) return null;
    const alpha = Math.min(1, pctOf(kg, ent.exported) / heatMax.value) * 0.55;
    return { backgroundColor: `rgba(44, 123, 229, ${alpha.toFixed(3)})` };
}

const showTotalLine = ref(false);

// Resumen de la selección activa, visible bajo cada tabla para no perder el contexto
const selectionBadges = computed(() => {
    const names = (ids, options) => ids.map(id => options.find(o => String(o.value) === String(id))?.label).filter(Boolean).join(', ');
    return [
        { icon: 'fa-leaf', label: 'Variedad', text: names(selectedVarietyIds.value, varietyOptions.value) || 'Todas' },
        { icon: 'fa-store', label: 'Sucursal', text: names(selectedBranchIds.value, branchOptions.value) || 'Todas' },
    ];
});

// ── Gráficos ──
const palette = ['#2c7be5', '#00d27a', '#f5803e', '#e63757', '#6f42c1', '#27bcfd', '#f5c242', '#d63384', '#198754', '#748194'];
const colorFor = (idx) => palette[idx % palette.length];
const pieCanvas = ref(null);
const barCanvas = ref(null);
let pieChart = null;
let barChart = null;

const hasChartData = computed(() => grandTotals.value.classified > 0);

function renderCharts() {
    pieChart?.destroy();
    barChart?.destroy();
    pieChart = barChart = null;
    if (!hasChartData.value) return;

    const cols = columns.value.filter(c => (grandTotals.value.cells[c.key]?.kg || 0) > 0);

    if (pieCanvas.value) {
        pieChart = new Chart(pieCanvas.value, {
            type: 'pie',
            data: {
                labels: cols.map(c => c.label),
                datasets: [{
                    data: cols.map(c => grandTotals.value.cells[c.key].kg),
                    backgroundColor: cols.map(c => colorFor(columns.value.findIndex(x => x.key === c.key))),
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
        // Curva: eje X = valores/grupos en orden de catálogo, eje Y = % de la exportación; una línea por variedad y el total
        const labels = columns.value.map(c => c.label);
        const lineFor = (name, ent, color, extra = {}) => ({
            label: name,
            data: columns.value.map(c => (ent.exported > 0 ? ((ent.cells[c.key]?.kg || 0) / ent.exported) * 100 : 0)),
            kgs: columns.value.map(c => ent.cells[c.key]?.kg || 0),
            borderColor: color,
            backgroundColor: color,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            ...extra,
        });
        const datasets = varietyGroups.value.map((g, i) => lineFor(g.name, g.totals, colorFor(i)));
        if (showTotalLine.value && varietyGroups.value.length > 1) {
            datasets.push(lineFor('Promedio ponderado', grandTotals.value, '#9da9bb', { borderWidth: 3, borderDash: [6, 4], pointStyle: 'rectRot' }));
        } else if (datasets.length === 1) {
            datasets[0].fill = true;
            datasets[0].backgroundColor = colorFor(0) + '33';
        }
        barChart = new Chart(barCanvas.value, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}% (${fmt(ctx.dataset.kgs[ctx.dataIndex])} kg)`,
                        },
                    },
                },
                scales: {
                    x: { title: { display: true, text: selectedTypeLabel.value } },
                    y: { beginAtZero: true, title: { display: true, text: '% de exportación' }, ticks: { callback: (v) => v + '%' } },
                },
            },
        });
    }
}

watch([varietyGroups, columns, showUnclassified, showTotalLine], () => nextTick(renderCharts));
onMounted(() => nextTick(renderCharts));
onBeforeUnmount(() => {
    pieChart?.destroy();
    barChart?.destroy();
});

// ── Configurar grupos ──
const draftGroups = ref([]);
const valueOptions = computed(() => baseColumns.value.map(b => ({ value: b.label, label: b.label })));

function openGroupsModal() {
    draftGroups.value = typeGroups.value.map(g => ({
        name: g.name,
        values: (g.values || []).map(v => baseColumns.value.find(b => b.key === norm(v))?.label ?? v),
    }));
    if (draftGroups.value.length === 0) addDraftGroup();
    $('#classificationGroupsModal').modal('show');
}

const addDraftGroup = () => draftGroups.value.push({ name: '', values: [] });
const removeDraftGroup = (index) => draftGroups.value.splice(index, 1);

// Valores ya usados por otros grupos del borrador (un valor pertenece a un solo grupo)
function optionsFor(index) {
    const taken = new Set();
    draftGroups.value.forEach((g, i) => { if (i !== index) g.values.forEach(v => taken.add(norm(v))); });
    return valueOptions.value.filter(o => !taken.has(norm(o.value)));
}

function saveGroups() {
    const cleaned = draftGroups.value
        .map(g => ({ name: g.name.trim(), values: g.values }))
        .filter(g => g.name !== '' || g.values.length > 0);

    if (cleaned.some(g => g.name === '' || g.values.length === 0)) {
        Swal.fire({ icon: 'warning', title: 'Grupos incompletos', text: 'Cada grupo necesita un nombre y al menos un valor.' });
        return;
    }

    router.post(route('production-analysis.classification-groups.save'), {
        fruit_id: selectedFruitId.value,
        classification_type: selectedType.value,
        groups: cleaned,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            $('#classificationGroupsModal').modal('hide');
            groupMode.value = true;
            Swal.fire({ icon: 'success', title: 'Grupos guardados', text: cleaned.length > 0 ? 'Se muestra la vista "Por grupo".' : '', showConfirmButton: false, timer: 1600 });
        },
        onError: (errors) => {
            Swal.fire({ icon: 'error', title: 'Error', text: errors.groups || 'No se pudieron guardar los grupos.' });
        },
    });
}

// ── Exportar Excel (números puros) ──
const selectedFruitLabel = computed(() =>
    fruitOptions.value.find(f => String(f.value) === String(selectedFruitId.value))?.label || ''
);
const selectedTypeLabel = computed(() => typeLabel(selectedType.value));

const excelHeaders = computed(() => [
    { label: compareLabel.value, key: 'variety' },
    { label: 'Cuartel', key: 'costCenter' },
    { label: 'Kg Exportados', key: 'exported', type: 'number' },
    ...columns.value.flatMap(c => [
        { label: `${c.label} (kg)`, key: 'k_' + c.key, type: 'number' },
        { label: `${c.label} (%)`, key: 'p_' + c.key, type: 'number' },
    ]),
    ...(showUnclassified.value ? [
        { label: 'Sin clasificar (kg)', key: 'unclassified_kg', type: 'number' },
        { label: 'Sin clasificar (%)', key: 'unclassified_pct', type: 'number' },
    ] : []),
    { label: 'Total clasificado (kg)', key: 'classified_kg', type: 'number' },
    { label: 'Total clasificado (%)', key: 'classified_pct', type: 'number' },
]);

const round1 = (v) => (v === null || v === undefined ? '' : Math.round(v * 10) / 10);

function excelRow(label, costCenter, ent) {
    const row = {
        variety: label,
        costCenter,
        exported: ent.exported,
        classified_kg: ent.classified,
        classified_pct: round1(pctOf(ent.classified, ent.exported)),
        unclassified_kg: ent.unclassified,
        unclassified_pct: round1(pctOf(ent.unclassified, ent.exported)),
    };
    columns.value.forEach(c => {
        const kg = ent.cells[c.key]?.kg || 0;
        row['k_' + c.key] = kg;
        row['p_' + c.key] = round1(pctOf(kg, ent.exported));
    });
    return row;
}

const excelData = computed(() => {
    const data = [];
    varietyGroups.value.forEach(g => {
        g.rows.forEach(r => data.push(excelRow(g.name, rowLabel(r), r)));
        data.push(excelRow(`Subtotal ${g.name}`, '', g.totals));
    });
    data.push(excelRow('TOTAL', '', grandTotals.value));
    return data;
});

const excelFilename = computed(() =>
    `Clasificacion_${selectedTypeLabel.value}_${selectedFruitLabel.value}.xlsx`.replace(/\s+/g, '_')
);

const columnCount = computed(() => 3 + columns.value.length + (showUnclassified.value ? 1 : 0));
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
                            <i class="fas fa-sort-amount-down me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <Link :href="route('production-analysis.index')" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Análisis de Cosecha</span>
                            </Link>
                            <ExportExcelButton
                                v-if="enrichedRows.length > 0 && columns.length > 0"
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
                        <label class="form-label fw-bold mb-1"><i class="fas fa-leaf me-1"></i> Variedad</label>
                        <Multiselect
                            v-model="selectedVarietyIds"
                            mode="tags"
                            :options="varietyOptions"
                            placeholder="Todas las variedades"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-tags me-1"></i> Clasificación</label>
                        <Multiselect
                            v-model="selectedType"
                            :options="typeOptions"
                            placeholder="Seleccione..."
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-columns me-1"></i> Comparar por</label>
                        <Multiselect
                            v-model="compareBy"
                            :options="compareOptions"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                </div>

                <div v-if="fruitOptions.length === 0" class="alert alert-info text-center mb-0">
                    <i class="fas fa-info-circle me-2"></i>No hay guías procesadas con clasificación para los filtros seleccionados.
                </div>

                <div v-else-if="columns.length === 0" class="alert alert-warning text-center mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>No hay valores de {{ selectedTypeLabel.toLowerCase() }} para esta especie.
                </div>

                <template v-else>
                    <!-- Agrupar -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Agrupación">
                            <button type="button" class="btn" :class="effectiveGroupMode ? 'btn-falcon-default' : 'btn-primary'" @click="groupMode = false">
                                Por valor
                            </button>
                            <button type="button" class="btn" :class="effectiveGroupMode ? 'btn-primary' : 'btn-falcon-default'" @click="groupMode = true">
                                Por grupo
                                <span v-if="typeGroups.length > 0" class="badge bg-light text-dark ms-1">{{ typeGroups.length }}</span>
                            </button>
                        </div>
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="openGroupsModal">
                            <span class="fas fa-layer-group" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Configurar grupos</span>
                        </button>
                        <small v-if="typeGroups.length === 0" class="text-muted">
                            Aún no hay grupos de {{ selectedTypeLabel.toLowerCase() }} para esta especie. Usa "Configurar grupos" para crearlos.
                        </small>
                        <div class="form-check form-switch mb-0 ms-2">
                            <input id="heatSwitch" v-model="showHeat" class="form-check-input" type="checkbox" />
                            <label class="form-check-label small" for="heatSwitch">Mapa de calor</label>
                        </div>
                        <div v-if="varietyGroups.length > 1" class="form-check form-switch mb-0">
                            <input id="totalSwitch" v-model="showTotalLine" class="form-check-input" type="checkbox" />
                            <label class="form-check-label small" for="totalSwitch" title="Promedio ponderado por kilos: suma de kilos de cada calibre ÷ suma de kilos exportados">Línea de promedio ponderado en la curva</label>
                        </div>
                    </div>

                    <!-- KPIs por columna -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3 col-xl-2">
                            <div class="card h-100 border">
                                <div class="card-header py-1 px-2">
                                    <h6 class="mb-0 fs-10">Kg Exportados</h6>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="row g-1">
                                        <div class="col-6"><div class="text-muted" style="font-size:0.7rem;">Kg</div><strong>{{ fmt(grandTotals.exported) }}</strong></div>
                                        <div class="col-6"><div class="text-muted" style="font-size:0.7rem;">Clasificado</div><strong>{{ fmtPct(pctOf(grandTotals.classified, grandTotals.exported)) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-for="(c, idx) in columns" :key="c.key" class="col-6 col-md-3 col-xl-2">
                            <div class="card h-100 border" :style="{ borderTop: '3px solid ' + colorFor(idx) + ' !important' }">
                                <div class="card-header py-1 px-2" :title="c.detail || ''">
                                    <h6 class="mb-0 fs-10 text-truncate">{{ c.label }}</h6>
                                </div>
                                <div class="card-body py-2 px-2">
                                    <div class="row g-1">
                                        <div class="col-6"><div class="text-muted" style="font-size:0.7rem;">Kg</div><strong>{{ fmt(grandTotals.cells[c.key]?.kg || 0) }}</strong></div>
                                        <div class="col-6"><div class="text-muted" style="font-size:0.7rem;">% de exportación</div><strong>{{ fmtPct(pctOf(grandTotals.cells[c.key]?.kg || 0, grandTotals.exported)) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!hasChartData" class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>Las guías procesadas no tienen {{ selectedTypeLabel.toLowerCase() }} registrado.
                    </div>

                    <!-- Gráficos -->
                    <div v-if="hasChartData" class="row g-2 mb-3">
                        <div class="col-lg-5">
                            <div class="card h-100 border">
                                <div class="card-header py-2">
                                    <h6 class="mb-0 fs-10"><i class="fas fa-chart-pie me-1"></i>Kg por {{ selectedTypeLabel.toLowerCase() }} (total)</h6>
                                </div>
                                <div class="card-body" style="height: 320px;">
                                    <canvas ref="pieCanvas"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100 border">
                                <div class="card-header py-2">
                                    <h6 class="mb-0 fs-10"><i class="fas fa-chart-line me-1"></i>Curva de {{ selectedTypeLabel.toLowerCase() }} (% de exportación)</h6>
                                </div>
                                <div class="card-body" style="height: 320px;">
                                    <canvas ref="barCanvas"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-1"><i class="fas fa-table me-2"></i>{{ selectedTypeLabel }} por {{ compareLabel.toLowerCase() }} y cuartel</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span v-for="b in selectionBadges" :key="b.label" class="badge bg-light text-dark border fw-normal">
                                    <i class="fas me-1" :class="b.icon"></i>{{ b.label }}: <strong>{{ b.text }}</strong>
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="allExpanded ? collapseAll() : expandAll()">
                            <span :class="allExpanded ? 'fas fa-compress-alt' : 'fas fa-expand-alt'" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">{{ allExpanded ? 'Contraer todo' : 'Expandir todo' }}</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ compareLabel }} / Cuartel</th>
                                    <th class="text-end">Kg Exportados</th>
                                    <th v-for="c in columns" :key="c.key" class="text-end" :title="c.detail || ''">{{ c.label }}</th>
                                    <th v-if="showUnclassified" class="text-end">Sin clasificar</th>
                                    <th class="text-end">Total clasificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="g in varietyGroups" :key="g.key">
                                    <tr class="table-secondary fw-bold" style="cursor: pointer;" @click="toggleGroup(g.key)">
                                        <td class="text-nowrap">
                                            <i class="fas fa-chevron-right fa-xs me-2" :class="{ 'fa-rotate-90': isExpanded(g.key) }"></i>{{ g.name }}
                                            <span class="badge bg-light text-dark border ms-1 fw-normal">{{ g.rows.length }} {{ g.rows.length === 1 ? 'cuartel' : 'cuarteles' }}</span>
                                        </td>
                                        <td class="text-end">{{ fmt(g.totals.exported) }}</td>
                                        <td v-for="c in columns" :key="c.key" class="text-end">{{ cellKg(g.totals, c.key) }}</td>
                                        <td v-if="showUnclassified" class="text-end">{{ unclassifiedKg(g.totals) }}</td>
                                        <td class="text-end">{{ totalKg(g.totals) }}</td>
                                    </tr>
                                    <template v-if="isExpanded(g.key)">
                                        <tr v-for="r in g.rows" :key="r.ccv_id">
                                            <td class="ps-5">{{ rowLabel(r) }}</td>
                                            <td class="text-end">{{ r.exported ? fmt(r.exported) : '-' }}</td>
                                            <td v-for="c in columns" :key="c.key" class="text-end">{{ cellKg(r, c.key) }}</td>
                                            <td v-if="showUnclassified" class="text-end">{{ unclassifiedKg(r) }}</td>
                                            <td class="text-end">{{ totalKg(r) }}</td>
                                        </tr>
                                    </template>
                                </template>
                                <tr v-if="varietyGroups.length === 0">
                                    <td :colspan="columnCount" class="text-center text-muted py-3">No hay datos para los filtros seleccionados.</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end">{{ fmt(grandTotals.exported) }}</td>
                                    <td v-for="c in columns" :key="c.key" class="text-end">{{ cellKg(grandTotals, c.key) }}</td>
                                    <td v-if="showUnclassified" class="text-end">{{ unclassifiedKg(grandTotals) }}</td>
                                    <td class="text-end">{{ totalKg(grandTotals) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Tabla de porcentajes -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <div>
                            <h6 class="mb-1"><i class="fas fa-percent me-2"></i>{{ selectedTypeLabel }} por {{ compareLabel.toLowerCase() }} y cuartel — % de exportación</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span v-for="b in selectionBadges" :key="b.label" class="badge bg-light text-dark border fw-normal">
                                    <i class="fas me-1" :class="b.icon"></i>{{ b.label }}: <strong>{{ b.text }}</strong>
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="allExpandedPct ? collapseAllPct() : expandAllPct()">
                            <span :class="allExpandedPct ? 'fas fa-compress-alt' : 'fas fa-expand-alt'" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">{{ allExpandedPct ? 'Contraer todo' : 'Expandir todo' }}</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ compareLabel }} / Cuartel</th>
                                    <th class="text-end">Kg Exportados</th>
                                    <th v-for="c in columns" :key="c.key" class="text-end" :title="c.detail || ''">{{ c.label }}</th>
                                    <th v-if="showUnclassified" class="text-end">Sin clasificar</th>
                                    <th class="text-end">Total clasificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="g in varietyGroups" :key="'pct-' + g.key">
                                    <tr class="table-secondary fw-bold" style="cursor: pointer;" @click="toggleGroupPct(g.key)">
                                        <td class="text-nowrap">
                                            <i class="fas fa-chevron-right fa-xs me-2" :class="{ 'fa-rotate-90': isExpandedPct(g.key) }"></i>{{ g.name }}
                                            <span class="badge bg-light text-dark border ms-1 fw-normal">{{ g.rows.length }} {{ g.rows.length === 1 ? 'cuartel' : 'cuarteles' }}</span>
                                        </td>
                                        <td class="text-end">{{ fmt(g.totals.exported) }}</td>
                                        <td v-for="c in columns" :key="c.key" class="text-end" :style="heatStyle(g.totals, c.key)">{{ cellPct(g.totals, c.key) }}</td>
                                        <td v-if="showUnclassified" class="text-end">{{ unclassifiedPct(g.totals) }}</td>
                                        <td class="text-end">{{ totalPct(g.totals) }}</td>
                                    </tr>
                                    <template v-if="isExpandedPct(g.key)">
                                        <tr v-for="r in g.rows" :key="'pct-' + r.ccv_id">
                                            <td class="ps-5">{{ rowLabel(r) }}</td>
                                            <td class="text-end">{{ r.exported ? fmt(r.exported) : '-' }}</td>
                                            <td v-for="c in columns" :key="c.key" class="text-end" :style="heatStyle(r, c.key)">{{ cellPct(r, c.key) }}</td>
                                            <td v-if="showUnclassified" class="text-end">{{ unclassifiedPct(r) }}</td>
                                            <td class="text-end">{{ totalPct(r) }}</td>
                                        </tr>
                                    </template>
                                </template>
                                <tr v-if="varietyGroups.length === 0">
                                    <td :colspan="columnCount" class="text-center text-muted py-3">No hay datos para los filtros seleccionados.</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end">{{ fmt(grandTotals.exported) }}</td>
                                    <td v-for="c in columns" :key="c.key" class="text-end">{{ cellPct(grandTotals, c.key) }}</td>
                                    <td v-if="showUnclassified" class="text-end">{{ unclassifiedPct(grandTotals) }}</td>
                                    <td class="text-end">{{ totalPct(grandTotals) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Solo considera guías procesadas. El porcentaje se calcula sobre los Kg Exportados; "Sin clasificar" es la parte exportada que no se repartió en {{ selectedTypeLabel.toLowerCase() }}.
                    </small>
                </template>
            </div>
        </div>

        <!-- Modal: configurar grupos -->
        <Modal :maxWidth="'lg'" :id="'classificationGroupsModal'">
            <template #header>
                <div class="d-flex align-items-center gap-2 mb-3 text-start">
                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.2rem;">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    <span>
                        <span class="fw-bold" style="font-size: 1.1rem;">Grupos de {{ selectedTypeLabel.toLowerCase() }} — {{ selectedFruitLabel }}</span>
                        <br>
                        <span class="text-muted" style="font-size: 0.8rem;">Agrupa valores para analizarlos juntos (por ejemplo, "Grandes" = J, XL, L). Un valor solo puede estar en un grupo.</span>
                    </span>
                </div>
            </template>
            <template #body>
                <div v-for="(g, index) in draftGroups" :key="index" class="row g-2 align-items-end mb-2">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Nombre del grupo</label>
                        <input v-model="g.name" type="text" class="form-control form-control-sm" maxlength="60" placeholder="Ej: Grandes" />
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small mb-1">Valores</label>
                        <Multiselect
                            v-model="g.values"
                            mode="tags"
                            :options="optionsFor(index)"
                            placeholder="Seleccione valores..."
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeDraftGroup(index)" title="Quitar grupo">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-falcon-default btn-sm mt-1" @click="addDraftGroup">
                    <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                    <span class="ms-1">Agregar grupo</span>
                </button>
                <div v-if="draftGroups.length === 0" class="text-muted small mt-2">Sin grupos: se mostrará cada valor por separado.</div>
            </template>
            <template #footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" @click="saveGroups">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
