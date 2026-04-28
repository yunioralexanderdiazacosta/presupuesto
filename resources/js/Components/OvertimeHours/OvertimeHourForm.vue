<script setup>
import { ref, computed, watch } from 'vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    form: Object,
    contracts: Array,
    months: Array,
    overtimeTypes: Array,
    costCenters: Array,
    groupings: Array,
    laborTypes: Array,
    level3s: Array,
});

// Estado para expandir/colapsar tags de CC
const expandedCC = ref(false);

// Agrupación → preselección de CC
const groupingFilter = ref(null);
watch(groupingFilter, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        props.form.cost_center_ids = grouping.cost_centers.map(cc => cc.id);
    }
});

// Nivel 3 → filtra labores
const level3Filter = ref('');
const filteredLaborTypes = computed(() => {
    if (!level3Filter.value) return [];
    return props.laborTypes.filter(lt => String(lt.level3_id) === String(level3Filter.value));
});

// Al editar: si ya hay labor seleccionada pero level3Filter está vacío, auto-detectar nivel3
watch(() => props.form.labor_type_id, (laborId) => {
    if (laborId && !level3Filter.value) {
        const lt = props.laborTypes?.find(l => String(l.value) === String(laborId));
        if (lt?.level3_id) {
            level3Filter.value = lt.level3_id;
        }
    }
}, { immediate: true });
</script>

<template>
    <div class="row g-3">
        <!-- Colaborador -->
        <div class="col-md-6">
            <label class="form-label small mb-1">Colaborador <span class="text-danger">*</span></label>
            <select v-model="form.contract_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.contract_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="c in contracts" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <div v-if="form.errors?.contract_id" class="invalid-feedback">{{ form.errors.contract_id }}</div>
        </div>

        <!-- Mes -->
        <div class="col-md-4">
            <label class="form-label small mb-1">Mes <span class="text-danger">*</span></label>
            <select v-model="form.month_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.month_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <div v-if="form.errors?.month_id" class="invalid-feedback">{{ form.errors.month_id }}</div>
        </div>

        <!-- Tipo de Hora Extra -->
        <div class="col-md-6">
            <label class="form-label small mb-1">Tipo de Hora Extra <span class="text-danger">*</span></label>
            <select v-model="form.overtime_type_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.overtime_type_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="t in overtimeTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
            <div v-if="form.errors?.overtime_type_id" class="invalid-feedback">{{ form.errors.overtime_type_id }}</div>
        </div>

        <!-- Cantidad de Horas -->
        <div class="col-md-3">
            <label class="form-label small mb-1">Horas <span class="text-danger">*</span></label>
            <input v-model="form.hours" type="number" min="0.01" max="24" step="0.25"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': form.errors?.hours }"
                placeholder="0.00" />
            <div v-if="form.errors?.hours" class="invalid-feedback">{{ form.errors.hours }}</div>
        </div>

        <!-- Agrupación (preselección rápida de CC) -->
        <div class="col-md-4">
            <label class="form-label small mb-1">
                <i class="fas fa-layer-group me-1"></i>Agrupación
            </label>
            <select v-model="groupingFilter" class="form-select form-select-sm">
                <option :value="null" disabled selected>Seleccione agrupación...</option>
                <option v-for="g in groupings" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
            <small class="text-muted d-block mt-1">
                <i class="fas fa-info-circle me-1"></i>Preselección rápida
            </small>
        </div>

        <!-- Centros de Costo (múltiple) -->
        <div class="col-md-8">
            <div class="d-flex align-items-center justify-content-between mb-0">
                <label class="form-label small mb-0">Centros de Costo <span class="text-danger">*</span>
                    <span v-if="form.cost_center_ids && form.cost_center_ids.length > 0"
                        class="badge bg-primary ms-1" style="font-size: 0.6rem; vertical-align: middle;">
                        {{ form.cost_center_ids.length }}
                    </span>
                </label>
                <button
                    v-if="form.cost_center_ids && form.cost_center_ids.length > 5"
                    type="button"
                    @click="expandedCC = !expandedCC"
                    class="btn btn-link btn-sm p-0 text-muted"
                    style="font-size: 0.65rem; text-decoration: none;"
                >
                    <i class="fas" :class="expandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size: 0.6rem;"></i>
                    {{ expandedCC ? 'Colapsar' : 'Ver todos' }}
                </button>
            </div>
            <Multiselect
                mode="tags"
                v-model="form.cost_center_ids"
                :options="costCenters"
                :searchable="true"
                :close-on-select="false"
                placeholder="Seleccione centros de costo..."
                :class="['multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCC }, { 'is-invalid': form.errors?.cost_center_ids }]"
            />
            <div v-if="form.errors?.cost_center_ids" class="text-danger" style="font-size:0.85em;">{{ form.errors.cost_center_ids }}</div>
        </div>

        <!-- Nivel 3 (filtro visual, no se guarda) -->
        <div class="col-md-4">
            <label class="form-label small mb-1">Nivel 3 (filtro)</label>
            <select v-model="level3Filter" class="form-select form-select-sm">
                <option value="">Todos los Nivel 3</option>
                <option v-for="l in level3s" :key="l.value" :value="l.value">{{ l.label }}</option>
            </select>
        </div>

        <!-- Labor -->
        <div class="col-md-8">
            <label class="form-label small mb-1">Labor <span class="text-danger">*</span></label>
            <select v-model="form.labor_type_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.labor_type_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="lt in filteredLaborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
            </select>
            <div v-if="form.errors?.labor_type_id" class="invalid-feedback">{{ form.errors.labor_type_id }}</div>
        </div>

        <!-- Observaciones -->
        <div class="col-12">
            <label class="form-label small mb-1">Observaciones</label>
            <input v-model="form.observations" type="text" class="form-control form-control-sm"
                placeholder="Opcional..." maxlength="500" />
        </div>
    </div>
</template>

<style scoped>
.multiselect-tags-limited :deep(.multiselect-tags) {
    max-height: 32px !important;
    overflow: hidden !important;
    flex-wrap: wrap;
    transition: max-height 0.3s ease;
}
.multiselect-tags-expanded :deep(.multiselect-tags) {
    max-height: 200px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar { width: 4px; }
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2); border-radius: 4px;
}
.multiselect-tags-limited {
    height: auto !important;
    max-height: 38px !important;
    min-height: 26px !important;
    transition: max-height 0.3s ease;
}
.multiselect-tags-expanded { max-height: 210px !important; }
</style>
