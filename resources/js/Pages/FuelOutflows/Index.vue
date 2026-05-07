<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateFuelOutflowModal from '@/Components/FuelOutflows/CreateFuelOutflowModal.vue';
import EditFuelOutflowModal from '@/Components/FuelOutflows/EditFuelOutflowModal.vue';
import AnalyticsModal from '@/Components/FuelOutflows/AnalyticsModal.vue';

// Función para mostrar detalles de centros de costo adicionales
function showMoreCenters(centers) {
    const items = centers.slice(2).map(cc => {
        return `<li><strong>${cc.name}</strong>${cc.observations ? ' - ' + cc.observations : ''}</li>`;
    }).join('');
    Swal.fire({
        title: 'Centros de Costo adicionales',
        html: `<ul style=\"text-align:left;margin:0;padding:0 1rem;list-style:none;\">${items}</ul>`,
        width: 400,
        confirmButtonText: 'Cerrar'
    });
}

const props = defineProps({
    fuelOutflows: Array,
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

const title = 'Consumos de Combustible';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const filteredRows = computed(() => {
    if (!props.fuelOutflows || !Array.isArray(props.fuelOutflows)) return [];
    if (!term.value) return props.fuelOutflows;
    const search = term.value.toLowerCase();
    return props.fuelOutflows.filter(item => {
        const machinery = item.machinery?.cod_machinery?.toLowerCase() || '';
        const operator = item.operator?.name?.toLowerCase() || '';
        const costCenter = item.cost_center?.name?.toLowerCase() || '';
        const product = item.product?.name?.toLowerCase() || '';
        const counter = item.counter?.name?.toLowerCase() || '';
        return (
            machinery.includes(search) ||
            operator.includes(search) ||
            costCenter.includes(search) ||
            product.includes(search) ||
            counter.includes(search)
        );
    });
});

// 🔥 Agrupar stock de combustible por product_id
const fuelStockByProduct = computed(() => {
    if (!props.availableFuelStocks || !Array.isArray(props.availableFuelStocks)) return [];
    
    const grouped = {};
    props.availableFuelStocks.forEach(item => {
        if (!grouped[item.product_id]) {
            grouped[item.product_id] = {
                product_id: item.product_id,
                product_name: item.product_name,
                unit: item.unit,
                stock_disponible: 0
            };
        }
        grouped[item.product_id].stock_disponible += item.stock_disponible;
    });
    
    return Object.values(grouped);
});

const totalStockCombustible = computed(() => {
    return fuelStockByProduct.value.reduce((sum, item) => sum + item.stock_disponible, 0);
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showAnalyticsModal = ref(false);
const editingFuelOutflow = ref(null);

function openCreateModal() {
    showCreateModal.value = true;
}
function closeCreateModal() {
    showCreateModal.value = false;
}
function openAnalyticsModal() {
    showAnalyticsModal.value = true;
}
function closeAnalyticsModal() {
    showAnalyticsModal.value = false;
}
function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    router.reload({ preserveScroll: true });
}

function openEditModal(fuelOutflow) {
    editingFuelOutflow.value = fuelOutflow;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingFuelOutflow.value = null;
}

function deleteFuelOutflow(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('fuel-outflows.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ preserveScroll: true });
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Registro eliminado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errors.error || 'No se pudo eliminar el registro'
                    });
                }
            });
        }
    });
}

</script>
<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-gas-pump text-primary me-2"></i>
                    {{ title }}
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-info btn-sm" @click="openAnalyticsModal">
                        <i class="fas fa-chart-line"></i> Análisis
                    </button>
                    <button class="btn btn-primary btn-sm" @click="openCreateModal">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Card Pills de Stock -->
                <div class="row mb-3">
                    <div class="col-md-4 col-12 mb-2">
                        <div class="card bg-light-info border-info h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-9">Stock Total Combustible</h6>
                                        <h4 class="mb-0 fw-bold text-info">{{ totalStockCombustible.toFixed(2) }} L</h4>
                                    </div>
                                    <i class="fas fa-gas-pump fa-2x text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 col-12">
                        <div class="card bg-light-secondary border-secondary h-100">
                            <div class="card-body py-2 px-3">
                                <h6 class="mb-1 text-muted fs-9">Detalle por Producto</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span 
                                        v-for="fuel in fuelStockByProduct" 
                                        :key="fuel.product_id"
                                        class="badge bg-secondary fs-10 px-2 py-1"
                                    >
                                        {{ fuel.product_name }}: {{ fuel.stock_disponible.toFixed(2) }} {{ fuel.unit }}
                                    </span>
                                    <span v-if="fuelStockByProduct.length === 0" class="text-muted fs-10">
                                        Sin stock disponible
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input v-model="term" class="form-control mb-3" placeholder="Buscar..." />
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                    <thead class="table-primary">
                      <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Código Maquinaria</th>
                        <th>Operario</th>
                        <th>Centro de Costo</th>
                        <th>Combustible</th>
                        <th>Litros</th>
                        <th>Tipo Contador</th>
                        <th>Valor Contador</th>
                        <th>Observaciones</th>
                        <th style="width: 100px;">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in filteredRows" :key="item.id">
                        <td>{{ item.id }}</td>
                        <td>{{ item.date }}</td>
                        <td>{{ item.machinery?.cod_machinery || '-' }}</td>
                        <td>{{ item.operator?.name || '-' }}</td>
                        <!-- ...existing code... -->
                                                <td>
                                                    <ul class="mb-0 ps-3">
                                                        <li v-for="cc in (item.costCenters || []).slice(0,2)" :key="cc.name">
                                                            <span class="fw-bold">{{ cc.name }}</span>
                                                            <span v-if="cc.observations"> - {{ cc.observations }}</span>
                                                        </li>
                                                        <li v-if="!item.costCenters || !item.costCenters.length">
                                                            <span class="text-muted">-</span>
                                                        </li>
                                                        <li v-if="(item.costCenters || []).length > 2">
                                                            <a href="#" class="text-primary small text-decoration-underline" @click.prevent="showMoreCenters(item.costCenters)">
                                                                +{{ item.costCenters.length - 2 }} más
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </td>
                        <td>{{ item.product?.name || '-' }}</td>
                        <td>{{ item.liters }}</td>
                        <td>{{ item.counter?.name || '-' }}</td>
                        <td>{{ item.counter_value || '-' }}</td>
                        <td>{{ item.observations }}</td>
                        <td>
                          <div class="d-flex gap-1 justify-content-center">
                            <button 
                              @click="openEditModal(item)" 
                              class="btn btn-sm btn-primary p-1" 
                              title="Editar"
                              style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"
                            >
                              <i class="fas fa-edit"></i>
                            </button>
                            <button 
                              @click="deleteFuelOutflow(item.id)" 
                              class="btn btn-sm btn-danger p-1" 
                              title="Eliminar"
                              style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"
                            >
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="filteredRows.length === 0">
                        <td colspan="11" class="text-center text-muted">No hay consumos registrados.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

            </div>
        </div>
        
        <!-- Modal de Creación -->
        <CreateFuelOutflowModal
            :show="showCreateModal"
            :machineries="props.machineries"
            :operators="props.operators"
            :costCenters="props.costCenters"
            :fuelProducts="props.fuelProducts"
            :counters="props.counters"
            :availableFuelStocks="props.availableFuelStocks"
            :projects="props.projects"
            :operations="props.operations"
            :fuelTanks="props.fuelTanks"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />
        
        <!-- Modal de Edición -->
        <EditFuelOutflowModal
            :show="showEditModal"
            :fuelOutflow="editingFuelOutflow"
            :machineries="props.machineries"
            :operators="props.operators"
            :costCenters="props.costCenters"
            :fuelProducts="props.fuelProducts"
            :counters="props.counters"
            :projects="props.projects"
            :operations="props.operations"
            :fuelTanks="props.fuelTanks"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />

        <!-- Modal de Análisis -->
        <AnalyticsModal
            :show="showAnalyticsModal"
            :fuelStockByProduct="fuelStockByProduct"
            :totalStock="totalStockCombustible"
            :fuelOutflows="props.fuelOutflows"
            @close="closeAnalyticsModal"
        />
    </AppLayout>
</template>
