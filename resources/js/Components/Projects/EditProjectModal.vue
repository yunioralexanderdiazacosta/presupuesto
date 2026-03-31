<script setup>
import ProjectForm from './ProjectForm.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    form: Object,
    operations: { type: Array, default: () => [] },
});

defineEmits(['update']);
</script>

<template>
    <Modal :maxWidth="'lg'" :id="'editProjectModal'">
        <template #header>
            <h5 class="modal-title mb-0 d-flex align-items-center">
                <i class="fas fa-edit text-primary me-2"></i>
                <span>Editar Proyecto</span>
            </h5>
        </template>
        <template #body>
            <ProjectForm :form="form" :operations="operations" />
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
                @click="$emit('update')"
                :disabled="form.processing"
                class="btn btn-primary"
            >
                <span v-if="!form.processing" class="indicator-label">
                    <i class="fas fa-save me-1"></i>Actualizar
                </span>
                <span v-else>
                    <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                </span>
            </button>
        </template>
    </Modal>
</template>
