
<script setup>
import Modal from '@/Components/Modal.vue';
import InvestmentForm from './InvestmentForm.vue';

const props = defineProps({
  costCenters: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  seasons: { type: Array, default: () => [] },
  form: { type: Object, required: true }
});

const emit = defineEmits(['close', 'store']);

import { onMounted } from 'vue';
onMounted(() => {
  const el = document.getElementById('createInvestmentModal');
  if (el) {
    el.addEventListener('hidden.bs.modal', () => emit('close'));
  }
});
</script>

<template>
  <Modal :maxWidth="'xl'" :id="'createInvestmentModal'">
    <template #header>
      <div class="d-flex align-items-center gap-2 mb-3 text-start">
        <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
          <i class="fas fa-piggy-bank"></i>
        </span>
        <span>
          <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar Inversión</span>
          <br>
          <span class="text-muted" style="font-size: 0.85rem;">Completa los datos de la inversión a registrar</span>
        </span>
      </div>
    </template>
    <template #body>
      <InvestmentForm :form="form" :costCenters="costCenters" :months="months" :seasons="seasons" @update:form="$emit('update:form', $event)" />
    </template>
    <template #footer>
      <button type="button" id="kt_modal_add_investment_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
      <button type="button" @click="$emit('store')" :disabled="form.processing" id="kt_modal_add_investment_submit" class="btn btn-primary">
        <span class="indicator-label">Guardar</span>
      </button>
    </template>
  </Modal>
</template>
