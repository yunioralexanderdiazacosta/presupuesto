<script setup>
import ProductForm from './ProductForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createProductModal'">
        <template #header>
            <h1 class="mb-3">Agregar producto</h1>
        </template>
        <template #body>
            <ProductForm :form="form" />
        </template>
        <template #footer>
            <button type="button" id="kt_modal_add_product_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_product_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>