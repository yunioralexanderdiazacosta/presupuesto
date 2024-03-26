<script setup>
import ManPowerForm from './ManPowerFormMultiple.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createManPowerModal'">
        <template #header>
            <h1 class="mt-1">Agregar mano de obra</h1>
        </template>
        <template #body>
            <ManPowerForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_agrochemical_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_team_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>