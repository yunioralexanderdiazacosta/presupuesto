<template>
  <button @click="copy" class="btn btn-secondary btn-sm">
    <slot>Copiar</slot>
  </button>
</template>

<script setup>
import { defineProps } from 'vue';
const props = defineProps({
  text: { type: String, required: true }
});

function copy() {
  navigator.clipboard.writeText(props.text)
    .then(() => alert('¡Copiado al portapapeles!'))
    .catch(() => alert('No se pudo copiar.'));
}
</script>
