<script setup>
import UserForm from './UserForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createUserModal'">
        <template #header>
            <h1 class="mb-3">Agregar usuario</h1>
        </template>
        <template #body>
            <UserForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_user_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_user_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>