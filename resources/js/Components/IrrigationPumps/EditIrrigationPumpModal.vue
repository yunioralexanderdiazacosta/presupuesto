<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IrrigationPumpForm from './IrrigationPumpForm.vue';

const props = defineProps({
    show: Boolean,
    pump: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    code: '',
    brand: '',
    model: '',
    sectors: [],
});

watch(() => props.show, (val) => {
    if (val && props.pump) {
        form.name = props.pump.name || '';
        form.code = props.pump.code || '';
        form.brand = props.pump.brand || '';
        form.model = props.pump.model || '';
        form.sectors = props.pump.sectors ? props.pump.sectors.map(s => ({
            id: s.id, // Incluir ID para actualización
            name: s.name,
            surface: s.surface,
            observations: s.observations || '',
            orders_count: s.orders_count || 0 // Incluir contador de órdenes
        })) : [];
        openModal();
    }
});

function openModal() {
    const modalElement = document.getElementById('editIrrigationPumpModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function closeModal() {
    const modalElement = document.getElementById('editIrrigationPumpModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    emit('close');
}

function update() {
    if (form.sectors.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un sector', 'error');
        return;
    }

    form.put(route('irrigation-pumps.update', props.pump.id), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'Bomba de riego actualizada correctamente',
                timer: 1500,
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
    <div class="modal fade" id="editIrrigationPumpModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title mb-0 fw-bold">
                        <i class="fas fa-edit text-primary me-2"></i>Editar Bomba de Riego
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <div class="modal-body bg-body-tertiary">
                    <IrrigationPumpForm :form="form" :is-editing="true" />
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="update"
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
