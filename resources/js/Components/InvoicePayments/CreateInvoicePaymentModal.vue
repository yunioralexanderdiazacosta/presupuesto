<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import InvoicePaymentForm from './InvoicePaymentForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    banks: Array,
    preselectedInvoice: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const searchNumber = ref('');
const selectedInvoice = ref(null);
const invoiceHistory = ref([]);
const isSearching = ref(false);

const form = useForm({
    invoice_id: null,
    payment_date: new Date().toISOString().split('T')[0],
    amount: '',
    payment_method: null,
    bank_id: null,
    transaction_number: '',
    observations: '',
});

// Búsqueda de facturas
async function searchInvoice() {
    if (!searchNumber.value) {
        Swal.fire('Atención', 'Debe ingresar un número de documento', 'warning');
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(route('invoices.search'), {
            params: { number_document: searchNumber.value }
        });

        if (response.data.length === 0) {
            Swal.fire('No encontrado', 'No se encontró ninguna factura con ese número', 'info');
            selectedInvoice.value = null;
            invoiceHistory.value = [];
        } else if (response.data.length === 1) {
            selectedInvoice.value = response.data[0];
            form.invoice_id = selectedInvoice.value.id;
            form.amount = selectedInvoice.value.balance;
            loadPaymentHistory();
        } else {
            // Mostrar selector si hay múltiples resultados
            showInvoiceSelector(response.data);
        }
    } catch (error) {
        console.error('Error al buscar factura:', error);
        Swal.fire('Error', 'Ocurrió un error al buscar la factura', 'error');
    } finally {
        isSearching.value = false;
    }
}

function showInvoiceSelector(invoices) {
    const options = invoices.map((inv, index) => `
        <div class="invoice-option" data-invoice-id="${inv.id}" 
             style="text-align: left; padding: 12px; border: 2px solid ${index === 0 ? '#3b82f6' : '#e0e0e0'}; 
                    border-radius: 5px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;">
            <div style="font-size: 0.9rem;"><strong>${inv.number_document}</strong> - ${inv.supplier.name}</div>
            <small class="text-muted">Saldo: $ ${Math.round(inv.balance).toLocaleString('es-CL')}</small>
        </div>
    `).join('');

    Swal.fire({
        title: `Se encontraron ${invoices.length} facturas`,
        html: `
            <div style="max-height: 400px; overflow-y: auto; padding: 15px;">
                <p class="text-muted mb-3">Seleccione la factura a la que desea aplicar el pago:</p>
                <div id="invoices-container">${options}</div>
            </div>
        `,
        width: '550px',
        showCancelButton: true,
        confirmButtonText: 'Seleccionar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            // Manejar clicks en las opciones
            document.querySelectorAll('.invoice-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Remover selección de todos
                    document.querySelectorAll('.invoice-option').forEach(opt => {
                        opt.style.border = '2px solid #e0e0e0';
                        opt.style.backgroundColor = 'transparent';
                    });
                    // Marcar el seleccionado
                    this.style.border = '2px solid #3b82f6';
                    this.style.backgroundColor = '#eff6ff';
                });
                
                // Hover effect
                option.addEventListener('mouseenter', function() {
                    if (this.style.border !== '2px solid #3b82f6' && this.style.border !== '2px solid rgb(59, 130, 246)') {
                        this.style.backgroundColor = '#f9fafb';
                    }
                });
                option.addEventListener('mouseleave', function() {
                    if (this.style.border !== '2px solid #3b82f6' && this.style.border !== '2px solid rgb(59, 130, 246)') {
                        this.style.backgroundColor = 'transparent';
                    }
                });
            });
        },
        preConfirm: () => {
            const selected = document.querySelector('.invoice-option[style*="rgb(59, 130, 246)"]') || 
                            document.querySelector('.invoice-option[style*="#3b82f6"]');
            if (!selected) {
                Swal.showValidationMessage('Debe seleccionar una factura');
                return false;
            }
            const selectedId = selected.getAttribute('data-invoice-id');
            return invoices.find(inv => inv.id == selectedId);
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            selectedInvoice.value = result.value;
            form.invoice_id = selectedInvoice.value.id;
            form.amount = selectedInvoice.value.balance;
            loadPaymentHistory();
        }
    });
}

async function loadPaymentHistory() {
    // Aquí podrías cargar el historial de pagos si implementas un endpoint
    // Por ahora lo dejamos vacío
    invoiceHistory.value = [];
}

function submitPayment() {
    if (!selectedInvoice.value) {
        Swal.fire('Atención', 'Debe buscar y seleccionar una factura primero', 'warning');
        return;
    }

    if (parseFloat(form.amount) > parseFloat(selectedInvoice.value.balance)) {
        Swal.fire('Error', 'El monto no puede ser mayor al saldo pendiente', 'error');
        return;
    }

    form.post(route('invoice-payments.store'), {
        onSuccess: () => {
            Swal.fire('¡Éxito!', 'Pago registrado correctamente', 'success');
            resetForm();
            emit('close');
            $('#createInvoicePaymentModal').modal('hide');
        },
        onError: () => {
            Swal.fire('Error', 'Ocurrió un error al guardar el pago', 'error');
        }
    });
}

function resetForm() {
    searchNumber.value = '';
    selectedInvoice.value = null;
    invoiceHistory.value = [];
    form.reset();
    form.payment_date = new Date().toISOString().split('T')[0];
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

// Cuando se abre el modal: pre-llenar si viene una factura desde la fila
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.preselectedInvoice) {
            selectedInvoice.value = props.preselectedInvoice;
            form.invoice_id = props.preselectedInvoice.id;
            form.amount = props.preselectedInvoice.balance;
            searchNumber.value = props.preselectedInvoice.number_document;
        }
        $('#createInvoicePaymentModal').modal('show');
    } else {
        $('#createInvoicePaymentModal').modal('hide');
    }
});
</script>

<template>
    <Modal :maxWidth="'xl'" :id="'createInvoicePaymentModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                      style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-money-bill-wave"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">
                        Registrar Pago de Factura
                    </span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        Busque la factura y registre el pago realizado
                    </span>
                </span>
            </div>
        </template>

        <template #body>
            <!-- Búsqueda de factura -->
            <div class="card mb-3 border-primary">
                <div class="card-body">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-search me-2"></i>Buscar Factura
                    </h6>
                    <div class="row">
                        <div class="col-md-8">
                            <input 
                                v-model="searchNumber" 
                                @keyup.enter="searchInvoice"
                                type="text" 
                                class="form-control" 
                                placeholder="Ingrese número de documento..."
                            >
                        </div>
                        <div class="col-md-4">
                            <button 
                                @click="searchInvoice" 
                                :disabled="isSearching"
                                class="btn btn-primary w-100"
                            >
                                <i class="fas fa-search me-2"></i>
                                {{ isSearching ? 'Buscando...' : 'Buscar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de factura seleccionada -->
            <div v-if="selectedInvoice" class="card mb-3 border-success">
                <div class="card-body">
                    <h6 class="text-success mb-3">
                        <i class="fas fa-file-invoice me-2"></i>Factura Seleccionada
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Número:</strong> {{ selectedInvoice.number_document }}</p>
                            <p class="mb-1"><strong>Proveedor:</strong> {{ selectedInvoice.supplier.name }}</p>
                            <p class="mb-1"><strong>Fecha:</strong> {{ selectedInvoice.date }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total Factura:</strong> $ {{ formatCurrency(selectedInvoice.total_invoice) }}</p>
                            <p class="mb-1"><strong>Total Pagado:</strong> $ {{ formatCurrency(selectedInvoice.total_paid) }}</p>
                            <p class="mb-1">
                                <strong>Saldo Pendiente:</strong> 
                                <span class="text-danger fw-bold">$ {{ formatCurrency(selectedInvoice.balance) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de pago -->
            <div v-if="selectedInvoice">
                <h6 class="mb-3">
                    <i class="fas fa-money-check me-2"></i>Datos del Pago
                </h6>
                <InvoicePaymentForm 
                    :form="form" 
                    :banks="banks"
                    :max-amount="selectedInvoice.balance"
                />
            </div>

            <div v-else class="text-center text-muted py-4">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>Busque una factura para comenzar</p>
            </div>
        </template>

        <template #footer>
            <button 
                type="button" 
                @click="resetForm(); emit('close')" 
                data-bs-dismiss="modal" 
                class="btn btn-light me-3"
            >
                Cancelar
            </button>
            <button 
                type="button" 
                @click="submitPayment" 
                :disabled="form.processing || !selectedInvoice" 
                class="btn btn-primary"
            >
                <span class="indicator-label">Guardar Pago</span>
            </button>
        </template>
    </Modal>
</template>
