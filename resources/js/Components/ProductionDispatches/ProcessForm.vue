<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    dispatch: { type: Object, required: true },
    classifications: { type: Object, default: () => ({}) },
    costCenterVarieties: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const form = props.form;

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

// Opciones para tipo de clasificación
const classificationTypeOptions = computed(() => {
    const types = Object.keys(currentClassifications.value || {});
    const labels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };
    return types.map(t => ({ value: t, label: labels[t] || t }));
});

function getValueOptions(type) {
    if (!type || !currentClassifications.value[type]) return [];
    return currentClassifications.value[type].map(c => ({
        value: c.value,
        label: c.value,
    }));
}

function addItem() {
    form.items.push({
        classification_type: '',
        classification_value: '',
        kg: 0,
        boxes: null,
    });
}

function removeItem(index) {
    form.items.splice(index, 1);
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

const typeLabels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };

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

defineExpose({ anyTypeExceeded, kgBreakdownExceeded });

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

        <!-- Desglose por Clasificación -->
        <div v-if="selectedFruitId && Object.keys(currentClassifications).length > 0" class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-chart-pie me-1"></i>Desglose por Clasificación
                </h6>
                <button type="button" class="btn btn-sm btn-falcon-default" @click="addItem">
                    <i class="fas fa-plus me-1"></i> Agregar fila
                </button>
            </div>

            <div class="table-responsive" v-if="form.items && form.items.length > 0">
                <table class="table table-sm table-bordered fs-10 mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 28%;">Tipo</th>
                            <th style="width: 28%;">Valor</th>
                            <th style="width: 20%;">Kg</th>
                            <th style="width: 16%;">Cajas</th>
                            <th style="width: 8%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td>
                                <select v-model="item.classification_type" class="form-select form-select-sm">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="opt in classificationTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </td>
                            <td>
                                <select v-model="item.classification_value" class="form-select form-select-sm">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="opt in getValueOptions(item.classification_type)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" v-model="item.kg" class="form-control form-control-sm" step="0.01" min="0"
                                    :class="{'is-invalid': item.classification_type && typeExceeded(item.classification_type)}" />
                            </td>
                            <td>
                                <input type="number" v-model="item.boxes" class="form-control form-control-sm" min="0" />
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm p-1" @click="removeItem(index)" title="Eliminar fila">
                                    <i class="fas fa-times text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr v-for="type in Object.keys(totalsByType)" :key="type">
                            <td colspan="2" class="text-end fw-bold">Total {{ typeLabels[type] || type }}:</td>
                            <td :class="typeExceeded(type) ? 'text-danger fw-bold' : 'fw-bold'">
                                {{ Number(totalsByType[type]).toLocaleString('es-CL') }}
                                <small class="text-muted">/ {{ kgLimit.toLocaleString('es-CL') }}</small>
                                <i v-if="typeExceeded(type)" class="fas fa-exclamation-triangle text-danger ms-1"></i>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-else class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>Agregue filas para detallar la clasificación del proceso.
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


