<script setup>
import OperatorForm from './OperatorForm.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    form: Object
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createOperatorModal'">
        <template #header>
            <h5 class="modal-title mb-0 d-flex align-items-center">
                <i class="fas fa-hard-hat text-primary me-2"></i>
                <span>Agregar Operario</span>
            </h5>
        </template>
        <template #body>
            <OperatorForm :form="form" />
        </template>
        <template #footer>
            <button
                type="button"
                data-bs-dismiss="modal"
                class="btn btn-light me-3"
            >
                <i class="fas fa-times me-1"></i>Cancelar
            </button>
            <button
                type="button"
                @click="$emit('store')"
                :disabled="form.processing"
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
