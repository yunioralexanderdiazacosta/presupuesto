<script setup>
import { reactive, computed, watch, ref, onMounted, onUpdated, nextTick } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';


const props = defineProps({
  show: Boolean,
  form: { type: Object, default: () => ({}) },
  projects: Array,
  operations: Array,
  machineries: Array,
  costCenters: Array,
  groupings: { type: Array, default: () => [] },
  investments: { type: Array, default: () => [] },
  stockAvailable: Number,
  stockLineData: Object, // Nuevo: datos de la línea asociada (factura/nota)
  levels2: { type: Array, default: () => [] },
  levels3: { type: Array, default: () => [] }
});

// Tooltip HTML para popover de Bootstrap
const creditNotePopover = computed(() => {
  if (!props.form || !props.form.has_credit_note || !props.form.credit_note_info) return '';
  let html = '';
  props.form.credit_note_info.forEach(note => {
    html += `<div><b>N°:</b> ${note.number} <b>Proveedor:</b> ${note.supplier} <b>Fecha:</b> ${note.date}</div>`;
    note.items.forEach(item => {
      html += `<div style='margin-left:1em;'>• <b>${item.product}</b>: ${item.quantity}</div>`;
    });
  });
  return html.trim();
});

// Inicializar popover de Bootstrap al montar y actualizar
onMounted(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});
onUpdated(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});


// Inicializar popover de Bootstrap al montar y actualizar
onMounted(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});
onUpdated(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});

// Computed para mostrar badge si hay nota de crédito
const showCreditNoteBadge = computed(() => {
  return props.form && props.form.has_credit_note;
});
// Datos de la línea asociada (factura/nota)
const stockLine = computed(() => props.stockLineData || null);

// Stock disponible y cantidad original de la línea asociada
const stockAvailable = computed(() => Number(stockLine.value?.stock_disponible) || 0);
const originalQuantity = computed(() => Number(stockLine.value?.cantidad_original) || 0);

// Máximo permitido: cantidad original + stock disponible
const maxQuantity = computed(() => originalQuantity.value + stockAvailable.value);

const emit = defineEmits(['close','updated']);

// Computed para filtrar levels3 según el level2 seleccionado
const filteredLevels3 = computed(() => {
  if (!localForm.level2_id) {
    return props.levels3; // Sin filtro, mostrar todos
  }
  return props.levels3.filter(l3 => Number(l3.level2_id) === Number(localForm.level2_id));
});

const productName = computed(() => {
  if (!props.form) return '';
  // No se puede mostrar el nombre del producto si no se pasa la relación, pero puedes agregarlo al form si lo necesitas
  return props.form.product_name || '';
});
const unitName = computed(() => {
  if (!props.form) return '';
  return props.form.unit_name || '';
});

// Usar un formulario local reactivo
const localForm = reactive({
  id: null,
  project_id: null,
  operation_id: null,
  investment_id: null,
  machinery_id: null,
  cost_center_ids: [],
  notes: '',
  quantity: '',
  date: '',
  invoice_product_id: null,
  credit_debit_note_item_id: null,
  product_name: '',
  unit_name: '',
  level2_id: null, // Filtro helper (no se guarda)
  level3_id: null
});
// Inicializar localForm cuando cambien las props.form
watch(() => props.form, (val) => {
  if (!val) return;
  localForm.id = val.id;
  localForm.project_id = val.project_id ? Number(val.project_id) : null;
  localForm.operation_id = val.operation_id ? Number(val.operation_id) : null;
  localForm.investment_id = val.investment_id ? Number(val.investment_id) : null;
  localForm.machinery_id = val.machinery_id ? Number(val.machinery_id) : null;
  localForm.cost_center_ids = Array.isArray(val.cost_center_ids) ? val.cost_center_ids.map(id => Number(id)) : [];
  localForm.notes = val.notes;
  localForm.quantity = val.quantity;
  localForm.date = val.date;
  localForm.invoice_product_id = val.invoice_product_id;
  localForm.credit_debit_note_item_id = val.credit_debit_note_item_id;
  localForm.product_name = val.product_name;
  localForm.unit_name = val.unit_name;
  localForm.level3_id = val.level3_id ? Number(val.level3_id) : null;
  
  // Auto-seleccionar level2_id basado en level3_id para el filtro
  if (localForm.level3_id && props.levels3) {
    const selectedLevel3 = props.levels3.find(l => l.value === localForm.level3_id);
    if (selectedLevel3) {
      localForm.level2_id = Number(selectedLevel3.level2_id);
    }
  } else {
    localForm.level2_id = null;
  }
}, { immediate: true });

// Variable para manejar la agrupación seleccionada
const selectedGrouping = ref(null);

// Estado para expandir/colapsar tags de CC
const expandedCC = ref(false);

// Watch para aplicar agrupación automáticamente
watch(selectedGrouping, (groupingId) => {
  if (!groupingId) return;
  const grouping = props.groupings?.find(g => g.id == groupingId);
  if (grouping && Array.isArray(grouping.cost_centers)) {
    const groupCCs = grouping.cost_centers.map(cc => cc.id);
    localForm.cost_center_ids = groupCCs;
  }
});



// Detecta si operation_id corresponde a una operación de tipo "Inversión" (por nombre)
const isInversionOp = computed(() => {
  if (!localForm.operation_id) return false;
  const op = props.operations.find(o => String(o.value) === String(localForm.operation_id));
  return op ? /invers/i.test(op.label) : false;
});

function submit() {
  if (Number(localForm.quantity) > stockAvailable.value) {
    return Swal.fire('Error', `La cantidad no puede exceder el stock disponible (${stockAvailable.value})`, 'error');
  }
  // Preparar datos para enviar (excluir level2_id que es solo filtro UI)
  const dataToSend = {
    id: localForm.id,
    project_id: localForm.project_id,
    operation_id: localForm.operation_id,
    investment_id: isInversionOp.value ? (localForm.investment_id || null) : null,
    machinery_id: localForm.machinery_id,
    cost_center_ids: localForm.cost_center_ids,
    notes: localForm.notes,
    quantity: localForm.quantity,
    date: localForm.date,
    level3_id: localForm.level3_id,
  };
  // Guardar con axios
  axios.put(`/outflows/${localForm.id}`, dataToSend)
    .then(() => {
      Swal.fire({ icon: 'success', title: '¡Actualizado!', timer: 1000, showConfirmButton: false });
      emit('updated');
      emit('close');
    })
    .catch(() => {
      Swal.fire('Error', 'No se pudo actualizar la salida', 'error');
    });
}


</script>






<template>
  <Teleport to="body">
  <!-- Modal Bootstrap estándar -->
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div class="d-flex align-items-center gap-2 text-start">
              <span class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                <i class="fas fa-edit"></i>
              </span>
              <span>
                <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Editar salida de producto</span>
                <br>
                <span class="text-muted" style="font-size: 0.85rem;">Modificar los datos de la salida registrada</span>
              </span>
            </div>
            <button type="button" class="btn-close" @click="$emit('close')"></button>
          </div>
          <form @submit.prevent="submit" autocomplete="off">
            <div class="modal-body row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Producto</label>
                <input class="form-control" :value="productName" disabled />
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Unidad</label>
                <input class="form-control" :value="unitName" disabled />
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Stock disponible
                  <span
                    v-if="showCreditNoteBadge"
                    class="badge bg-warning text-dark ms-2 small"
                    tabindex="0"
                    data-bs-toggle="popover"
                    data-bs-html="true"
                    :data-bs-content="creditNotePopover"
                    data-bs-trigger="focus hover"
                    style="cursor:pointer;"
                  >+NC</span>
                </label>
                <input class="form-control" :value="stockAvailable.toFixed(2)" disabled />
              </div>

              <div class="col-12 col-md-3">
                <label class="form-label">Cantidad</label>
                <input
                  type="number"
                  class="form-control"
                  v-model.number="localForm.quantity"
                  :max="stockAvailable"
                  :min="0"
                  step="0.01"
                  required
                />
                <div class="form-text">Máximo permitido: {{ stockAvailable.toFixed(2) }}</div>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Fecha <span class="text-danger">*</span></label>
                <input
                  type="date"
                  class="form-control"
                  v-model="localForm.date"
                  required
                />
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Operación</label>
                <select 
                  v-model="localForm.operation_id" 
                  class="form-select form-select-sm"
                  @change="localForm.investment_id = null"
                >
                  
                  <option v-for="operation in operations" :key="operation.value" :value="operation.value">
                    {{ operation.label }}
                  </option>
                </select>
              </div>
              <!-- Select de inversión: solo cuando la operación es de tipo Inversión -->
              <div v-if="isInversionOp" class="col-12 col-md-3">
                <label class="form-label">Inversión</label>
                <select
                  v-model="localForm.investment_id"
                  class="form-select form-select-sm"
                >
                  <option :value="null">— Sin inversión —</option>
                  <option v-for="inv in investments" :key="inv.value" :value="inv.value">
                    {{ inv.label }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Proyecto</label>
                <select 
                  v-model="localForm.project_id" 
                  class="form-select form-select-sm"
                >
                  
                  <option v-for="project in projects" :key="project.value" :value="project.value">
                    {{ project.label }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Maquinaria</label>
                <select 
                  v-model="localForm.machinery_id" 
                  class="form-select form-select-sm"
                >
                 
                  <option v-for="machinery in machineries" :key="machinery.value" :value="machinery.value">
                    {{ machinery.label }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">
                  Nivel 2 (Filtro)
                  <i class="fas fa-filter text-muted" style="font-size: 0.65rem;"></i>
                </label>
                <select 
                  v-model="localForm.level2_id" 
                  class="form-select form-select-sm"
                  @change="localForm.level3_id = null"
                >
                 
                  <option v-for="level2 in levels2" :key="level2.value" :value="level2.value">
                    {{ level2.label }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Clasificación (Nivel 3) <span class="text-danger">*</span></label>
                <select 
                  v-model="localForm.level3_id" 
                  class="form-select form-select-sm"
                  required
                >
                  <option :value="null" disabled selected hidden>Seleccione clasificación</option>
                  <option v-for="level in filteredLevels3" :key="level.value" :value="level.value">
                    {{ level.label }}
                  </option>
                </select>
                <small v-if="localForm.level2_id && filteredLevels3.length === 0" class="text-muted">
                  No hay opciones para este nivel 2
                </small>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Agrupación</label>
                <select 
                  v-model="selectedGrouping" 
                  class="form-select form-select-sm"
                >
                  <option :value="null">Seleccione agrupación</option>
                  <option v-for="g in (props.groupings || [])" :key="g.id" :value="g.id">
                    {{ g.name }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-8">
                <div class="d-flex align-items-center justify-content-between mb-0">
                  <label class="form-label mb-0">Centros de Costo
                    <span v-if="localForm.cost_center_ids && localForm.cost_center_ids.length > 0" class="badge bg-primary ms-1" style="font-size: 0.6rem; vertical-align: middle;">
                      {{ localForm.cost_center_ids.length }}
                    </span>
                  </label>
                  <button
                    v-if="localForm.cost_center_ids && localForm.cost_center_ids.length > 5"
                    type="button"
                    @click="expandedCC = !expandedCC"
                    class="btn btn-link btn-sm p-0 text-muted"
                    style="font-size: 0.65rem; text-decoration: none;"
                  >
                    <i class="fas" :class="expandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size: 0.6rem;"></i>
                    {{ expandedCC ? 'Colapsar' : 'Ver todos' }}
                  </button>
                </div>
                <Multiselect
                  mode="tags"
                  v-model="localForm.cost_center_ids"
                  :options="costCenters"
                  option-label="label"
                  option-value="value"
                  placeholder="Seleccione centros de costo"
                  :searchable="true"
                  :class="['multiselect-blue multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCC }]"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Observaciones</label>
                <textarea v-model="localForm.notes" class="form-control" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
      <StockLineSelectorModal
        :show="showStockLineModal"
        :productId="localForm.product_id || props.form.product_id"
        @close="showStockLineModal = false"
        @selected="handleStockLineSelected"
      />
  </div>
  </Teleport>
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

/* Limitar tags visibles en el multiselect de centros de costo */
.multiselect-tags-limited .multiselect-tags {
    max-height: 32px !important;
    overflow: hidden !important;
    flex-wrap: wrap;
    transition: max-height 0.3s ease;
}

/* Estado expandido */
.multiselect-tags-expanded .multiselect-tags {
    max-height: 200px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

/* Scrollbar discreto para los tags expandidos */
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar {
    width: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-track {
    background: transparent;
}

.multiselect-blue.multiselect-tags-limited {
    height: auto !important;
    max-height: 38px !important;
    min-height: 26px !important;
    transition: max-height 0.3s ease;
}

.multiselect-blue.multiselect-tags-expanded {
    max-height: 210px !important;
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