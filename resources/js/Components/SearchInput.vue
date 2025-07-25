<template>
  <input
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    :placeholder="placeholder"
    :class="['form-control', 'form-control-sm', 'mb-2', customClass]"
    style="max-width: 300px;"
  />
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Buscar...'
  },
  customClass: {
    type: String,
    default: 'input-busqueda'
  }
});
</script>

<style scoped>
.input-busqueda::placeholder {
  font-size: 0.65rem;
}
</style>
