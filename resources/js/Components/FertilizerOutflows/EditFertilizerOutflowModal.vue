<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    fertilizerOutflow: Object,
    availableStocks: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    date: props.fertilizerOutflow?.date?.split('T')[0] || props.fertilizerOutflow?.date || '',
    invoice_product_id: props.fertilizerOutflow?.invoice_product_id || null,
    quantity: props.fertilizerOutflow?.quantity || 0,
    observations: props.fertilizerOutflow?.observations || '',
});

// Obtener stock disponible de la factura seleccionada
const selectedStockInfo = computed(() => {
    if (!form.invoice_product_id || !props.availableStocks) return null;
    return props.availableStocks.find(s => s.invoice_product_id == form.invoice_product_id);
});

const submitForm = () => {
    form.put(route('fertilizer-outflows.update', props.fertilizerOutflow.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'La aplicación ha sido actualizada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            closeModal();
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errors.error || errors.quantity || 'Error al actualizar la aplicación'
            });
        }
    });
};

const closeModal = () => {
    const modalElement = document.getElementById('editFertilizerOutflowModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    emit('close');
};
</script>

<template>
    <div class="modal fade" id="editFertilizerOutflowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Aplicación de Fertilizante
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="modal-body">
                        <!-- Info de la orden -->
                        <div class="alert alert-info mb-3">
                            <strong>Orden:</strong> #{{ fertilizerOutflow?.fertilizer_order_id }}<br>
                            <strong>Producto:</strong> {{ fertilizerOutflow?.product?.name }}
                        </div>

                        <!-- Fecha -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha *</label>
                            <input 
                                type="date" 
                                v-model="form.date" 
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.date }"
                                required
                            >
                            <div v-if="form.errors.date" class="invalid-feedback">{{ form.errors.date }}</div>
                        </div>

                        <!-- Factura origen -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Factura Origen *</label>
                            <select
                                v-model="form.invoice_product_id"
                                class="form-select"
                                :class="{ 'is-invalid': form.errors.invoice_product_id }"
                                required
                            >
                                <option :value="null">Seleccione una factura...</option>
                                <option 
                                    v-for="stock in availableStocks" 
                                    :key="stock.invoice_product_id" 
                                    :value="stock.invoice_product_id"
                                >
                                    {{ stock.invoice_number }} - {{ stock.supplier_name }} 
                                    (Stock: {{ stock.stock_disponible.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ stock.unit_name }})
                                </option>
                            </select>
                            <div v-if="form.errors.invoice_product_id" class="invalid-feedback">
                                {{ form.errors.invoice_product_id }}
                            </div>
                        </div>

                        <!-- Cantidad -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Cantidad ({{ fertilizerOutflow?.product?.unit?.name }}) *
                            </label>
                            <input 
                                type="number" 
                                v-model="form.quantity" 
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.quantity }"
                                step="0.01"
                                min="0.01"
                                :max="selectedStockInfo?.stock_disponible || 999999"
                                required
                            >
                            <div v-if="selectedStockInfo" class="form-text">
                                Stock disponible: {{ selectedStockInfo.stock_disponible.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ fertilizerOutflow?.product?.unit?.name }}
                            </div>
                            <div v-if="form.errors.quantity" class="invalid-feedback">{{ form.errors.quantity }}</div>
                            <div v-if="selectedStockInfo && form.quantity > selectedStockInfo.stock_disponible" class="text-danger small">
                                ⚠️ Excede el stock disponible
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea 
                                v-model="form.observations" 
                                class="form-control"
                                rows="3"
                                :class="{ 'is-invalid': form.errors.observations }"
                            ></textarea>
                            <div v-if="form.errors.observations" class="invalid-feedback">{{ form.errors.observations }}</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-falcon-default" @click="closeModal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <i class="fas fa-save me-1"></i>
                            {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
