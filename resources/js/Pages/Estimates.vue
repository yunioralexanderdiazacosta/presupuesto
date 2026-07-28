<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Multiselect from '@vueform/multiselect';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const page = usePage();
const costCenterVarieties = computed(() => page.props.costCenterVarieties || []);
const estimates = computed(() => page.props.estimates || []);
const estimate_statuses = computed(() => page.props.estimate_statuses || []);
const fruits = computed(() => page.props.fruits || []);
const branches = computed(() => page.props.branches || []);
const season_id = computed(() => page.props.season_id);

// ── Opciones para selects ──
const fruitOptions = computed(() =>
    fruits.value.map(f => ({ value: f.id, label: f.name }))
);

// Filtro de sucursal (client-side). '' = todas.
const branchOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    ...branches.value,
]);
const selectedBranch = ref('');

// Resuelve el nombre de la sucursal a partir del branch_id del cuartel
function branchLabelById(branchId) {
    if (!branchId) return '-';
    const found = branches.value.find(b => String(b.value) === String(branchId));
    return found ? found.label : '-';
}

const selectedFruitId = ref(fruitOptions.value.length ? fruitOptions.value[0].value : '');

const estimateStatusOptions = computed(() =>
    estimate_statuses.value
        .filter(s => s.fruit_id == selectedFruitId.value)
        .map(s => ({ value: s.id, label: s.name }))
);

const selectedEstimateStatusId = ref('');

// Inicializar estimate status cuando cambia fruta
watch(selectedFruitId, () => {
    const opts = estimateStatusOptions.value;
    selectedEstimateStatusId.value = opts.length ? opts[0].value : '';
});
// Inicializar al montar
watch(estimateStatusOptions, (opts) => {
    if (opts.length && !selectedEstimateStatusId.value) {
        selectedEstimateStatusId.value = opts[0].value;
    }
}, { immediate: true });

// ── Crear EstimateStatus ──
const showStatusModal = ref(false);
const nuevoNombre = ref('');
const nuevoFruitId = ref('');

function openStatusModal() {
    nuevoFruitId.value = selectedFruitId.value;
    nuevoNombre.value = '';
    showStatusModal.value = true;
}

function guardarEstimateStatus() {
    if (!nuevoNombre.value || !nuevoFruitId.value) return;
    fetch(route('estimate-status.store'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ name: nuevoNombre.value, fruit_id: nuevoFruitId.value })
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.id) {
            showStatusModal.value = false;
            Swal.fire({ icon: 'success', title: 'Nombre de estimación creado', showConfirmButton: false, timer: 1200 });
            router.reload({ only: ['estimate_statuses'] });
        } else {
            Swal.fire('Error', data.error || 'Error al guardar', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Error al guardar', 'error'));
}

// ── Tabla editable ──
const kilosInputs = ref({});
const observationsInputs = ref({});
const modifiedRows = ref({});

function markAsModified(ccvId) {
    modifiedRows.value[ccvId] = true;
}

const rows = computed(() => {
    if (!selectedFruitId.value || !selectedEstimateStatusId.value) return [];

    return costCenterVarieties.value
        .filter(ccv => ccv.fruit_id == selectedFruitId.value)
        .filter(ccv => !selectedBranch.value || String(ccv.cost_center?.branch_id) === String(selectedBranch.value))
        .map(ccv => {
            const estimate = estimates.value.find(
                e => e.cost_center_variety_id == ccv.id && e.estimate_status_id == selectedEstimateStatusId.value
            );

            const currentKilos = kilosInputs.value[ccv.id] !== undefined
                ? kilosInputs.value[ccv.id]
                : (estimate ? estimate.kilos_ha : '');

            const currentObs = observationsInputs.value[ccv.id] !== undefined
                ? observationsInputs.value[ccv.id]
                : (estimate ? estimate.observations || '' : '');

            const surfaceNum = ccv.surface ? Number(ccv.surface) : 0;
            const kilosNum = currentKilos ? Number(currentKilos) : 0;

            return {
                id: estimate ? estimate.id : null,
                ccvId: ccv.id,
                costCenterName: ccv.cost_center?.name || '-',
                branchName: ccv.cost_center?.branch?.name || branchLabelById(ccv.cost_center?.branch_id),
                varietyName: ccv.variety?.name || '-',
                rootstockName: ccv.rootstock?.name || '-',
                devStateName: ccv.development_state?.name || '-',
                surface: surfaceNum,
                kilos: currentKilos,
                observation: currentObs,
                kilosTotal: surfaceNum * kilosNum,
                isExisting: !!estimate,
                isModified: !!modifiedRows.value[ccv.id]
            };
        })
        .sort((a, b) => a.costCenterName.localeCompare(b.costCenterName));
});

function updateKilos(ccvId, value) {
    kilosInputs.value[ccvId] = value;
    markAsModified(ccvId);
}
function updateObservation(ccvId, value) {
    observationsInputs.value[ccvId] = value;
    markAsModified(ccvId);
}

// Resetear inputs al cambiar filtros
watch([selectedFruitId, selectedEstimateStatusId, selectedBranch], () => {
    kilosInputs.value = {};
    observationsInputs.value = {};
    modifiedRows.value = {};
});

// ── Contadores ──
const countNewRows = computed(() => rows.value.filter(r => !r.isExisting && r.kilos && r.kilos !== '').length);
const countModifiedRows = computed(() => rows.value.filter(r => r.isExisting && r.isModified).length);

// ── KPIs ──
const totalKilos = computed(() => rows.value.reduce((sum, r) => sum + r.kilosTotal, 0));
const totalSurface = computed(() => rows.value.reduce((sum, r) => sum + r.surface, 0));
const averageKilosHa = computed(() => totalSurface.value ? Math.round(totalKilos.value / totalSurface.value) : 0);

// ── Guardar ──
async function handleSave() {
    const newRecords = rows.value
        .filter(r => !r.isExisting && r.kilos && r.kilos !== '')
        .map(r => ({
            cost_center_variety_id: r.ccvId,
            kilos_ha: r.kilos,
            estimate_status_id: selectedEstimateStatusId.value,
            observations: r.observation || ''
        }));

    const modifiedRecords = rows.value
        .filter(r => r.isExisting && r.isModified)
        .map(r => ({
            id: r.id,
            cost_center_variety_id: r.ccvId,
            kilos_ha: r.kilos,
            estimate_status_id: selectedEstimateStatusId.value,
            observations: r.observation || ''
        }));

    if (!newRecords.length && !modifiedRecords.length) return;

    let hasErrors = false;

    if (newRecords.length > 0) {
        await new Promise((resolve) => {
            router.post(route('estimates.store'), newRecords, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => {
                    hasErrors = true;
                    Swal.fire({ icon: 'error', title: 'Error al guardar nuevos', text: errors.error || 'Revisa los datos.', showConfirmButton: true });
                    resolve();
                }
            });
        });
    }

    if (modifiedRecords.length > 0) {
        for (const record of modifiedRecords) {
            await new Promise((resolve) => {
                router.post(`/estimates/${record.id}/update`, record, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: () => {
                        hasErrors = true;
                        Swal.fire({ icon: 'error', title: 'Error al actualizar', showConfirmButton: true });
                        resolve();
                    }
                });
            });
        }
    }

    if (!hasErrors) {
        kilosInputs.value = {};
        observationsInputs.value = {};
        modifiedRows.value = {};
        Swal.fire({
            icon: 'success',
            title: 'Guardado correctamente',
            text: `${newRecords.length} nuevo(s), ${modifiedRecords.length} modificado(s)`,
            showConfirmButton: false,
            timer: 2000
        });
        router.reload({ only: ['estimates'] });
    }
}

// ── Exportar Excel ──
const excelHeaders = [
    { label: 'Cuartel',         key: 'costCenterName' },
    { label: 'Sucursal',        key: 'branchName' },
    { label: 'Variedad',        key: 'varietyName' },
    { label: 'Portainjerto',    key: 'rootstockName' },
    { label: 'Superficie (ha)', key: 'surface',     type: 'number' },
    { label: 'Kilos/ha',        key: 'kilos',       type: 'number' },
    { label: 'Kilos Total',     key: 'kilosTotal',  type: 'number' },
    { label: 'Observaciones',   key: 'observation' },
];

const excelData = computed(() =>
    rows.value.map(r => ({
        costCenterName: r.costCenterName,
        branchName:     r.branchName,
        varietyName:    r.varietyName,
        rootstockName:  r.rootstockName,
        surface:        r.surface || 0,
        kilos:          r.kilos !== '' && r.kilos !== undefined ? Number(r.kilos) : 0,
        kilosTotal:     r.kilosTotal || 0,
        observation:    r.observation || '',
    }))
);

const excelFilename = computed(() => {
    const fruta = fruitOptions.value.find(f => String(f.value) === String(selectedFruitId.value))?.label || 'estimacion';
    const estado = estimateStatusOptions.value.find(s => String(s.value) === String(selectedEstimateStatusId.value))?.label || '';
    return `Estimaciones_${fruta}${estado ? '_' + estado : ''}.xlsx`;
});

// ── Eliminar ──
function handleDelete(row) {
    if (!row.id) return;
    Swal.fire({
        title: '¿Eliminar esta estimación?',
        text: `${row.costCenterName} - ${row.varietyName}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/estimates/${row.id}/delete`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminada', showConfirmButton: false, timer: 1200 });
                    router.reload({ only: ['estimates'] });
                }
            });
        }
    });
}
</script>

<template>
    <AppLayout title="Estimaciones">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-chart-line me-2"></i>Estimaciones
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                v-if="rows.length > 0"
                                :data="excelData"
                                :headers="excelHeaders"
                                :filename="excelFilename"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <button class="btn btn-falcon-default btn-sm" @click="openStatusModal">
                                <span class="fas fa-tag" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nombre Estimación</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-store me-1"></i> Sucursal</label>
                        <Multiselect
                            v-model="selectedBranch"
                            :options="branchOptions"
                            placeholder="Todas las sucursales"
                            :searchable="true"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-seedling me-1"></i> Especie</label>
                        <Multiselect
                            v-model="selectedFruitId"
                            :options="fruitOptions"
                            placeholder="Seleccione especie..."
                            :searchable="true"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold mb-1"><i class="fas fa-clipboard-list me-1"></i> Nombre Estimación</label>
                        <Multiselect
                            v-model="selectedEstimateStatusId"
                            :options="estimateStatusOptions"
                            placeholder="Seleccione estimación..."
                            :searchable="true"
                            :can-clear="false"
                            class="multiselect-blue"
                        />
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button
                            class="btn btn-falcon-default btn-sm w-100"
                            @click="handleSave"
                            :disabled="countNewRows === 0 && countModifiedRows === 0"
                        >
                            <i class="fas fa-save me-1"></i> Guardar
                            <span v-if="countNewRows + countModifiedRows > 0" class="badge bg-primary ms-1">{{ countNewRows + countModifiedRows }}</span>
                        </button>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row mb-3 g-2" v-if="rows.length > 0">
                    <div class="col-md-3">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Total Kilos</div>
                                <div class="fs-7 fw-bold">{{ totalKilos.toLocaleString('es-CL') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Promedio kg/ha</div>
                                <div class="fs-7 fw-bold">{{ averageKilosHa.toLocaleString('es-CL') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Superficie Total</div>
                                <div class="fs-7 fw-bold">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }} ha</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Registros</div>
                                <div class="fs-7 fw-bold">{{ rows.length }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leyenda -->
                <div class="alert alert-light border mb-3 py-2" v-if="rows.length > 0">
                    <small class="d-flex flex-wrap gap-3 mb-0">
                        <span><span class="badge bg-secondary me-1">Vacío</span> Sin datos</span>
                        <span><span class="badge bg-success me-1">Nuevo</span> Se creará al guardar</span>
                        <span><span class="badge bg-info me-1">Guardado</span> Ya existe</span>
                        <span><span class="badge bg-warning text-dark me-1">Modificado</span> Cambios sin guardar</span>
                    </small>
                </div>

                <!-- Mensaje sin selección -->
                <div v-if="!selectedFruitId || !selectedEstimateStatusId" class="alert alert-warning text-center">
                    <i class="fas fa-info-circle me-2"></i>Selecciona una <strong>especie</strong> y un <strong>nombre de estimación</strong> para ver las variedades disponibles.
                </div>

                <!-- Mensaje sin variedades -->
                <div v-else-if="rows.length === 0" class="alert alert-info text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>No hay variedades por cuartel registradas para esta especie. Crea primero las variedades en el módulo <strong>Variedades por Cuartel</strong>.
                </div>

                <!-- Tabla editable -->
                <div v-else class="table-responsive">
                    <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 90px">Estado</th>
                                <th>Cuartel</th>
                                <th>Sucursal</th>
                                <th>Variedad</th>
                                <th>Portainjerto</th>
                                <th class="text-end" style="width: 100px">Superficie</th>
                                <th style="width: 130px">Kilos/ha</th>
                                <th class="text-end" style="width: 120px">Kilos Total</th>
                                <th style="width: 200px">Observaciones</th>
                                <th style="width: 60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.ccvId"
                                :class="{
                                    'table-success': !row.isExisting && row.kilos,
                                    'table-warning': row.isExisting && row.isModified
                                }">
                                <td>
                                    <span v-if="!row.isExisting && row.kilos" class="badge bg-success">Nuevo</span>
                                    <span v-else-if="row.isExisting && row.isModified" class="badge bg-warning text-dark">Modificado</span>
                                    <span v-else-if="row.isExisting" class="badge bg-info">Guardado</span>
                                    <span v-else class="badge bg-secondary">Vacío</span>
                                </td>
                                <td>{{ row.costCenterName }}</td>
                                <td>{{ row.branchName }}</td>
                                <td>{{ row.varietyName }}</td>
                                <td>{{ row.rootstockName }}</td>
                                <td class="text-end">{{ row.surface ? row.surface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) : '-' }}</td>
                                <td>
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        :value="row.kilos"
                                        @input="updateKilos(row.ccvId, $event.target.value)"
                                        min="0"
                                        placeholder="0"
                                        :class="{
                                            'border-warning border-2': row.isExisting && row.isModified,
                                            'border-success border-2': !row.isExisting && row.kilos
                                        }"
                                    />
                                </td>
                                <td class="text-end fw-bold">{{ row.kilosTotal ? row.kilosTotal.toLocaleString('es-CL') : '-' }}</td>
                                <td>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        :value="row.observation"
                                        @input="updateObservation(row.ccvId, $event.target.value)"
                                        placeholder="Opcional"
                                        :class="{
                                            'border-warning border-2': row.isExisting && row.isModified
                                        }"
                                    />
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="row.isExisting"
                                        type="button"
                                        @click="handleDelete(row)"
                                        class="btn btn-sm btn-light-danger p-1"
                                        v-tooltip="'Eliminar'"
                                    >
                                        <i class="fas fa-trash-alt fa-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="5" class="text-end">Totales:</td>
                                <td class="text-end">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }}</td>
                                <td class="text-end">{{ averageKilosHa.toLocaleString('es-CL') }} prom.</td>
                                <td class="text-end">{{ totalKilos.toLocaleString('es-CL') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Resumen y botón guardar inferior -->
                <div class="mt-3 d-flex justify-content-between align-items-center" v-if="rows.length > 0">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        <span v-if="countNewRows > 0 || countModifiedRows > 0">
                            Se guardarán <strong>{{ countNewRows }}</strong> nuevo(s) y <strong>{{ countModifiedRows }}</strong> modificado(s)
                        </span>
                        <span v-else>No hay cambios para guardar</span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-falcon-default btn-sm"
                        @click="handleSave"
                        :disabled="countNewRows === 0 && countModifiedRows === 0"
                    >
                        <i class="fas fa-save me-1"></i> Guardar todo
                        <span v-if="countNewRows + countModifiedRows > 0" class="badge bg-primary ms-2">{{ countNewRows + countModifiedRows }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para crear nombre de estimación -->
        <div v-if="showStatusModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-body-tertiary">
                        <h5 class="modal-title"><i class="fas fa-tag me-2"></i>Nuevo nombre de estimación</h5>
                        <button type="button" class="btn-close" @click="showStatusModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre</label>
                            <input v-model="nuevoNombre" class="form-control" placeholder="Ej: Estimación Marzo" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Especie</label>
                            <Multiselect
                                v-model="nuevoFruitId"
                                :options="fruitOptions"
                                placeholder="Seleccione especie..."
                                :searchable="true"
                                :can-clear="false"
                                class="multiselect-blue"
                            />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" @click="showStatusModal = false">Cancelar</button>
                        <button class="btn btn-falcon-default btn-sm" @click="guardarEstimateStatus" :disabled="!nuevoNombre || !nuevoFruitId">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.multiselect-blue {
    --ms-tag-bg: #DBEAFE;
    --ms-tag-color: #1E40AF;
    --ms-ring-color: #93C5FD40;
    --ms-radius: 0.375rem;
    --ms-py: 0.3rem;
    --ms-px: 0.75rem;
    --ms-option-bg-selected: #2563EB;
    --ms-option-bg-selected-pointed: #1D4ED8;
    --ms-font-size: 0.85rem;
    --ms-line-height: 1.3;
}
</style>
