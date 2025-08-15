<script setup>
import SeasonForm from './SeasonForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editSeasonModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Editar temporada</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Modificar los datos de la temporada</span>
                </span>
            </div>
        </template>
        <template #body>
            <SeasonForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_season_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_season_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>