<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    show: Boolean,
    fuelOutflow: Object,
    machineries: Array,
    operators: Array,
    costCenters: Array,
    fuelProducts: Array,
    counters: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id: '',
    date: '',
    machinery_id: '',
    operator_id: '',
    cost_center_id: [],
    product_id: '',
    liters: '',
    counter_id: '',
    counter_value: '',
    observations: '',
});

const selectedMachinery = ref(null);

watch(() => form.machinery_id, (machineryId) => {
    if (machineryId) {
        const machinery = props.machineries.find(m => m.value === machineryId);
        selectedMachinery.value = machinery;
        if (machinery && machinery.counter_id) {
            form.counter_id = machinery.counter_id;
        }
    } else {
        selectedMachinery.value = null;
    }
});

watch(() => props.show, (val) => {
    if (val && props.fuelOutflow) {
        form.id = props.fuelOutflow.id;
        form.date = props.fuelOutflow.date;
        form.machinery_id = props.fuelOutflow.machinery_id;
        form.operator_id = props.fuelOutflow.operator_id;
        // Convertir los centros de costo a array de IDs
        form.cost_center_id = (props.fuelOutflow.costCenters || []).map(cc => cc.cost_center_id || cc.id);
        form.product_id = props.fuelOutflow.product_id;
        form.liters = props.fuelOutflow.liters;
        form.counter_id = props.fuelOutflow.counter_id || '';
        form.counter_value = props.fuelOutflow.counter_value || '';
        form.observations = props.fuelOutflow.observations || '';
        
        // Cargar machinery seleccionada
        const machinery = props.machineries.find(m => m.value === props.fuelOutflow.machinery_id);
        selectedMachinery.value = machinery;
    }
});

function closeModal() {
    emit('close');
}

function update() {
    form.put(route('fuel-outflows.update', form.id), {
        onSuccess: () => {
            Swal.fire({ 
                icon: 'success', 
                title: 'Actualizado', 
                text: 'Consumo actualizado correctamente', 
                timer: 1200, 
                showConfirmButton: false 
            });
            form.reset();
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: 'Revisa los campos e inténtalo de nuevo.' 
            });
        }
    });
}
</script>

<template>
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center">
            <span class="svg-icon svg-icon-2 svg-icon-primary me-2">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"/>
                <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"/>
              </svg>
            </span>
            Editar Consumo de Combustible
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="update">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Fecha</label>
                <input type="date" v-model="form.date" class="form-control" required />
              </div>
             
              <div class="col-md-4">
                <label class="form-label">Maquinaria</label>
                <Multiselect
                  placeholder="Seleccione maquinaria"
                  v-model="form.machinery_id"
                  :close-on-select="true"
                  :options="props.machineries"
                  :searchable="true"
                  class="multiselect-blue form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Operario</label>
                <select v-model="form.operator_id" class="form-select" required>
                  <option value="">Seleccione</option>
                  <option v-for="o in operators" :key="o.id" :value="o.id">{{ o.name }}</option>
                </select>
              </div>
              <div class="col-md-4">
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
                <label class="form-label">Combustible</label>
                <Multiselect
                  placeholder="Seleccione combustible"
                  v-model="form.product_id"
                  :close-on-select="true"
                  :options="props.fuelProducts"
                  :searchable="true"
                  class="multiselect-blue form-control-sm"
                  required
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Litros</label>
                <input type="number" v-model="form.liters" class="form-control" min="0.01" step="0.01" required />
              </div>
              <div class="col-md-4">
                <label class="form-label">Tipo Contador</label>
                <input 
                  type="text" 
                  :value="selectedMachinery?.counter_name || '-'" 
                  class="form-control" 
                  disabled 
                  readonly
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Valor Contador</label>
                <input type="number" v-model="form.counter_value" class="form-control" min="0" step="0.01" />
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
          <button type="button" class="btn btn-primary" @click="update" :disabled="form.processing">
            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
            Actualizar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}
</style>
