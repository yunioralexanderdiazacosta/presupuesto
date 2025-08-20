<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Editar Consumo</h1>
    <ConsumptionForm :mode="'edit'" :consumption="consumption" :products="products" />
  </div>
</template>

<script setup>
import { defineProps } from 'vue'
import ConsumptionForm from '@/Components/Consumptions/ConsumptionForm.vue'

defineProps({
  consumption: Object,
  products: Array
})
</script>
