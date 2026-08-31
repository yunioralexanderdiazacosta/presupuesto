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
    branches: { type: Array, default: () => [] },
    groupings: { type: Array, default: () => [] },
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
    tank_meter: '',
    observations: '',
});

const selectedMachinery = ref(null);
const selectedStockLine = ref(null);
const selectedStockKey = ref(null); // key única: 'ip-{id}' o 'nd-{id}'
const filterBranch = ref(''); // filtro de sucursal para el select de origen
const maxLiters = ref(null);
const selectedGrouping = ref(null); // preselección rápida de CC (no se guarda)
const expandedCC = ref(false); // toggle para expandir tags del multiselect de CC

const selectedCcSurface = computed(() => {
    const selected = (form.cost_center_id || []).map(String);
    return (props.costCenters || [])
        .filter(cc => selected.includes(String(cc.id)))
        .reduce((sum, cc) => sum + (Number(cc.surface) || 0), 0);
});

const selectedProductName = computed(() => {
    if (selectedStockLine.value) {
        return selectedStockLine.value.product_name + ' (' + selectedStockLine.value.unit + ')';
    }
    return '-';
});

// Facturas filtradas por sucursal seleccionada
const filteredFuelStocks = computed(() => {
    if (!props.availableFuelStocks) return [];
    if (!filterBranch.value) return props.availableFuelStocks;
    return props.availableFuelStocks.filter(s => String(s.branch_id) === String(filterBranch.value));
});

// Generar key única por stock
function stockKey(stock) {
    return stock.invoice_product_id ? 'ip-' + stock.invoice_product_id : 'nd-' + stock.credit_debit_note_item_id;
}

// Manejar selección de línea de stock
function onStockLineSelected() {
    if (selectedStockKey.value) {
        const found = props.availableFuelStocks.find(s => stockKey(s) === selectedStockKey.value);
        if (found) {
            selectedStockLine.value = found;
            form.product_id = found.product_id;
            form.invoice_product_id = found.invoice_product_id;
            form.credit_debit_note_item_id = found.credit_debit_note_item_id;
            form.tank_id = found.tank_id ?? null;
            maxLiters.value = found.stock_disponible;
            form.liters = found.stock_disponible;
            return;
        }
    }
    selectedStockLine.value = null;
    form.product_id = '';
    form.invoice_product_id = null;
    form.credit_debit_note_item_id = null;
    form.tank_id = null;
    maxLiters.value = null;
    form.liters = '';
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
        selectedStockKey.value = null;
        filterBranch.value = '';
        maxLiters.value = null;
        selectedGrouping.value = null;
        expandedCC.value = false;
    }
});

// Agrupación → preselección rápida de centros de costo
watch(selectedGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id === groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        form.cost_center_id = grouping.cost_centers.map(cc => cc.id);
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
  <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);" v-if="show">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-white border-bottom">
          <h5 class="modal-title d-flex align-items-center fw-bold">
            <i class="fas fa-gas-pump text-primary me-2 fs-8"></i>
            Nuevo Consumo de Combustible
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body bg-body-tertiary">
          <form @submit.prevent="save">

            <!-- Sección: Origen del Combustible -->
            <div class="mb-4">
              <h6 class="fuel-section-title">
                <i class="fas fa-file-invoice"></i>Origen del Combustible
              </h6>
              <div class="row g-3">
                <div v-if="props.branches.length > 0" class="col-md-4">
                  <label class="form-label fw-bold">
                    <i class="fas fa-building me-1"></i>Sucursal
                  </label>
                  <select v-model="filterBranch" class="form-select form-select-sm" @change="selectedStockKey = null; onStockLineSelected()">
                    <option value="">Todas las sucursales</option>
                    <option v-for="b in props.branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                  </select>
                </div>

                <div :class="props.branches.length > 0 ? 'col-md-8' : 'col-md-12'">
                  <label class="form-label fw-bold text-primary">
                    <i class="fas fa-file-invoice me-1"></i>
                    Factura / Nota de origen
                    <span class="badge bg-info ms-2" style="font-size: 0.7rem;">Stock Disponible</span>
                  </label>
                  <select
                    v-model="selectedStockKey"
                    class="form-select form-select-sm"
                    required
                    @change="onStockLineSelected"
                  >
                    <option :value="null">Seleccione factura/nota con combustible disponible</option>
                    <option
                      v-for="stock in filteredFuelStocks"
                      :key="stockKey(stock)"
                      :value="stockKey(stock)"
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
                  <label class="form-label">
                    Estanque
                    <span v-if="selectedStockLine?.tank_id" class="badge bg-info ms-1" style="font-size:0.65rem;">Auto</span>
                  </label>
                  <!-- Auto-asignado desde la factura -->
                  <div v-if="selectedStockLine?.tank_id" class="input-group">
                    <span class="form-control form-control-sm bg-light text-muted" style="font-size:0.85rem;">
                      <i class="fas fa-drum me-1 text-warning"></i>
                      {{ props.fuelTanks.find(t => String(t.value) === String(selectedStockLine.tank_id))?.label ?? 'Estanque #' + selectedStockLine.tank_id }}
                      <span class="text-muted small ms-1">
                        ({{ props.fuelTanks.find(t => String(t.value) === String(selectedStockLine.tank_id))?.branch_name ?? '' }})
                      </span>
                    </span>
                  </div>
                  <!-- Sin tank en la factura: selección manual -->
                  <select v-else v-model="form.tank_id" class="form-select form-select-sm">
                    <option :value="null">Sin estanque</option>
                    <option v-for="t in props.fuelTanks" :key="t.value" :value="t.value">
                      {{ t.label }}<span v-if="t.branch_name"> ({{ t.branch_name }})</span>
                    </option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" v-model="form.date" class="form-control form-control-sm" required />
                </div>

                <div class="col-md-4">
                  <label class="form-label">Combustible</label>
                  <input
                    type="text"
                    :value="selectedProductName"
                    class="form-control form-control-sm"
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
                    class="form-control form-control-sm"
                    :max="maxLiters"
                    min="0.01"
                    step="0.01"
                    required
                  />
                </div>
              </div>
            </div>

            <hr class="fuel-section-divider">

            <!-- Sección: Maquinaria y Operario -->
            <div class="mb-4">
              <h6 class="fuel-section-title">
                <i class="fas fa-tractor"></i>Maquinaria y Operario
              </h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Maquinaria</label>
                  <select v-model="form.machinery_id" class="form-select form-select-sm" required>
                    <option value="" disabled selected hidden>Seleccione maquinaria</option>
                    <option v-for="m in props.machineries" :key="m.value" :value="m.value">{{ m.label }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Operario</label>
                  <select v-model="form.operator_id" class="form-select form-select-sm" required>
                    <option value="">Seleccione</option>
                    <option v-for="o in operators" :key="o.id" :value="o.id">{{ o.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tipo Contador</label>
                  <input
                    type="text"
                    :value="selectedMachinery?.counter_name || '-'"
                    class="form-control form-control-sm"
                    disabled
                    readonly
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">{{ selectedMachinery?.counter_name ? 'Valor ' + selectedMachinery.counter_name : 'Valor Contador' }}</label>
                  <input type="number" v-model="form.counter_value" class="form-control form-control-sm" min="0" step="0.01" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Totalizador Estanque</label>
                  <input type="number" v-model="form.tank_meter" class="form-control form-control-sm" min="0" step="0.01" placeholder="Lectura del totalizador" />
                </div>
              </div>
            </div>

            <hr class="fuel-section-divider">

            <!-- Sección: Centro de Costo y Clasificación -->
            <div class="mb-4">
              <h6 class="fuel-section-title">
                <i class="fas fa-layer-group"></i>Centro de Costo y Clasificación
              </h6>
              <div class="row g-3">
                <!-- Agrupación (preselección rápida de CC) -->
                <div v-if="props.groupings && props.groupings.length > 0" class="col-md-4">
                  <label class="form-label">
                    <i class="fas fa-layer-group me-1"></i>Agrupación
                  </label>
                  <select v-model="selectedGrouping" class="form-select form-select-sm">
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
                      <span v-if="form.cost_center_id && form.cost_center_id.length > 0" class="badge bg-info ms-1" style="font-size:0.65rem;">
                        <i class="fas fa-ruler-combined"></i>
                        {{ selectedCcSurface.toLocaleString('es-CL', { maximumFractionDigits: 2 }) }} ha
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
                  <select v-model="form.project_id" class="form-select form-select-sm">
                    <option :value="null">Seleccione proyecto</option>
                    <option v-for="p in props.projects" :key="p.value" :value="p.value">{{ p.label }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Operación</label>
                  <select v-model="form.operation_id" class="form-select form-select-sm">
                    <option :value="null">Seleccione operación</option>
                    <option v-for="o in props.operations" :key="o.value" :value="o.value">{{ o.label }}</option>
                  </select>
                </div>
              </div>
            </div>

            <hr class="fuel-section-divider">

            <!-- Observaciones -->
            <div>
              <label class="form-label">Observaciones</label>
              <textarea v-model="form.observations" class="form-control form-control-sm" rows="2"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer bg-white border-top">
          <button type="button" class="btn btn-light" @click="closeModal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="save">
            <i class="fas fa-save me-1"></i>Guardar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fuel-section-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--kt-text-muted, #6c757d);
    margin-bottom: 0.9rem;
}
.fuel-section-title i {
    color: #2c7be5;
}
.fuel-section-divider {
    border: none;
    height: 1px;
    background: linear-gradient(90deg, rgba(44,123,229,0.15) 0%, rgba(44,123,229,0.35) 50%, rgba(44,123,229,0.15) 100%);
    margin: 1.25rem 0;
}
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
</style>
