<template>
  <div class="modal-backdrop" v-if="show">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nueva Inversión</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        <form @submit.prevent="submit">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input v-model="form.name" type="text" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Mes</label>
              <select v-model="form.month" class="form-select" required>
                <option v-for="m in months" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Monto</label>
              <input v-model="form.amount" type="number" class="form-control" required min="0" step="0.01" />
            </div>
            <div class="mb-3">
              <label class="form-label">Centros de Costo</label>
              <select v-model="form.cost_centers" class="form-select" multiple required>
                <option v-for="cc in costCenters" :key="cc.id" :value="cc.id">{{ cc.name }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Temporada</label>
              <select v-model="form.season_id" class="form-select" required>
                <option v-for="s in seasons" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Responsable</label>
              <input v-model="form.responsable" type="text" class="form-control" required />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
const props = defineProps({
  show: { type: Boolean, default: true },
  costCenters: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  seasons: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] }
});
const emit = defineEmits(['close']);
const form = useForm({
  name: '',
  month: '',
  amount: '',
  cost_centers: [],
  season_id: '',
  user_id: ''
});
function submit() {
  form.post(route('investments.store'), {
    onSuccess: () => {
      emit('close');
      form.reset();
    }
  });
}
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
