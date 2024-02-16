<script setup>
import AgrochemicalForm from './AgrochemicalForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createAgrochemicalModal'">
        <template #header>
            <h1 class="mb-3">Agregar presupuesto</h1>
        </template>
        <template #body>
            <AgrochemicalForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_budget_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_team_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>