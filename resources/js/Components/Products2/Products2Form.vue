<template>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input v-model="modelValue.name" type="text" class="form-control" required maxlength="255" />
    </div>
    <div class="col-md-6">
      <label class="form-label">Nivel 3</label>
      <Multiselect v-model="modelValue.level3" :options="level3Options" placeholder="Seleccione..." :searchable="true" :clear-on-select="false" :close-on-select="true" :allow-empty="true" />
    </div>
    <div class="col-md-6">
      <label class="form-label">Ingrediente Activo</label>
      <input v-model="modelValue.active_ingredient" type="text" class="form-control" maxlength="255" />
    </div>
    <div class="col-md-3">
      <label class="form-label">Precio</label>
      <input v-model="modelValue.price" type="number" step="0.01" class="form-control" min="0" />
    </div>
    <div class="col-md-3">
      <label class="form-label">Unidad</label>
      <Multiselect v-model="modelValue.unit_price_id" :options="unitOptions" label="name" valueProp="id" placeholder="Seleccione..." :searchable="true" :clear-on-select="false" :close-on-select="true" :allow-empty="true" />
    </div>
    <div class="col-md-6">
      <label class="form-label">Forma</label>
      <Multiselect v-model="modelValue.form" :options="formOptions" placeholder="Seleccione..." :searchable="true" :clear-on-select="false" :close-on-select="true" :allow-empty="true" />
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
  modelValue: Object,
  units: {
    type: Array,
    default: () => []
  },
  formOptions: {
    type: Array,
    default: () => []
  },
  level3Options: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue']);

const unitOptions = computed(() => props.units);
const formOptions = computed(() => props.formOptions);
const level3Options = computed(() => props.level3Options);

// Sincronizar el valor del modelo con el value del objeto
watch(() => props.modelValue.level3, (val) => {
  if (val && typeof val === 'object' && val.value) {
    props.modelValue.level3 = val.value;
  }
});
watch(() => props.modelValue.form, (val) => {
  if (val && typeof val === 'object' && val.value) {
    props.modelValue.form = val.value;
  }
});
</script>

