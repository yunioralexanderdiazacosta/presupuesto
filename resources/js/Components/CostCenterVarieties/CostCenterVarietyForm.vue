<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    // id del registro que se está editando (para excluirlo del cálculo)
    editingId: { type: [Number, String], default: null },
});

const page = usePage();

const filteredVarieties = computed(() => {
    if (!props.form.fruit_id) return [];
    return (page.props.varieties || []).filter(v => v.fruit_id === props.form.fruit_id);
});

watch(() => props.form.fruit_id, () => {
    props.form.variety_id = '';
});

// Superficie ya usada en el cuartel (excluyendo el registro en edición)
const costCenterOptions = computed(() => page.props.costCentersSelect ?? page.props.costCenters ?? []);

const selectedCostCenter = computed(() =>
    costCenterOptions.value.find(c => c.value === props.form.cost_center_id)
);

const costCenterSurface = computed(() => {
    if (!props.form.cost_center_id) return null;
    const cc = (page.props.costCentersData || []).find(c => c.id === props.form.cost_center_id);
    return cc ? cc.surface : null;
});

const usedSurface = computed(() => {
    if (!props.form.cost_center_id) return 0;
    const season_id = page.props.season_id;
    return (page.props.costCenterVarietiesAll || [])
        .filter(v =>
            v.cost_center_id === props.form.cost_center_id &&
            (!props.editingId || v.id !== Number(props.editingId))
        )
        .reduce((sum, v) => sum + parseFloat(v.surface || 0), 0);
});

const remainingSurface = computed(() => {
    if (costCenterSurface.value === null) return null;
    return Math.round((costCenterSurface.value - usedSurface.value) * 10000) / 10000;
});
</script>

<template>
    <div class="row g-3">
        <!-- Centro de costo -->
        <div class="col-md-6">
            <label class="col-form-label">Centro de costo <span class="text-danger">*</span></label>
            <Multiselect
                placeholder="Seleccione cuartel"
                v-model="form.cost_center_id"
                :options="costCenterOptions"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.cost_center_id }"
                :searchable="true"
                :close-on-select="true"
            />
            <InputError class="mt-1" :message="form.errors.cost_center_id" />
        </div>

        <!-- Frutal -->
        <div class="col-md-6">
            <label class="col-form-label">Frutal <span class="text-danger">*</span></label>
            <Multiselect
                placeholder="Seleccione frutal"
                v-model="form.fruit_id"
                :options="$page.props.fruits"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.fruit_id }"
                :searchable="true"
                :close-on-select="true"
            />
            <InputError class="mt-1" :message="form.errors.fruit_id" />
        </div>

        <!-- Variedad -->
        <div class="col-md-6">
            <label class="col-form-label">Variedad <span class="text-danger">*</span></label>
            <Multiselect
                :placeholder="form.fruit_id ? 'Seleccione variedad' : 'Seleccione frutal primero'"
                v-model="form.variety_id"
                :options="filteredVarieties"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.variety_id }"
                :searchable="true"
                :close-on-select="true"
                :disabled="!form.fruit_id"
            />
            <InputError class="mt-1" :message="form.errors.variety_id" />
        </div>

        <!-- Portainjerto -->
        <div class="col-md-6">
            <label class="col-form-label">Portainjerto</label>
            <Multiselect
                placeholder="Seleccione portainjerto (opcional)"
                v-model="form.rootstock_id"
                :options="$page.props.rootstocks"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.rootstock_id }"
                :searchable="true"
                :close-on-select="true"
            />
            <InputError class="mt-1" :message="form.errors.rootstock_id" />
        </div>

        <!-- Estado de desarrollo -->
        <div class="col-md-6">
            <label class="col-form-label">Estado de desarrollo</label>
            <Multiselect
                placeholder="Seleccione estado (opcional)"
                v-model="form.development_state_id"
                :options="$page.props.developmentStates"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.development_state_id }"
                :searchable="true"
                :close-on-select="true"
            />
            <InputError class="mt-1" :message="form.errors.development_state_id" />
        </div>

        <!-- Superficie -->
        <div class="col-md-3">
            <label class="col-form-label">
                Superficie (ha) <span class="text-danger">*</span>
                <span v-if="remainingSurface !== null"
                    class="ms-2 badge"
                    :class="remainingSurface < 0 ? 'bg-danger' : 'bg-success'">
                    Disponible: {{ remainingSurface }} ha
                </span>
            </label>
            <input
                v-model="form.surface"
                type="number"
                step="0.01"
                min="0"
                class="form-control form-control-solid"
                :class="{ 'is-invalid': form.errors.surface }"
                placeholder="0.00"
            />
            <InputError class="mt-1" :message="form.errors.surface" />
        </div>

        <!-- Año de plantación -->
        <div class="col-md-3">
            <label class="col-form-label">Año de plantación</label>
            <input
                v-model="form.year_plantation"
                type="number"
                min="1900"
                max="2100"
                class="form-control form-control-solid"
                :class="{ 'is-invalid': form.errors.year_plantation }"
                placeholder="Ej: 2015"
            />
            <InputError class="mt-1" :message="form.errors.year_plantation" />
        </div>

        <!-- Observaciones -->
        <div class="col-12">
            <label class="col-form-label">Observaciones</label>
            <textarea
                v-model="form.observations"
                rows="2"
                class="form-control form-control-solid"
                :class="{ 'is-invalid': form.errors.observations }"
                placeholder="Observaciones opcionales..."
            ></textarea>
            <InputError class="mt-1" :message="form.errors.observations" />
        </div>
    </div>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg, #f5f8fa) !important;
    --ms-border-color: var(--kt-input-solid-bg, #f5f8fa);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: #fff;
    --ms-option-bg-selected: #2c7be5;
    --ms-option-color-selected: #fff;
}
</style>
