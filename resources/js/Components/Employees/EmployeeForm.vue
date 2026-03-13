<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    nationalities: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);
const form = props.form;
const rutError = ref('');

watch(form, () => emit('update:form', form), { deep: true });

function validateRut(rut) {
    if (!rut) return false;
    const cleaned = rut.replace(/[.\-]/g, '').toUpperCase();
    if (cleaned.length < 2) return false;
    const body = cleaned.slice(0, -1);
    const dv = cleaned.slice(-1);
    if (!/^\d+$/.test(body)) return false;
    let sum = 0;
    let mul = 2;
    for (let i = body.length - 1; i >= 0; i--) {
        sum += parseInt(body[i]) * mul;
        mul = mul === 7 ? 2 : mul + 1;
    }
    const remainder = 11 - (sum % 11);
    const expected = remainder === 11 ? '0' : remainder === 10 ? 'K' : String(remainder);
    return dv === expected;
}

function formatRut(value) {
    if (!value) return '';
    let cleaned = value.replace(/[^0-9kK]/g, '');
    if (cleaned.length > 9) cleaned = cleaned.substring(0, 9);
    if (cleaned.length <= 1) return cleaned;
    const body = cleaned.slice(0, -1);
    const dv = cleaned.slice(-1).toUpperCase();
    let formatted = '';
    let count = 0;
    for (let i = body.length - 1; i >= 0; i--) {
        formatted = body[i] + formatted;
        count++;
        if (count % 3 === 0 && i > 0) {
            formatted = '.' + formatted;
        }
    }
    return formatted + '-' + dv;
}

function onRutInput(event) {
    const formatted = formatRut(event.target.value);
    form.rut = formatted;
    if (formatted && formatted.length >= 3) {
        rutError.value = validateRut(formatted) ? '' : 'RUT inválido';
    } else {
        rutError.value = '';
    }
}
</script>

<template>
    <form @submit.prevent>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small mb-1">Nombre <span class="text-danger">*</span></label>
                <input type="text" v-model="form.first_name" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.first_name }" placeholder="Nombre" />
                <div v-if="form.errors?.first_name" class="invalid-feedback">{{ form.errors.first_name }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Segundo Nombre</label>
                <input type="text" v-model="form.second_name" class="form-control form-control-sm"
                    placeholder="Segundo nombre (opcional)" />
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Apellido Paterno <span class="text-danger">*</span></label>
                <input type="text" v-model="form.paternal_surname" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.paternal_surname }" placeholder="Apellido paterno" />
                <div v-if="form.errors?.paternal_surname" class="invalid-feedback">{{ form.errors.paternal_surname }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Apellido Materno</label>
                <input type="text" v-model="form.maternal_surname" class="form-control form-control-sm"
                    placeholder="Apellido materno (opcional)" />
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">RUT <span class="text-danger">*</span></label>
                <input type="text" :value="form.rut" @input="onRutInput" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.rut || rutError, 'is-valid': form.rut && !rutError && !form.errors?.rut }" placeholder="12.345.678-9" maxlength="12" />
                <div v-if="rutError" class="invalid-feedback">{{ rutError }}</div>
                <div v-if="form.errors?.rut" class="invalid-feedback">{{ form.errors.rut }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Fecha de Nacimiento</label>
                <input type="date" v-model="form.birth_date" class="form-control form-control-sm" />
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Nacionalidad</label>
                <select v-model="form.nationality" class="form-select form-select-sm">
                    <option value="" disabled>Seleccione nacionalidad</option>
                    <option v-for="nat in nationalities" :key="nat" :value="nat">{{ nat }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Estado</label>
                <select v-model="form.is_active" class="form-select form-select-sm">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>
        </div>
    </form>
</template>
