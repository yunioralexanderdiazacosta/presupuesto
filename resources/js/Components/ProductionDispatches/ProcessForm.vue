<script setup>
import { computed, watch, nextTick, onMounted, ref } from 'vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    form: { type: Object, required: true },
    dispatch: { type: Object, required: true },
    lot: { type: Object, required: true },
    classifications: { type: Object, default: () => ({}) },
    dispatches: { type: Array, default: () => [] },
    processes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const form = props.form;

function n(val) {
    const num = parseFloat(val);
    return isNaN(num) ? 0 : num;
}

// Formato para mostrar totales: redondea a 2 decimales y evita errores de punto flotante
function fmt(val) {
    return Math.round(n(val) * 100) / 100;
}

function fruitOf(dispatch) {
    return dispatch?.cost_centers?.[0]?.cost_center_variety?.fruit_id ?? null;
}

// Frutal de la guía: los lotes de un proceso deben ser del mismo frutal
const selectedFruitId = computed(() => fruitOf(props.dispatch));

// Lotes de la temporada del mismo frutal (de cualquier guía) con su saldo sin procesar
const lotPool = computed(() => {
    const pool = [];
    props.dispatches
        .filter(d => String(fruitOf(d)) === String(selectedFruitId.value))
        .forEach(d => (d.lots || []).forEach(l => pool.push({
            id: l.id,
            lot_number: l.lot_number,
            guide_number: d.guide_number,
            company_reason: d.company_reason?.name || '',
            packing: d.packing_house?.name || '',
            kg_received: n(l.kg_received),
            used: n(l.kg_to_process),
        })));
    return pool;
});

function poolLot(id) {
    return lotPool.value.find(l => String(l.id) === String(id));
}

// Procesos en los que ya participa el lote desde el que se abrió el modal
const lotProcesses = computed(() =>
    props.processes.filter(p => (p.allocations || []).some(a => String(a.production_lot_id) === String(props.lot.id)))
);

// Proceso que se está editando (null = proceso nuevo)
const currentProcess = computed(() =>
    props.processes.find(p => String(p.id) === String(form.process_id)) || null
);

// Al editar, lo que el proceso ya aportó vuelve a estar disponible para el propio proceso
function ownKg(lotId) {
    const alloc = (currentProcess.value?.allocations || []).find(a => String(a.production_lot_id) === String(lotId));
    return n(alloc?.kg_to_process);
}

function saldoOf(lotId) {
    const l = poolLot(lotId);
    return l ? Math.max(0, l.kg_received - l.used + ownKg(lotId)) : 0;
}

const processKey = ref('new');
const processOptions = computed(() => [
    { value: 'new', label: 'Nuevo proceso' },
    ...lotProcesses.value.map(p => ({ value: String(p.id), label: `Editar proceso N° ${p.process_number}` })),
]);

function initialProcessKey() {
    // Un lote ya procesado por completo se vuelve a abrir en su proceso para corregirlo
    if (lotProcesses.value.length && saldoOf(props.lot.id) <= 0.001) {
        return String(lotProcesses.value[0].id);
    }
    return 'new';
}

function loadProcess() {
    const p = processKey.value === 'new'
        ? null
        : props.processes.find(x => String(x.id) === processKey.value);

    form.process_id = p?.id ?? null;
    form.process_number = p?.process_number ?? '';
    form.process_date = p?.process_date ? String(p.process_date).slice(0, 10) : new Date().toLocaleDateString('en-CA');
    form.kg_exported = p ? n(p.kg_exported) || '' : '';
    form.kg_national = p ? n(p.kg_national) || '' : '';
    form.kg_industrial = p ? n(p.kg_industrial) || '' : '';
    form.kg_waste = p ? n(p.kg_waste) || '' : '';

    if (p) {
        form.lots = (p.allocations || []).map(a => ({ id: a.production_lot_id, kg: n(a.kg_to_process) }));
        // Los ítems del proceso están repartidos por lote: se vuelven a sumar para mostrar el total ingresado
        const totals = new Map();
        (p.items || []).forEach(i => {
            const key = `${i.classification_type}|${i.classification_value}`;
            const acc = totals.get(key) || { classification_type: i.classification_type, classification_value: i.classification_value, kg: 0, boxes: null };
            acc.kg += n(i.kg);
            if (i.boxes !== null && i.boxes !== undefined) acc.boxes = (acc.boxes || 0) + Number(i.boxes);
            totals.set(key, acc);
        });
        form.items = [...totals.values()].map(i => ({ ...i, kg: fmt(i.kg) }));
    } else {
        form.lots = [{ id: props.lot.id, kg: fmt(saldoOf(props.lot.id)) || '' }];
        form.items = [];
    }
    nextTick(() => initializeItems());
}

watch(processKey, () => loadProcess());

// Clasificaciones filtradas por frutal
const currentClassifications = computed(() => {
    if (!selectedFruitId.value || !props.classifications[selectedFruitId.value]) return {};
    return props.classifications[selectedFruitId.value];
});

const typeLabels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };
const typeIcons = { caliber: 'fa-ruler', color: 'fa-palette', quality: 'fa-star' };

// Inicializar items desde clasificaciones (enfoque matriz)
function initializeItems() {
    const cls = currentClassifications.value;
    if (!cls || Object.keys(cls).length === 0) return;

    for (const type of Object.keys(cls)) {
        for (const c of cls[type]) {
            const exists = form.items.find(
                i => i.classification_type === type && i.classification_value === c.value
            );
            if (!exists) {
                form.items.push({
                    classification_type: type,
                    classification_value: c.value,
                    kg: 0,
                    boxes: null,
                });
            }
        }
    }
}

watch(currentClassifications, () => {
    nextTick(() => initializeItems());
});

onMounted(() => {
    processKey.value = initialProcessKey();
    loadProcess();
});

function ensureItemsInitialized() {
    initializeItems();
}

// --- Lotes del proceso ---
const lotRows = computed(() => (form.lots || []).map(l => {
    const info = poolLot(l.id);
    return {
        id: l.id,
        lot_number: info?.lot_number ?? l.id,
        guide_number: info?.guide_number ?? '-',
        company_reason: info?.company_reason ?? '',
        packing: info?.packing ?? '',
        kg_received: info?.kg_received ?? 0,
        saldo: saldoOf(l.id),
    };
}));

const kgToProcess = computed(() => (form.lots || []).reduce((sum, l) => sum + n(l.kg), 0));

function lotExceeded(index) {
    const l = form.lots[index];
    return n(l.kg) - saldoOf(l.id) > 0.001;
}
const anyLotExceeded = () => (form.lots || []).some((_, i) => lotExceeded(i));

function lotPct(index) {
    return kgToProcess.value > 0 ? ((n(form.lots[index].kg) / kgToProcess.value) * 100).toFixed(1) + '%' : '';
}

// Lotes que se pueden agregar: mismo frutal, con saldo y aún no incluidos
const addableLots = computed(() => lotPool.value
    .filter(l => !(form.lots || []).some(x => String(x.id) === String(l.id)))
    .filter(l => saldoOf(l.id) > 0.001)
    .map(l => ({
        value: l.id,
        label: `Lote ${l.lot_number} · Guía ${l.guide_number}${l.packing ? ' · ' + l.packing : ''} · saldo ${fmt(saldoOf(l.id))} kg`,
    })));

const lotToAdd = ref(null);
watch(lotToAdd, (id) => {
    if (id === null || id === undefined || id === '') return;
    form.lots.push({ id, kg: fmt(saldoOf(id)) || '' });
    nextTick(() => { lotToAdd.value = null; });
});

function removeLot(index) {
    form.lots.splice(index, 1);
}

// --- Totales por tipo de clasificación (se desglosan sobre los Kg de Exportación) ---
function getItem(type, value) {
    return form.items.find(i => i.classification_type === type && i.classification_value === value);
}

const kgLimit = computed(() => n(form.kg_exported));

const totalsByType = computed(() => {
    const totals = {};
    (form.items || []).forEach(item => {
        if (item.classification_type) {
            totals[item.classification_type] = (totals[item.classification_type] || 0) + n(item.kg);
        }
    });
    return totals;
});

function typeExceeded(type) {
    return (totalsByType.value[type] || 0) - kgLimit.value > 0.001;
}

function anyTypeExceeded() {
    return Object.keys(totalsByType.value).some(t => typeExceeded(t));
}

function statusOf(type) {
    const total = totalsByType.value[type] || 0;
    if (typeExceeded(type)) return 'exceeded';
    if (kgLimit.value > 0 && Math.abs(total - kgLimit.value) <= 0.001) return 'complete';
    return total > 0 ? 'partial' : 'empty';
}
const progressPct = (type) => {
    if (kgLimit.value <= 0) return typeExceeded(type) ? 100 : 0;
    return Math.min(100, ((totalsByType.value[type] || 0) / kgLimit.value) * 100);
};
const progressClass = (type) => ({ exceeded: 'bg-danger', complete: 'bg-success', partial: 'bg-primary', empty: 'bg-secondary' }[statusOf(type)]);
const remainingKg = (type) => Math.max(0, kgLimit.value - (totalsByType.value[type] || 0));
const excessKg = (type) => Math.max(0, (totalsByType.value[type] || 0) - kgLimit.value);
function itemPct(type, value) {
    const kg = n(getItem(type, value)?.kg);
    return kgLimit.value > 0 && kg > 0 ? ((kg / kgLimit.value) * 100).toFixed(1) + '%' : '';
}

// Export+Nacional+Industrial+Descarte <= Kilos a Proceso
const kgBreakdownTotal = computed(() => n(form.kg_exported) + n(form.kg_national) + n(form.kg_industrial) + n(form.kg_waste));
const kgBreakdownExceeded = computed(() => kgBreakdownTotal.value > 0 && kgBreakdownTotal.value - kgToProcess.value > 0.001);
const kgBreakdownRemaining = computed(() => Math.max(0, kgToProcess.value - kgBreakdownTotal.value));

// --- Prorrateo: mismo algoritmo que el servidor (el resto del redondeo va a las partes con mayor fracción) ---
function splitProportional(total, weights, decimals = 2) {
    const sum = weights.reduce((a, b) => a + b, 0);
    if (sum <= 0) return weights.map(() => 0);
    const scale = 10 ** decimals;
    const totalUnits = Math.round(total * scale);
    const exact = weights.map(w => (totalUnits * w) / sum);
    const units = exact.map(x => Math.floor(x + 1e-9));
    const missing = totalUnits - units.reduce((a, b) => a + b, 0);
    const order = exact.map((x, i) => i).sort((a, b) => (exact[b] - units[b]) - (exact[a] - units[a]) || a - b);
    for (let i = 0; i < missing; i++) units[order[i]]++;
    return units.map(u => u / scale);
}

const preview = computed(() => {
    const weights = (form.lots || []).map(l => n(l.kg));
    const fields = ['kg_exported', 'kg_national', 'kg_industrial', 'kg_waste'];
    const byField = Object.fromEntries(fields.map(f => [f, splitProportional(n(form[f]), weights)]));
    return (form.lots || []).map((l, i) => ({
        id: l.id,
        kg_to_process: weights[i],
        ...Object.fromEntries(fields.map(f => [f, byField[f][i]])),
    }));
});

const previewTotals = computed(() => {
    const t = { kg_to_process: 0, kg_exported: 0, kg_national: 0, kg_industrial: 0, kg_waste: 0 };
    preview.value.forEach(r => Object.keys(t).forEach(k => { t[k] += r[k]; }));
    return t;
});

defineExpose({ anyTypeExceeded, anyLotExceeded, kgBreakdownExceeded, ensureItemsInitialized, lotCount: () => (form.lots || []).length });

watch(form, () => emit('update:form', form), { deep: true });
</script>

<template>
    <form @submit.prevent>
        <!-- Info del lote desde el que se abrió -->
        <div class="card border mb-3">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-soft-info text-info px-2 py-1">
                        <i class="fas fa-building me-1"></i>{{ dispatch.exporter?.name || '-' }}
                    </span>
                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                        <i class="fas fa-tag me-1"></i>Lote: {{ lot.lot_number }}
                    </span>
                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                        <i class="fas fa-file-alt me-1"></i>Guía: {{ dispatch.guide_number }}
                    </span>
                    <span v-if="dispatch.company_reason" class="badge bg-soft-info text-info px-2 py-1">
                        <i class="fas fa-file-invoice me-1"></i>{{ dispatch.company_reason.name }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-md-4" v-if="lotProcesses.length">
                <label class="form-label small mb-1">Proceso</label>
                <Multiselect v-model="processKey" class="multiselect-sm" :options="processOptions" :can-clear="false" :searchable="false" />
            </div>

            <div class="col-md-4">
                <label class="form-label small mb-1">N° Proceso</label>
                <input type="text" v-model="form.process_number" class="form-control form-control-sm" maxlength="50" required />
            </div>

            <div class="col-md-4">
                <label class="form-label small mb-1">Fecha Proceso</label>
                <input type="date" v-model="form.process_date" class="form-control form-control-sm" required />
            </div>
        </div>

        <!-- Lotes del proceso -->
        <div class="card border mt-3">
            <div class="card-header py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold"><i class="fas fa-boxes me-2 text-primary"></i>Lotes del proceso</span>
                <div style="width: 620px; max-width: 100%;" v-if="addableLots.length">
                    <Multiselect v-model="lotToAdd" class="multiselect-sm" :options="addableLots" :searchable="true" placeholder="Agregar lote..." no-options-text="Sin lotes disponibles" no-results-text="Sin resultados" />
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm fs-10 mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Lote</th>
                            <th>Guía</th>
                            <th>Razón Social</th>
                            <th class="text-end">Kg Recep.</th>
                            <th class="text-end">Saldo</th>
                            <th style="width: 160px;">Kg a proceso</th>
                            <th class="text-end">% del proceso</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in lotRows" :key="row.id">
                            <td class="fw-semibold">{{ row.lot_number }}</td>
                            <td>{{ row.guide_number }}</td>
                            <td>{{ row.company_reason || '-' }}</td>
                            <td class="text-end">{{ fmt(row.kg_received) }}</td>
                            <td class="text-end">{{ fmt(row.saldo) }}</td>
                            <td>
                                <input type="number" v-model.number="form.lots[i].kg" class="form-control form-control-sm text-end" step="0.01" min="0" :max="row.saldo"
                                    :class="{ 'is-invalid': lotExceeded(i) }" />
                            </td>
                            <td class="text-end">{{ lotPct(i) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-falcon-default p-0" style="width: 24px; height: 24px;" title="Quitar lote"
                                    :disabled="form.lots.length <= 1" @click="removeLot(i)">
                                    <i class="fas fa-times text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">Kilos a Proceso</td>
                            <td class="text-end pe-3">{{ fmt(kgToProcess) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-if="anyLotExceeded()" class="px-3 pb-2 small text-danger">
                <i class="fas fa-exclamation-triangle me-1"></i>Algún lote supera su saldo disponible.
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Exportación</label>
                <input type="number" v-model.number="form.kg_exported" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Nacional</label>
                <input type="number" v-model.number="form.kg_national" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Industrial</label>
                <input type="number" v-model.number="form.kg_industrial" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Descarte</label>
                <input type="number" v-model.number="form.kg_waste" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <div class="col-12" v-if="kgToProcess > 0">
                <div class="alert py-1 px-2 mb-0 fs-10" :class="kgBreakdownExceeded ? 'alert-danger' : 'alert-light'">
                    <i v-show="kgBreakdownExceeded" class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Total asignado:</strong>
                    {{ fmt(kgBreakdownTotal) }} de {{ fmt(kgToProcess) }} kg
                    {{ kgBreakdownRemaining > 0 ? '— quedan ' + fmt(kgBreakdownRemaining) + ' kg sin asignar' : '' }}
                    {{ kgBreakdownExceeded ? '— Excede los kilos a proceso' : '' }}
                </div>
            </div>
        </div>

        <!-- Desglose por Clasificación (Matriz) -->
        <div v-if="selectedFruitId && Object.keys(currentClassifications).length > 0" class="mt-3">
            <h6 class="mb-2 fw-bold">
                <i class="fas fa-chart-pie me-1"></i>Desglose por Clasificación
                <small class="text-muted fw-normal ms-1">sobre los {{ fmt(kgLimit) }} kg de exportación del proceso</small>
            </h6>

            <div v-if="kgLimit <= 0" class="alert alert-light border py-2 fs-10 mb-2">
                <i class="fas fa-info-circle me-1"></i>Ingresa primero los <strong>Kg Exportación</strong> para repartirlos por clasificación.
            </div>

            <div v-for="(values, type) in currentClassifications" :key="type" class="card border mb-2" :class="{ 'border-danger': typeExceeded(type) }">
                <div class="card-header py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="fw-semibold">
                        <i class="fas me-2 text-primary" :class="typeIcons[type] || 'fa-tag'"></i>{{ typeLabels[type] || type }}
                    </span>
                    <div class="d-flex align-items-center gap-2" style="min-width: 260px;">
                        <div class="progress flex-grow-1" style="height: 6px;">
                            <div class="progress-bar" :class="progressClass(type)" :style="{ width: progressPct(type) + '%' }"></div>
                        </div>
                        <small class="text-nowrap" :class="typeExceeded(type) ? 'text-danger fw-bold' : 'text-muted'">
                            {{ fmt(totalsByType[type] || 0) }} / {{ fmt(kgLimit) }} kg
                        </small>
                    </div>
                </div>

                <div class="card-body py-2 px-3">
                    <div class="row g-2">
                        <div v-for="cls in values" :key="cls.value" class="col-6 col-sm-4 col-md-3 col-xl-2">
                            <label class="form-label small fw-semibold mb-1 d-flex justify-content-between">
                                <span>{{ cls.value }}</span>
                                <span class="text-muted fw-normal">{{ itemPct(type, cls.value) }}</span>
                            </label>
                            <div v-if="getItem(type, cls.value)" class="input-group input-group-sm">
                                <input
                                    type="number"
                                    v-model.number="getItem(type, cls.value).kg"
                                    class="form-control text-end"
                                    step="0.01" min="0" placeholder="0"
                                    :class="{ 'is-invalid': typeExceeded(type) }"
                                />
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="kgLimit > 0 || typeExceeded(type)" class="mt-2 small">
                        <span v-if="statusOf(type) === 'complete'" class="text-success"><i class="fas fa-check-circle me-1"></i>Completo</span>
                        <span v-else-if="statusOf(type) === 'exceeded'" class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Excede en {{ fmt(excessKg(type)) }} kg</span>
                        <span v-else class="text-muted">Faltan {{ fmt(remainingKg(type)) }} kg por repartir</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="selectedFruitId && Object.keys(currentClassifications).length === 0" class="mt-3">
            <div class="alert alert-warning py-2 fs-10 mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                No hay clasificaciones configuradas para este frutal. Use el botón <strong>Catálogos</strong> para configurarlas.
            </div>
        </div>

        <!-- Vista previa del prorrateo por lote -->
        <div v-if="lotRows.length > 1 && kgToProcess > 0" class="card border mt-3">
            <div class="card-header py-2 px-3">
                <span class="fw-semibold"><i class="fas fa-balance-scale me-2 text-primary"></i>Prorrateo por lote</span>
                <small class="text-muted ms-2">Se reparte en proporción a los kg a proceso de cada lote; las clasificaciones se reparten igual.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm fs-10 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lote</th>
                            <th class="text-end">Kg Proceso</th>
                            <th class="text-end">Exportación</th>
                            <th class="text-end">Nacional</th>
                            <th class="text-end">Industrial</th>
                            <th class="text-end">Descarte</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in preview" :key="row.id">
                            <td class="fw-semibold">{{ lotRows[i]?.lot_number }}</td>
                            <td class="text-end">{{ fmt(row.kg_to_process) }}</td>
                            <td class="text-end">{{ fmt(row.kg_exported) }}</td>
                            <td class="text-end">{{ fmt(row.kg_national) }}</td>
                            <td class="text-end">{{ fmt(row.kg_industrial) }}</td>
                            <td class="text-end">{{ fmt(row.kg_waste) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">{{ fmt(previewTotals.kg_to_process) }}</td>
                            <td class="text-end">{{ fmt(previewTotals.kg_exported) }}</td>
                            <td class="text-end">{{ fmt(previewTotals.kg_national) }}</td>
                            <td class="text-end">{{ fmt(previewTotals.kg_industrial) }}</td>
                            <td class="text-end">{{ fmt(previewTotals.kg_waste) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>
</template>

<style>
/* Mismo alto y tamaño de letra que form-control-sm de Bootstrap */
.multiselect.multiselect-sm {
    --ms-font-size: 0.875rem;
    --ms-line-height: 1.5;
    --ms-py: 0.1875rem;
    --ms-px: 0.75rem;
    --ms-option-font-size: 0.8rem;
    --ms-option-py: 0.2rem;
    min-height: calc(1.5em + 0.375rem + 2px);
    font-size: 0.875rem;
}
.multiselect.multiselect-sm .multiselect-placeholder,
.multiselect.multiselect-sm .multiselect-search input {
    font-size: 0.875rem;
}
</style>
