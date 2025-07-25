<script setup>
import MachineryForm from './MachineryForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createMachineryModal'">
        <template #header>
            <h2 class="mb-3">Agregar Maquinaria</h2>
        </template>
        <template #body>
            <MachineryForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_machinery_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_machinery_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>