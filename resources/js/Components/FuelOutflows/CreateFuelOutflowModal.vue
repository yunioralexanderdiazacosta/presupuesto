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
    fuelProducts: Array,
    counters: Array,
    availableFuelStocks: Array,
    projects: Array,
    operations: Array,
    fuelTanks: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    date: '',
    machinery_id: '',
    operator_id: '',
    cost_center_id: [],
    invoice_product_id: null,
    credit_debit_note_item_id: null,
    product_id: '',
    tank_id: null,
    project_id: null,
    operation_id: null,
    liters: '',
    counter_id: '',
    counter_value: '',
    observations: '',
});

const selectedMachinery = ref(null);
const selectedStockLine = ref(null);
const selectedStockLineIndex = ref(null);
const maxLiters = ref(null);

const selectedProductName = computed(() => {
    if (selectedStockLine.value) {
        return selectedStockLine.value.product_name + ' (' + selectedStockLine.value.unit + ')';
    }
    return '-';
});

// Manejar selección de línea de stock
function onStockLineSelected() {
    if (selectedStockLineIndex.value !== null && props.availableFuelStocks[selectedStockLineIndex.value]) {
        selectedStockLine.value = props.availableFuelStocks[selectedStockLineIndex.value];
        form.product_id = selectedStockLine.value.product_id;
        form.invoice_product_id = selectedStockLine.value.invoice_product_id;
        form.credit_debit_note_item_id = selectedStockLine.value.credit_debit_note_item_id;
        form.tank_id = selectedStockLine.value.tank_id ?? null;
        maxLiters.value = selectedStockLine.value.stock_disponible;
        form.liters = selectedStockLine.value.stock_disponible;
    } else {
        selectedStockLine.value = null;
        form.product_id = '';
        form.invoice_product_id = null;
        form.credit_debit_note_item_id = null;
        form.tank_id = null;
        maxLiters.value = null;
        form.liters = '';
    }
}

watch(() => form.machinery_id, (machineryId) => {
    if (machineryId) {
        const machinery = props.machineries.find(m => m.value === machineryId);
        selectedMachinery.value = machinery;
        
        if (machinery && machinery.counter_id) {
            form.counter_id = machinery.counter_id;
        } else {
            form.counter_id = null;
        }
    } else {
        selectedMachinery.value = null;
        form.counter_id = null;
    }
});

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        selectedMachinery.value = null;
        selectedStockLine.value = null;
        selectedStockLineIndex.value = null;
        maxLiters.value = null;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    // Validar que no exceda el stock
    if (maxLiters.value && form.liters > maxLiters.value) {
        Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            text: `No puede consumir más de ${maxLiters.value} litros (stock disponible).` 
        });
        return;
    }
    
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

// ...existing code...
</script>
<template>
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
    <div class="modal-dialog modal-xl">
      <div class="modal-content" style="background-color: #f8f9fa;">
        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title d-flex align-items-center">
            <i class="fas fa-gas-pump text-primary me-2 fs-8"></i>
            Nuevo Consumo de Combustible
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="save">
            <div class="row g-2">
              
              <!-- 🔥 NUEVO: Select de línea de factura/nota -->
              <div class="col-md-12">
                <label class="form-label fw-bold text-primary">
                  <i class="fas fa-file-invoice me-1"></i>
                  Origen del Combustible
                  <span class="badge bg-info ms-2" style="font-size: 0.7rem;">Stock Disponible</span>
                </label>
                <select 
                  v-model="selectedStockLineIndex" 
                  class="form-select" 
                  required
                  @change="onStockLineSelected"
                >
                  <option :value="null">Seleccione factura/nota con combustible disponible</option>
                  <option 
                    v-for="(stock, index) in (props.availableFuelStocks || [])" 
                    :key="stock.invoice_product_id || stock.credit_debit_note_item_id" 
                    :value="index"
                  >
                    {{ stock.origen === 'nota_debito' ? '📋' : '📄' }} 
                    {{ stock.number_document }} - 
                    {{ stock.supplier }} - 
                    {{ stock.product_name }} 
                    (Disponible: {{ stock.stock_disponible }} {{ stock.unit }})
                  </option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Estanque</label>
                <select v-model="form.tank_id" class="form-select">
                  <option :value="null">Sin estanque</option>
                  <option v-for="t in props.fuelTanks" :key="t.value" :value="t.value">
                    {{ t.label }}<span v-if="t.branch_name"> ({{ t.branch_name }})</span>
                  </option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Fecha</label>
                <input type="date" v-model="form.date" class="form-control" required />
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Combustible</label>
                <input 
                  type="text" 
                  :value="selectedProductName" 
                  class="form-control" 
                  disabled 
                  readonly
                />
              </div>
              
              <div class="col-md-4">
                <label class="form-label">
                  Litros 
                  <span v-if="maxLiters" class="text-muted small">
                    (Máx: {{ maxLiters }})
                  </span>
                </label>
                <input 
                  type="number" 
                  v-model="form.liters" 
                  class="form-control" 
                  :max="maxLiters"
                  min="0.01" 
                  step="0.01" 
                  required 
                />
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
          <button type="button" class="btn btn-primary" @click="save">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</template>
