<script setup>
import FertilizerForm from './FertilizerForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editFertilizerModal'">
        <template #header>
            <h1 class="mb-3">Editar fertilizante</h1>
        </template>
        <template #body>
            <FertilizerForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_fertilizer_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_update_fertilizer_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>