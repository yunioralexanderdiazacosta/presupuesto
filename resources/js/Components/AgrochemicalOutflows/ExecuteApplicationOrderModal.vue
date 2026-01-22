<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ExecuteApplicationOrderForm from './ExecuteApplicationOrderForm.vue';

const props = defineProps({
    show: Boolean,
    availableOrders: Array,
    availableStocksByProduct: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    application_order_id: null,
    date: '',
    maquinadas: '',
    observations: '',
    products: [], // [{product_id, invoice_product_id, quantity}]
});

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        // Establecer fecha actual por defecto
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        form.date = `${year}-${month}-${day}`;
        $('#executeApplicationOrderModal').modal('show');
    } else {
        $('#executeApplicationOrderModal').modal('hide');
    }
});

onMounted(() => {
    $('#executeApplicationOrderModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function closeModal() {
    $('#executeApplicationOrderModal').modal('hide');
    // Forzar eliminación del backdrop
    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '');
    }, 300);
}

function save() {
    // Validaciones básicas
    if (!form.application_order_id) {
        Swal.fire('Error', 'Debe seleccionar una orden de aplicación', 'error');
        return;
    }
    
    if (form.products.length === 0) {
        Swal.fire('Error', 'La orden no tiene productos', 'error');
        return;
    }

    // Validar que TODOS los productos de la orden estén completos
    for (const product of form.products) {
        const realQuantity = parseFloat(product.real_quantity || 0);
        const sumLines = product.lines.reduce((sum, line) => sum + parseFloat(line.quantity || 0), 0);
        
        // Verificar que tenga cantidad real definida
        if (realQuantity <= 0) {
            Swal.fire('Error', `Debe ingresar la cantidad real para ${product.product_name}`, 'error');
            return;
        }
        
        // Verificar que la suma de líneas coincida con la cantidad real
        if (Math.abs(sumLines - realQuantity) > 0.01) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: `Para <strong>${product.product_name}</strong>:<br>
                       Cantidad real definida: ${realQuantity.toLocaleString('es-ES')} ${product.unit_name}<br>
                       Suma de líneas: ${sumLines.toLocaleString('es-ES')} ${product.unit_name}<br><br>
                       Las cantidades deben coincidir exactamente.`,
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Verificar que todas las líneas tengan factura y cantidad
        const hasEmptyLines = product.lines.some(line => !line.invoice_product_id || !line.quantity || line.quantity <= 0);
        if (hasEmptyLines) {
            Swal.fire('Error', `Complete todas las líneas de factura para ${product.product_name}`, 'error');
            return;
        }

        // Verificar que ninguna línea exceda el stock disponible de su factura
        for (const line of product.lines) {
            const invoice = product.availableInvoices?.find(inv => inv.invoice_product_id === line.invoice_product_id);
            const stockDisponible = invoice ? invoice.stock_disponible : 0;
            
            if (parseFloat(line.quantity) > stockDisponible) {
                Swal.fire('Error', `Stock insuficiente para ${product.product_name}. Disponible: ${stockDisponible} ${product.unit_name}`, 'error');
                return;
            }
        }
    }

    // Confirmar que se aplicarán todos los productos
    Swal.fire({
        title: '¿Confirma la aplicación?',
        html: `Se aplicarán <strong>${form.products.length} productos</strong> de la orden.<br>Esta acción no se puede deshacer.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aplicar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('agrochemical-outflows.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Aplicación registrada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    emit('saved');
                    closeModal();
                },
                onError: (errors) => {
                    console.error('Errores de validación:', errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errors.message || 'Por favor revise los datos ingresados',
                    });
                }
            });
        }
    });
}
</script>

<template>
    <div class="modal fade" id="executeApplicationOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-spray-can me-2"></i>Registrar Aplicación Real
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <ExecuteApplicationOrderForm
                        :form="form"
                        :available-orders="availableOrders"
                        :available-stocks-by-product="availableStocksByProduct"
                    />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-success"
                        @click="save"
                        :disabled="form.processing"
                    >
                        <i class="fas fa-save me-1"></i>
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
