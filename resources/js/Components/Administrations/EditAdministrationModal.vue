<script setup>
import AdministrationForm from './AdministrationForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editAdministrationModal'">
        <template #header>
            <h3 class="mb-3">Editar</h3>
        </template>
        <template #body>
            <AdministrationForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_administration_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_update_administration_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>