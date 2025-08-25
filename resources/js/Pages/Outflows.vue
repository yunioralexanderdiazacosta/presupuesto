<script setup>
import { ref, watch, getCurrentInstance } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Multiselect from '@vueform/multiselect';

const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page || { props: {} };

const props = defineProps({
  outflows: Object,
  term: String,
  projects: Array,
  operations: Array,
  machineries: Array,
  cost_centers: Array,
  outflowDetails: { type: Array, default: () => [] }
});

const title = 'Salidas de productos';
const term  = ref(props.term);

const form = ref({ outflows: [] });
const showCards = ref([]);
const selectedOutflows = ref([]);

const onFilter = () => {
  router.get(route('outflows.index', {term: term.value}), { preserveState: true });  
}


function add() {
  form.value.outflows.push({
    project_id: '',
    operation_id: '',
    machinery_id: '',
    product_name: '',
    unit_name: '',
    quantity: '',
    cost_center_ids: [],
    observations: ''
  });
}

function onDeleted(index) {
  form.value.outflows.splice(index, 1);
}

function openCard(outflow) {
  // Evitar duplicados
  if (!showCards.value.includes(outflow.invoice_product_id)) {
    showCards.value.push(outflow.invoice_product_id);
    selectedOutflows.value.push({
      id: outflow.invoice_product_id,
      project_id: '',
      operation_id: '',
      machinery_id: '',
      product_name: outflow.product,
      unit_name: outflow.unit,
      quantity: '',
      cost_center_ids: [],
      observations: ''
    });
  }
}
function closeCard(id) {
  const idx = showCards.value.indexOf(id);
  if (idx !== -1) {
    showCards.value.splice(idx, 1);
    selectedOutflows.value.splice(idx, 1);
  }
}

function handleSave() {
  // Filtrar solo las cards con datos obligatorios (ejemplo: cantidad y al menos un centro de costo)
  const registros = selectedOutflows.value.filter(sel => {
    return sel.quantity && sel.cost_center_ids && sel.cost_center_ids.length > 0;
  });
  if (registros.length === 0) {
    Swal.fire({ icon: 'warning', title: 'Atención', text: 'No hay registros completos para guardar.' });
    return;
  }
  router.post(route('outflows.store'), { outflows: registros }, {
    onSuccess: () => {
      selectedOutflows.value = [];
      showCards.value = [];
      Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Las salidas fueron registradas correctamente.' });
    },
    onError: () => {
      Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al guardar las salidas.' });
    }
  });
}

// Función para mostrar detalles de centros de costo adicionales
const showMoreCenters = (centers) => {
  const items = centers.slice(2).map(cc => {
    return `<li><strong>${cc.name}</strong>${cc.observations ? ' - ' + cc.observations : ''}</li>`;
  }).join('');
  Swal.fire({
    title: 'Centros de Costo adicionales',
    html: `<ul style="text-align:left;margin:0;padding:0 1rem;list-style:none;">${items}</ul>`,
    width: 400,
    confirmButtonText: 'Cerrar'
  });
};

// Estado para agrupación seleccionada por cada card
const selectedGroupings = ref({});

watch(selectedGroupings, (newVals) => {
  Object.entries(newVals).forEach(([cardId, groupingId]) => {
    if (!groupingId) return;
    const grouping = page.props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
      const groupCCs = grouping.cost_centers.map(cc => cc.id);
      const card = selectedOutflows.value.find(sel => sel.id == cardId);
      if (card) card.cost_center_ids = groupCCs;
    }
  });
}, { deep: true });
</script>



<template>
  
   
    <Head :title="title" />
    <AppLayout>
         <Breadcrumb :links="links" />
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0">Salidas de productos</h5>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                  <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-salidas" data-bs-toggle="tab" href="#pill-tab-salidas" role="tab" aria-controls="pill-tab-salidas" aria-selected="false">Salidas</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">kjhyuass</a></li>
                 <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">kjuh</a></li>
            </ul>
                <div class="row justify-content-end g-0">
                    <div class="col-auto col-sm-5 mb-3">
                        <div class="input-group">
                            <input class="form-control form-control-sm shadow-none search" type="text" placeholder="Buscar..." @keyup.enter="onFilter()" v-model="term" />
                            <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                        </div>
                    </div>
                </div>


                <div class="tab-content">
                  <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="pill-edicion">
                    <div class="table-responsive mb-4" style="max-height:340px;overflow-y:auto;">
                      <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0">
                        <thead class="table-light">
                          <tr>
                            <th>Fecha</th>
                            <th>Proyecto</th>
                            <th>Operación</th>
                            <th>Maquinaria</th>
                            <th>Cantidad</th>
                            <th>Notas</th>
                            <th>Centros de Costo</th>
                            <th>Usuario</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="outflow in props.outflowDetails" :key="outflow.id">
                            <td>{{ outflow.date }}</td>
                            <td>{{ outflow.project }}</td>
                            <td>{{ outflow.operation }}</td>
                            <td>{{ outflow.machinery }}</td>
                            <td>{{ outflow.quantity }}</td>
                            <td>{{ outflow.notes }}</td>
                            <td>
                              <ul class="mb-0 ps-3">
                                <li v-for="cc in outflow.cost_centers.slice(0, 2)" :key="cc.name">
                                  <span class="fw-bold">{{ cc.name }}</span>
                                 
                                </li>
                                <li v-if="outflow.cost_centers.length > 2">
                                  <a
                                    href="#"
                                    class="text-primary small text-decoration-underline"
                                    @click.prevent="showMoreCenters(outflow.cost_centers)">
                                    +{{ outflow.cost_centers.length - 2 }} más
                                  </a>
                                </li>
                              </ul>
                            </td>
                            <td>{{ outflow.user }}</td>
                          </tr>
                          <tr v-if="!props.outflowDetails || !props.outflowDetails.length">
                            <td colspan="8" class="text-center text-muted">No hay salidas registradas.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="pill-tab-salidas" role="tabpanel" aria-labelledby="salidas-tab">
                    <div style="max-height: 340px; overflow-y: auto;">
                      <Table :id="'outflows'" :total="outflows.data.length" :links="outflows.links">
                          <template #header>
                              <th>Factura</th>
                              <th>Proveedor</th>
                              <th>Producto</th>
                              <th>Cantidad</th>
                               <th>Unidad</th>
                              <th class="text-center">Acciones</th>
                          </template>
                          <template #body>
                              <tr v-for="outflow in outflows.data" :key="outflow.invoice_product_id">
                                  <td>{{ outflow.number_document }}</td>
                                  <td>{{ outflow.supplier }}</td>
                                  <td>{{ outflow.product }}</td>
                                  <td>{{ outflow.quantity }}</td>
                                  <td>{{ outflow.unit }}</td>
                                  <td class="text-center">
                                    <button @click="openCard(outflow)"
                                      class="btn btn-sm me-1"
                                      :class="showCards.map(String).includes(String(outflow.invoice_product_id)) ? 'btn-primary btn-active' : 'btn-white'">
                                      <span 
                                        class="fas fa-paper-plane"
                                        :class="showCards.map(String).includes(String(outflow.invoice_product_id)) ? 'text-white' : 'text-secondary'"
                                      ></span>
                                    </button>
                                  </td>
                              </tr>
                              <tr v-if="outflows.data.length === 0">
                                  <td colspan="5"><Empty /></td>
                              </tr>
                          </template>
                      </Table>
                    </div>
                    <!-- Cards para agregar nuevas salidas SOLO visibles en la pestaña Salidas -->
                    <div v-for="(selected, idx) in selectedOutflows" :key="selected.id" class="outflow-cards mt-4">
                      <h6>Registrar salida</h6>
                      <div class="card mb-2 p-3 shadow-sm">
                        <div class="row g-2 align-items-center">
                          <div class="col-12 col-md-3">
                            <label class="form-label">Producto</label>
                            <input v-model="selected.product_name" class="form-control form-control-sm w-100" disabled />
                          </div>
                          <div class="col-6 col-md-1">
                            <label class="form-label">Unidad</label>
                            <input v-model="selected.unit_name" class="form-control form-control-sm w-100" disabled />
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label">Cantidad</label>
                            <input v-model="selected.quantity" class="form-control form-control-sm w-100" type="number" min="1" />
                          </div>
                          <div class="col-12 col-md-2">
                            <label class="form-label">Proyecto</label>
                            <Multiselect
                              placeholder="Proyecto"
                              v-model="selected.project_id"
                              :options="props.projects"
                              option-label="label"
                              option-value="value"
                              :searchable="true"
                              class="multiselect-blue form-control"
                            />
                          </div>
                          <div class="col-12 col-md-2">
                            <label class="form-label">Operación</label>
                            <Multiselect
                              placeholder="Operación"
                              v-model="selected.operation_id"
                              :options="props.operations"
                              option-label="label"
                              option-value="value"
                              :searchable="true"
                              class="multiselect-blue form-control"
                            />
                          </div>
                          <div class="col-12 col-md-2">
                            <label class="form-label">Maquinaria</label>
                            <Multiselect
                              placeholder="Maquinaria"
                              v-model="selected.machinery_id"
                              :options="props.machineries"
                              option-label="label"
                              option-value="value"
                              :searchable="true"
                              class="multiselect-blue form-control"
                            />
                          </div>
                          <!-- Selector de agrupación con Multiselect -->
        <div class="col-sm-4">
            <label for="grouping" class="col-form-label mb-0">Agrupación</label>
           
                <Multiselect
                    id="grouping"
                    v-model="selectedGroupings[selected.id]"
                    :options="(page.props.groupings || []).map(g => ({ value: g.id, label: g.name }))"
                    :placeholder="'Seleccione agrupación'"
                    :searchable="true"
                    :close-on-select="true"
                    :hide-selected="false"
                    class="multiselect-blue form-control-sm"
                />
          
        </div>




                          <div class="col-12 col-md-5">
                            <label class="form-label">Centro de Costo</label>
                            <Multiselect
                              mode="tags"
                              placeholder="Centro de Costo"
                              v-model="selected.cost_center_ids"
                              :close-on-select="false"
                              :options="props.cost_centers"
                              option-label="label"
                              option-value="value"
                              :searchable="true"
                              :hide-selected="false"
                              class="multiselect-blue form-control-sm"
                            />
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Observaciones</label>
                            <input v-model="selected.observations" class="form-control form-control-sm w-100" />
                          </div>
                          <div class="col-12 col-md-2 mt-4">
                            <button type="button" @click="closeCard(selected.id)" class="btn btn-sm btn-secondary">Cerrar</button>
                          </div>
                        </div>
                      </div>
                    </div>


                      <!-- Botón Guardar global -->
                <div class="row mt-4" v-if="selectedOutflows.length">
                  <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary btn-sm px-3 py-1" @click="handleSave">
                      <i class="fas fa-save me-2"></i> Guardar todas las salidas
                    </button>
                  </div>
                </div>
                    <!-- Fin cards -->
                  </div>
              
              
            </div>
        </div>
      </div>
    </AppLayout>
  

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

/* Fuente más pequeña para la tabla de edición */
#pill-tab-edicion .table {
  font-size: 0.72rem !important;
}
</style>