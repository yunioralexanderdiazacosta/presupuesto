<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    show: Boolean,
    machineries: Array,
    operators: Array,
    costCenters: Array,
    level1s: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    date: '',
    machinery_id: '',
    operator_id: '',
    cost_center_id: [], // Debe ser array para Multiselect tags
    level1_id: '',
    fuel_type: '',
    liters: '',
    horometer: '',
    odometer: '',
    observations: '',
});

watch(() => props.show, (val) => {
    if (val) form.reset();
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('fuel-outflows.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Consumo registrado correctamente', timer: 1200, showConfirmButton: false });
            form.reset();
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    });
}

//determina si se muestra el campo de centro de costo según el nivel 1 seleccionado
const selectedLevel1 = computed(() => {
  return props.level1s?.find(l => l.id === form.level1_id) || null;
});
const showCostCenter = computed(() => {
  if (!selectedLevel1.value) return false;
  const label = selectedLevel1.value.name?.toLowerCase() || '';
  return label === 'costos directos' || label === 'cosecha';
});

console.log('level1s:', props.level1s);
</script>
<template>
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nuevo Consumo de Combustible</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="save">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Fecha</label>
                <input type="date" v-model="form.date" class="form-control" required />
              </div>
             
              <div class="col-md-4">
                <label class="form-label">Maquinaria</label>
                <select v-model="form.machinery_id" class="form-select" required>
                  <option value="">Seleccione</option>
                  <option v-for="m in machineries" :key="m.id" :value="m.id">{{ m.cod_machinery }}</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Operario</label>
                <select v-model="form.operator_id" class="form-select" required>
                  <option value="">Seleccione</option>
                  <option v-for="o in operators" :key="o.id" :value="o.id">{{ o.name }}</option>
                </select>
              </div>
               <div class="col-md-4">
                <label class="form-label">Nivel 1</label>
                <Multiselect
                  v-model="form.level1_id"
                  :options="props.level1s ? props.level1s.map(l => ({ value: l.id, label: l.name })) : []"
                  placeholder="Seleccione"
                  :searchable="true"
                  :clearable="true"
                  required
                />
              </div>
              <div class="col-md-4" v-if="showCostCenter">
                <label class="form-label">Centro de Costo</label>
                <Multiselect
                  mode="tags"
                  placeholder="Centro de Costo"
                  v-model="form.cost_center_id"
                  :close-on-select="false"
                  :options="props.costCenters.map(c => ({ value: c.id, label: c.name }))"
                  :searchable="true"
                  :hide-selected="false"
                  class="multiselect-blue form-control-sm"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Tipo Combustible</label>
                <input v-model="form.fuel_type" class="form-control" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Litros</label>
                <input type="number" v-model="form.liters" class="form-control" min="0.01" step="0.01" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Horómetro</label>
                <input type="number" v-model="form.horometer" class="form-control" min="0" step="0.01" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Odómetro</label>
                <input type="number" v-model="form.odometer" class="form-control" min="0" step="0.01" />
              </div>
              <div class="col-md-12">
                <label class="form-label">Observaciones</label>
                <textarea v-model="form.observations" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="save">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
