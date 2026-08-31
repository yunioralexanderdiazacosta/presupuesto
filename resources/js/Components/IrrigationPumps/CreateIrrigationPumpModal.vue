<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IrrigationPumpForm from './IrrigationPumpForm.vue';

const props = defineProps({
    show: Boolean,
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
    if (val) {
        form.reset();
        form.sectors = [];
        openModal();
    }
});

function openModal() {
    const modalElement = document.getElementById('createIrrigationPumpModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function closeModal() {
    const modalElement = document.getElementById('createIrrigationPumpModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    emit('close');
}

function save() {
    if (form.sectors.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un sector', 'error');
        return;
    }

    form.post(route('irrigation-pumps.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado!',
                text: 'Bomba de riego creada correctamente',
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
    <div class="modal fade" id="createIrrigationPumpModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title mb-0 fw-bold">
                        <i class="fas fa-tint text-primary me-2"></i>Nueva Bomba de Riego
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>

                <div class="modal-body bg-body-tertiary">
                    <IrrigationPumpForm :form="form" />
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" @click="closeModal">
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
