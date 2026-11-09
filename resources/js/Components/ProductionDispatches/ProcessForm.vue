<script setup>
import { computed, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    dispatch: { type: Object, required: true },
    lot: { type: Object, required: true },
    classifications: { type: Object, default: () => ({}) },
    costCenterVarieties: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const form = props.form;

// Cargar datos existentes si el lote ya fue procesado; si no, propone todo lo recepcionado
function loadExistingData() {
    if (props.lot?.status === 'processed') {
        form.process_date = props.lot.process_date || '';
        form.kg_to_process = Number(props.lot.kg_to_process) || '';
        form.kg_exported = Number(props.lot.kg_exported) || '';
        form.kg_national = Number(props.lot.kg_national) || '';
        form.kg_industrial = Number(props.lot.kg_industrial) || '';
        form.kg_waste = Number(props.lot.kg_waste) || '';
        form.items = (props.lot.items || []).map(item => ({
            classification_type: item.classification_type,
            classification_value: item.classification_value,
            kg: Number(item.kg) || 0,
            boxes: item.boxes,
        }));
    } else {
        form.process_date = new Date().toLocaleDateString('en-CA');
        form.kg_to_process = Number(props.lot.kg_received) || '';
    }
}

// Frutal de la guía: todos sus cuarteles son del mismo frutal
const selectedFruitId = computed(() => {
    const first = props.dispatch?.cost_centers?.[0]?.cost_center_variety;
    return first?.fruit_id ?? null;
});

// Clasificaciones filtradas por frutal
const currentClassifications = computed(() => {
    if (!selectedFruitId.value || !props.classifications[selectedFruitId.value]) return {};
    return props.classifications[selectedFruitId.value];
});

const typeLabels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };
const typeIcons = { caliber: 'fa-ruler', color: 'fa-palette', quality: 'fa-star' };

function n(val) {
    const num = parseFloat(val);
    return isNaN(num) ? 0 : num;
}

// Formato para mostrar totales: redondea a 2 decimales y evita errores de punto flotante
function fmt(val) {
    return Math.round(n(val) * 100) / 100;
}

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
    loadExistingData();
    nextTick(() => initializeItems());
});

// Exponer para que el modal pueda inicializar después de cargar datos
function ensureItemsInitialized() {
    initializeItems();
}

function getItem(type, value) {
    return form.items.find(i => i.classification_type === type && i.classification_value === value);
}

// Totales por tipo de clasificación
// Las clasificaciones se desglosan sobre los Kg de Exportación
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

// Estado de cada tipo respecto a los kg de exportación, para la barra de avance y el mensaje
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

// Tope absoluto: Kg Recepcionados del lote en el packing
const kgReceivedLot = computed(() => n(props.lot.kg_received));

// Validación: Kilos a Proceso no puede superar los Kg Recepcionados
const kgToProcessExceeded = computed(() => {
    const toProcess = n(form.kg_to_process);
    return toProcess > 0 && toProcess - kgReceivedLot.value > 0.001;
});

// Validación: Export+Nacional+Industrial+Descarte <= Kilos a Proceso (y <= Kg Recepcionados)
const kgBreakdownTotal = computed(() => {
    return n(form.kg_exported) + n(form.kg_national) + n(form.kg_industrial) + n(form.kg_waste);
});
const kgBreakdownLimit = computed(() => {
    const toProcess = n(form.kg_to_process);
    return toProcess > 0 ? Math.min(toProcess, kgReceivedLot.value) : kgReceivedLot.value;
});
const kgBreakdownExceeded = computed(() => {
    return kgBreakdownTotal.value > 0 && kgBreakdownTotal.value - kgBreakdownLimit.value > 0.001;
});
const kgBreakdownRemaining = computed(() => {
    return Math.max(0, kgBreakdownLimit.value - kgBreakdownTotal.value);
});
const kgBreakdownPercent = computed(() => {
    if (kgBreakdownLimit.value <= 0) return 0;
    return (kgBreakdownTotal.value / kgBreakdownLimit.value) * 100;
});

defineExpose({ anyTypeExceeded, kgBreakdownExceeded, kgToProcessExceeded, ensureItemsInitialized });

watch(form, () => emit('update:form', form), { deep: true });
</script>

<template>
    <form @submit.prevent>
        <!-- Info del despacho (solo lectura) -->
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
                        <i class="fas fa-file-alt me-1"></i>Guía: {{ dispatch.guide_number }} ({{ fmt(dispatch.kg_dispatched) }} kg)
                    </span>
                    <span class="badge bg-soft-warning text-warning px-2 py-1 ms-auto">
                        <i class="fas fa-weight-hanging me-1"></i>{{ fmt(lot.kg_received) }} kg recepcionados
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <!-- Fecha Proceso -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Fecha Proceso</label>
                <input type="date" v-model="form.process_date" class="form-control form-control-sm" required />
            </div>

            <!-- Kilos a Proceso -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kilos a Proceso <small class="text-muted">(máx {{ fmt(kgReceivedLot) }})</small></label>
                <input type="number" v-model.number="form.kg_to_process" class="form-control form-control-sm" step="0.01" min="0" :max="kgReceivedLot" required
                    :class="{'is-invalid': kgToProcessExceeded}" />
                <div v-if="kgToProcessExceeded" class="invalid-feedback">No puede superar los {{ fmt(kgReceivedLot) }} kg recepcionados</div>
            </div>

            <!-- Kg Exportación -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Exportación</label>
                <input type="number" v-model.number="form.kg_exported" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Nacional -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Nacional</label>
                <input type="number" v-model.number="form.kg_national" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Industrial -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Industrial</label>
                <input type="number" v-model.number="form.kg_industrial" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Descarte -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Descarte</label>
                <input type="number" v-model.number="form.kg_waste" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Resumen kg desglose -->
            <div class="col-12" v-if="kgBreakdownLimit > 0">
                <div class="alert py-1 px-2 mb-0 fs-10" :class="kgBreakdownExceeded ? 'alert-danger' : 'alert-light'">
                    <i v-show="kgBreakdownExceeded" class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Total asignado:</strong>
                    {{ fmt(kgBreakdownTotal) }} de {{ fmt(kgBreakdownLimit) }} kg
                    {{ kgBreakdownRemaining > 0 ? '— quedan ' + fmt(kgBreakdownRemaining) + ' kg' : '' }}
                    {{ kgBreakdownExceeded ? '— Excede el límite' : '' }}
                </div>
            </div>
        </div>

        <!-- Desglose por Clasificación (Matriz) -->
        <div v-if="selectedFruitId && Object.keys(currentClassifications).length > 0" class="mt-3">
            <h6 class="mb-2 fw-bold">
                <i class="fas fa-chart-pie me-1"></i>Desglose por Clasificación
                <small class="text-muted fw-normal ms-1">sobre los {{ fmt(kgLimit) }} kg de exportación</small>
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
    </form>
</template>


