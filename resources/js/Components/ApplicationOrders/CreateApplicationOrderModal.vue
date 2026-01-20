<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ApplicationOrderForm from './ApplicationOrderForm.vue';

const props = defineProps({
    show: Boolean,
    products: Array,
    costCenters: Array,
    units: Array,
    groupings: Array,
    fruits: Array,
    phenologicalStages: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    date: '',
    start_date: '',
    volume: '',
    mojamiento: '',
    recomendado: '',
    aplicadores: '',
    status: 'pendiente',
    responsable: '',
    observations: '',
    phenological_stage_id: null,
    products: [],
    cost_centers: [],
});

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        form.status = 'pendiente';
        // Establecer fecha actual por defecto
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        form.date = `${year}-${month}-${day}`;
        $('#createApplicationOrderModal').modal('show');
    } else {
        $('#createApplicationOrderModal').modal('hide');
    }
});

onMounted(() => {
    $('#createApplicationOrderModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function closeModal() {
    $('#createApplicationOrderModal').modal('hide');
    // Forzar eliminación del backdrop
    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '');
    }, 300);
}

function save() {
    // Validaciones básicas
    if (form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }
    
    if (form.cost_centers.length === 0) {
        Swal.fire('Error', 'Debe seleccionar al menos un centro de costo', 'error');
        return;
    }

    form.post(route('application-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Orden de aplicación creada correctamente',
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
</script>

<template>
    <div class="modal fade" id="createApplicationOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Orden de Aplicación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <ApplicationOrderForm
                        :form="form"
                        :products="products"
                        :cost-centers="costCenters"
                        :units="units"
                        :groupings="groupings"
                        :fruits="fruits"
                        :phenological-stages="phenologicalStages"
                    />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
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
