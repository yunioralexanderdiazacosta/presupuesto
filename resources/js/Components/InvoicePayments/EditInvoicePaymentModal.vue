<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import InvoicePaymentForm from './InvoicePaymentForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    payment: Object,
    banks: Array,
    supplierAccounts: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    payment_date: '',
    amount: '',
    payment_method: null,
    bank_id: null,
    supplier_bank_account_id: null,
    transaction_number: '',
    observations: '',
});

// Actualizar form cuando cambia el payment
watch(() => props.payment, (newPayment) => {
    if (newPayment) {
        // Tomar solo los primeros 10 caracteres (YYYY-MM-DD) independiente del formato
        const rawDate = newPayment.payment_date || '';
        form.payment_date = rawDate ? rawDate.substring(0, 10) : '';
        form.amount = newPayment.amount || '';
        form.payment_method = newPayment.payment_method || null;
        form.bank_id = newPayment.bank_id || null;
        form.supplier_bank_account_id = newPayment.supplier_bank_account_id || null;
        form.transaction_number = newPayment.transaction_number || '';
        form.observations = newPayment.observations || '';
        form.clearErrors();
    }
}, { immediate: true });

function submitUpdate() {
    if (!props.payment?.id) return;
    
    form.put(route('invoice-payments.update', props.payment.id), {
        onSuccess: () => {
            Swal.fire('¡Éxito!', 'Pago actualizado correctamente', 'success');
            emit('close');
            $('#editInvoicePaymentModal').modal('hide');
        },
        onError: () => {
            Swal.fire('Error', 'Ocurrió un error al actualizar el pago', 'error');
        }
    });
}

// Control de modal
watch(() => props.show, (newVal) => {
    if (newVal) {
        $('#editInvoicePaymentModal').modal('show');
    } else {
        $('#editInvoicePaymentModal').modal('hide');
    }
});
</script>

<template>
    <Modal :maxWidth="'lg'" :id="'editInvoicePaymentModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" 
                      style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-edit"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">
                        Editar Pago
                    </span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        Factura: {{ payment?.number_document }}
                    </span>
                </span>
            </div>
        </template>

        <template #body>
            <InvoicePaymentForm 
                v-if="payment"
                :form="form" 
                :banks="banks"
                :supplier-accounts="supplierAccounts"
            />
        </template>

        <template #footer>
            <button 
                type="button" 
                @click="emit('close')" 
                data-bs-dismiss="modal" 
                class="btn btn-light me-3"
            >
                Cancelar
            </button>
            <button 
                type="button" 
                @click="submitUpdate" 
                :disabled="form.processing" 
                class="btn btn-primary"
            >
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>
