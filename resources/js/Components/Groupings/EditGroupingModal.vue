<script setup>
import GroupingForm from './GroupingFormMultiple.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    costCenters: {
        type: Array,
        default: () => [],
    }
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editGroupingModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-object-group"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Editar Agrupación</span>
                    <br>
                    <span class="text-muted ml-1" style="font-size: 0.85rem;">Modificar los datos del grupo</span>
                </span>
            </div>
        </template>
        <template #body>
            <GroupingForm :form="form" :cost-centers="costCenters" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_grouping_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_update_grouping_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>