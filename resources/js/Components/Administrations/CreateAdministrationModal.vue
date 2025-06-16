<script setup>
import AdministrationForm from './AdministrationFormMultiple.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createAdministrationModal'">
        <template #header>
            <h3 class="mt-1">Agregar</h3>
        </template>
        <template #body>
            <AdministrationForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_administration_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_team_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>