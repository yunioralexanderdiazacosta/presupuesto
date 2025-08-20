<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Detalle de Consumo</h1>
    <!-- Aquí puedes mostrar los detalles del consumo -->
    <pre>{{ consumption }}</pre>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  consumption: Object
})
</script>
