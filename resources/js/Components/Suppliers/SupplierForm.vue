<script setup>
    import { ref } from 'vue';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	const props = defineProps({
		form: Object,
		banks: { type: Array, default: () => [] },
		accountTypes: { type: Array, default: () => [] },
	});

    const rutError = ref('');

    function addAccount() {
        if (!Array.isArray(props.form.accounts)) props.form.accounts = [];
        props.form.accounts.push({ bank_id: '', account_type_id: '', account_number: '' });
    }

    function removeAccount(index) {
        props.form.accounts.splice(index, 1);
    }

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

    <!-- Cuentas bancarias (opcional, varias por proveedor) -->
    <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="col-form-label fw-bold mb-0">
                <i class="fas fa-university text-primary me-1"></i>Cuentas bancarias
                <small class="text-muted fw-normal ms-1">(opcional)</small>
            </label>
            <button type="button" class="btn btn-falcon-default btn-sm" @click="addAccount">
                <i class="fas fa-plus me-1"></i>Agregar cuenta
            </button>
        </div>

        <div v-if="!form.accounts || form.accounts.length === 0" class="text-muted small fst-italic px-1">
            Este proveedor no tiene cuentas bancarias registradas.
        </div>

        <div
            v-for="(account, index) in form.accounts"
            :key="index"
            class="border rounded p-2 mb-2 bg-body-tertiary"
        >
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label small mb-1">Banco <span class="text-danger">*</span></label>
                    <select
                        v-model="account.bank_id"
                        class="form-select form-select-sm"
                    >
                        <option :value="''" disabled>Seleccione banco</option>
                        <option v-for="bank in banks" :key="bank.value" :value="bank.value">
                            {{ bank.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors[`accounts.${index}.bank_id`]" />
                </div>
                <div class="col-lg-3">
                    <label class="form-label small mb-1">Tipo de cuenta <span class="text-danger">*</span></label>
                    <select
                        v-model="account.account_type_id"
                        class="form-select form-select-sm"
                    >
                        <option :value="''" disabled>Seleccione tipo</option>
                        <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors[`accounts.${index}.account_type_id`]" />
                </div>
                <div class="col-lg-4">
                    <label class="form-label small mb-1">N° de cuenta <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        v-model="account.account_number"
                        class="form-control form-control-solid"
                        placeholder="Ej: 00012345678"
                        maxlength="30"
                    />
                    <InputError class="mt-1" :message="form.errors[`accounts.${index}.account_number`]" />
                </div>
                <div class="col-lg-1 text-end">
                    <button
                        type="button"
                        class="btn btn-link text-danger p-0"
                        v-tooltip="'Quitar cuenta'"
                        @click="removeAccount(index)"
                    >
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
