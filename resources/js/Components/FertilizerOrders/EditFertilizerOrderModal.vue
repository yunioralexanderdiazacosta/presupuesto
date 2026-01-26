<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import FertilizerOrderForm from './FertilizerOrderForm.vue';

const props = defineProps({
    show: Boolean,
    fertilizerOrder: Object,
    products: Array,
    irrigationPumps: Array,
    costCenters: Array,
    units: Array,
    groupings: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    date: '',
    irrigation_pump_id: null,
    responsable: '',
    observations: '',
    products: [],
    irrigation_sectors: [],
    cost_centers: [],
});

watch(() => props.show, (val) => {
    if (val && props.fertilizerOrder) {
        form.clearErrors();
        
        const formatDate = (dateString) => {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        };
        
        form.date = formatDate(props.fertilizerOrder.date);
        form.irrigation_pump_id = props.fertilizerOrder.irrigation_pump_id;
        form.responsable = props.fertilizerOrder.responsable || '';
        form.observations = props.fertilizerOrder.observations || '';
        
        form.products = (props.fertilizerOrder.order_products || []).map(op => ({
            product_id: op.product_id,
            dosis_por_hectarea: parseFloat(op.dosis_por_hectarea),
            cantidad_total: parseFloat(op.cantidad_total),
            unit_id: op.unit_id,
        }));

        form.irrigation_sectors = (props.fertilizerOrder.order_irrigation_sectors || []).map(ois => ois.irrigation_sector_id);
        form.cost_centers = (props.fertilizerOrder.order_cost_centers || []).map(occ => occ.cost_center_id);
        
        setTimeout(() => {
            const modalElement = document.getElementById('editFertilizerOrderModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }, 100);
    } else if (!val) {
        const modalElement = document.getElementById('editFertilizerOrderModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
});

onMounted(() => {
    const modalElement = document.getElementById('editFertilizerOrderModal');
    modalElement.addEventListener('hidden.bs.modal', () => {
        emit('close');
    });
});

function closeModal() {
    const modalElement = document.getElementById('editFertilizerOrderModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    
    // Limpiar backdrop y restaurar scroll
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

function save() {
    if (form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }
    
    if (form.irrigation_sectors.length === 0) {
        Swal.fire('Error', 'Debe seleccionar al menos un sector de riego', 'error');
        return;
    }

    form.put(route('fertilizer-orders.update', props.fertilizerOrder.id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Orden de fertilizante actualizada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            closeModal();
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor revise los datos ingresados',
            });
        }
    });
}

function printOrder() {
    window.open(route('fertilizer-orders.pdf', props.fertilizerOrder.id), '_blank');
}
</script>

<template>
    <div class="modal fade" id="editFertilizerOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Orden de Fertilizante
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <FertilizerOrderForm
                        :form="form"
                        :fertilizer-order="fertilizerOrder"
                        :products="products"
                        :irrigation-pumps="irrigationPumps"
                        :cost-centers="costCenters"
                        :units="units"
                        :groupings="groupings"
                        :is-editing="true"
                    />
                </div>

                <div class="modal-footer">
                    <button 
                        type="button" 
                        class="btn btn-info"
                        @click="printOrder"
                        title="Imprimir orden para operario"
                    >
                        <i class="fas fa-print me-1"></i>Imprimir
                    </button>
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-warning"
                        @click="save"
                        :disabled="form.processing"
                    >
                        <i class="fas fa-save me-1"></i>
                        {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
