<script setup>
import { computed, watch } from 'vue';
const props = defineProps({
  items: Array,
  products: Array,  // líneas de factura (crédito) o productos (débito)
  units: Array,
  is_annulment: Boolean,
  type: String // 'credito' o 'debito'
});
const emit = defineEmits(['update:items']);
// Diccionario de productos o líneas para autocompletar
const itemDict = {};
props.products.forEach(p => {
  itemDict[p.value] = p;
});

function add() {
  const newItems = props.items ? [...props.items] : [];
  newItems.push({
    invoice_product_id: '',
    product_id: '',
    unit_id: '',
    quantity: 0,
    unit_price: 0,
  });
  emit('update:items', newItems);
}
function remove(idx) {
  const newItems = props.items ? [...props.items] : [];
  newItems.splice(idx, 1);
  emit('update:items', newItems);
}

// Watch para crédito: al cambiar invoice_product_id, auto-rellenar datos
watch(
  () => props.items.map(i => i.invoice_product_id),
  (newIds, oldIds) => {
    if (props.type !== 'credito') return;
    newIds.forEach((id, idx) => {
      if (id && id !== oldIds[idx]) {
        const line = props.products.find(p => p.value === id);
        if (line) {
          props.items[idx].product_id = line.product_id;
          props.items[idx].unit_id = line.unit_id;
          props.items[idx].unit_price = line.unit_price;
        }
      }
    });
  }, { deep: true }
);
// Watch para débito: al cambiar product_id, auto-rellenar unidad
watch(
  () => props.items.map(i => i.product_id),
  (newIds, oldIds) => {
    if (props.type !== 'debito') return;
    newIds.forEach((id, idx) => {
      if (id && id !== oldIds[idx]) {
        const prod = props.products.find(p => p.value === id);
        if (prod && prod.unit_id) {
          props.items[idx].unit_id = prod.unit_id;
        }
      }
    });
  }, { deep: true }
);

const total = computed(() => {
  let t = 0;
  if (!props.items) return 0;
  props.items.forEach(item => {
    t += (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
  });
  // Si es nota de crédito, mostrar negativo
  return props.type === 'credito' ? -1 * t : t;
});
</script>

<template>
  <div>
    <h5 class="mt-4 ms-3">Items</h5>
    <table class="table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Unidad</th>
          <th>Cantidad</th>
          <th>Precio Unitario</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="idx">
          <td>
            <select v-if="type === 'credito'" v-model="item.invoice_product_id" class="form-control">
              <option value="" disabled>Seleccione línea</option>
              <option v-for="p in products" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
            <select v-else v-model="item.product_id" class="form-control">
              <option value="" disabled>Seleccione producto</option>
              <option v-for="p in products" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
          </td>
          <td>
            <select v-model="item.unit_id" class="form-control">
              <option value="" disabled>Unidad</option>
              <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
            </select>
          </td>
          <td><input type="number" v-model="item.quantity" class="form-control" min="0" step="0.01" /></td>
          <td><input type="number" v-model="item.unit_price" class="form-control" min="0" step="0.01" /></td>
          <td>
            <input v-if="type === 'credito' && products.length && products.find(p => p.value === item.product_id) && products.find(p => p.value === item.product_id).id" type="hidden" v-model="item.invoice_product_id" />
            <button class="btn btn-danger btn-sm" @click.prevent="remove(idx)">-</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button class="btn btn-success btn-sm" @click.prevent="add" :disabled="is_annulment">Agregar línea</button>
    <div class="text-end mt-2 me-4">
      <strong>Total de la nota:
        <span :class="type === 'credito' ? 'text-danger' : 'text-success'">
          {{ total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }) }}
        </span>
      </strong>
    </div>
  </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.75rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajuste de placeholder dentro de multiselect-blue */
.multiselect-blue .multiselect__placeholder {
    font-size: 0.85rem !important;
    opacity: 0.7 !important;
	 white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ajustes para inputs nativos */
input.form-control:not([role="combobox"]),
select.form-control {
    height: 26px;
    min-height: 26px;
    font-size: 0.75rem;
    padding-top: 2px;
    padding-bottom: 2px;
}

/* Ajuste de tamaño de placeholder en inputs nativos */
input.form-control::placeholder {
    font-size: 0.75rem !important;
    opacity: 0.7 !important;
}

/* Checkboxes */
.form-check-input[type="checkbox"] {
    width: 0.8em;
    height: 0.8em;
    vertical-align: middle;
}
/* Group icon alignment */
.input-group-text {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}
/* Labels */
.col-form-label,
label {
    font-size: 0.8rem;
}
/* Opciones del multiselect */
.multiselect__option {
    font-size: 0.7rem;
}
/* Asegura z-index adecuado para dropdown */
.multiselect__content {
    z-index: 2050;
}


input::placeholder,
textarea::placeholder {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
textarea::placeholder {
  text-transform: none !important;
}


.elegant-divider {
	width: 100%;
	height: 3px;
	border: none;
	border-radius: 2px;
	background: linear-gradient(90deg, rgba(44,123,229,0.18) 0%, rgba(44,123,229,0.45) 50%, rgba(44,123,229,0.18) 100%);
	box-shadow: 0 2px 8px 0 rgba(44,123,229,0.10);
}
</style>


