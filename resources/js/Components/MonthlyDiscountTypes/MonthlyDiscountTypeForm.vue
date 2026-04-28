<script setup>
const props = defineProps({ form: { type: Object, required: true } });
</script>

<template>
    <form @submit.prevent>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small mb-1">Nombre <span class="text-danger">*</span></label>
                <input type="text" v-model="form.name" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.name }"
                    placeholder="Ej: Descuento préstamo, Descuento anticipos" />
                <div v-if="form.errors?.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Estado</label>
                <select v-model="form.is_active" class="form-select form-select-sm">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>
        </div>
    </form>
</template>
