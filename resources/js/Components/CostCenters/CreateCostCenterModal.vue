<script setup>
import CostCenterForm from './CostCenterForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createCostCenterModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-list"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar Centro de Costo</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Completa los datos del centro de costo a registrar</span>
                </span>
            </div>
        </template>
        <template #body>
            <CostCenterForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_cost_center_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_cost_center_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>