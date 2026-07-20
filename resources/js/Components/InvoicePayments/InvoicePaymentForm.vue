<script setup>
import { watch } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    banks: Array,
    supplierAccounts: {
        type: Array,
        default: () => []
    },
    maxAmount: {
        type: Number,
        default: null
    }
});

const paymentMethods = [
    { value: 1, label: 'Transferencia' },
    { value: 2, label: 'Efectivo' },
    { value: 3, label: 'Cheque' }
];

// Validar que el banco sea requerido si es transferencia
watch(() => props.form.payment_method, (newMethod) => {
    if (newMethod !== 1) {
        props.form.bank_id = null;
    }
    if (newMethod === 2) {
        props.form.transaction_number = '';
    }
});
</script>

<template>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="payment_date" class="form-label small fw-bold">
                    Fecha de Pago <span class="text-danger">*</span>
                </label>
                <input
                    id="payment_date"
                    v-model="form.payment_date"
                    type="date"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.payment_date}"
                />
                <InputError class="mt-1" :message="form.errors.payment_date" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="amount" class="form-label small fw-bold">
                    Monto ($) <span class="text-danger">*</span>
                    <span v-if="maxAmount" class="text-muted fw-normal">
                        (Máx: $ {{ maxAmount.toLocaleString('es-CL') }})
                    </span>
                </label>
                <input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    step="1"
                    min="1"
                    :max="maxAmount"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.amount}"
                    placeholder="0"
                />
                <InputError class="mt-1" :message="form.errors.amount" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="payment_method" class="form-label small fw-bold">
                    Método de Pago <span class="text-danger">*</span>
                </label>
                <select
                    id="payment_method"
                    v-model="form.payment_method"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.payment_method}"
                >
                    <option :value="null" disabled>Seleccione método de pago</option>
                    <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                        {{ method.label }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.payment_method" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="bank_id" class="form-label small fw-bold">
                    Banco
                    <span v-if="form.payment_method == 1" class="text-danger">*</span>
                    <span v-else class="text-muted fw-normal">(Opcional)</span>
                </label>
                <select
                    id="bank_id"
                    v-model="form.bank_id"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.bank_id}"
                    :disabled="form.payment_method != 1"
                >
                    <option :value="null">Seleccione un banco</option>
                    <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                        {{ bank.name }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.bank_id" />
            </div>
        </div>

        <div v-if="supplierAccounts && supplierAccounts.length > 0" class="col-md-12">
            <div class="mb-3">
                <label for="supplier_bank_account_id" class="form-label small fw-bold">
                    Cuenta bancaria del proveedor
                    <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <select
                    id="supplier_bank_account_id"
                    v-model="form.supplier_bank_account_id"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.supplier_bank_account_id}"
                >
                    <option :value="null">Sin especificar</option>
                    <option v-for="account in supplierAccounts" :key="account.value" :value="account.value">
                        {{ account.label }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.supplier_bank_account_id" />
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <label for="transaction_number" class="form-label small fw-bold">
                    Número de Transacción/Cheque
                    <span v-if="form.payment_method == 1 || form.payment_method == 3" class="text-danger">*</span>
                    <span v-else class="text-muted fw-normal">(No requerido para efectivo)</span>
                </label>
                <input
                    id="transaction_number"
                    v-model="form.transaction_number"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.transaction_number}"
                    placeholder="Ingrese número de transacción o cheque"
                    :disabled="form.payment_method == 2"
                />
                <InputError class="mt-1" :message="form.errors.transaction_number" />
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <label for="observations" class="form-label small fw-bold">
                    Observaciones <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <textarea
                    id="observations"
                    v-model="form.observations"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.observations}"
                    rows="3"
                    placeholder="Observaciones adicionales..."
                ></textarea>
                <InputError class="mt-1" :message="form.errors.observations" />
            </div>
        </div>
    </div>
</template>
