<script setup>
import SupplierForm from './SupplierForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object,
    banks: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createSupplierModal'">
        <template #header>
            <h5 class="modal-title mb-0 d-flex align-items-center">
                <i class="fas fa-truck text-primary me-2"></i>
                <span>Agregar Proveedor</span>
            </h5>
        </template>
        <template #body>
            <SupplierForm :form="form" :banks="banks" :account-types="accountTypes" />
        </template>
        <template #footer>
            <button
                type="button"
                id="kt_modal_add_supplier_cancel"
                data-bs-dismiss="modal"
                class="btn btn-light me-3"
            >
                <i class="fas fa-times me-1"></i>Cancelar
            </button>
            <button
                type="button"
                @click="$emit('store')"
                :disabled="form.processing"
                id="kt_modal_add_supplier_submit"
                class="btn btn-primary"
            >
                <span v-if="!form.processing" class="indicator-label">
                    <i class="fas fa-save me-1"></i>Guardar
                </span>
                <span v-else class="indicator-label">
                    <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                </span>
            </button>
        </template>
    </Modal>
</template>
