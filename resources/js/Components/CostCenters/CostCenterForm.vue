<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    form: Object,
});

const page = usePage();

const filteredVarieties = computed(() => {
    if (!props.form.fruit_id) return [];
    return (page.props.varieties || []).filter(v => String(v.fruit_id) === String(props.form.fruit_id));
});

watch(() => props.form.fruit_id, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        props.form.variety_id = '';
    }
});
</script>

<template>
    <div class="mb-3">
        <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <small>Ingresa los datos generales del cuartel. Los campos con <span class="text-danger">*</span> son obligatorios.</small>
        </div>
    </div>

    <div class="row g-3">
        <!-- Nombre -->
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-map-marker-alt text-primary me-1"></i>Nombre <span class="text-danger">*</span>
            </label>
            <TextInput
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: Cuartel Norte Sector A"
                :class="{ 'is-invalid': form.errors.name }"
            />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>

        <!-- Superficie -->
        <div class="col-lg-2">
            <label class="col-form-label fw-bold">
                <i class="fas fa-ruler-combined text-primary me-1"></i>Superficie (ha) <span class="text-danger">*</span>
            </label>
            <TextInput
                v-model="form.surface"
                class="form-control form-control-solid"
                type="number"
                step="0.01"
                placeholder="0.00"
                :class="{ 'is-invalid': form.errors.surface }"
            />
            <InputError class="mt-1" :message="form.errors.surface" />
        </div>

        <!-- Frutal -->
        <div class="col-lg-3">
            <label class="col-form-label fw-bold">
                <i class="fas fa-apple-alt text-primary me-1"></i>Frutal <span class="text-danger">*</span>
            </label>
            <select
                v-model="form.fruit_id"
                class="form-select form-select-solid"
                :class="{ 'is-invalid': form.errors.fruit_id }"
            >
                <option value="">Seleccione el frutal</option>
                <option v-for="f in $page.props.fruits" :key="f.value" :value="f.value">
                    {{ f.label }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.fruit_id" />
        </div>

        <!-- Variedad -->
        <div class="col-lg-3">
            <label class="col-form-label fw-bold">
                <i class="fas fa-leaf text-primary me-1"></i>Variedad <span class="text-danger">*</span>
            </label>
            <select
                v-model="form.variety_id"
                class="form-select form-select-solid"
                :class="{ 'is-invalid': form.errors.variety_id }"
                :disabled="!form.fruit_id"
            >
                <option value="">{{ form.fruit_id ? "Seleccione variedad" : "Seleccione frutal primero" }}</option>
                <option v-for="v in filteredVarieties" :key="v.value" :value="v.value">
                    {{ v.label }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.variety_id" />
        </div>

        <!-- Parcela -->
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-th-large text-primary me-1"></i>Parcela
            </label>
            <select
                v-model="form.parcel_id"
                class="form-select form-select-solid"
                :class="{ 'is-invalid': form.errors.parcel_id }"
            >
                <option value="">Seleccione parcela</option>
                <option v-for="p in $page.props.parcels" :key="p.value" :value="p.value">
                    {{ p.label }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.parcel_id" />
        </div>

        <!-- Estado de desarrollo -->
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-chart-line text-primary me-1"></i>Estado de desarrollo
            </label>
            <select
                v-model="form.development_state_id"
                class="form-select form-select-solid"
                :class="{ 'is-invalid': form.errors.development_state_id }"
            >
                <option value="">Seleccione estado</option>
                <option v-for="d in $page.props.developmentStates" :key="d.value" :value="d.value">
                    {{ d.label }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.development_state_id" />
        </div>

        <!-- Año plantación -->
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-calendar-alt text-primary me-1"></i>Año plantación
            </label>
            <TextInput
                v-model="form.year_plantation"
                class="form-control form-control-solid"
                type="number"
                min="1900"
                max="3000"
                step="1"
                placeholder="Ej: 2018"
                :class="{ 'is-invalid': form.errors.year_plantation }"
            />
            <InputError class="mt-1" :message="form.errors.year_plantation" />
        </div>

        <!-- Razón social -->
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-briefcase text-primary me-1"></i>Razón social
            </label>
            <select
                v-model="form.company_reason_id"
                class="form-select form-select-solid"
                :class="{ 'is-invalid': form.errors.company_reason_id }"
            >
                <option value="">Seleccione razón social</option>
                <option v-for="c in $page.props.companyReasons" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.company_reason_id" />
        </div>

        <!-- Observaciones -->
        <div class="col-lg-8">
            <label class="col-form-label fw-bold">
                <i class="fas fa-sticky-note text-primary me-1"></i>Observaciones
            </label>
            <textarea
                v-model="form.observations"
                rows="3"
                class="form-control form-control-solid"
                placeholder="Notas adicionales sobre el cuartel..."
                :class="{ 'is-invalid': form.errors.observations }"
            ></textarea>
            <InputError class="mt-1" :message="form.errors.observations" />
        </div>

        <!-- Activo -->
        <div class="col-12">
            <label class="form-check form-check-inline">
                <Checkbox
                    class="form-check-input"
                    v-model:checked="form.status"
                    name="status"
                />
                <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">Activo</span>
            </label>
        </div>
    </div>
</template>
