<script setup>
import { ref, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import InvoicePaymentForm from './InvoicePaymentForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    payment: Object,
    banks: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    payment_date: '',
    amount: '',
    payment_method: null,
    bank_id: null,
    transaction_number: '',
    observations: '',
});

// Actualizar form cuando cambia el payment
watch(() => props.payment, (newPayment) => {
    if (newPayment) {
        // Convertir fecha de DD-MM-YYYY a YYYY-MM-DD
        let paymentDate = newPayment.payment_date || '';
        if (paymentDate && paymentDate.includes('-')) {
            const parts = paymentDate.split('-');
            if (parts.length === 3 && parts[0].length === 2) {
                // Si está en formato DD-MM-YYYY, convertir a YYYY-MM-DD
                paymentDate = `${parts[2]}-${parts[1]}-${parts[0]}`;
            }
        }
        
        form.payment_date = paymentDate;
        form.amount = newPayment.amount || '';
        form.payment_method = newPayment.payment_method || null;
        form.bank_id = newPayment.bank?.id || null;
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
    console.log('EditModal show changed to:', newVal);
    nextTick(() => {
        const modalElement = document.getElementById('editInvoicePaymentModal');
        console.log('Modal element found:', modalElement);
        if (newVal) {
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('Modal should be visible now');
            }
        } else {
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        }
    });
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
                        Factura: {{ payment?.invoice.number_document }}
                    </span>
                </span>
            </div>
        </template>

        <template #body>
            <InvoicePaymentForm 
                v-if="payment"
                :form="form" 
                :banks="banks"
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
