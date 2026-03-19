<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const page = usePage();
const varieties       = computed(() => page.props.varieties       || []);
const summaries       = computed(() => page.props.summaries       || []);
const fruits          = computed(() => page.props.fruits          || []);
const surfaceData       = computed(() => page.props.surfaceData       || []);
const developmentStates = computed(() => page.props.developmentStates || []);

// â”€â”€ Opciones de especie â”€â”€
const fruitOptions = computed(() =>
    fruits.value.map(f => ({ value: f.id, label: f.name }))
);
const selectedFruitId  = ref(fruitOptions.value.length ? fruitOptions.value[0].value : '');

const devStateOptions  = computed(() => developmentStates.value);
const selectedDevStateId = ref('');

// â”€â”€ Tabla editable â”€â”€
const harvestedInputs    = ref({});
const exportedInputs     = ref({});
const netKiloInputs      = ref({});
const observationsInputs = ref({});
const modifiedRows       = ref({});

function markAsModified(varId) { modifiedRows.value[varId] = true; }
function updateHarvested(varId, value)  { harvestedInputs.value[varId]  = value; markAsModified(varId); }
function updateExported(varId, value)   { exportedInputs.value[varId]   = value; markAsModified(varId); }
function updateNetKilo(varId, value)    { netKiloInputs.value[varId]    = value; markAsModified(varId); }
function updateObservation(varId, value){ observationsInputs.value[varId] = value; markAsModified(varId); }

watch(selectedFruitId, () => {
    harvestedInputs.value    = {};
    exportedInputs.value     = {};
    netKiloInputs.value      = {};
    observationsInputs.value = {};
    modifiedRows.value       = {};
});

watch(selectedDevStateId, () => {
    harvestedInputs.value    = {};
    exportedInputs.value     = {};
    netKiloInputs.value      = {};
    observationsInputs.value = {};
    modifiedRows.value       = {};
});

const rows = computed(() => {
    if (!selectedFruitId.value) return [];

    return varieties.value
        .filter(v => {
            if (v.fruit_id != selectedFruitId.value) return false;
            if (selectedDevStateId.value) {
                return surfaceData.value.some(
                    d => String(d.variety_id) === String(v.id) && String(d.development_state_id) === String(selectedDevStateId.value)
                );
            }
            return true;
        })
        .map(v => {
            const summary = summaries.value.find(s => s.variety_id == v.id);

            const currentHarvested = harvestedInputs.value[v.id] !== undefined
                ? harvestedInputs.value[v.id]
                : (summary ? summary.kg_harvested : '');

            const currentExported = exportedInputs.value[v.id] !== undefined
                ? exportedInputs.value[v.id]
                : (summary ? (summary.kg_exported ?? '') : '');

            const currentObs = observationsInputs.value[v.id] !== undefined
                ? observationsInputs.value[v.id]
                : (summary ? summary.observations || '' : '');

            const currentNetKilo = netKiloInputs.value[v.id] !== undefined
                ? netKiloInputs.value[v.id]
                : (summary ? (summary.net_kilo ?? '') : '');

            const harvestedNum   = currentHarvested ? Number(currentHarvested) : 0;
            const exportedNum    = currentExported  ? Number(currentExported)  : 0;
            const netKiloNum     = currentNetKilo   ? Number(currentNetKilo)   : 0;
            const exportPct      = harvestedNum > 0 ? Math.round((exportedNum / harvestedNum) * 100) : 0;
            const estimatedReturn = exportedNum > 0 && netKiloNum > 0 ? exportedNum * netKiloNum : 0;
            const surface = (() => {
                if (selectedDevStateId.value) {
                    const item = surfaceData.value.find(
                        d => String(d.variety_id) === String(v.id) && String(d.development_state_id) === String(selectedDevStateId.value)
                    );
                    return item ? Number(item.total_surface) : 0;
                }
                return surfaceData.value
                    .filter(d => String(d.variety_id) === String(v.id))
                    .reduce((s, d) => s + Number(d.total_surface), 0);
            })();

            return {
                id:              summary ? summary.id : null,
                varId:           v.id,
                varietyName:     v.name,
                surface,
                harvested:       currentHarvested,
                exported:        currentExported,
                netKilo:         currentNetKilo,
                estimatedReturn,
                observation:     currentObs,
                exportPct,
                isExisting:      !!summary,
                isModified:      !!modifiedRows.value[v.id],
            };
        })
        .sort((a, b) => b.surface - a.surface || a.varietyName.localeCompare(b.varietyName));
});

// â”€â”€ Contadores â”€â”€
const countNewRows      = computed(() => rows.value.filter(r => !r.isExisting && r.harvested && r.harvested !== '').length);
const countModifiedRows = computed(() => rows.value.filter(r => r.isExisting && r.isModified).length);

// â”€â”€ KPIs â”€â”€
const totalHarvested    = computed(() => rows.value.reduce((s, r) => s + (r.harvested ? Number(r.harvested) : 0), 0));
const totalExported     = computed(() => rows.value.reduce((s, r) => s + (r.exported  ? Number(r.exported)  : 0), 0));
const totalSurface      = computed(() => rows.value.reduce((s, r) => s + r.surface, 0));
const totalEstReturn    = computed(() => rows.value.reduce((s, r) => s + r.estimatedReturn, 0));
const globalExportPct   = computed(() => totalHarvested.value > 0 ? Math.round((totalExported.value / totalHarvested.value) * 100) : 0);
const avgKgPerHa        = computed(() => {
    const activeRows = rows.value.filter(r => r.harvested && Number(r.harvested) > 0);
    const activeSurface = activeRows.reduce((s, r) => s + r.surface, 0);
    const activeHarvested = activeRows.reduce((s, r) => s + Number(r.harvested), 0);
    return activeSurface > 0 ? Math.round(activeHarvested / activeSurface) : 0;
});

// â”€â”€ Guardar â”€â”€
async function handleSave() {
    const newRecords = rows.value
        .filter(r => !r.isExisting && r.harvested && r.harvested !== '')
        .map(r => ({
            variety_id:    r.varId,
            kg_harvested:  r.harvested,
            kg_exported:   r.exported || 0,
            net_kilo:      r.netKilo || null,
            observations:  r.observation || '',
        }));

    const modifiedRecords = rows.value
        .filter(r => r.isExisting && r.isModified)
        .map(r => ({
            id:           r.id,
            kg_harvested: r.harvested,
            kg_exported:  r.exported || 0,
            net_kilo:     r.netKilo || null,
            observations: r.observation || '',
        }));

    if (!newRecords.length && !modifiedRecords.length) return;

    let hasErrors = false;

    if (newRecords.length > 0) {
        await new Promise(resolve => {
            router.post(route('production-summaries.store'), newRecords, {
                preserveState: true, preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => {
                    hasErrors = true;
                    Swal.fire({ icon: 'error', title: 'Error al guardar', text: errors.error || 'Revisa los datos.', showConfirmButton: true });
                    resolve();
                },
            });
        });
    }

    if (modifiedRecords.length > 0) {
        for (const record of modifiedRecords) {
            await new Promise(resolve => {
                router.post(`/production-summaries/${record.id}/update`, record, {
                    preserveState: true, preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: () => { hasErrors = true; Swal.fire({ icon: 'error', title: 'Error al actualizar', showConfirmButton: true }); resolve(); },
                });
            });
        }
    }

    if (!hasErrors) {
        harvestedInputs.value    = {};
        exportedInputs.value     = {};
        netKiloInputs.value      = {};
        observationsInputs.value = {};
        modifiedRows.value       = {};
        Swal.fire({ icon: 'success', title: 'Guardado correctamente', text: `${newRecords.length} nuevo(s), ${modifiedRecords.length} modificado(s)`, showConfirmButton: false, timer: 2000 });
        router.reload({ only: ['summaries'] });
    }
}

// â”€â”€ Eliminar â”€â”€
function handleDelete(row) {
    if (!row.id) return;
    Swal.fire({ title: '¿Eliminar este resumen?', text: row.varietyName, icon: 'warning', showCancelButton: true, confirmButtonText: 'SÃ­, eliminar', cancelButtonText: 'Cancelar' })
        .then(result => {
            if (result.isConfirmed) {
                router.delete(`/production-summaries/${row.id}/delete`, {
                    preserveState: true, preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({ icon: 'success', title: 'Eliminado', showConfirmButton: false, timer: 1200 });
                        router.reload({ only: ['summaries'] });
                    },
                });
            }
        });
}

// â”€â”€ Excel â”€â”€
const excelHeaders = [
    { label: 'Variedad',           key: 'varietyName' },
    { label: 'Superficie (ha)',    key: 'surface',         type: 'number' },
    { label: 'Kg Cosechados',      key: 'harvested',       type: 'number' },
    { label: 'Kg Exportados',      key: 'exported',        type: 'number' },
    { label: '% Exportación',      key: 'exportPct',       type: 'number' },
    { label: 'Net Kilo (USD)',      key: 'netKilo',         type: 'number' },
    { label: 'Retorno Est. (USD)',  key: 'estimatedReturn', type: 'number' },
    { label: 'Observaciones',      key: 'observation' },
];
const excelData = computed(() =>
    rows.value.map(r => ({
        varietyName:     r.varietyName,
        surface:         r.surface || 0,
        harvested:       r.harvested !== '' && r.harvested !== undefined ? Number(r.harvested) : 0,
        exported:        r.exported  !== '' && r.exported  !== undefined ? Number(r.exported)  : 0,
        exportPct:       r.exportPct || 0,
        netKilo:         r.netKilo   !== '' && r.netKilo   !== undefined ? Number(r.netKilo)   : 0,
        estimatedReturn: r.estimatedReturn || 0,
        observation:     r.observation || '',
    }))
);
const excelFilename = computed(() => {
    const fruta = fruitOptions.value.find(f => String(f.value) === String(selectedFruitId.value))?.label || 'produccion';
    return `ResumenProduccion_${fruta}.xlsx`;
});
</script>

<template>
    <AppLayout title="Ingreso Rápido de Producción">
        <div class="card my-3">
            <div class="card-header" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-bottom: 2px solid #a5d6a7;">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-apple-alt me-2"></i>Ingreso Rápido de Producción
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Descripción del módulo -->
                <div class="alert alert-info border-0 mb-3 py-2">
                    <small>
                        <i class="fas fa-info-circle me-2"></i>
                        Registra de forma anticipada y simplificada los <strong>kg cosechados</strong> y <strong>kg exportados</strong>
                        por variedad, sin necesidad de ingresar detalle por cuartel. Ideal para tener un avance rápido de la producción de la temporada.
                    </small>
                </div>
                <!-- Filtros + botón guardar -->
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">
                            <i class="fas fa-seedling me-1"></i> Especie
                        </label>
                        <select v-model="selectedFruitId" class="form-select form-select-sm">
                            <option value="" disabled>Seleccione especie...</option>
                            <option v-for="f in fruitOptions" :key="f.value" :value="f.value">{{ f.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">
                            <i class="fas fa-leaf me-1"></i> Estado de Desarrollo
                        </label>
                        <select v-model="selectedDevStateId" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option v-for="s in devStateOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                        <button class="btn btn-falcon-default btn-sm" @click="handleSave" :disabled="countNewRows === 0 && countModifiedRows === 0">
                            <i class="fas fa-save me-1"></i> Guardar
                            <span v-if="countNewRows + countModifiedRows > 0" class="badge bg-primary ms-1">{{ countNewRows + countModifiedRows }}</span>
                        </button>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row mb-3 g-2" v-if="rows.length > 0">
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Superficie Total</div>
                                <div class="fs-7 fw-bold">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }} ha</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Kg Cosechados</div>
                                <div class="fs-7 fw-bold">{{ totalHarvested.toLocaleString('es-CL') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Kg Exportados</div>
                                <div class="fs-7 fw-bold">{{ totalExported.toLocaleString('es-CL') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">% Exportación</div>
                                <div class="fs-7 fw-bold">{{ globalExportPct }}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Prom. Kg / ha</div>
                                <div class="fs-7 fw-bold">{{ avgKgPerHa.toLocaleString('es-CL') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-success">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Retorno Estimado</div>
                                <div class="fs-7 fw-bold text-success">USD {{ totalEstReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</div>
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

                <!-- Sin selección -->
                <div v-if="!selectedFruitId" class="alert alert-warning text-center">
                    <i class="fas fa-info-circle me-2"></i>Selecciona una <strong>especie</strong> para ver las variedades.
                </div>

                <!-- Sin variedades -->
                <div v-else-if="rows.length === 0" class="alert alert-info text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>No hay variedades registradas para esta especie en la temporada actual.
                </div>

                <!-- Tabla -->
                <div v-else class="table-responsive">
                    <table class="table table-bordered table-hover table-sm fs-10 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 90px">Estado</th>
                                <th>Variedad</th>
                                <th class="text-end" style="width: 110px">Superficie (ha)</th>
                                <th style="width: 150px">Kg Cosechados</th>
                                <th style="width: 150px">Kg Exportados</th>
                                <th class="text-end" style="width: 100px">% Export.</th>
                                <th class="text-end" style="width: 110px">Net Kilo (USD)</th>
                                <th class="text-end" style="width: 130px">Retorno Est. (USD)</th>
                                <th style="width: 220px">Observaciones</th>
                                <th style="width: 60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.varId"
                                :class="{ 'table-success': !row.isExisting && row.harvested, 'table-warning': row.isExisting && row.isModified }">
                                <td>
                                    <span v-if="!row.isExisting && row.harvested" class="badge bg-success">Nuevo</span>
                                    <span v-else-if="row.isExisting && row.isModified" class="badge bg-warning text-dark">Modificado</span>
                                    <span v-else-if="row.isExisting" class="badge bg-info">Guardado</span>
                                    <span v-else class="badge bg-secondary">Vacío</span>
                                </td>
                                <td class="fw-semibold">{{ row.varietyName }}</td>
                                <td class="text-end">{{ row.surface ? row.surface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) : '-' }}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" :value="row.harvested"
                                        @input="updateHarvested(row.varId, $event.target.value)" min="0" placeholder="0"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" :value="row.exported"
                                        @input="updateExported(row.varId, $event.target.value)" min="0" placeholder="0"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.exportPct > 0">{{ row.exportPct }}%</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" :value="row.netKilo"
                                        @input="updateNetKilo(row.varId, $event.target.value)" min="0" step="0.01" placeholder="0.00"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.estimatedReturn > 0" class="text-success">USD {{ row.estimatedReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" :value="row.observation"
                                        @input="updateObservation(row.varId, $event.target.value)" placeholder="Opcional"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified }" />
                                </td>
                                <td class="text-center">
                                    <button v-if="row.isExisting" type="button" @click="handleDelete(row)"
                                        class="btn btn-sm btn-light-danger p-1" v-tooltip="'Eliminar'">
                                        <i class="fas fa-trash-alt fa-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Totales:</td>
                                <td class="text-end">{{ totalSurface.toLocaleString('es-CL', { minimumFractionDigits: 2 }) }}</td>
                                <td class="text-end">{{ totalHarvested.toLocaleString('es-CL') }}</td>
                                <td class="text-end">{{ totalExported.toLocaleString('es-CL') }}</td>
                                <td class="text-end">{{ globalExportPct }}%</td>
                                <td></td>
                                <td class="text-end text-success fw-bold">USD {{ totalEstReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Botón guardar inferior -->
                <div class="mt-3 d-flex justify-content-end" v-if="rows.length > 0">
                    <button class="btn btn-falcon-default btn-sm" @click="handleSave" :disabled="countNewRows === 0 && countModifiedRows === 0">
                        <i class="fas fa-save me-1"></i> Guardar
                        <span v-if="countNewRows + countModifiedRows > 0" class="badge bg-primary ms-1">{{ countNewRows + countModifiedRows }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
