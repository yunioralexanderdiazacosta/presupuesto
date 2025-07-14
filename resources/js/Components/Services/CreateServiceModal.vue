<script setup>
import ServiceForm from './ServiceFormMultiple.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createServiceModal'">
        <template #header>
            <h3 class="mb-3">Agregar Servicio</h3>
        </template>
        <template #body>
            <ServiceForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_service_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_service_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>