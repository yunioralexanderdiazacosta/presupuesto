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
