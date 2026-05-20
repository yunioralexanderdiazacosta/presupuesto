<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import axios from 'axios';

const page = usePage();
const varieties       = computed(() => page.props.varieties       || []);
const summaries       = computed(() => page.props.summaries       || []);
const fruits          = computed(() => page.props.fruits          || []);
const surfaceData       = computed(() => page.props.surfaceData       || []);
const developmentStates = computed(() => page.props.developmentStates || []);
const productionsList   = ref(page.props.productions || []);

// â”€â”€ Opciones de especie â”€â”€
const fruitOptions = computed(() =>
    fruits.value.map(f => ({ value: f.id, label: f.name }))
);
const selectedFruitId  = ref(fruitOptions.value.length ? fruitOptions.value[0].value : '');


const selectedVarietyId = ref('');

// -- Tabla editable --
const harvestedInputs    = ref({});
const exportedInputs     = ref({});
const netKiloInputs      = ref({});
const commercialCostInputs = ref({});
const observationsInputs = ref({});
const modifiedRows       = ref({});

// -- Ajustes globales de producción (descuentos / abonos) --
const discountsInput      = ref([]);   // [ { name: '', amount: 0 }, ... ]
const advancesInput       = ref([]);   // [ { name: '', amount: 0 }, ... ]
const notesInput          = ref('');
const isSavingAdjustments = ref(false);
const showRetornoDetail = ref(false);

function addAdvance()         { advancesInput.value.push({ name: '', amount: 0 }); }
function removeAdvance(idx)   { advancesInput.value.splice(idx, 1); }
function addDiscount()        { discountsInput.value.push({ name: '', amount: 0 }); }
function removeDiscount(idx)  { discountsInput.value.splice(idx, 1); }

function openAdjustmentsModal() {
    const prod = currentProduction.value;
    const all  = prod?.advances || [];
    discountsInput.value = all.filter(a => a.type === 'discount').map(a => ({ name: a.name, amount: Number(a.amount) }));
    advancesInput.value  = all.filter(a => a.type === 'advance' || !a.type).map(a => ({ name: a.name, amount: Number(a.amount) }));
    notesInput.value     = prod ? (prod.notes || '') : '';
    $('#adjustmentsModal').modal('show');
}

function markAsModified(varId) { modifiedRows.value[varId] = true; }
function parseKg(value) {
    const raw = String(value).replace(/\./g, '').replace(/,/g, '.');
    const num = parseFloat(raw);
    return isNaN(num) ? '' : num;
}
function formatKg(value) {
    const num = parseFloat(value);
    if (isNaN(num) || num === 0) return '';
    return num.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
function updateHarvested(varId, value)  { harvestedInputs.value[varId]  = parseKg(value); markAsModified(varId); }
function updateExported(varId, value)   { exportedInputs.value[varId]   = parseKg(value); markAsModified(varId); }
function updateNetKilo(varId, value)    { netKiloInputs.value[varId]    = value; markAsModified(varId); }
function updateCommercialCost(varId, value) { commercialCostInputs.value[varId] = value; markAsModified(varId); }
function updateObservation(varId, value){ observationsInputs.value[varId] = value; markAsModified(varId); }

watch(selectedFruitId, () => {
    harvestedInputs.value    = {};
    exportedInputs.value     = {};
    netKiloInputs.value      = {};
    commercialCostInputs.value = {};
    observationsInputs.value = {};
    modifiedRows.value       = {};
    selectedVarietyId.value  = '';
    // Solo sincronizar nota (descuentos/abonos se cargan al abrir el modal)
    const prod = productionsList.value.find(p => String(p.fruit_id) === String(selectedFruitId.value));
    notesInput.value = prod ? (prod.notes || '') : '';
});

// -- Computeds para producción actual (cabecera) --
const currentProduction = computed(() =>
    productionsList.value.find(p => String(p.fruit_id) === String(selectedFruitId.value)) || null
);
const savedTotalDiscounts = computed(() => {
    if (!currentProduction.value?.advances) return 0;
    return currentProduction.value.advances
        .filter(a => a.type === 'discount')
        .reduce((s, a) => s + Number(a.amount), 0);
});
const netReturnAfterDiscount = computed(() => totalEstReturn.value - savedTotalDiscounts.value);
const savedTotalAdvances = computed(() => {
    if (!currentProduction.value?.advances) return 0;
    return currentProduction.value.advances
        .filter(a => a.type === 'advance' || !a.type)
        .reduce((s, a) => s + Number(a.amount), 0);
});
const pendingBalance = computed(() => totalEstReturn.value + savedTotalAdvances.value - savedTotalDiscounts.value);

const varietyOptions = computed(() => {
    if (!selectedFruitId.value) return [];
    return varieties.value
        .filter(v => v.fruit_id != selectedFruitId.value ? false : true)
        .map(v => ({ value: v.id, label: v.name }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

const rows = computed(() => {
    if (!selectedFruitId.value) return [];

    return varieties.value
        .filter(v => {
            if (v.fruit_id != selectedFruitId.value) return false;
            if (selectedVarietyId.value && String(v.id) !== String(selectedVarietyId.value)) return false;
            return true;
        })
        .map(v => {
            const summary = summaries.value.find(s => s.variety_id == v.id);

            const currentHarvested = harvestedInputs.value[v.id] !== undefined
                ? harvestedInputs.value[v.id]
                : (summary && summary.kg_harvested ? parseFloat(Number(summary.kg_harvested).toFixed(2)) : '');

            const currentExported = exportedInputs.value[v.id] !== undefined
                ? exportedInputs.value[v.id]
                : (summary && summary.kg_exported ? parseFloat(Number(summary.kg_exported).toFixed(2)) : '');

            const currentObs = observationsInputs.value[v.id] !== undefined
                ? observationsInputs.value[v.id]
                : (summary ? summary.observations || '' : '');

            const rawNetKilo = summary ? (summary.net_kilo ?? '') : '';
            const currentNetKilo = netKiloInputs.value[v.id] !== undefined
                ? netKiloInputs.value[v.id]
                : (rawNetKilo !== '' ? parseFloat(rawNetKilo).toFixed(4) : '');

            const rawCommercialCost = summary ? (summary.commercial_cost_per_kg ?? '') : '';
            const currentCommercialCost = commercialCostInputs.value[v.id] !== undefined
                ? commercialCostInputs.value[v.id]
                : (rawCommercialCost !== '' && rawCommercialCost !== 0 ? parseFloat(rawCommercialCost).toFixed(2) : '');

            const harvestedNum   = currentHarvested ? Number(currentHarvested) : 0;
            const exportedNum    = currentExported  ? Number(currentExported)  : 0;
            const netKiloNum     = currentNetKilo   ? Number(currentNetKilo)   : 0;
            const commercialKg   = harvestedNum - exportedNum;
            const commercialCostNum = currentCommercialCost ? Number(currentCommercialCost) : 0;
            const exportPct      = harvestedNum > 0 ? Math.round((exportedNum / harvestedNum) * 100) : 0;
            const exportReturn   = exportedNum > 0 && netKiloNum > 0 ? exportedNum * netKiloNum : 0;
            const commercialDiscount = commercialKg > 0 && commercialCostNum > 0 ? commercialKg * commercialCostNum : 0;
            const estimatedReturn = exportReturn - commercialDiscount;
            const surface = surfaceData.value
                    .filter(d => String(d.variety_id) === String(v.id))
                    .reduce((s, d) => s + Number(d.total_surface), 0);

            return {
                id:              summary ? summary.id : null,
                varId:           v.id,
                varietyName:     v.name,
                surface,
                harvested:       currentHarvested,
                exported:        currentExported,
                netKilo:         currentNetKilo,
                commercialKg,
                commercialCost:  currentCommercialCost,
                exportReturn,
                commercialTotal: commercialDiscount,
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
const totalExportReturn = computed(() => rows.value.reduce((s, r) => s + (r.exportReturn || 0), 0));
const totalCommercialKg = computed(() => rows.value.reduce((s, r) => s + (r.commercialKg > 0 ? r.commercialKg : 0), 0));
const totalCommercialTotal = computed(() => rows.value.reduce((s, r) => s + (r.commercialTotal > 0 ? r.commercialTotal : 0), 0));
const globalExportPct   = computed(() => totalHarvested.value > 0 ? Math.round((totalExported.value / totalHarvested.value) * 100) : 0);
const avgKgPerHa        = computed(() => {
    const activeRows = rows.value.filter(r => r.harvested && Number(r.harvested) > 0);
    const activeSurface = activeRows.reduce((s, r) => s + r.surface, 0);
    const activeHarvested = activeRows.reduce((s, r) => s + Number(r.harvested), 0);
    return activeSurface > 0 ? Math.round(activeHarvested / activeSurface) : 0;
});
const returnPerKgExported = computed(() => totalExported.value > 0 ? totalExportReturn.value / totalExported.value : 0);
const returnPerKgHarvested = computed(() => totalHarvested.value > 0 ? totalExportReturn.value / totalHarvested.value : 0);

// â”€â”€ Guardar â”€â”€
async function handleSave() {
    const newRecords = rows.value
        .filter(r => !r.isExisting && r.harvested && r.harvested !== '')
        .map(r => ({
            variety_id:    r.varId,
            kg_harvested:  r.harvested,
            kg_exported:   r.exported || 0,
            net_kilo:      r.netKilo || null,
            commercial_cost_per_kg: r.commercialCost || 0,
            observations:  r.observation || '',
        }));

    const modifiedRecords = rows.value
        .filter(r => r.isExisting && r.isModified)
        .map(r => ({
            id:           r.id,
            kg_harvested: r.harvested,
            kg_exported:  r.exported || 0,
            net_kilo:     r.netKilo || null,
            commercial_cost_per_kg: r.commercialCost || 0,
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
        commercialCostInputs.value = {};
        observationsInputs.value = {};
        modifiedRows.value       = {};
        Swal.fire({ icon: 'success', title: 'Guardado correctamente', text: `${newRecords.length} nuevo(s), ${modifiedRecords.length} modificado(s)`, showConfirmButton: false, timer: 2000 });
        router.reload({ only: ['summaries', 'productions'] });
    }
}

// -- Guardar ajustes globales (descuento / abono) --
async function handleSaveAdjustments() {
    if (!selectedFruitId.value) return;
    isSavingAdjustments.value = true;
    try {
        const response = await axios.post(route('productions.adjust'), {
            fruit_id:  selectedFruitId.value,
            notes:     notesInput.value || null,
            advances:  advancesInput.value.filter(a => a.name.trim() !== ''),
            discounts: discountsInput.value.filter(a => a.name.trim() !== ''),
        });
        // Actualizar la lista local de productions
        const updated = response.data.production;
        const idx = productionsList.value.findIndex(p => String(p.fruit_id) === String(updated.fruit_id));
        if (idx >= 0) {
            productionsList.value[idx] = updated;
        } else {
            productionsList.value.push(updated);
        }
        $('#adjustmentsModal').modal('hide');
        Swal.fire({ icon: 'success', title: 'Ajustes guardados', showConfirmButton: false, timer: 1200 });
    } catch (error) {
        Swal.fire('Error', 'No se pudieron guardar los ajustes.', 'error');
    } finally {
        isSavingAdjustments.value = false;
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
    { label: 'Kg Comerciales',      key: 'commercialKg',    type: 'number' },
    { label: 'Net Kilo (USD)',      key: 'netKilo',         type: 'number' },
    { label: 'Costo/Kg Com. (USD)', key: 'commercialCost',  type: 'number' },
    { label: 'Ingreso Neto (USD)',   key: 'exportReturn',    type: 'number' },
    { label: 'Total Com. (USD)',    key: 'commercialTotal', type: 'number' },
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
        commercialKg:    r.commercialKg || 0,
        netKilo:         r.netKilo   !== '' && r.netKilo   !== undefined ? Number(r.netKilo)   : 0,
        commercialCost:  r.commercialCost !== '' && r.commercialCost !== undefined ? Number(r.commercialCost) : 0,
        exportReturn:    r.exportReturn || 0,
        commercialTotal: r.commercialTotal || 0,
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
                            <button v-if="selectedFruitId" class="btn btn-falcon-default btn-sm" @click="openAdjustmentsModal">
                                <i class="fas fa-sliders-h me-1"></i> Ajustes
                                <span v-if="currentProduction?.advances?.length" class="badge bg-secondary ms-1">
                                    {{ currentProduction.advances.length }}
                                </span>
                            </button>
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
                    <div class="col-md-3">
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
                            <i class="fas fa-grape me-1"></i> Variedad
                        </label>
                        <select v-model="selectedVarietyId" class="form-select form-select-sm">
                            <option value="">Todas las variedades</option>
                            <option v-for="v in varietyOptions" :key="v.value" :value="v.value">{{ v.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-auto ms-auto d-flex align-items-end">
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
                                <div class="text-muted small">Retorno</div>
                                <div class="fs-7 fw-bold text-success">USD {{ totalEstReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-info">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Retorno / Kg Exportado</div>
                                <div class="fs-7 fw-bold text-info">USD {{ returnPerKgExported.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 p-1 border border-info">
                            <div class="card-body py-2 px-3 text-center">
                                <div class="text-muted small">Retorno / Kg Producido</div>
                                <div class="fs-7 fw-bold text-info">USD {{ returnPerKgHarvested.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                            </div>
                        </div>
                    </div>
                    <!-- KPI Retorno neto (solo si hay ajustes guardados) -->
                    <template v-if="currentProduction && currentProduction.advances && currentProduction.advances.length > 0">
                        <div class="col">
                            <div class="card h-100 p-1 border" :class="pendingBalance >= 0 ? 'border-success' : 'border-danger'" style="border-width: 2px !important; min-width: 160px;">
                                <div class="card-body py-2 px-3">
                                    <!-- Header clicable -->
                                    <div class="d-flex justify-content-between align-items-center" style="cursor:pointer;" @click="showRetornoDetail = !showRetornoDetail">
                                        <span class="text-muted small fw-semibold">Retorno Estimado</span>
                                        <i class="fas fa-xs ms-1" :class="showRetornoDetail ? 'fa-chevron-up' : 'fa-chevron-down'" style="color:#aaa;"></i>
                                    </div>
                                    <!-- Desglose colapsable -->
                                    <div v-show="showRetornoDetail" class="mt-1">
                                        <div class="d-flex justify-content-between small">
                                            <span class="text-muted">Base</span>
                                            <span class="fw-semibold text-success">USD {{ totalEstReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                        </div>
                                        <div v-if="savedTotalDiscounts > 0" class="d-flex justify-content-between small">
                                            <span class="text-muted">(-) Descuentos</span>
                                            <span class="text-danger">USD {{ savedTotalDiscounts.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                        </div>
                                        <div v-if="savedTotalAdvances > 0" class="d-flex justify-content-between small">
                                            <span class="text-muted">(+) Abonos</span>
                                            <span class="fw-semibold text-success">USD {{ savedTotalAdvances.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between border-top mt-1 pt-1">
                                        <span class="small fw-bold">Neto</span>
                                        <span class="fw-bold" style="font-size: 0.95rem;" :class="pendingBalance >= 0 ? 'text-success' : 'text-danger'">
                                            USD {{ pendingBalance.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
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
                        <thead style="background-color: #e8f4f8;">
                            <tr>
                                <th style="width: 90px">Estado</th>
                                <th>Variedad</th>
                                <th class="text-end" style="width: 110px">Superficie (ha)</th>
                                <th style="width: 150px">Kg Cosechados</th>
                                <th style="width: 150px">Kg Exportados</th>
                                <th class="text-end" style="width: 100px">% Export.</th>
                                <th class="text-end" style="width: 130px">Kg Comerciales</th>
                                <th class="text-end" style="width: 110px">Net Kilo (USD)</th>
                                <th style="width: 130px">Costo/Kg Com. (USD)</th>
                                <th class="text-end" style="width: 140px">Ingreso Neto (USD)</th>
                                <th class="text-end" style="width: 140px">Total Com. (USD)</th>
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
                                    <input type="text" class="form-control form-control-sm text-end" :value="formatKg(row.harvested)"
                                        @input="updateHarvested(row.varId, $event.target.value)" placeholder="0"
                                        @blur="$event.target.value = formatKg(row.harvested)"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-end" :value="formatKg(row.exported)"
                                        @input="updateExported(row.varId, $event.target.value)" placeholder="0"
                                        @blur="$event.target.value = formatKg(row.exported)"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.exportPct > 0">{{ row.exportPct }}%</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.commercialKg > 0">{{ row.commercialKg.toLocaleString('es-CL') }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" :value="row.netKilo"
                                        @input="updateNetKilo(row.varId, $event.target.value)" min="0" step="0.0001" placeholder="0.0000"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" :value="row.commercialCost"
                                        @input="updateCommercialCost(row.varId, $event.target.value)" min="0" step="0.01" placeholder="0.00"
                                        :class="{ 'border-warning border-2': row.isExisting && row.isModified, 'border-success border-2': !row.isExisting && row.harvested }" />
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.exportReturn > 0" class="text-success">USD {{ row.exportReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="text-end fw-bold">
                                    <span v-if="row.commercialTotal > 0" class="text-danger">USD {{ row.commercialTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</span>
                                    <span v-else class="text-muted">-</span>
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
                                <td class="text-end">{{ totalCommercialKg.toLocaleString('es-CL') }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-end text-success fw-bold">USD {{ totalExportReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                <td class="text-end text-danger fw-bold">USD {{ totalCommercialTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                <td class="text-end text-success fw-bold">USD {{ totalEstReturn.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                <td colspan="2"></td>
                            </tr>
                            <!-- Filas de ajuste global (descuento / abono) -->
                            <template v-if="currentProduction && (Number(currentProduction.discount) > 0 || Number(currentProduction.advance) > 0)">
                                <tr v-if="Number(currentProduction.discount) > 0" class="table-danger">
                                    <td colspan="11" class="text-end text-danger small">(-) Descuento global:</td>
                                    <td class="text-end text-danger fw-bold">- USD {{ Number(currentProduction.discount).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr v-if="Number(currentProduction.advance) > 0" class="table-primary">
                                    <td colspan="11" class="text-end text-primary small">(+) Abono ya recibido:</td>
                                    <td class="text-end text-primary fw-bold">USD {{ Number(currentProduction.advance).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="11" class="text-end fw-bold small">Saldo Pendiente:</td>
                                    <td class="text-end fw-bold" :class="pendingBalance >= 0 ? 'text-success' : 'text-danger'">
                                        USD {{ pendingBalance.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </template>
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

    <!-- Modal: Ajustes globales de producción -->
    <div class="modal fade" id="adjustmentsModal" tabindex="-1" aria-labelledby="adjustmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);">
                    <h6 class="modal-title" id="adjustmentsModalLabel">
                        <i class="fas fa-sliders-h me-2 text-secondary"></i>
                        Ajustes globales de producción
                        <small class="text-muted ms-2" v-if="selectedFruitId">— {{ fruitOptions.find(f => String(f.value) === String(selectedFruitId))?.label }}</small>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-2">

                    <!-- Notas generales -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Notas generales</label>
                        <input type="text" v-model="notesInput" class="form-control form-control-sm"
                            placeholder="Observaciones generales..." maxlength="500" />
                    </div>

                    <div class="row g-3">
                        <!-- Columna Descuentos -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="fw-semibold text-danger">
                                    <i class="fas fa-minus-circle me-1"></i>Descuentos
                                </small>
                                <button type="button" @click="addDiscount"
                                    class="btn btn-sm btn-outline-danger py-0 px-2">
                                    <i class="fas fa-plus fa-xs me-1"></i> Agregar
                                </button>
                            </div>
                            <div v-if="discountsInput.length === 0" class="text-muted small text-center py-2 border rounded">
                                Sin descuentos.
                            </div>
                            <div v-for="(disc, idx) in discountsInput" :key="idx"
                                class="d-flex gap-1 align-items-center mb-1">
                                <input type="text" v-model="disc.name"
                                    class="form-control form-control-sm flex-grow-1"
                                    placeholder="Ej: Descuento envases" />
                                <div class="input-group input-group-sm" style="max-width:130px">
                                    <span class="input-group-text py-0">USD</span>
                                    <input type="number" v-model.number="disc.amount" min="0" step="0.01"
                                        class="form-control form-control-sm" placeholder="0" />
                                </div>
                                <button type="button" @click="removeDiscount(idx)"
                                    class="btn btn-sm btn-outline-danger py-0 px-1">
                                    <i class="fas fa-times fa-xs"></i>
                                </button>
                            </div>
                            <div v-if="discountsInput.length > 0" class="text-end small text-danger border-top mt-1 pt-1">
                                Total: <strong>USD {{ discountsInput.reduce((s, a) => s + (Number(a.amount) || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong>
                            </div>
                        </div>

                        <!-- Columna Abonos -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="fw-semibold text-primary">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Abonos recibidos
                                </small>
                                <button type="button" @click="addAdvance"
                                    class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <i class="fas fa-plus fa-xs me-1"></i> Agregar
                                </button>
                            </div>
                            <div v-if="advancesInput.length === 0" class="text-muted small text-center py-2 border rounded">
                                Sin abonos.
                            </div>
                            <div v-for="(adv, idx) in advancesInput" :key="idx"
                                class="d-flex gap-1 align-items-center mb-1">
                                <input type="text" v-model="adv.name"
                                    class="form-control form-control-sm flex-grow-1"
                                    placeholder="Ej: Abono camión" />
                                <div class="input-group input-group-sm" style="max-width:130px">
                                    <span class="input-group-text py-0">USD</span>
                                    <input type="number" v-model.number="adv.amount" min="0" step="0.01"
                                        class="form-control form-control-sm" placeholder="0" />
                                </div>
                                <button type="button" @click="removeAdvance(idx)"
                                    class="btn btn-sm btn-outline-danger py-0 px-1">
                                    <i class="fas fa-times fa-xs"></i>
                                </button>
                            </div>
                            <div v-if="advancesInput.length > 0" class="text-end small text-primary border-top mt-1 pt-1">
                                Total: <strong>USD {{ advancesInput.reduce((s, a) => s + (Number(a.amount) || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="handleSaveAdjustments" :disabled="isSavingAdjustments">
                        <i class="fas fa-save me-1" :class="{ 'fa-spin': isSavingAdjustments }"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
