<script setup>
import { watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    laborTypes: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);
const form = props.form;

watch(form, () => emit('update:form', form), { deep: true });
</script>

<template>
    <form @submit.prevent>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small mb-1">Nombre <span class="text-danger">*</span></label>
                <input type="text" v-model="form.name" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.name }" placeholder="Ej: Amarra cereza adulta abril" />
                <div v-if="form.errors?.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Tarifa ($) <span class="text-danger">*</span></label>
                <input type="number" v-model="form.rate" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.rate }" placeholder="0" min="0" />
                <div v-if="form.errors?.rate" class="invalid-feedback">{{ form.errors.rate }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Unidad</label>
                <select v-model="form.unit_id" class="form-select form-select-sm">
                    <option :value="''" selected>Seleccione...</option>
                    <option v-for="item in units" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Labor Asociada</label>
                <select v-model="form.labor_type_id" class="form-select form-select-sm">
                    <option :value="''" selected>Seleccione labor (opcional)...</option>
                    <option v-for="item in laborTypes" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Estado</label>
                <select v-model="form.is_active" class="form-select form-select-sm">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>
        </div>
    </form>
</template>
