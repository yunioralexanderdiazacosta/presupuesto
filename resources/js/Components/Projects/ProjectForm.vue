<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: Object,
    operations: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="row mb-3">
        <div class="col-lg-8">
            <label class="col-form-label fw-bold">
                <i class="fas fa-folder text-primary me-1"></i>Nombre <span class="text-danger">*</span>
            </label>
            <TextInput
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: Proyecto Temporada 2025"
                :class="{ 'is-invalid': form.errors.name }"
            />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>
        <div class="col-lg-4">
            <label class="col-form-label fw-bold">
                <i class="fas fa-calendar text-primary me-1"></i>Fecha
            </label>
            <TextInput
                v-model="form.date"
                class="form-control form-control-solid"
                type="date"
                :class="{ 'is-invalid': form.errors.date }"
            />
            <InputError class="mt-1" :message="form.errors.date" />
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-dollar-sign text-primary me-1"></i>Presupuesto (CLP)
            </label>
            <TextInput
                v-model="form.budget"
                class="form-control form-control-solid"
                type="number"
                min="0"
                placeholder="Ej: 5000000"
                :class="{ 'is-invalid': form.errors.budget }"
            />
            <InputError class="mt-1" :message="form.errors.budget" />
        </div>
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-tasks text-primary me-1"></i>Operación
            </label>
            <select
                v-model="form.operation_id"
                class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors.operation_id }"
            >
                <option :value="null">— Sin operación —</option>
                <option v-for="op in operations" :key="op.id" :value="op.id">
                    {{ op.name }}
                </option>
            </select>
            <InputError class="mt-1" :message="form.errors.operation_id" />
        </div>
    </div>

    <div class="mb-3">
        <label class="col-form-label fw-bold">
            <i class="fas fa-sticky-note text-primary me-1"></i>Observaciones
        </label>
        <textarea
            v-model="form.observations"
            class="form-control form-control-solid"
            rows="3"
            placeholder="Notas adicionales..."
            :class="{ 'is-invalid': form.errors.observations }"
        ></textarea>
        <InputError class="mt-1" :message="form.errors.observations" />
    </div>
</template>
