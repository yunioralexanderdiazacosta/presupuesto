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
    projects: Array,
    operations: Array,
    fuelTanks: { type: Array, default: () => [] },
    groupings: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id: '',
    date: '',
    machinery_id: '',
    operator_id: '',
    cost_center_id: [],
    product_id: '',
    invoice_product_id: null,
    credit_debit_note_item_id: null,
    project_id: null,
    operation_id: null,
    liters: '',
    counter_id: '',
    counter_value: '',
    observations: '',
    tank_id: null,
});

const selectedMachinery = ref(null);
const selectedGrouping = ref(null);
const expandedCC = ref(false);

// Agrupación → preselección rápida de centros de costo
watch(selectedGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id === groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        form.cost_center_id = grouping.cost_centers.map(cc => cc.id);
    }
});

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
        form.invoice_product_id = props.fuelOutflow.invoice_product_id || null;
        form.credit_debit_note_item_id = props.fuelOutflow.credit_debit_note_item_id || null;
        form.project_id = props.fuelOutflow.outflow?.project_id || null;
        form.operation_id = props.fuelOutflow.outflow?.operation_id || null;
        form.liters = props.fuelOutflow.liters;
        form.counter_id = props.fuelOutflow.counter_id || '';
        form.counter_value = props.fuelOutflow.counter_value || '';
        form.observations = props.fuelOutflow.observations || '';
        form.tank_id = props.fuelOutflow.tank_id || null;
        
        // Cargar machinery seleccionada
        const machinery = props.machineries.find(m => m.value === props.fuelOutflow.machinery_id);
        selectedMachinery.value = machinery;
        selectedGrouping.value = null;
        expandedCC.value = false;
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
      <div class="modal-content" style="background-color: #f8f9fa;">
        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title d-flex align-items-center">
            <i class="fas fa-gas-pump text-primary me-2 fs-8"></i>
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
                <label class="form-label">Estanque</label>
                <select v-model="form.tank_id" class="form-select">
                  <option :value="null">Sin estanque</option>
                  <option v-for="t in props.fuelTanks" :key="t.value" :value="t.value">
                    {{ t.label }}<template v-if="t.branch_name"> ({{ t.branch_name }})</template>
                  </option>
                </select>
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
              <!-- Agrupación (preselección rápida de CC) -->
              <div v-if="props.groupings && props.groupings.length > 0" class="col-md-4">
                <label class="form-label">
                  <i class="fas fa-layer-group me-1"></i>Agrupación
                </label>
                <select v-model="selectedGrouping" class="form-select">
                  <option :value="null" disabled selected>Seleccione agrupación...</option>
                  <option v-for="g in props.groupings" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>
                <small class="text-muted d-block mt-1">
                  <i class="fas fa-info-circle me-1"></i>Preselección rápida
                </small>
              </div>

              <!-- Centro de Costo -->
              <div :class="props.groupings && props.groupings.length > 0 ? 'col-md-8' : 'col-md-4'">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label mb-0">
                    Centro de Costo
                    <span v-if="form.cost_center_id && form.cost_center_id.length > 0" class="badge bg-primary ms-1" style="font-size:0.65rem;">
                      {{ form.cost_center_id.length }}
                    </span>
                  </label>
                  <button
                    v-if="form.cost_center_id && form.cost_center_id.length > 5"
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
                  placeholder="Centro de Costo"
                  v-model="form.cost_center_id"
                  :close-on-select="false"
                  :options="props.costCenters.map(c => ({ value: c.id, label: c.name }))"
                  :searchable="true"
                  :hide-selected="false"
                  :class="['multiselect-blue form-control-sm multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCC }]"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Proyecto</label>
                <Multiselect
                  placeholder="Seleccione proyecto"
                  v-model="form.project_id"
                  :close-on-select="true"
                  :options="props.projects"
                  :searchable="true"
                  class="multiselect-blue form-control-sm"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label">Operación</label>
                <Multiselect
                  placeholder="Seleccione operación"
                  v-model="form.operation_id"
                  :close-on-select="true"
                  :options="props.operations"
                  :searchable="true"
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
                <label class="form-label">{{ selectedMachinery?.counter_name ? 'Valor ' + selectedMachinery.counter_name : 'Valor Contador' }}</label>
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

<style scoped>
.multiselect-tags-limited :deep(.multiselect-tags) {
    max-height: 32px !important;
    overflow: hidden !important;
    flex-wrap: wrap;
    transition: max-height 0.3s ease;
}
.multiselect-tags-expanded :deep(.multiselect-tags) {
    max-height: 200px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar { width: 4px; }
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2); border-radius: 4px;
}
.multiselect-tags-limited {
    height: auto !important;
    max-height: 38px !important;
    min-height: 26px !important;
    transition: max-height 0.3s ease;
}
.multiselect-tags-expanded { max-height: 210px !important; }
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
