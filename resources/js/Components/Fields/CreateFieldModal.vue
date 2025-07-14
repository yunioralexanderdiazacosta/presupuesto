<script setup>
import FieldForm from './FieldFormMultiple.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createFieldModal'">
        <template #header>
            <h2 class="mb-1">Agregar Generales Campo</h2>
        </template>
        <template #body>
            <FieldForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_field_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_field_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>