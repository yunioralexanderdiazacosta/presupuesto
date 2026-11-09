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
import ReceiveLotModal from '@/Components/ProductionDispatches/ReceiveLotModal.vue';

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

// Cuarteles de la guía (una guía puede llevar varios)
function ccvsOf(item) {
    return (item.cost_centers || []).map(c => c.cost_center_variety).filter(Boolean);
}

const filteredRows = computed(() => {
    if (!props.dispatches || !props.dispatches.data) return [];
    let rows = props.dispatches.data;

    if (statusFilter.value) {
        rows = rows.filter(item => item.status === statusFilter.value);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const ccName = ccvsOf(item).map(c => (c.cost_center?.name || '').toLowerCase()).join(' ');
            const variety = ccvsOf(item).map(c => (c.variety?.name || '').toLowerCase()).join(' ');
            const exporter = item.exporter?.name?.toLowerCase() || '';
            const packing = item.packing_house?.name?.toLowerCase() || '';
            const guide = item.guide_number?.toLowerCase() || '';
            const lots = (item.lots || []).map(l => (l.lot_number || '').toLowerCase()).join(' ');
            const carrier = item.carrier?.name?.toLowerCase() || '';
            return ccName.includes(search) || variety.includes(search) || exporter.includes(search) || packing.includes(search) || guide.includes(search) || lots.includes(search) || carrier.includes(search);
        });
    }

    return rows;
});

// Totales
const totalKgDispatched = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + Number(d.kg_dispatched || 0), 0);
});
const totalKgReceived = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + lotsKgReceived(d), 0);
});
const totalKgExported = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.reduce((sum, d) => sum + Number(d.kg_exported || 0), 0);
});
const countPending = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.filter(d => d.status !== 'processed').length;
});
const countProcessed = computed(() => {
    if (!props.dispatches?.data) return 0;
    return props.dispatches.data.filter(d => d.status === 'processed').length;
});

function lotsKgReceived(dispatch) {
    return (dispatch.lots || []).reduce((sum, l) => sum + Number(l.kg_received || 0), 0);
}

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showProcessModal = ref(false);
const showCatalogsModal = ref(false);
const showLotModal = ref(false);
const editingDispatch = ref(null);
const processingDispatch = ref(null);
const processingLot = ref(null);
const viewingDispatch = ref(null);
const viewingLot = ref(null);
const showViewDetailModal = ref(false);
const lotDispatch = ref(null);
const editingLot = ref(null);

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

function openLotModal(dispatch, lot = null) {
    lotDispatch.value = dispatch;
    editingLot.value = lot;
    showLotModal.value = true;
}
function closeLotModal() {
    showLotModal.value = false;
    lotDispatch.value = null;
    editingLot.value = null;
}

function openProcessModal(dispatch, lot) {
    processingDispatch.value = dispatch;
    processingLot.value = lot;
    showProcessModal.value = true;
}
function closeProcessModal() {
    showProcessModal.value = false;
    processingDispatch.value = null;
    processingLot.value = null;
}

function openCatalogsModal() { showCatalogsModal.value = true; }
function closeCatalogsModal() { showCatalogsModal.value = false; }

function openViewDetailModal(dispatch, lot) {
    viewingDispatch.value = dispatch;
    viewingLot.value = lot;
    showViewDetailModal.value = true;
}
function closeViewDetailModal() {
    showViewDetailModal.value = false;
    viewingDispatch.value = null;
    viewingLot.value = null;
}

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    closeLotModal();
    closeProcessModal();
    router.reload({ preserveScroll: true });
}

function deleteDispatch(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se eliminará la guía junto con sus lotes. Esta acción no se puede deshacer',
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

function deleteLot(lot) {
    Swal.fire({
        title: '¿Eliminar lote?',
        text: `Se eliminará el lote ${lot.lot_number}${lot.status === 'processed' ? ' junto con su proceso' : ''}. Esta acción no se puede deshacer`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('production-lots.delete', lot.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Lote eliminado correctamente', timer: 1500, showConfirmButton: false });
                },
                onError: () => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el lote' });
                }
            });
        }
    });
}

function formatNumber(val) {
    if (val === null || val === undefined) return '-';
    return Number(val).toLocaleString('es-CL');
}

const statusLabels = { dispatched: 'Despachado', received: 'En packing', processed: 'Procesado' };
const statusClasses = {
    dispatched: 'badge bg-warning text-dark',
    received: 'badge bg-info text-dark',
    processed: 'badge bg-success',
};

function statusLabel(status) {
    return statusLabels[status] || statusLabels.dispatched;
}

function statusClass(status) {
    return statusClasses[status] || statusClasses.dispatched;
}

function getLotClassificationStatus(lot) {
    if (lot.status !== 'processed' || !lot.items || lot.items.length === 0) return null;
    // Mismo criterio que el formulario de proceso: cada tipo se compara contra los Kg Exportados
    const kgExported = Number(lot.kg_exported) || 0;
    if (kgExported === 0) return null;

    const totalsByType = {};
    lot.items.forEach(i => {
        if (i.classification_type) {
            totalsByType[i.classification_type] = (totalsByType[i.classification_type] || 0) + Number(i.kg || 0);
        }
    });

    // Los tipos sin kg (todos sus valores en 0) no se usaron, no deben marcar descuadre
    const types = Object.keys(totalsByType).filter(t => totalsByType[t] > 0);
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
                    <div class="col-md-2 col-6 mb-2">
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
                    <div class="col-md-2 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Kg Recepcionados</h6>
                                        <h4 class="mb-0 fw-bold text-info">{{ formatNumber(totalKgReceived) }}</h4>
                                    </div>
                                    <i class="fas fa-dolly fa-2x text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-2">
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
                                        <h6 class="mb-0 text-muted fs-10">Pendientes</h6>
                                        <h4 class="mb-0 fw-bold text-warning">{{ countPending }}</h4>
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
                            <option value="dispatched">Despachado (sin recepcionar)</option>
                            <option value="received">En packing (con lotes por procesar)</option>
                            <option value="processed">Procesado</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Estado</th>
                                <th>Fecha Despacho</th>
                                <th>Cuartel</th>
                                <th>Variedad</th>
                                <th>Exportadora</th>
                                <th>Packing</th>
                                <th>N° Guía</th>
                                <th class="text-end">Kg Desp.</th>
                                <th>N° Lote</th>
                                <th class="text-end">Kg Recep.</th>
                                <th class="text-end">Kg Export.</th>
                                <th class="text-center">Clasif.</th>
                                <th class="text-center" style="width: 110px;">Acciones Lote</th>
                                <th class="text-center" style="width: 110px;">Acciones Guía</th>
                            </tr>
                        </thead>
                        <!-- Un tbody por guía: cada lote es una fila y los datos de la guía se agrupan con rowspan -->
                        <tbody v-for="item in filteredRows" :key="item.id" style="border-top: 2px solid #9da9bb;">
                            <tr v-for="(lot, idx) in (item.lots && item.lots.length ? item.lots : [null])" :key="lot ? lot.id : 'sin-lote'">
                                <template v-if="idx === 0">
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">
                                        <span :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                                    </td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">{{ item.dispatch_date ? new Date(item.dispatch_date).toLocaleDateString('es-CL') : '-' }}</td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">
                                        <span class="text-nowrap" :title="ccvsOf(item).map(c => `${c.cost_center?.name || '-'} · ${c.variety?.name || '-'}`).join('\n')">
                                            {{ ccvsOf(item)[0]?.cost_center?.name || '-' }}
                                            <span v-if="ccvsOf(item).length > 1" class="badge bg-soft-primary text-primary ms-1">+{{ ccvsOf(item).length - 1 }}</span>
                                        </span>
                                    </td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">
                                        <span class="text-nowrap" :title="ccvsOf(item).map(c => `${c.cost_center?.name || '-'} · ${c.variety?.name || '-'}`).join('\n')">
                                            {{ ccvsOf(item)[0]?.variety?.name || '-' }}
                                        </span>
                                    </td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">{{ item.exporter?.name || '-' }}</td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">{{ item.packing_house?.name || '-' }}</td>
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">{{ item.guide_number }}</td>
                                    <td class="text-end" :rowspan="Math.max(item.lots?.length || 0, 1)">{{ formatNumber(item.kg_dispatched) }}</td>
                                </template>

                                <template v-if="lot">
                                    <td class="text-nowrap fw-semibold">
                                        <i class="fas fa-circle me-1" :class="lot.status === 'processed' ? 'text-success' : 'text-info'" style="font-size: 0.5rem; vertical-align: middle;" :title="lot.status === 'processed' ? 'Procesado' : 'Pendiente de proceso'"></i>{{ lot.lot_number }}
                                    </td>
                                    <td class="text-end">{{ formatNumber(lot.kg_received) }}</td>
                                    <td class="text-end">{{ lot.status === 'processed' ? formatNumber(lot.kg_exported) : '-' }}</td>
                                    <td class="text-center">
                                        <span v-if="getLotClassificationStatus(lot) === 'ok'" class="badge bg-success" title="Clasificaciones cuadradas">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        <span v-else-if="getLotClassificationStatus(lot) === 'mismatch'" class="badge bg-danger" title="Clasificaciones no cuadran con Kg Exportados">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button v-if="lot.status === 'processed'" @click="openViewDetailModal(item, lot)" class="btn btn-sm btn-falcon-default p-0" title="Ver detalle del proceso" style="width: 24px; height: 24px;">
                                                <i class="fas fa-eye text-info"></i>
                                            </button>
                                            <button @click="openProcessModal(item, lot)" class="btn btn-sm btn-falcon-default p-0" :title="lot.status === 'processed' ? 'Re-procesar lote' : 'Procesar lote'" style="width: 24px; height: 24px;">
                                                <i class="fas fa-industry" :class="lot.status === 'processed' ? 'text-success' : 'text-warning'"></i>
                                            </button>
                                            <button @click="openLotModal(item, lot)" class="btn btn-sm btn-falcon-default p-0" title="Editar lote" style="width: 24px; height: 24px;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="deleteLot(lot)" class="btn btn-sm btn-falcon-default p-0" title="Eliminar lote" style="width: 24px; height: 24px;">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </template>
                                <td v-else colspan="5" class="text-muted text-center">Sin recepcionar</td>

                                <td v-if="idx === 0" :rowspan="Math.max(item.lots?.length || 0, 1)">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button @click="openLotModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Recepcionar lote" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-dolly text-primary"></i>
                                        </button>
                                        <button @click="openEditModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Editar guía" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteDispatch(item.id)" class="btn btn-sm btn-falcon-default p-1" title="Eliminar guía" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-if="filteredRows.length === 0">
                            <tr>
                                <td colspan="14" class="text-center text-muted">No hay despachos registrados.</td>
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

        <ReceiveLotModal
            :show="showLotModal"
            :dispatch="lotDispatch"
            :lot="editingLot"
            @close="closeLotModal"
            @saved="reloadAfterSave"
        />

        <ProcessDispatchModal
            v-if="processingDispatch && processingLot"
            :show="showProcessModal"
            :dispatch="processingDispatch"
            :lot="processingLot"
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
            :lot="viewingLot"
            :classifications="props.classifications"
            :costCenterVarieties="props.costCenterVarieties"
            @close="closeViewDetailModal"
        />
    </AppLayout>
</template>
