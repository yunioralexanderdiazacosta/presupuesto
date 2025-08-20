<template>
  <div>
    <h5 class="mt-4 ms-3">Items consumidos</h5>
    <table class="table table-bordered table-sm">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Factura/Línea</th>
          <th>Cantidad</th>
          <th>Observaciones</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="idx">
          <td>
            <select v-model="item.product_id" class="form-control" required>
              <option value="">Seleccione...</option>
              <option v-for="p in products" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </td>
          <td>
            <select v-model="item.invoice_item_id" class="form-control" required :disabled="!item.product_id">
              <option value="">Seleccione...</option>
              <option v-for="line in (props.invoiceLinesByProduct[item.product_id] || [])" :key="line.value" :value="line.value">
                {{ line.label }}
              </option>
            </select>
          </td>
          <td>
            <input v-model="item.quantity" type="number" step="0.01" class="form-control" required />
          </td>
          <td>
            <input v-model="item.observations" type="text" class="form-control" />
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm" @click="removeItem(idx)">-</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button type="button" class="btn btn-success btn-sm mt-2" @click="addItem">Agregar línea</button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  items: Array,
  products: {
    type: Array,
    default: () => []
  },
  invoiceLinesByProduct: {
    type: Object,
    default: () => ({})
  }
})
const emit = defineEmits(['update:items'])

function addItem() {
  const newItems = [...props.items, { product_id: '', invoice_item_id: '', quantity: '', observations: '' }]
  emit('update:items', newItems)
}
function removeItem(idx) {
  const newItems = props.items.slice()
  newItems.splice(idx, 1)
  emit('update:items', newItems)
}
</script>
