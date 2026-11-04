<script setup>
import BudgetVersionForm from './BudgetVersionForm.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    form: Object,
    versions: Array,
});

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'createBudgetVersionModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-code-branch"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Nueva versión de presupuesto</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Crea una copia editable dentro de la temporada activa</span>
                </span>
            </div>
        </template>
        <template #body>
            <BudgetVersionForm :form="form" :versions="versions" mode="create" />
        </template>
        <template #footer>
            <button type="button" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" :disabled="form.processing" class="btn btn-primary">
                <span v-if="form.processing"><span class="spinner-border spinner-border-sm me-2"></span>Copiando...</span>
                <span v-else>Crear versión</span>
            </button>
        </template>
    </Modal>
</template>
