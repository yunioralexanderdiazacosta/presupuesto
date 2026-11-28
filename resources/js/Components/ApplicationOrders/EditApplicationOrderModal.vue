<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ApplicationOrderForm from './ApplicationOrderForm.vue';

const props = defineProps({
    show: Boolean,
    order: Object,
    products: Array,
    costCenters: Array,
    branches: Array,
    units: Array,
    groupings: Array,
    fruits: Array,
    phenologicalStages: Array,
    machineries: Array,
    operators: Array,
});

const emit = defineEmits(['close']);
const orderForm = ref(null);

const form = useForm({
    date: '',
    start_date: '',
    volume: '',
    mojamiento: '',
    recomendado: '',
    status: 'pendiente',
    responsable: '',
    observations: '',
    phenological_stage_id: null,
    tractors: '',
    equipments: '',
    operators: '',
    products: [],
    cost_centers: [],
    superficie_total: null,
});

watch(() => props.show, (val) => {
    if (val && props.order) {
        // Limpiar errores previos
        form.clearErrors();
        orderForm.value?.cancelEditProduct();
        
        // Formatear fecha para input date (solo YYYY-MM-DD)
        const formatDate = (dateString) => {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        };
        
        // Cargar datos de la orden
        form.date = formatDate(props.order.date);
        form.start_date = formatDate(props.order.start_date);
        form.volume = props.order.volume || '';
        form.mojamiento = props.order.mojamiento;
        form.recomendado = props.order.recomendado;
        form.status = props.order.status;
        form.responsable = props.order.responsable;
        form.observations = props.order.observations || '';
        form.phenological_stage_id = props.order.phenological_stage_id || null;
        form.tractors = props.order.tractors || '';
        form.equipments = props.order.equipments || '';
        form.operators = props.order.operators || '';
        form.superficie_total = props.order.superficie_total ?? null;
        
        // Cargar productos
        form.products = (props.order.order_products || []).map(op => ({
            product_id: op.product_id,
            unit_id: op.unit_id,
            tipo_dosis: op.tipo_dosis,
            dosis_por_100: op.dosis_por_100,
            dosis_por_hectarea: op.dosis_por_hectarea,
            carencia: op.carencia,
            reingreso: op.reingreso,
        }));
        
        // Cargar centros de costo
        form.cost_centers = (props.order.order_cost_centers || []).map(occ => ({
            cost_center_id: occ.cost_center_id,
            surface: occ.cost_center?.surface || 0,
        }));
        
        $('#editApplicationOrderModal').modal('show');
    } else if (!val) {
        $('#editApplicationOrderModal').modal('hide');
    }
});

onMounted(() => {
    $('#editApplicationOrderModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function closeModal() {
    $('#editApplicationOrderModal').modal('hide');
}

function save() {
    if (form.processing || !orderForm.value?.applyPendingProduct()) return;

    // Validaciones básicas
    if (form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }
    
    if (form.cost_centers.length === 0) {
        Swal.fire('Error', 'Debe seleccionar al menos un centro de costo', 'error');
        return;
    }

    form.put(route('application-orders.update', props.order.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Orden de aplicación actualizada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: Object.values(errors).flat().join('\n') || 'Por favor revise los datos ingresados.',
            });
        }
    });
}
</script>

<template>
    <div class="modal fade" id="editApplicationOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Orden de Aplicación
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <ApplicationOrderForm
                        ref="orderForm"
                        :form="form"
                        :products="products"
                        :cost-centers="costCenters"
                        :branches="branches"
                        :units="units"
                        :groupings="groupings"
                        :fruits="fruits"
                        :phenological-stages="phenologicalStages"
                        :machineries="machineries"
                        :operators="operators"
                        :is-editing="true"
                        @save-order="save"
                    />
                </div>

                <div class="modal-footer">
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
                        {{ form.processing ? 'Actualizando...' : 'Actualizar orden' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
