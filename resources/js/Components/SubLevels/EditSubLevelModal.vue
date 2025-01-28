<script setup>
import SubLevelForm from './SubLevelForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    level: String
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editSubLevelModal'">
        <template #header>
            <h1 class="mb-3">Editar {{level}}</h1>
        </template>
        <template #body>
            <SubLevelForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_level_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_level_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>