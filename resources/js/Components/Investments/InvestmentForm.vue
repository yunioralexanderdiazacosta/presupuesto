<script setup>
import { watch, defineProps, defineEmits } from 'vue';
import Multiselect from '@vueform/multiselect';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
// Emitimos cuando el form cambie para sincronizar con el padre
const props = defineProps({
  form: { type: Object, required: true },
  costCenters: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  seasons: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});
const form = props.form;
const emit = defineEmits(['update:form']);
// Emitimos cuando el form cambie para sincronizar con el padre
watch(form, () => emit('update:form', form), { deep: true });
 </script>

<template>
  <form @submit.prevent>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input v-model="form.name" type="text" class="form-control" required />
      </div>
      <div class="col-md-6">
        <label class="form-label">Mes</label>
        <Multiselect
          v-model="form.month_execute"
          :options="months.map((m, idx) => ({ value: idx+1, label: m }))"
          :searchable="true"
          :close-on-select="true"
          :allow-empty="false"
          placeholder="Seleccione mes"
          class="multiselect-blue form-control"
          :class="{'is-invalid': form.errors?.month_execute}"
        />
      </div>
      <div class="col-md-6">
        <div class="fv-row">
          <label class="form-label">Monto</label>
          <TextInput
            v-model="form.amount"
            type="number"
            class="form-control"
            :class="{'is-invalid': form.errors.amount}"
          />
          <InputError class="mt-2" :message="form.errors.amount" />
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Centros de Costo</label>
        <Multiselect
          v-model="form.cost_centers"
          :options="costCenters.map(cc => ({ value: cc.id, label: cc.name }))"
          mode="tags"
          :searchable="true"
          :close-on-select="false"
          placeholder="Seleccione centros de costo"
          class="multiselect-blue form-control"
          :class="{'is-invalid': form.errors?.cost_centers}"
        />
      </div>
      <div class="col-md-6">
        <label class="form-label">Responsable</label>
        <input v-model="form.responsable" type="text" class="form-control" required />
      </div>
      <div class="col-md-12">
        <label class="form-label">Observaciones</label>
        <textarea v-model="form.observations" class="form-control" rows="2"></textarea>
      </div>
      <input type="hidden" v-model="form.estado" />
    </div>
  </form>
</template>
