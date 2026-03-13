<script setup>
import { computed, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    dispatch: { type: Object, required: true },
    classifications: { type: Object, default: () => ({}) },
    costCenterVarieties: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const form = props.form;

// Cargar datos existentes si el despacho ya fue procesado
function loadExistingData() {
    if (props.dispatch?.status === 'processed') {
        form.process_date = props.dispatch.process_date || '';
        form.kg_received = props.dispatch.kg_received || '';
        form.kg_exported = props.dispatch.kg_exported || '';
        form.kg_national = props.dispatch.kg_national || '';
        form.kg_industrial = props.dispatch.kg_industrial || '';
        form.kg_waste = props.dispatch.kg_waste || '';
        form.items = (props.dispatch.items || []).map(item => ({
            classification_type: item.classification_type,
            classification_value: item.classification_value,
            kg: Number(item.kg) || 0,
            boxes: item.boxes,
        }));
    }
}

// Obtener fruit_id del despacho
const selectedFruitId = computed(() => {
    if (!props.dispatch?.cost_center_variety_id) return null;
    const ccv = props.costCenterVarieties.find(c => c.value === props.dispatch.cost_center_variety_id);
    return ccv ? ccv.fruit_id : null;
});

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
const kgLimit = computed(() => Number(props.dispatch.kg_dispatched) || 0);

const totalsByType = computed(() => {
    const totals = {};
    (form.items || []).forEach(item => {
        if (item.classification_type) {
            totals[item.classification_type] = (totals[item.classification_type] || 0) + Number(item.kg || 0);
        }
    });
    return totals;
});

function typeExceeded(type) {
    return (totalsByType.value[type] || 0) > kgLimit.value;
}

function anyTypeExceeded() {
    return Object.keys(totalsByType.value).some(t => typeExceeded(t));
}

// Validación: Export+Nacional+Industrial+Descarte <= Kilos a Proceso
const kgBreakdownTotal = computed(() => {
    return Number(form.kg_exported || 0) + Number(form.kg_national || 0) + Number(form.kg_industrial || 0) + Number(form.kg_waste || 0);
});
const kgBreakdownExceeded = computed(() => {
    const limit = Number(form.kg_received) || 0;
    return limit > 0 && kgBreakdownTotal.value > limit;
});
const kgBreakdownRemaining = computed(() => {
    return Math.max(0, (Number(form.kg_received) || 0) - kgBreakdownTotal.value);
});

defineExpose({ anyTypeExceeded, kgBreakdownExceeded, ensureItemsInitialized });

watch(form, () => emit('update:form', form), { deep: true });
</script>

<template>
    <form @submit.prevent>
        <!-- Info del despacho (solo lectura) -->
        <div class="card border mb-3">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-soft-primary text-primary px-2 py-1">
                        <i class="fas fa-map-marker-alt me-1"></i>{{ dispatch.cost_center_variety?.cost_center?.name || '-' }}
                    </span>
                    <span class="badge bg-soft-success text-success px-2 py-1">
                        {{ dispatch.cost_center_variety?.variety?.name || '-' }}
                        <small v-if="dispatch.cost_center_variety?.fruit"> ({{ dispatch.cost_center_variety.fruit.name }})</small>
                    </span>
                    <span class="badge bg-soft-info text-info px-2 py-1">
                        <i class="fas fa-building me-1"></i>{{ dispatch.exporter?.name || '-' }}
                    </span>
                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                        <i class="fas fa-tag me-1"></i>Lote: {{ dispatch.lot_number || '-' }}
                    </span>
                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                        <i class="fas fa-file-alt me-1"></i>Guía: {{ dispatch.guide_number }}
                    </span>
                    <span class="badge bg-soft-warning text-warning px-2 py-1 ms-auto">
                        <i class="fas fa-weight-hanging me-1"></i>{{ Number(dispatch.kg_dispatched || 0).toLocaleString('es-CL') }} kg
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
                <label class="form-label small mb-1">Kilos a Proceso</label>
                <input type="number" v-model="form.kg_received" class="form-control form-control-sm" step="0.01" min="0" required />
            </div>

            <!-- Kg Exportación -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Exportación</label>
                <input type="number" v-model="form.kg_exported" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Nacional -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Nacional</label>
                <input type="number" v-model="form.kg_national" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Industrial -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Industrial</label>
                <input type="number" v-model="form.kg_industrial" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Kg Descarte -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Kg Descarte</label>
                <input type="number" v-model="form.kg_waste" class="form-control form-control-sm" step="0.01" min="0"
                    :class="{'is-invalid': kgBreakdownExceeded}" />
            </div>

            <!-- Resumen kg desglose -->
            <div class="col-12" v-if="Number(form.kg_received) > 0">
                <small :class="kgBreakdownExceeded ? 'text-danger fw-bold' : 'text-muted'">
                    <i v-if="kgBreakdownExceeded" class="fas fa-exclamation-triangle me-1"></i>
                    Total asignado: {{ Number(kgBreakdownTotal).toLocaleString('es-CL') }} / {{ Number(form.kg_received).toLocaleString('es-CL') }} kg a proceso
                    <span v-if="kgBreakdownRemaining > 0">(quedan {{ Number(kgBreakdownRemaining).toLocaleString('es-CL') }} kg)</span>
                    <span v-if="kgBreakdownExceeded"> — Excede los kilos a proceso</span>
                </small>
            </div>
        </div>

        <!-- Desglose por Clasificación (Matriz) -->
        <div v-if="selectedFruitId && Object.keys(currentClassifications).length > 0" class="mt-3">
            <h6 class="mb-2 fw-bold">
                <i class="fas fa-chart-pie me-1"></i>Desglose por Clasificación
            </h6>

            <div v-for="(values, type) in currentClassifications" :key="type" class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold small">
                        <i class="fas me-1" :class="typeIcons[type] || 'fa-tag'"></i>
                        {{ typeLabels[type] || type }}
                    </span>
                    <small :class="typeExceeded(type) ? 'text-danger fw-bold' : 'text-muted'">
                        Total: {{ Number(totalsByType[type] || 0).toLocaleString('es-CL') }} / {{ kgLimit.toLocaleString('es-CL') }} kg
                        <i v-if="typeExceeded(type)" class="fas fa-exclamation-triangle ms-1"></i>
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered fs-10 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;"></th>
                                <th v-for="cls in values" :key="cls.value" class="text-center" style="min-width: 80px;">
                                    {{ cls.value }}
                                </th>
                                <th class="text-center" style="min-width: 80px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th class="text-end small align-middle">Kg</th>
                                <td v-for="cls in values" :key="cls.value" class="p-1">
                                    <input
                                        v-if="getItem(type, cls.value)"
                                        type="number"
                                        v-model.number="getItem(type, cls.value).kg"
                                        class="form-control form-control-sm text-center"
                                        step="0.01" min="0" placeholder="0"
                                        :class="{'is-invalid': typeExceeded(type)}"
                                    />
                                </td>
                                <td class="text-center align-middle fw-bold" :class="typeExceeded(type) ? 'text-danger' : ''">
                                    {{ Number(totalsByType[type] || 0).toLocaleString('es-CL') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
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


