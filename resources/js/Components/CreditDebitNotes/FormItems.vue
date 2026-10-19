<script setup>
import { computed, watch, onMounted, reactive } from 'vue';
const props = defineProps({
  items: Array,
  products: Array,  // líneas de la factura seleccionada (crédito y débito)
  units: Array,
  is_annulment: Boolean,
  type: String, // 'credito' o 'debito'
  affects_inventory: {
    type: Boolean,
    default: true
  }
});

onMounted(() => {
  console.log('FormItems mounted with items:', props.items);
  console.log('FormItems products:', props.products);
  console.log('FormItems type:', props.type);
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
    branch_id: '',
  });
  emit('update:items', newItems);
}
function remove(idx) {
  const newItems = props.items ? [...props.items] : [];
  newItems.splice(idx, 1);
  emit('update:items', newItems);
}

// Al elegir una línea de la factura (crédito o débito), auto-rellenar producto, unidad, precio y sucursal
watch(
  () => props.items.map(i => i.invoice_product_id),
  (newIds, oldIds) => {
    newIds.forEach((id, idx) => {
      if (id && id !== oldIds[idx]) {
        const line = props.products.find(p => String(p.value) === String(id));
        if (line) {
          // Si la línea tiene product_id, úsalo; si no, usa value (debe ser el id real de producto)
          props.items[idx].product_id = line.product_id ?? line.value;
          props.items[idx].unit_id = line.unit_id;
          props.items[idx].unit_price = props.type === 'credito' && !props.affects_inventory && !props.is_annulment ? 0 : line.unit_price;
          // La sucursal es la de la línea de la factura y no se puede cambiar
          props.items[idx].branch_id = line.branch_id ?? '';
        }
      }
    });
  }, { deep: true }
);

// Sucursal (solo lectura) de la línea de factura elegida
const getBranchName = (item) => {
  if (!item.invoice_product_id) return null;
  const line = props.products.find(p => String(p.value) === String(item.invoice_product_id));
  return line ? (line.branch_name || 'Sin sucursal') : null;
};

const total = computed(() => {
  let t = 0;
  if (!props.items) return 0;
  props.items.forEach(item => {
    t += (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
  });
  // Si es nota de crédito, mostrar negativo
  return props.type === 'credito' ? -1 * t : t;
});

// Calcular el total de la factura relacionada (solo para crédito)
const invoiceTotal = computed(() => {
  if (props.type !== 'credito' || !props.products) return 0;
  let t = 0;
  props.products.forEach(item => {
    t += (parseFloat(item.unit_price) || 0) * (parseFloat(item.amount || item.quantity || 0));
  });
  return t;
});

// Validar si el monto de la nota excede el total de la factura
const exceedsInvoiceTotal = computed(() => {
  if (props.type !== 'credito') return false;
  return Math.abs(total.value) > invoiceTotal.value && invoiceTotal.value > 0;
});

// Obtener la cantidad máxima permitida para un item (basada en la línea de factura)
const getMaxQuantity = (item) => {
  if (props.type !== 'credito' || !item.invoice_product_id) return null;
  const line = props.products.find(p => p.value === item.invoice_product_id);
  return line ? (line.amount || line.quantity || null) : null;
};

// Verificar si un item excede la cantidad facturada
const exceedsQuantity = (item) => {
  const max = getMaxQuantity(item);
  if (max === null) return false;
  return Number(item.quantity) > Number(max);
};

// Stock de la línea de factura seleccionada (consumido, devuelto y disponible en bodega)
const getStockInfo = (item) => {
  if (props.type !== 'credito' || !item.invoice_product_id) return null;
  const line = props.products.find(p => String(p.value) === String(item.invoice_product_id));
  if (!line || line.available === undefined) return null;
  return { consumed: line.consumed, returned: line.returned, available: line.available };
};

// La devolución física (o anulación) no puede superar lo que queda en bodega
const exceedsStock = (item) => {
  const info = getStockInfo(item);
  if (!info || !(props.affects_inventory || props.is_annulment)) return false;
  return Number(item.quantity) - Number(info.available) > 0.0001;
};

// NC de solo precio: no mueve stock; el monto se reparte entre TODAS las unidades facturadas
const isFinancialCredit = computed(() => props.type === 'credito' && !props.affects_inventory && !props.is_annulment);

const discountTotal = (item) => {
  const t = (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
  return t ? Math.round(t * 100) / 100 : '';
};

// Monto tal como lo escribió el usuario (el precio unitario se guarda con 2 decimales y podría redondear)
const typedTotals = reactive({});
const unitFromTotal = (item, total) => {
  const qty = parseFloat(item.quantity) || 0;
  return qty > 0 ? Math.round((total / qty) * 100) / 100 : 0;
};
const discountInputValue = (item) => {
  const typed = typedTotals[item.invoice_product_id];
  if (typed !== undefined && typed !== '' && unitFromTotal(item, parseFloat(typed) || 0) === (parseFloat(item.unit_price) || 0)) {
    return typed;
  }
  return discountTotal(item);
};

// El usuario escribe el monto total a rebajar; se guarda como descuento por unidad sobre la cantidad facturada
const setDiscountTotal = (item, value) => {
  typedTotals[item.invoice_product_id] = value;
  item.unit_price = unitFromTotal(item, parseFloat(value) || 0);
};
</script>

<template>
  <div>
    <div class="d-flex align-items-center justify-content-between mt-4 ms-3 me-3">
      <h5 class="mb-0">Items</h5>
      <div v-if="type === 'credito' && invoiceTotal > 0" class="text-muted" style="font-size: 0.75rem;">
        <i class="fas fa-file-invoice me-1"></i>Total neto factura: <strong>{{ invoiceTotal.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }) }}</strong>
      </div>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Unidad</th>
          <th>{{ isFinancialCredit ? 'Cantidad facturada' : 'Cantidad' }}</th>
          <th>{{ isFinancialCredit ? 'Monto a rebajar (neto)' : 'Precio Unitario' }}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="idx" :class="(exceedsQuantity(item) || exceedsStock(item)) ? 'table-danger' : ''">
          <td>
            <select v-model="item.invoice_product_id" class="form-control" :disabled="isFinancialCredit">
              <option value="" disabled>Seleccione línea de la factura</option>
              <option v-for="p in products" :key="p.value" :value="p.value">{{ p.label }}{{ p.branch_name ? ' — ' + p.branch_name : '' }}</option>
            </select>
            <div v-if="getBranchName(item)" class="text-muted" style="font-size: 0.65rem;">
              <i class="fas fa-building me-1"></i>Sucursal: <strong>{{ getBranchName(item) }}</strong>
            </div>
          </td>
          <td>
            <select v-model="item.unit_id" class="form-control">
              <option value="" disabled>Unidad</option>
              <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
            </select>
          </td>
          <td>
            <input
              type="number"
              v-model="item.quantity"
              class="form-control"
              :class="exceedsQuantity(item) ? 'is-invalid' : ''"
              :readonly="isFinancialCredit"
              :placeholder="type === 'debito' ? 'Ingrese cantidad' : ''"
              :min="type === 'debito' && affects_inventory === false ? 0.01 : 0"
              :max="getMaxQuantity(item) || undefined"
              step="0.01"
            />
            <div v-if="isFinancialCredit" class="text-muted" style="font-size: 0.65rem;">
              El descuento se reparte entre todas las unidades facturadas, incluidas las ya consumidas.
            </div>
            <div v-if="!isFinancialCredit && type === 'credito' && getMaxQuantity(item)" style="font-size: 0.65rem;" :class="exceedsQuantity(item) ? 'text-danger fw-bold' : 'text-muted'">
              Máx: {{ getMaxQuantity(item) }}
            </div>
            <div v-if="!isFinancialCredit && getStockInfo(item)" style="font-size: 0.65rem;" :class="exceedsStock(item) ? 'text-danger fw-bold' : 'text-muted'">
              Consumido: {{ getStockInfo(item).consumed }} · Devuelto: {{ getStockInfo(item).returned }} ·
              <strong>Disponible: {{ getStockInfo(item).available }}</strong>
            </div>
            <div v-if="exceedsStock(item)" class="text-danger" style="font-size: 0.65rem;">
              Supera lo disponible. Si es solo rebaja de precio, quite el check de
              <template v-if="is_annulment">«Anula factura completa» y «Afecta inventario»</template>
              <template v-else>«Afecta inventario»</template>
              o elimine las salidas.
            </div>
          </td>
          <td>
            <template v-if="isFinancialCredit">
              <input
                type="number"
                class="form-control"
                :value="discountInputValue(item)"
                @input="setDiscountTotal(item, $event.target.value)"
                min="0"
                step="1"
                placeholder="Monto total neto a rebajar"
              />
              <div class="text-muted" style="font-size: 0.65rem;">
                Equivale a rebajar ${{ (parseFloat(item.unit_price) || 0).toLocaleString('es-CL', { maximumFractionDigits: 2 }) }} por unidad
                <span v-if="discountTotal(item) !== '' && Math.abs(discountTotal(item) - (parseFloat(discountInputValue(item)) || 0)) > 0.005">
                  (se aplicará ${{ discountTotal(item).toLocaleString('es-CL', { maximumFractionDigits: 2 }) }} por redondeo)
                </span>
              </div>
            </template>
            <input v-else type="number" v-model="item.unit_price" class="form-control" min="0" step="0.01" />
          </td>
          <td>
            <input v-if="type === 'credito' && products.length && products.find(p => p.value === item.product_id) && products.find(p => p.value === item.product_id).id" type="hidden" v-model="item.invoice_product_id" />
            <button class="btn btn-danger btn-sm" @click.prevent="remove(idx)"
              :disabled="is_annulment || (!affects_inventory && type === 'credito')">-</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button v-if="!isFinancialCredit" class="btn btn-success btn-sm" @click.prevent="add"
      :disabled="is_annulment || (type === 'credito' && Math.abs(total) >= invoiceTotal) || (type === 'debito' && affects_inventory === false)">
      Agregar línea
    </button>

    <!-- Alerta si el monto excede la factura -->
    <div v-if="exceedsInvoiceTotal" class="alert alert-danger d-flex align-items-center py-2 px-3 mt-2" style="font-size: 0.78rem;">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <div>
        <strong>El monto de la nota (${{ Math.abs(total).toLocaleString('es-CL') }}) excede el total de la factura (${{ invoiceTotal.toLocaleString('es-CL') }}).</strong>
        Verifique las cantidades y precios ingresados.
      </div>
    </div>

    <div class="d-flex justify-content-end mt-2 mb-4 me-4">
      <table style="font-size: 0.85rem; min-width: 260px;">
        <tbody>
          <tr>
            <td class="text-end pe-3 text-muted">Neto</td>
            <td class="text-end fw-semibold" :class="type === 'credito' ? 'text-danger' : 'text-success'">
              {{ Math.abs(total).toLocaleString('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }) }}
            </td>
          </tr>
          <tr>
            <td class="text-end pe-3 text-muted">IVA (19%)</td>
            <td class="text-end fw-semibold" :class="type === 'credito' ? 'text-danger' : 'text-success'">
              {{ Math.round(Math.abs(total) * 0.19).toLocaleString('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }) }}
            </td>
          </tr>
          <tr class="border-top">
            <td class="text-end pe-3 fw-bold pt-1">
              Total
              <span v-if="type === 'credito'" class="text-muted fw-normal">(Crédito)</span>
              <span v-else-if="type === 'debito'" class="text-muted fw-normal">(Débito)</span>
            </td>
            <td class="text-end fw-bold pt-1" :class="type === 'credito' ? 'text-danger' : 'text-success'">
              {{ Math.round(Math.abs(total) * 1.19).toLocaleString('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }) }}
            </td>
          </tr>
          <tr v-if="type === 'credito' && invoiceTotal > 0">
            <td colspan="2" class="text-end text-muted" style="font-size: 0.75rem;">
              ({{ Math.min(100, (Math.abs(total) / invoiceTotal * 100)).toFixed(1) }}% de la factura)
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
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


