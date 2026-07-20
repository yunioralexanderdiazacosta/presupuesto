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
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                      style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-truck"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">
                        Agregar Proveedor
                    </span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        Complete los datos del nuevo proveedor
                    </span>
                </span>
            </div>
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
