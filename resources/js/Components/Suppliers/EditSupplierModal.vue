<script setup>
import SupplierForm from './SupplierForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    banks: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'editSupplierModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                      style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-edit"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">
                        Editar Proveedor
                    </span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        Actualice los datos del proveedor
                    </span>
                </span>
            </div>
        </template>
        <template #body>
            <SupplierForm :form="form" :banks="banks" :account-types="accountTypes" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_update_cost_center_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" id="kt_modal_supplier_submit" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>