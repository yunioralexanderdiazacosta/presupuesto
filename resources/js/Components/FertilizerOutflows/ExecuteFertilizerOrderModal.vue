<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ExecuteFertilizerOrderForm from './ExecuteFertilizerOrderForm.vue';

const props = defineProps({
    show: Boolean,
    availableOrders: Array,
    availableStocksByProduct: Object,
    branches: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'order-executed']);

const form = useForm({
    fertilizer_order_id: null,
    date: new Date().toISOString().split('T')[0],
    observations: '',
    products: [],
});

watch(() => props.show, (val) => {
    if (val) {
        form.clearErrors();
        form.reset();
        form.date = new Date().toISOString().split('T')[0];
        openModal();
    }
});

onMounted(() => {
    const modalElement = document.getElementById('executeFertilizerOrderModal');
    modalElement?.addEventListener('hidden.bs.modal', () => {
        emit('close');
    });
});

function openModal() {
    setTimeout(() => {
        const modalElement = document.getElementById('executeFertilizerOrderModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }, 100);
}

function closeModal() {
    const modalElement = document.getElementById('executeFertilizerOrderModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Limpiar backdrop
    setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }, 300);
}

function executeOrder() {
    // Validar que haya productos
    if (!form.products || form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }

    // Validar que cada producto tenga líneas
    for (let product of form.products) {
        if (!product.lines || product.lines.length === 0) {
            Swal.fire('Error', `El producto ${product.product_name} debe tener al menos una línea de factura`, 'error');
            return;
        }

        const realQty = parseFloat(product.real_quantity || 0);
        if (realQty <= 0) {
            Swal.fire('Error', `Debe ingresar una cantidad real para el producto ${product.product_name}`, 'error');
            return;
        }

        // Validar que cada línea tenga factura seleccionada
        for (let line of product.lines) {
            if (!line.invoice_product_id) {
                Swal.fire('Error', `Debe seleccionar una factura en todas las líneas del producto ${product.product_name}`, 'error');
                return;
            }
            if (parseFloat(line.quantity || 0) <= 0) {
                Swal.fire('Error', `La cantidad de cada línea debe ser mayor a 0 en el producto ${product.product_name}`, 'error');
                return;
            }
        }

        // Validar que la suma de líneas coincida con cantidad real
        const totalUsed = product.lines.reduce((sum, line) => sum + parseFloat(line.quantity || 0), 0);
        
        if (Math.abs(totalUsed - realQty) > 0.01) {
            Swal.fire('Error', `La suma de líneas del producto ${product.product_name} (${totalUsed}) no coincide con la cantidad real (${realQty})`, 'error');
            return;
        }
    }

    form.post(route('fertilizer-outflows.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Aplicación de fertilizante registrada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            closeModal();
            emit('order-executed');
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: Object.values(errors)[0] || 'Por favor revise los datos ingresados',
            });
        }
    });
}
</script>

<template>
    <div class="modal fade" id="executeFertilizerOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-seedling me-2"></i>Registrar Aplicación de Fertilizante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <ExecuteFertilizerOrderForm
                        :form="form"
                        :available-orders="availableOrders"
                        :available-stocks-by-product="availableStocksByProduct"
                        :branches="branches"
                    />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-success"
                        @click="executeOrder"
                        :disabled="form.processing"
                    >
                        <i class="fas fa-check me-1"></i>
                        {{ form.processing ? 'Registrando...' : 'Registrar Aplicación' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
