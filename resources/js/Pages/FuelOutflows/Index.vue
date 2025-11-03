<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateFuelOutflowModal from '@/Components/FuelOutflows/CreateFuelOutflowModal.vue';
import EditFuelOutflowModal from '@/Components/FuelOutflows/EditFuelOutflowModal.vue';

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
    fuelOutflows: Object,
    machineries: Array,
    operators: Array,
    costCenters: Array,
    fuelProducts: Array,
    counters: Array,
    availableFuelStocks: Array,
});

const title = 'Consumos de Combustible';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const filteredRows = computed(() => {
    if (!props.fuelOutflows || !props.fuelOutflows.data) return [];
    if (!term.value) return props.fuelOutflows.data;
    const search = term.value.toLowerCase();
    return props.fuelOutflows.data.filter(item => {
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

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingFuelOutflow = ref(null);

function openCreateModal() {
    showCreateModal.value = true;
}
function closeCreateModal() {
    showCreateModal.value = false;
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
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Registro eliminado correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar el registro'
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
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
            </div>
            <div class="card-body">
                <input v-model="term" class="form-control mb-3" placeholder="Buscar..." />
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                    <thead class="table-primary">
                      <tr>
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
                <div v-if="props.fuelOutflows && props.fuelOutflows.links" class="mt-3">
                    <nav>
                        <ul class="pagination">
                            <li v-for="link in props.fuelOutflows.links" :key="link.label" :class="['page-item', { active: link.active }]">
                                <a v-if="link.url" class="page-link" @click.prevent="router.get(link.url)">
                                    <span v-html="link.label" />
                                </a>
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
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
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
