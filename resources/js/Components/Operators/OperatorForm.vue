<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: Object,
    branches: { type: Array, default: () => [] }
});
</script>
<template>
    <div class="mb-3">
        <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <small>Complete los datos del operario. Los campos marcados con * son obligatorios.</small>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-user text-primary me-1"></i>Nombre <span class="text-danger">*</span>
            </label>
            <TextInput
                id="name"
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: Juan Pérez"
                :class="{'is-invalid': form.errors.name}"
            />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-briefcase text-primary me-1"></i>Cargo <span class="text-danger">*</span>
            </label>
            <TextInput
                id="position"
                v-model="form.position"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: Tractorista"
                :class="{'is-invalid': form.errors.position}"
            />
            <InputError class="mt-1" :message="form.errors.position" />
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-building text-primary me-1"></i>Sucursal
            </label>
            <select v-model="form.branch_id" class="form-select form-select-sm" :class="{'is-invalid': form.errors.branch_id}">
                <option :value="null">Sin sucursal</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <InputError class="mt-1" :message="form.errors.branch_id" />
        </div>
    </div>
</template>
