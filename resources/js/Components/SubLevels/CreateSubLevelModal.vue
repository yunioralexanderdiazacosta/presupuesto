<script setup>
import SubLevelForm from './SubLevelForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    level: String
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createSubLevelModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-layer-group"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar {{level}}</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Completa los datos del subnivel</span>
                </span>
            </div>
        </template>
        <template #body>
            <SubLevelForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_sub_level_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_sub_level_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>