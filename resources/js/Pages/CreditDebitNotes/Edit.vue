<template>
  <AppLayout title="Editar Nota de Crédito/Débito">
    <FormCreditDebitNote :form="note" :suppliers="suppliers" :invoices="invoices" :products="products" :units="units" :items="items" />
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormCreditDebitNote from '@/Components/CreditDebitNotes/Form.vue';
const props = defineProps({ note: Object, suppliers: Array, invoices: Array, products: Array, units: Array, items: Array });

// Debug temporal
console.log('Note object from backend:', props.note);
console.log('Note supplier_id:', props.note?.supplier_id);
console.log('Note invoice_id:', props.note?.invoice_id);
</script>
