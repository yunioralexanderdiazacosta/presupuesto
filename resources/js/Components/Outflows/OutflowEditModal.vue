<script setup>
import { reactive, computed, watch } from 'vue';
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
  stockAvailable: Number,
});

const emit = defineEmits(['close','updated']);

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
  machinery_id: null,
  cost_center_ids: [],
  notes: '',
  quantity: '',
  invoice_product_id: null,
  credit_debit_note_item_id: null,
  product_name: '',
  unit_name: ''
});
// Inicializar localForm cuando cambien las props.form
watch(() => props.form, (val) => {
  if (!val) return;
  localForm.id = val.id;
  localForm.project_id = val.project_id ? Number(val.project_id) : null;
  localForm.operation_id = val.operation_id ? Number(val.operation_id) : null;
  localForm.machinery_id = val.machinery_id ? Number(val.machinery_id) : null;
  localForm.cost_center_ids = Array.isArray(val.cost_center_ids) ? val.cost_center_ids.map(id => Number(id)) : [];
  localForm.notes = val.notes;
  localForm.quantity = val.quantity;
  localForm.invoice_product_id = val.invoice_product_id;
  localForm.credit_debit_note_item_id = val.credit_debit_note_item_id;
  localForm.product_name = val.product_name;
  localForm.unit_name = val.unit_name;
}, { immediate: true });

function submit() {
  if (localForm.quantity > props.stockAvailable) {
    return Swal.fire('Error', 'La cantidad no puede exceder el stock disponible', 'error');
  }
  // Guardar con axios
  axios.put(`/outflows/${localForm.id}`, localForm)
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
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar salida de producto</h5>
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
                <label class="form-label">Stock disponible</label>
                <input class="form-control" :value="stockAvailable.toFixed(2)" disabled />
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control" v-model.number="localForm.quantity" :max="stockAvailable" step="0.01" required />
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Proyecto</label>
                <Multiselect
                  v-model="localForm.project_id"
                  :options="projects"
                  option-label="label"
                  option-value="value"
                  placeholder="Seleccione proyecto"
                  :searchable="true"
                  class="multiselect-blue"
                />
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Operación</label>
                <Multiselect
                  v-model="localForm.operation_id"
                  :options="operations"
                  option-label="label"
                  option-value="value"
                  placeholder="Seleccione operación"
                  :searchable="true"
                  class="multiselect-blue"
                />
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Maquinaria</label>
                <Multiselect
                  v-model="localForm.machinery_id"
                  :options="machineries"
                  option-label="label"
                  option-value="value"
                  placeholder="Seleccione maquinaria"
                  :searchable="true"
                  class="multiselect-blue"
                />
              </div>
              <div class="col-12 col-md-8">
                <label class="form-label">Centros de Costo</label>
                <Multiselect
                  mode="tags"
                  v-model="localForm.cost_center_ids"
                  :options="costCenters"
                  option-label="label"
                  option-value="value"
                  placeholder="Seleccione centros de costo"
                  :searchable="true"
                  class="multiselect-blue"
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