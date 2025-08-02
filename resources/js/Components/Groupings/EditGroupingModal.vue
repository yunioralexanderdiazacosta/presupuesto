<script setup>
import GroupingForm from './GroupingForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editGroupingModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-seedling"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Editar Cosecha</span>
                    <br>
                    <span class="text-muted ml-1" style="font-size: 0.85rem;">Modificar los datos</span>
                </span>
            </div>
        </template>
        <template #body>
            <GroupingForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_grouping_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_update_grouping_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>