<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CardHeader from '@/Components/CardHeader.vue';




const props = defineProps({
  inventory: Array,
  kardex: Array
});

// Estado local independiente para cada tabla
const inventoryEdit = ref(props.inventory ? JSON.parse(JSON.stringify(props.inventory)) : []);
// El kardexView ahora será un diccionario: { [product_id]: movimientos[] }
const kardexView = ref({});

// Estado para expandir/cerrar detalles por producto
const expandedRows = ref([]); // array de product_id

async function toggleRow(productId) {
  const idx = expandedRows.value.indexOf(productId);
  if (idx === -1) {
    expandedRows.value.push(productId);
    // Si no está cargado el kardex de este producto, cargarlo
    if (!kardexView.value[productId]) {
      await loadKardex(productId);
    }
  } else {
    expandedRows.value.splice(idx, 1);
  }
}

async function loadKardex(productId) {
  try {
    const response = await fetch(route('kardex.show', { product: productId }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!response.ok) throw new Error('Error al cargar el kardex');
    const data = await response.json();
    kardexView.value[productId] = data.kardex || [];
  } catch (e) {
    kardexView.value[productId] = [];
  }
}


// --- FUNCIÓN DE IMPRESIÓN ---
function printKardex(productId) {
  const table = document.getElementById('kardex-table-' + productId);
  if (!table) return;
  const printContents = table.outerHTML;
  const originalContents = document.body.innerHTML;
  document.body.innerHTML = printContents;
  window.print();
  document.body.innerHTML = originalContents;
  location.reload(); // Para recargar el estado de la app después de imprimir
}

// --- ESTILOS DE IMPRESIÓN ---
</script>




<template>
     <Head :title="title" />
    <AppLayout>
    <!--begin::Breadcrumb-->
    <Breadcrumb :links="links" />
    <!--end::Breadcrumb-->

  <div class="card my-3 h-100" style="min-height:100vh;">
     <CardHeader title="Inventario" />

         <div class="card-body bg-body-tertiary">
            <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-kardex" data-bs-toggle="tab" href="#pill-tab-kardex" role="tab" aria-controls="pill-tab-kardex" aria-selected="false">Kardex</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">Gastos por Hectarea</a></li>
                 <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">Detalle de compra</a></li>
            </ul>
           <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
       
              <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="pill-edicion">
                <div class="table-responsive mb-4" style="max-height:340px;overflow-y:auto;">
                <table class="table table-bordered table-striped table-sm small">
                  <thead class="table-primary text-white">
                    <tr>
                      <th>Nivel 2</th>
                      <th>Nivel 3</th>
                      <th>Producto</th>
                      <th>Stock</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in inventoryEdit" :key="item.level2_name + '-' + item.level3_name + '-' + item.product_name">
                      <td>{{ item.level2_name }}</td>
                      <td>{{ item.level3_name }}</td>
                      <td>{{ item.product_name }}</td>
                      <td>{{ item.cantidad }}</td>
                    </tr>
                    <tr v-if="!inventoryEdit.length">
                      <td colspan="4" class="text-center text-muted">No hay datos de inventario.</td>
                    </tr>
                  </tbody>
                </table>
                </div>
              </div>



               <div class="tab-pane fade" id="pill-tab-kardex" role="tabpanel" aria-labelledby="pill-kardex">
                  <div class="table-responsive mb-4" style="max-height:340px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-sm small">
                      <thead class="table-primary text-white">
                        <tr>
                          <th>Nivel 2</th>
                          <th>Nivel 3</th>
                          <th>Producto</th>
                          <th>Stock</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template v-for="item in inventoryEdit" :key="'kardex-' + item.level2_name + '-' + item.level3_name + '-' + item.product_name">
                          <tr>
                            <td>{{ item.level2_name }}</td>
                            <td>{{ item.level3_name }}</td>
                            <td>{{ item.product_name }}</td>
                            <td>{{ item.cantidad }}</td>
                            <td>
                              <button
                                class="btn btn-sm border-0 bg-transparent p-0"
                                @click="toggleRow(item.product_id)"
                                style="display: flex; align-items: center; gap: 0.25rem; box-shadow: none;"
                                :aria-label="expandedRows.includes(item.product_id) ? 'Ocultar detalles' : 'Ver detalles'"
                              >
                                <i :class="expandedRows.includes(item.product_id) ? 'fa fa-eye-slash' : 'fa fa-eye'" style="font-size: 1.2em;"></i>
                              </button>
                            </td>
                          </tr>
                          <tr v-if="expandedRows.includes(item.product_id)">
                            <td colspan="5" class="p-0">
                              <div class="p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                  <strong>Kardex de {{ item.product_name }}</strong>
                                  <button class="btn btn-outline-secondary btn-sm" @click="printKardex(item.product_id)">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' viewBox='0 0 16 16'>
                                      <path d='M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2H2V2zm12 3v2a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2V5h12zm-2 7v2H4v-2h8z'/>
                                    </svg>
                                  </button>
                                </div>
                                <table class="table table-bordered table-sm mb-0 mt-2 kardex-print" :id="'kardex-table-' + item.product_id">




                                  <thead style="background: #DADAE6; color: #fff;">
                                    <tr>
                                      <th>Fecha</th>
                                      <th>Tipo</th>
                                      <th>Documento</th>
                                      <th>Entrada</th>
                                      <th>Salida</th>
                                      <th>Saldo</th>
                                      <th>Precio</th>
                                      <th>Observaciones</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(mov, idx) in kardexView[item.product_id] || []" :key="'mov-' + idx">
                                      <td>{{ mov.fecha }}</td>
                                      <td>{{ mov.tipo }}</td>
                                      <td>{{ mov.documento }}</td>
                                      <td>{{ mov.entrada || '' }}</td>
                                      <td>{{ mov.salida || '' }}</td>
                                      <td>{{ mov.saldo }}</td>
                                      <td>{{ mov.precio ?? '' }}</td>
                                      <td>{{ mov.observaciones || '' }}</td>
                                    </tr>
                                    <tr v-if="kardexView[item.product_id] && !kardexView[item.product_id].length">
                                      <td colspan="8" class="text-center text-muted">No hay movimientos de Kardex.</td>
                                    </tr>
                                    <tr v-if="!kardexView[item.product_id]">
                                      <td colspan="8" class="text-center text-muted">Cargando...</td>
                                    </tr>
                                    <tr v-if="kardexView[item.product_id] && kardexView[item.product_id].length">
                                      <td colspan="5" class="text-end fw-bold">Total stock actual:</td>
                                      <td class="fw-bold">{{ kardexView[item.product_id][kardexView[item.product_id].length-1].saldo }}</td>
                                      <td colspan="2"></td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>
                          </tr>
                        </template>
                        <tr v-if="!inventoryEdit.length">
                          <td colspan="5" class="text-center text-muted">No hay datos de inventario.</td>
                        </tr>
                      </tbody>
                    </table>
                 </div>
              </div>
  
          </div>
    </div>
  </div>

  </AppLayout>
</template>

<style>
@media print {
  body * {
    visibility: hidden !important;
  }
  .kardex-print, .kardex-print * {
    visibility: visible !important;
  }
  .kardex-print {
    position: absolute !important;
    left: 0; top: 0; width: 100vw;
    background: white;
    z-index: 9999;
    box-shadow: none !important;
  }
}
</style>