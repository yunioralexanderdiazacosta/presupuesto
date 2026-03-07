<script setup>
import RootstockForm from './RootstockForm.vue';
import Modal from '@/Components/Modal.vue';

defineProps({ form: Object });
defineEmits(['update']);
</script>
<template>
    <Modal :maxWidth="'lg'" :id="'editRootstockModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3 text-start">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:1.4rem;">
                    <i class="fas fa-tree"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size:1.2rem;color:#2d3748;">Editar Portainjerto</span><br>
                    <span class="text-muted" style="font-size:0.85rem;">Modifica los datos del portainjerto</span>
                </span>
            </div>
        </template>
        <template #body>
            <RootstockForm :form="form" />
        </template>
        <template #footer>
            <button type="button" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('update')" :disabled="form.processing" class="btn btn-primary">
                <span class="indicator-label">Actualizar</span>
            </button>
        </template>
    </Modal>
</template>
