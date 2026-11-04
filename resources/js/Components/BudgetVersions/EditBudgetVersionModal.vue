<script setup>
import BudgetVersionForm from './BudgetVersionForm.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    form: Object,
});

defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editBudgetVersionModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-code-branch"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Editar versión</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Cambia el nombre u observaciones de la versión</span>
                </span>
            </div>
        </template>
        <template #body>
            <BudgetVersionForm :form="form" mode="edit" />
        </template>
        <template #footer>
            <button type="button" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>
