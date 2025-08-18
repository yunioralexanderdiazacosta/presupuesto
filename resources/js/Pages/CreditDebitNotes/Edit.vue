<template>
  <AppLayout title="Editar Nota de Crédito/Débito">
    <FormCreditDebitNote :note="note" :suppliers="suppliers" :invoices="invoices" :products="products" :units="units" :items="items" />
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormCreditDebitNote from '@/Components/CreditDebitNotes/Form.vue';
const props = defineProps({ note: Object, suppliers: Array, invoices: Array, products: Array, units: Array, items: Array });
</script>
