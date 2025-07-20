<script setup>
import EstimateForm from './EstimateForm.vue';
import Modal from '@/Components/Modal.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    form: Object,
    costcenters: Array,
    estimates: Array,
    estimate_statuses: Array,
    season_id: Number
});

const { costcenters, estimates, success } = usePage().props;

defineEmits(['store']);

const handleStore = (data) => {
    console.log('handleStore recibido:', data);
    router.post(route('estimates.store'), data, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            if (usePage().props.error) {
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error',
                    text: usePage().props.error,
                    showConfirmButton: true
                });
                return;
            }
            // Resetear el formulario si existe el método
            props.form.reset && props.form.reset();
            // Cerrar el modal (jQuery)
            if (window.$) {
                $('#createEstimateModal').modal('hide');
            }
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Guardado correctamente',
                showConfirmButton: false,
                timer: 1000
            });
        }
    });
};
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createEstimateModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-chess"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar estimaciones</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Completa los datos de la tabla con los kilos estimados</span>
                </span>
            </div>
        </template>
        <template #body>
            <EstimateForm :form="form" :costcenters="costcenters" :estimates="estimates" :estimate_statuses="estimate_statuses" :season_id="form.season_id" @store="handleStore" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_estimate_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
        </template>
    </Modal>
</template>