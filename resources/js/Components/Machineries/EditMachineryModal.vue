<script setup>
import MachineryForm from './MachineryForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editMachineryModal'">
        <template #header>
            <h2 class="mb-3">Editar Maquinaria</h2>
        </template>
        <template #body>
            <MachineryForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_machinery_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_machinery_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>