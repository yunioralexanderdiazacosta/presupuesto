<template>
  <AppLayout title="Detalle Nota de Crédito/Débito">
    <!-- Aquí puedes mostrar los detalles principales y los items -->
    <div class="mb-4">
      <h2>Nota #{{ note.number }}</h2>
      <div>Tipo: {{ note.type }}</div>
      <div>Proveedor: {{ supplier?.name }}</div>
      <div>Factura asociada: {{ invoice?.number_document }}</div>
      <div>Fecha: {{ note.date }}</div>
      <div>Motivo: {{ note.reason }}</div>
    </div>
    <div>
      <h4>Items</h4>
      <table class="table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Unidad</th>
            <th>Cantidad</th>
            <th>Precio Unitario</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.product_id">
            <td>{{ item.product_name }}</td>
            <td>{{ item.unit_name }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ item.unit_price }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ note: Object, supplier: Object, invoice: Object, items: Array });
</script>
