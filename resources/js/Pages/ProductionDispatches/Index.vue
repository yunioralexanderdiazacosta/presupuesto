<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import CreateDispatchModal from '@/Components/ProductionDispatches/CreateDispatchModal.vue';
import EditDispatchModal from '@/Components/ProductionDispatches/EditDispatchModal.vue';
import ProcessDispatchModal from '@/Components/ProductionDispatches/ProcessDispatchModal.vue';
import ManageCatalogsModal from '@/Components/ProductionDispatches/ManageCatalogsModal.vue';
import ViewDispatchDetailModal from '@/Components/ProductionDispatches/ViewDispatchDetailModal.vue';
import ReceiveLotModal from '@/Components/ProductionDispatches/ReceiveLotModal.vue';

const props = defineProps({
    dispatches: Array,
    costCenterVarieties: Array,
    exporters: Array,
    packingHouses: Array,
    binTypes: Array,
    boxTypes: Array,
    carriers: Array,
    classifications: Object,
    fruits: Array,
    branches: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    varieties: { type: Array, default: () => [] },
});

const title = 'Despachos de Producción';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');
const selectedBranchIds = ref([]);
const selectedReasonIds = ref([]);
const selectedVarietyIds = ref([]);
const dateFrom = ref('');
const dateTo = ref('');

const hasActiveFilters = computed(() =>
    !!(term.value || statusFilter.value || dateFrom.value || dateTo.value
        || selectedBranchIds.value.length || selectedReasonIds.value.length || selectedVarietyIds.value.length)
);

function clearFilters() {
    term.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    selectedBranchIds.value = [];
    selectedReasonIds.value = [];
    selectedVarietyIds.value = [];
}

// Cuarteles de la guía (una guía puede llevar varios)
function ccvsOf(item) {
    return (item.cost_centers || []).map(c => c.cost_center_variety).filter(Boolean);
}

// Guías con todos los filtros salvo el estado (los totales de arriba se calculan sobre estas)
const scopedRows = computed(() => {
    let rows = Array.isArray(props.dispatches) ? props.dispatches : [];

    const branchIds = selectedBranchIds.value.map(String);
    const reasonIds = selectedReasonIds.value.map(String);
    const varietyIds = selectedVarietyIds.value.map(String);

    // La razón social es la que emite la guía
    if (reasonIds.length) {
        rows = rows.filter(item => reasonIds.includes(String(item.company_reason_id)));
    }

    if (branchIds.length || varietyIds.length) {
        // La guía coincide si alguno de sus cuarteles cumple los filtros elegidos
        rows = rows.filter(item => ccvsOf(item).some(c =>
            (branchIds.length === 0 || branchIds.includes(String(c.cost_center?.branch_id))) &&
            (varietyIds.length === 0 || varietyIds.includes(String(c.variety_id)))
        ));
    }

    if (dateFrom.value || dateTo.value) {
        rows = rows.filter(item => {
            const date = item.dispatch_date ? String(item.dispatch_date).slice(0, 10) : '';
            if (!date) return false;
            return (!dateFrom.value || date >= dateFrom.value) && (!dateTo.value || date <= dateTo.value);
        });
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const ccName = ccvsOf(item).map(c => (c.cost_center?.name || '').toLowerCase()).join(' ');
            const variety = ccvsOf(item).map(c => (c.variety?.name || '').toLowerCase()).join(' ');
            const exporter = item.exporter?.name?.toLowerCase() || '';
            const company = item.company_reason?.name?.toLowerCase() || '';
            const packing = item.packing_house?.name?.toLowerCase() || '';
            const guide = item.guide_number?.toLowerCase() || '';
            const lots = (item.lots || []).map(l => (l.lot_number || '').toLowerCase()).join(' ');
            const carrier = item.carrier?.name?.toLowerCase() || '';
            return ccName.includes(search) || variety.includes(search) || exporter.includes(search) || company.includes(search) || packing.includes(search) || guide.includes(search) || lots.includes(search) || carrier.includes(search);
        });
    }

    return rows;
});

const filteredRows = computed(() => {
    if (!statusFilter.value) return scopedRows.value;
    return scopedRows.value.filter(item => item.status === statusFilter.value);
});

// Totales
const totalKgDispatched = computed(() => scopedRows.value.reduce((sum, d) => sum + Number(d.kg_dispatched || 0), 0));
const totalKgReceived = computed(() => scopedRows.value.reduce((sum, d) => sum + lotsKgReceived(d), 0));
const totalKgExported = computed(() => scopedRows.value.reduce((sum, d) => sum + Number(d.kg_exported || 0), 0));
const countPending = computed(() => scopedRows.value.filter(d => d.status !== 'processed').length);
const countProcessed = computed(() => scopedRows.value.filter(d => d.status === 'processed').length);

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

const excelHeaders = [
    { label: 'Estado', key: 'estado', type: 'text' },
    { label: 'Fecha Despacho', key: 'fecha', type: 'text' },
    { label: 'Cuartel', key: 'cuartel', type: 'text' },
    { label: 'Variedad', key: 'variedad', type: 'text' },
    { label: 'Razón Social del Despacho', key: 'razonSocial', type: 'text' },
    { label: 'Exportadora', key: 'exportadora', type: 'text' },
    { label: 'Packing', key: 'packing', type: 'text' },
    { label: 'N° Guía', key: 'guia', type: 'text' },
    { label: 'Kg Desp.', key: 'kgDesp', type: 'number' },
    { label: 'N° Lote', key: 'lote', type: 'text' },
    { label: 'Kg Recep.', key: 'kgRecep', type: 'number' },
    { label: 'Kg Export.', key: 'kgExport', type: 'number' },
    { label: 'Clasif.', key: 'clasif', type: 'text' },
];

// Una fila por lote (igual que la tabla); las guías sin recepcionar van en una sola fila
const excelData = computed(() => {
    const classifLabels = { ok: 'Cuadrada', mismatch: 'No cuadra' };
    const rows = [];
    filteredRows.value.forEach(item => {
        const ccvs = ccvsOf(item);
        const base = {
            estado: statusLabel(item.status),
            fecha: item.dispatch_date ? new Date(item.dispatch_date).toLocaleDateString('es-CL') : '',
            cuartel: ccvs.map(c => c.cost_center?.name).filter(Boolean).join(', '),
            variedad: ccvs.map(c => c.variety?.name).filter(Boolean).join(', '),
            razonSocial: item.company_reason?.name || '',
            exportadora: item.exporter?.name || '',
            packing: item.packing_house?.name || '',
            guia: item.guide_number ?? '',
            kgDesp: Number(item.kg_dispatched || 0),
        };

        if (!item.lots || item.lots.length === 0) {
            rows.push({ ...base, lote: 'Sin recepcionar', kgRecep: '', kgExport: '', clasif: '' });
            return;
        }

        item.lots.forEach(lot => {
            rows.push({
                ...base,
                lote: lot.lot_number ?? '',
                kgRecep: Number(lot.kg_received || 0),
                kgExport: lot.status === 'processed' ? Number(lot.kg_exported || 0) : '',
                clasif: classifLabels[getLotClassificationStatus(lot)] || '',
            });
        });
    });
    return rows;
});

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
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                filename="despachos_produccion.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
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
                <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                    <div style="flex: 1 1 170px; min-width: 150px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-store me-1"></i> Sucursal</label>
                        <Multiselect
                            v-model="selectedBranchIds"
                            mode="tags"
                            :options="props.branches"
                            placeholder="Todas"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue multiselect-filter"
                        />
                    </div>
                    <div style="flex: 1.2 1 190px; min-width: 170px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-building me-1"></i> Razón Social</label>
                        <Multiselect
                            v-model="selectedReasonIds"
                            mode="tags"
                            :options="props.companyReasons"
                            placeholder="Todas"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue multiselect-filter"
                        />
                    </div>
                    <div style="flex: 1 1 170px; min-width: 150px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-leaf me-1"></i> Variedad</label>
                        <Multiselect
                            v-model="selectedVarietyIds"
                            mode="tags"
                            :options="props.varieties"
                            placeholder="Todas"
                            :searchable="true"
                            :close-on-select="false"
                            class="multiselect-blue multiselect-filter"
                        />
                    </div>
                    <div style="flex: 1 1 150px; min-width: 140px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-flag me-1"></i> Estado</label>
                        <select v-model="statusFilter" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="dispatched">Despachado (sin recepcionar)</option>
                            <option value="received">En packing (con lotes por procesar)</option>
                            <option value="processed">Procesado</option>
                        </select>
                    </div>
                    <div style="flex: 0 1 140px; min-width: 130px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-calendar-alt me-1"></i> Desde</label>
                        <input v-model="dateFrom" type="date" class="form-control form-control-sm" :max="dateTo || undefined" />
                    </div>
                    <div style="flex: 0 1 140px; min-width: 130px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-calendar-alt me-1"></i> Hasta</label>
                        <input v-model="dateTo" type="date" class="form-control form-control-sm" :min="dateFrom || undefined" />
                    </div>
                    <div style="flex: 1.5 1 200px; min-width: 160px;">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-search me-1"></i> Buscar</label>
                        <input v-model="term" class="form-control form-control-sm" placeholder="Guía, lote, cuartel..." title="Busca por cuartel, variedad, razón social, exportadora, packing, transportista, guía o lote" />
                    </div>
                    <div>
                        <button class="btn btn-falcon-default btn-sm" :disabled="!hasActiveFilters" @click="clearFilters">
                            <span class="fas fa-eraser me-1"></span>Limpiar
                        </button>
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
                                <th style="max-width: 130px;">Razón Social del Despacho</th>
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
                                    <td :rowspan="Math.max(item.lots?.length || 0, 1)">
                                        <div class="text-truncate" style="max-width: 130px;" v-tooltip="item.company_reason?.name || ''">{{ item.company_reason?.name || '-' }}</div>
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
                                <td colspan="15" class="text-center text-muted">No hay despachos registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateDispatchModal
            :show="showCreateModal"
            :costCenterVarieties="props.costCenterVarieties"
            :exporters="props.exporters"
            :companyReasons="props.companyReasons"
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
            :companyReasons="props.companyReasons"
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

<style>
/* Filtros: mismo alto y tamaño de letra que los inputs form-control-sm (fechas, estado) */
.multiselect.multiselect-filter {
    --ms-font-size: 0.875rem;
    --ms-line-height: 1.5;
    --ms-py: 0.1875rem;
    --ms-px: 0.75rem;
    --ms-tag-font-size: 0.75rem;
    --ms-tag-line-height: 1.25rem;
    --ms-tag-py: 0;
    --ms-tag-px: 0.4rem;
    --ms-option-font-size: 0.8rem;
    --ms-option-py: 0.2rem;
    min-height: calc(1.5em + 0.375rem + 2px);
    font-size: 0.875rem;
}
.multiselect.multiselect-filter .multiselect-tags {
    margin-top: 0;
    padding-left: 0;
}
.multiselect.multiselect-filter .multiselect-tag {
    margin: 0 0.25rem 0 0;
}
.multiselect.multiselect-filter .multiselect-placeholder,
.multiselect.multiselect-filter .multiselect-search input {
    font-size: 0.875rem;
}
</style>
