<script setup>
import ManPowerForm from './ManPowerForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editManPowerModal'">
        <template #header>
            <h1 class="mt-1">Editar mano de obra</h1>
        </template>
        <template #body>
            <ManPowerForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_man_power_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_update_man_power_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>