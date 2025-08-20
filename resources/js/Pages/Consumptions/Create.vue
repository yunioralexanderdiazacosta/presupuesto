<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Registrar Consumo</h1>
    <ConsumptionForm
      :mode="'create'"
      :form="form"
      :products="products"
      :cost-centers="costCenters"
      :operations="operations"
      :machineries="machineries"
      :projects="projects"
      :invoice-lines-by-product="invoiceLinesByProduct"
    />
  </div>
</template>

<script setup>
import ConsumptionForm from '@/Components/Consumptions/ConsumptionForm.vue'
import { defineProps } from 'vue'
import { useForm } from '@inertiajs/vue3'

defineProps({
  products: Array,
  costCenters: Array,
  operations: Array,
  machineries: Array,
  projects: Array,
  invoiceLinesByProduct: Object
})

const form = useForm({
  date: '',
  due_date: '',
  cost_center_id: null,
  operation_id: null,
  project_id: null,
  machinery_id: null,
  observations: '',
  items: [], // <-- inicializar items como array vacío
  // ...otros campos necesarios
})
</script>
