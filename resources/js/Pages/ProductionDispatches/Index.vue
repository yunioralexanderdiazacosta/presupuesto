<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateDispatchModal from '@/Components/ProductionDispatches/CreateDispatchModal.vue';
import EditDispatchModal from '@/Components/ProductionDispatches/EditDispatchModal.vue';
import ProcessDispatchModal from '@/Components/ProductionDispatches/ProcessDispatchModal.vue';
import ManageCatalogsModal from '@/Components/ProductionDispatches/ManageCatalogsModal.vue';
import ViewDispatchDetailModal from '@/Components/ProductionDispatches/ViewDispatchDetailModal.vue';

const props = defineProps({
    dispatches: Object,
    costCenterVarieties: Array,
    exporters: Array,
    packingHouses: Array,
    binTypes: Array,
    boxTypes: Array,
    carriers: Array,
    classifications: Object,
    fruits: Array,
});

const title = 'Despachos de Producción';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');

const filteredRows = computed(() => {
    if (!props.dispatches || !props.dispatches.data) return [];
    let rows = props.dispatches.data;

    if (statusFilter.value) {
        rows = rows.filter(item => item.status === statusFilter.value);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const ccName = item.cost_center_variety?.cost_center?.name?.toLowerCase() || '';
            const variety = item.cost_center_variety?.variety?.name?.toLowerCase() || '';
            const exporter = item.exporter?.name?.toLowerCase() || '';
            const packing = item.packing_house?.name?.toLowerCase() || '';
            const guide = item.guide_number?.toLowerCase() || '';
            const lot = item.lot_number?.toLowerCase() || '';
            const carrier = item.carrier?.toLowerCase() || '';
            return ccName.includes(search) || variety.includes(search) || exporter.includes(search) || packing.includes(search) || guide.includes(search) || lot.includes(search) || carrier.includes(search);
        });
    }

    return rows;
});

// Totales
const totalKgDispatched = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + Number(d.kg_dispatched || 0), 0);
});
const totalKgExported = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + Number(d.kg_exported || 0), 0);
});
const totalKgWaste = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + Number(d.kg_waste || 0), 0);
});
const countDispatched = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.filter(d => d.status === 'dispatched').length;
});
const countProcessed = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.filter(d => d.status === 'processed').length;
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showProcessModal = ref(false);
const showCatalogsModal = ref(false);
const editingDispatch = ref(null);
const processingDispatch = ref(null);
const viewingDispatch = ref(null);
const showViewDetailModal = ref(false);

function openCreateModal() { showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; }

function openEditModal(dispatch) {
    editingDispatch.value = dispatch;
    showEditModal.value = true;
}
function closeEditModal() {
    showEditModal.value = false;
    editingDispatch.value = null;
}

function openProcessModal(dispatch) {
    processingDispatch.value = dispatch;
    showProcessModal.value = true;
}
function closeProcessModal() {
    showProcessModal.value = false;
    processingDispatch.value = null;
}

function openCatalogsModal() { showCatalogsModal.value = true; }
function closeCatalogsModal() { showCatalogsModal.value = false; }

function openViewDetailModal(dispatch) {
    viewingDispatch.value = dispatch;
    showViewDetailModal.value = true;
}
function closeViewDetailModal() {
    showViewDetailModal.value = false;
    viewingDispatch.value = null;
}

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    closeProcessModal();
    router.reload({ preserveScroll: true });
}

function deleteDispatch(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('production-dispatches.delete', id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Despacho eliminado correctamente', timer: 1500, showConfirmButton: false });
                },
                onError: () => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el registro' });
                }
            });
        }
    });
}

function formatNumber(val) {
    if (val === null || val === undefined) return '-';
    return Number(val).toLocaleString('es-CL');
}

function statusLabel(status) {
    return status === 'processed' ? 'Procesado' : 'Despachado';
}

function statusClass(status) {
    return status === 'processed' ? 'badge bg-success' : 'badge bg-warning text-dark';
}

function getClassificationStatus(item) {
    if (item.status !== 'processed' || !item.items || item.items.length === 0) return null;
    const kgExported = Number(item.kg_exported) || 0;
    if (kgExported === 0) return null;

    const totalsByType = {};
    item.items.forEach(i => {
        if (i.classification_type) {
            totalsByType[i.classification_type] = (totalsByType[i.classification_type] || 0) + Number(i.kg || 0);
        }
    });

    const types = Object.keys(totalsByType);
    if (types.length === 0) return null;

    const mismatched = types.filter(t => Math.abs(totalsByType[t] - kgExported) > 0.01);
    if (mismatched.length > 0) return 'mismatch';
    return 'ok';
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-truck-loading me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCatalogsModal">
                                <span class="fas fa-cogs" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Catálogos</span>
                            </button>
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Resumen -->
                <div class="row mb-3">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Kg Despachados</h6>
                                        <h4 class="mb-0 fw-bold">{{ formatNumber(totalKgDispatched) }}</h4>
                                    </div>
                                    <i class="fas fa-truck fa-2x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Kg Exportados</h6>
                                        <h4 class="mb-0 fw-bold text-success">{{ formatNumber(totalKgExported) }}</h4>
                                    </div>
                                    <i class="fas fa-globe-americas fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Kg Descarte</h6>
                                        <h4 class="mb-0 fw-bold text-danger">{{ formatNumber(totalKgWaste) }}</h4>
                                    </div>
                                    <i class="fas fa-trash-alt fa-2x text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Pendientes</h6>
                                        <h4 class="mb-0 fw-bold text-warning">{{ countDispatched }}</h4>
                                    </div>
                                    <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Procesados</h6>
                                        <h4 class="mb-0 fw-bold text-success">{{ countProcessed }}</h4>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-8">
                        <input v-model="term" class="form-control form-control-sm" placeholder="Buscar por cuartel, variedad, exportadora, packing, guía, lote..." />
                    </div>
                    <div class="col-md-4">
                        <select v-model="statusFilter" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="dispatched">Despachado (pendiente)</option>
                            <option value="processed">Procesado</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Despacho</th>
                                <th>Cuartel</th>
                                <th>Variedad</th>
                                <th>Exportadora</th>
                                <th>Packing</th>
                                <th>N° Guía</th>
                                <th>Lote</th>
                                <th class="text-end">Kg Desp.</th>
                                <th class="text-end">Kg Export.</th>
                                <th class="text-end">Kg Desc.</th>
                                <th class="text-center">Clasif.</th>
                                <th style="width: 140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td>
                                    <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                                </td>
                                <td>{{ item.dispatch_date ? new Date(item.dispatch_date).toLocaleDateString('es-CL') : '-' }}</td>
                                <td>{{ item.cost_center_variety?.cost_center?.name || '-' }}</td>
                                <td>{{ item.cost_center_variety?.variety?.name || '-' }}</td>
                                <td>{{ item.exporter?.name || '-' }}</td>
                                <td>{{ item.packing_house?.name || '-' }}</td>
                                <td>{{ item.guide_number }}</td>
                                <td>{{ item.lot_number || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.kg_dispatched) }}</td>
                                <td class="text-end">{{ formatNumber(item.kg_exported) }}</td>
                                <td class="text-end">{{ formatNumber(item.kg_waste) }}</td>
                                <td class="text-center">
                                    <span v-if="getClassificationStatus(item) === 'ok'" class="badge bg-success" title="Clasificaciones cuadradas">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <span v-else-if="getClassificationStatus(item) === 'mismatch'" class="badge bg-danger" title="Clasificaciones no cuadran con Kg Exportados">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button v-if="item.status === 'processed'" @click="openViewDetailModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Ver detalle" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-eye text-info"></i>
                                        </button>
                                        <button @click="openProcessModal(item)" class="btn btn-sm btn-falcon-default p-1" :title="item.status === 'processed' ? 'Re-procesar' : 'Procesar'" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-industry" :class="item.status === 'processed' ? 'text-success' : 'text-warning'"></i>
                                        </button>
                                        <button @click="openEditModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Editar despacho" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteDispatch(item.id)" class="btn btn-sm btn-falcon-default p-1" title="Eliminar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="13" class="text-center text-muted">No hay despachos registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="props.dispatches && props.dispatches.links" class="mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li v-for="link in props.dispatches.links" :key="link.label" :class="['page-item', { active: link.active }]">
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

        <!-- Modales -->
        <CreateDispatchModal
            :show="showCreateModal"
            :costCenterVarieties="props.costCenterVarieties"
            :exporters="props.exporters"
            :packingHouses="props.packingHouses"
            :binTypes="props.binTypes"
            :boxTypes="props.boxTypes"
            :carriers="props.carriers"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditDispatchModal
            :show="showEditModal"
            :dispatch="editingDispatch"
            :costCenterVarieties="props.costCenterVarieties"
            :exporters="props.exporters"
            :packingHouses="props.packingHouses"
            :binTypes="props.binTypes"
            :boxTypes="props.boxTypes"
            :carriers="props.carriers"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />

        <ProcessDispatchModal
            v-if="processingDispatch"
            :show="showProcessModal"
            :dispatch="processingDispatch"
            :classifications="props.classifications"
            :costCenterVarieties="props.costCenterVarieties"
            @close="closeProcessModal"
            @saved="reloadAfterSave"
        />

        <ManageCatalogsModal
            :show="showCatalogsModal"
            :fruits="props.fruits"
            @close="closeCatalogsModal"
        />

        <ViewDispatchDetailModal
            :show="showViewDetailModal"
            :dispatch="viewingDispatch"
            :classifications="props.classifications"
            :costCenterVarieties="props.costCenterVarieties"
            @close="closeViewDetailModal"
        />
    </AppLayout>
</template>
