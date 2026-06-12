<script setup>
    import { ref } from 'vue';
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});

    const rutError = ref('');

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

    function onRutInput(event, form) {
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
    <div class="mb-3">
        <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <small>Complete los datos del nuevo proveedor. Los campos marcados con * son obligatorios.</small>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-building text-primary me-1"></i>Nombre <span class="text-danger">*</span>
            </label>
            <TextInput
                id="name"
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: AGROQUÍMICOS DEL SUR"
                :class="{'is-invalid': form.errors.name}"
                @input="form.name = $event.target.value.toUpperCase()"
                style="text-transform: uppercase;"
            />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>
        <div class="col-lg-6">
            <label class="col-form-label fw-bold">
                <i class="fas fa-id-card text-primary me-1"></i>RUT <span class="text-danger">*</span>
            </label>
            <input
                id="rut"
                type="text"
                :value="form.rut"
                @input="onRutInput($event, form)"
                class="form-control form-control-solid"
                placeholder="12.345.678-9"
                maxlength="12"
                :class="{'is-invalid': form.errors.rut || rutError, 'is-valid': form.rut && !rutError && !form.errors.rut}"
            />
            <div v-if="rutError" class="invalid-feedback">{{ rutError }}</div>
            <InputError class="mt-1" :message="form.errors.rut" />
        </div>
    </div>

    <div class="fv-row mb-3">
        <label class="col-form-label fw-bold">
            <i class="fas fa-user text-primary me-1"></i>Contacto
        </label>
        <TextInput
            id="contact"
            v-model="form.contact"
            class="form-control form-control-solid"
            type="text"
            placeholder="Ej: Juan Pérez"
            :class="{'is-invalid': form.errors.contact}"
        />
        <InputError class="mt-1" :message="form.errors.contact" />
    </div>

    <div class="row mb-3">
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label fw-bold">
                    <i class="fas fa-envelope text-primary me-1"></i>Email
                </label>
                <TextInput
                    id="email"
                    v-model="form.email"
                    class="form-control form-control-solid"
                    type="email"
                    placeholder="Ej: contacto@proveedor.cl"
                    :class="{'is-invalid': form.errors.email}"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label fw-bold">
                    <i class="fas fa-phone text-primary me-1"></i>Teléfono
                </label>
                <TextInput
                    id="phone"
                    v-model="form.phone"
                    class="form-control form-control-solid"
                    type="text"
                    placeholder="Ej: +56 9 1234 5678"
                    :class="{'is-invalid': form.errors.phone}"
                />
                <InputError class="mt-1" :message="form.errors.phone" />
            </div>
        </div>
    </div>

    <!--
    <div class="fv-row mb-3">
        <label for="month" class="form-label required fs-6 fw-bold mb-3">Presupuesto</label>
        <Multiselect
            :placeholder="'Seleccione el presupuesto'"
            v-model="form.budget_id"
            :close-on-select="false"
            :options="$page.props.budgets"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.budget_id}"
            :searchable="true"
        />
        <InputError class="mt-2" :message="form.errors.budget_id" />
    </div>-->
</template>
