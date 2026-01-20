<script setup>
import PhenologicalStageForm from './PhenologicalStageForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    fruits: Array
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createPhenologicalStageModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-seedling"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar Etapa Fenológica</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Completa los datos de la etapa fenológica</span>
                </span>
            </div>
        </template>
        <template #body>
            <PhenologicalStageForm :form="form" :fruits="fruits" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_phenological_stage_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_phenological_stage_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>
