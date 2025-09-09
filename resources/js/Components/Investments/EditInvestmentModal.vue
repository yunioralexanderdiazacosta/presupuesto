<template>
  <Modal :id="'editInvestmentModal'" :maxWidth="'xl'">
    <template #header>
      <!-- Mismo header que CreateInvestmentModal -->
      <div class="d-flex align-items-center gap-2 mb-3 text-start">
        <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:1.4rem;">
          <i class="fas fa-piggy-bank"></i>
        </span>
        <span>
          <span class="fw-bold" style="font-size:1.2rem;color:#2d3748;letter-spacing:0.5px;">Editar Inversión</span><br>
          <span class="text-muted" style="font-size:0.85rem;">Modifica los datos de la inversión</span>
        </span>
      </div>
    </template>
    <template #body>
      <InvestmentForm
        :form="form"
        :costCenters="costCenters"
        :months="months"
        :seasons="seasons"
        @update:form="$emit('update:form', $event)"
      />
    </template>
    <template #footer>
      <button type="button" data-bs-dismiss="modal" class="btn btn-light me-3" @click="$emit('close')">
        Cerrar
      </button>
      <button type="button" class="btn btn-primary" @click="$emit('update')" :disabled="form.processing">
        Actualizar
      </button>
    </template>
  </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import InvestmentForm from './InvestmentForm.vue';
import { onMounted } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  costCenters: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  seasons: { type: Array, default: () => [] },
});
const emit = defineEmits(['close', 'update', 'update:form']);

// Al cerrar modal con Bootstrap, emitir evento close
onMounted(() => {
  const el = document.getElementById('editInvestmentModal');
  if (el) {
    el.addEventListener('hidden.bs.modal', () => emit('close'));
  }
});
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.3);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
