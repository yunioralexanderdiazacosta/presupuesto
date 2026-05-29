<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CardHeader from '@/Components/CardHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import EditProductModal from '@/Components/Products/EditProductModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';




const props = defineProps({
  inventory: Array,
  kardex: Array,
  branches: { type: Array, default: () => [] },
});

// Form para editar clasificación del producto
const productForm = useForm({
    id: '',
    name: '',
    unit_id: '',
    level1_id: '',
    level2_id: '',
    level3_id: '',
    level4_id: '',
    level2s: [],
    level3s: [],
    level4s: []
});

// Filtro local
const term = ref('');
const filterBranch = ref('');
const filteredInventory = computed(() => {
  if (!props.inventory || !props.inventory.length) return [];
  let data = props.inventory;
  if (term.value) {
    const search = term.value.toLowerCase();
    data = data.filter(item => {
      const level2 = item.level2_name ? item.level2_name.toLowerCase() : '';
      const level3 = item.level3_name ? item.level3_name.toLowerCase() : '';
      const product = item.product_name ? item.product_name.toLowerCase() : '';
      const branch = item.branch_name ? item.branch_name.toLowerCase() : '';
      return level2.includes(search) || level3.includes(search) || product.includes(search) || branch.includes(search);
    });
  }
  if (filterBranch.value) {
    data = data.filter(item => String(item.branch_id) === String(filterBranch.value));
  }
  return data;
});
// El kardexView ahora será un diccionario: { [product_id_branch_id]: movimientos[] }
const kardexView = ref({});
const kardexUnits = ref({});

// Estado para expandir/cerrar detalles por producto
const expandedRows = ref([]); // array de 'productId_branchId'

function kardexKey(productId, branchId) {
  return `${productId}_${branchId ?? 'null'}`;
}

async function toggleRow(productId, branchId) {
  const key = kardexKey(productId, branchId);
  const idx = expandedRows.value.indexOf(key);
  if (idx === -1) {
    expandedRows.value.push(key);
    // Si no está cargado el kardex de este producto+sucursal, cargarlo
    if (!kardexView.value[key]) {
      await loadKardex(productId, branchId);
    }
  } else {
    expandedRows.value.splice(idx, 1);
  }
}

async function loadKardex(productId, branchId) {
  const key = kardexKey(productId, branchId);
  try {
    const url = route('kardex.show', { product: productId }) + (branchId ? `?branch_id=${branchId}` : '');
    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!response.ok) throw new Error('Error al cargar el kardex');
    const data = await response.json();
    kardexView.value[key] = data.kardex || [];
    kardexUnits.value[key] = data.product.unit || '';
  } catch (e) {
    kardexView.value[key] = [];
    kardexUnits.value[key] = '';
  }
}


// --- FUNCIONES PARA EDITAR PRODUCTO ---
const getLevel2s = (level1Id) => {
    if (level1Id && level1Id != "") {
        axios.get(route('levels2.get', level1Id))
            .then(response => {
                productForm.level2s = response.data;
            }).catch(error => console.log(error));
    }
};

const getLevel3s = (level2Id) => {
    if (level2Id && level2Id != "") {
        axios.get(route('levels3.get', level2Id))
            .then(response => {
                productForm.level3s = response.data;
            }).catch(error => console.log(error));
    }
};

const getLevel4s = (level3Id) => {
    if (level3Id && level3Id != "") {
        axios.get(route('levels4.get', level3Id))
            .then(response => {
                productForm.level4s = response.data;
            }).catch(error => console.log(error));
    }
};

const openEditProduct = async (productId) => {
    try {
        const response = await axios.get(`/products/${productId}/show`);
        const product = response.data;
        
        if (product) {
            // Resetear formulario
            productForm.reset();
            
            // Inicializar arrays vacíos
            productForm.level2s = [];
            productForm.level3s = [];
            productForm.level4s = [];
            
            // Asignar valores del producto
            productForm.id = product.id;
            productForm.name = product.name;
            productForm.unit_id = product.unit_id;
            productForm.level1_id = product.level1_id;
            productForm.level2_id = product.level2_id;
            productForm.level3_id = product.level3_id;
            productForm.level4_id = product.level4_id;
            
            // Cargar niveles dependientes si existen
            if (product.level1_id) await getLevel2s(product.level1_id);
            if (product.level2_id) await getLevel3s(product.level2_id);
            if (product.level3_id) await getLevel4s(product.level3_id);
            
            $('#editProductModal').modal('show');
        }
    } catch (error) {
        console.error('Error al cargar producto:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el producto',
        });
    }
};

const updateProduct = () => {
    productForm.post(route('products.update', productForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            productForm.reset();
            $('#editProductModal').modal('hide');
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Clasificación actualizada correctamente',
                showConfirmButton: false,
                timer: 1500
            });
            // Recargar la página para actualizar el inventario
            router.reload();
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar la clasificación del producto',
            });
        }
    });
};


// --- HEADERS PARA EXPORTAR A EXCEL ---
const inventoryEdicionHeaders = [
  { label: 'Sucursal', key: 'branch_name' },
  { label: 'Nivel 2', key: 'level2_name' },
  { label: 'Nivel 3', key: 'level3_name' },
  { label: 'Producto', key: 'product_name' },
  { label: 'Stock', key: 'cantidad' },
  { label: 'Unidad', key: 'unit_name' },
];

const inventoryKardexHeaders = [
  { label: 'Sucursal', key: 'branch_name' },
  { label: 'Nivel 2', key: 'level2_name' },
  { label: 'Nivel 3', key: 'level3_name' },
  { label: 'Producto', key: 'product_name' },
  { label: 'Stock', key: 'cantidad' },
];

function exportKardexDetail(key, productName) {
  const movs = kardexView.value[key];
  if (!movs || !movs.length) return;
  const sorted = [...movs].sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
  const rows = sorted.map(mov => ({
    'Fecha': mov.fecha,
    'Tipo': mov.tipo,
    'Proveedor': mov.proveedor || '',
    'Documento': mov.documento,
    'Entrada': mov.entrada !== undefined && mov.entrada !== null ? Number(mov.entrada) : '',
    'Salida': mov.salida !== undefined && mov.salida !== null ? Number(mov.salida) : '',
    'Saldo': mov.saldo !== undefined && mov.saldo !== null ? Number(mov.saldo) : '',
    'Precio': mov.precio ?? '',
    'Observaciones': mov.observaciones || '',
    'Afecta inventario': (mov.affects_inventory === 0 || mov.affects_inventory === '0') ? 'No afecta' : 'Afecta',
  }));
  const ws = XLSX.utils.json_to_sheet(rows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Kardex');
  XLSX.writeFile(wb, `kardex_${productName.replace(/[^a-zA-Z0-9]/g, '_')}.xlsx`);
}

// --- FUNCIÓN DE IMPRESIÓN ---
function printKardex(key) {
  const table = document.getElementById('kardex-table-' + key);
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
                <!-- Search Input para tab Edición -->
                <div class="d-flex align-items-center gap-2 mb-3">
                  <div style="flex:1;">
                    <SearchInput
                      v-model="term"
                      placeholder="Buscar por producto, nivel 2, nivel 3 o sucursal..."
                    />
                  </div>
                  <div style="width:180px; flex-shrink:0;">
                    <select v-model="filterBranch" class="form-select form-select-sm">
                      <option value="">Todas las sucursales</option>
                      <option v-for="b in props.branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                  </div>
                  <ExportExcelButton
                    :data="filteredInventory"
                    :headers="inventoryEdicionHeaders"
                    filename="inventario_edicion.xlsx"
                    class="btn btn-falcon-default btn-sm flex-shrink-0"
                  />                  
                </div>
                <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped table-sm small">
                  <thead class="table-primary text-white">
                    <tr>
                      <th>Sucursal</th>
                      <th>Nivel 2</th>
                      <th>Nivel 3</th>
                      <th>Producto</th>
                      <th>Stock</th>
                      <th>Unidad</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in filteredInventory" :key="item.product_id + '-' + (item.branch_id ?? 'null')">
                      <td>
                        <span v-if="item.branch_name">{{ item.branch_name }}</span>
                        <span v-else class="text-muted">—</span>
                      </td>
                      <td>{{ item.level2_name || '--' }}</td>
                      <td>{{ item.level3_name || '--' }}</td>
                      <td>{{ item.product_name }}</td>
                      <td>{{ item.cantidad }}</td>
                      <td>{{ item.unit_name || '' }}</td>
                      <td class="text-center">
                        <button
                          @click="openEditProduct(item.product_id)"
                          class="btn btn-sm p-1"
                          :class="(!item.level2_name || !item.level3_name) ? 'btn-warning' : 'btn-primary'"
                          :title="(!item.level2_name || !item.level3_name) ? 'Faltan datos de clasificación' : 'Editar Clasificacion'"
                          style="display:flex; align-items:center; justify-content:center; padding:0.25rem; font-size:0.75rem; width:1.5rem; height:1.3rem;"
                        >
                          <i :class="(!item.level2_name || !item.level3_name) ? 'fa fa-exclamation-triangle' : 'fa fa-edit'"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="!filteredInventory.length">
                      <td colspan="7" class="text-center text-muted">No hay datos de inventario.</td>
                    </tr>
                  </tbody>
                </table>
                </div>
              </div>



               <div class="tab-pane fade" id="pill-tab-kardex" role="tabpanel" aria-labelledby="pill-kardex">
                  <!-- Search Input para Kardex -->
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="flex:1;">
                      <SearchInput
                        v-model="term"
                        placeholder="Buscar por producto, nivel 2, nivel 3 o sucursal..."
                      />
                    </div>
                    <div style="width:180px; flex-shrink:0;">
                      <select v-model="filterBranch" class="form-select form-select-sm">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in props.branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                      </select>
                    </div>
                    <ExportExcelButton
                      :data="filteredInventory"
                      :headers="inventoryKardexHeaders"
                      filename="inventario_kardex.xlsx"
                      class="btn btn-falcon-default btn-sm flex-shrink-0"
                    />
                  </div>
                  <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped table-sm small">
                      <thead class="table-primary text-white">
                        <tr>
                          <th>Sucursal</th>
                          <th>Nivel 2</th>
                          <th>Nivel 3</th>
                          <th>Producto</th>
                          <th>Stock</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template v-for="item in filteredInventory" :key="'kardex-' + item.product_id + '-' + (item.branch_id ?? 'null')">
                          <tr>
                            <td>
                              <span v-if="item.branch_name">{{ item.branch_name }}</span>
                              <span v-else class="text-muted">—</span>
                            </td>
                            <td>{{ item.level2_name || '--' }}</td>
                            <td>{{ item.level3_name || '--' }}</td>
                            <td>{{ item.product_name }}</td>
                            <td>{{ item.cantidad }}</td>
                            <td>
                              <button
                                class="btn btn-sm p-1"
                                :class="expandedRows.includes(kardexKey(item.product_id, item.branch_id)) ? 'btn-success' : 'btn-info'"
                                @click="toggleRow(item.product_id, item.branch_id)"
                                style="display:flex; align-items:center; justify-content:center; padding:0.25rem; font-size:0.75rem; width:1.5rem; height:1.3rem;"
                              >
                                <span>
                                  <i :class="expandedRows.includes(kardexKey(item.product_id, item.branch_id)) ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                </span>
                              </button>
                            </td>
                          </tr>
                          <tr v-if="expandedRows.includes(kardexKey(item.product_id, item.branch_id))">
                            <td colspan="6" class="p-0">
                              <div class="p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                  <strong>Kardex de {{ item.product_name }}<span v-if="item.branch_name" class="text-muted fw-normal"> — {{ item.branch_name }}</span></strong>
                                  <div class="d-flex gap-1">
                                    <button
                                      class="btn btn-falcon-default btn-sm"
                                      @click="exportKardexDetail(kardexKey(item.product_id, item.branch_id), item.product_name + (item.branch_name ? '_' + item.branch_name : ''))"
                                      title="Exportar a Excel"
                                    >
                                      <i class="fas fa-file-excel fa-xs me-1"></i>Excel
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="printKardex(kardexKey(item.product_id, item.branch_id))">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='currentColor' viewBox='0 0 16 16'>
                                      <path d='M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2H2V2zm12 3v2a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2V5h12zm-2 7v2H4v-2h8z'/>
                                    </svg>
                                  </button>
                                  </div><!-- /d-flex gap-1 -->
                                </div><!-- /d-flex justify-content-between -->
                                <table class="table table-bordered table-sm mb-0 mt-2 kardex-print" :id="'kardex-table-' + kardexKey(item.product_id, item.branch_id)">
                                  <thead style="background: #DADAE6; color: #fff;">
                                    <tr>
                                      <th>Fecha</th>
                                      <th>Tipo</th>
                                      <th>Proveedor</th>
                                      <th>Documento</th>
                                      <th>Entrada</th>
                                      <th>Salida</th>
                                      <th>Saldo</th>
                                      <th>Precio</th>
                                      <th>Observaciones</th>
                                      <th>Afecta inventario</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(mov, idx) in (kardexView[kardexKey(item.product_id, item.branch_id)] ? [...kardexView[kardexKey(item.product_id, item.branch_id)]].sort((a, b) => new Date(a.fecha) - new Date(b.fecha)) : [])" :key="'mov-' + idx">
                                      <td>{{ mov.fecha }}</td>
                                      <td>{{ mov.tipo }}</td>
                                      <td>{{ mov.proveedor || '' }}</td>
                                      <td>{{ mov.documento }}</td>
                                      <td>{{ mov.entrada !== undefined && mov.entrada !== null ? Number(mov.entrada).toFixed(2) : '' }}</td>
                                      <td class="text-danger">{{ mov.salida !== undefined && mov.salida !== null ? (-Number(mov.salida)).toFixed(2) : '' }}</td>
                                      <td>{{ mov.saldo !== undefined && mov.saldo !== null ? Number(mov.saldo).toFixed(2) : '' }}</td>
                                      <td>{{ mov.precio ?? '' }}</td>
                                      <td>{{ mov.observaciones || '' }}</td>
                                      <td>
                                        <span v-if="mov.affects_inventory === 0 || mov.affects_inventory === '0'" class="text-danger">No afecta</span>
                                        <span v-else class="text-success">Afecta</span>
                                      </td>
                                    </tr>
                                    <tr v-if="kardexView[kardexKey(item.product_id, item.branch_id)] && !kardexView[kardexKey(item.product_id, item.branch_id)].length">
                                      <td colspan="10" class="text-center text-muted">No hay movimientos de Kardex.</td>
                                    </tr>
                                    <tr v-if="!kardexView[kardexKey(item.product_id, item.branch_id)]">
                                      <td colspan="10" class="text-center text-muted">Cargando...</td>
                                    </tr>
                                    <tr v-if="kardexView[kardexKey(item.product_id, item.branch_id)] && kardexView[kardexKey(item.product_id, item.branch_id)].length">
                                      <td colspan="5" class="text-end fw-bold">Total stock actual:</td>
                                      <td class="fw-bold">
                                        {{ (() => { const k = kardexKey(item.product_id, item.branch_id); const arr = kardexView[k]; const v = Number(arr[arr.length-1].saldo); return Number(v.toFixed(2)); })() }}
                                        <span v-if="kardexUnits[kardexKey(item.product_id, item.branch_id)]">
                                          {{ kardexUnits[kardexKey(item.product_id, item.branch_id)] }}
                                        </span>
                                      </td>
                                      <td colspan="3"></td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>
                          </tr>
                        </template>
                        <tr v-if="!filteredInventory.length">
                          <td colspan="5" class="text-center text-muted">No hay datos de inventario.</td>
                        </tr>
                      </tbody>
                    </table>
                 </div>
              </div>
  
          </div>
    </div>
  </div>
  
  <!-- Modal para editar clasificación del producto -->
  <EditProductModal @update="updateProduct" :form="productForm" :readonlyBasicFields="true" />
  
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