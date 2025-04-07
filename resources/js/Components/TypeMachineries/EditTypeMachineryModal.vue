<script setup>
import TypeMachineryForm from './TypeMachineryForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editTypeMachineryModal'">
        <template #header>
            <h1 class="mb-3">Editar Tipo de Maquinaria</h1>
        </template>
        <template #body>
            <TypeMachineryForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_type_machinery_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_type_machinery_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>