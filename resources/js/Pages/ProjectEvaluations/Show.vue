<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    evaluation:        Object,
    rows:              Array,
    varieties:         Array,
    fruits:            Array,
    rnpPrices:         Array,
    varietyCostParams: Array,
    kgYieldCosts:      Array,
});

// ─── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('composicion');
const showPerHa = ref(true);
const localTargetMargin = ref(Number(props.evaluation.target_margin) || 0);

// ─── Parámetros: colapsables ──────────────────────────────────────────────────
const openRnp = ref(false);
const openCostParams = ref(false);
const openKgLookup = ref(false);
const activeScenario = ref('base');
const scenarioLabels = { pessimistic: 'Pesimista', base: 'Base', optimistic: 'Optimista' };
const scenarioColors = { pessimistic: 'text-warning', base: 'text-primary', optimistic: 'text-success' };

// ─── Helpers ──────────────────────────────────────────────────────────────────
const weeks = [42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52];

const fmt = (n, dec = 0) =>
    n == null || isNaN(n) ? '—' : Number(n).toLocaleString('es-CL', { minimumFractionDigits: dec, maximumFractionDigits: dec });

const fmtUsd = (n, dec = 0) =>
    n == null || isNaN(n) ? '—' : 'US$ ' + Number(n).toLocaleString('es-CL', { minimumFractionDigits: dec, maximumFractionDigits: dec });

const varietyName = (id) => {
    const v = props.varieties.find(x => String(x.id) === String(id));
    return v ? v.name : `#${id}`;
};

// ═══════════════════════════════════════════════════════════════════════════════
// TAB PARÁMETROS
// ═══════════════════════════════════════════════════════════════════════════════

// ── RNP Prices (editable matrix: variety × week) ──────────────────────────────
// localRnp[variety_id][week] = price (editable number)
const buildRnpMatrix = () => {
    const matrix = {};
    props.varieties.forEach(v => {
        matrix[v.id] = {};
        weeks.forEach(w => { matrix[v.id][w] = ''; });
    });
    props.rnpPrices.forEach(r => {
        if (!matrix[r.variety_id]) matrix[r.variety_id] = {};
        matrix[r.variety_id][r.week] = Math.round(Number(r.price_usd) * 100) / 100;
    });
    return matrix;
};
const localRnp = ref(buildRnpMatrix());
watch(() => props.rnpPrices, () => { localRnp.value = buildRnpMatrix(); }, { deep: true });

const savingRnp = ref(false);
const saveRnpPrices = () => {
    savingRnp.value = true;
    const prices = [];
    props.varieties.forEach(v => {
        weeks.forEach(w => {
            const val = localRnp.value[v.id]?.[w];
            if (val !== '' && val !== null && val !== undefined) {
                prices.push({ variety_id: v.id, week: w, price_usd: Number(val) });
            }
        });
    });
    router.post(route('project-evaluations.rnp-prices.upsert'), { prices }, {
        preserveScroll: true,
        onSuccess: () => {
            savingRnp.value = false;
            activeTab.value = 'parametros';
            Swal.fire({ icon: 'success', title: 'RNP guardado', showConfirmButton: false, timer: 1000 });
        },
        onError: (errors) => {
            savingRnp.value = false;
            const msg = Object.values(errors).flat().join('\n');
            Swal.fire({ icon: 'error', title: 'Error al guardar RNP', text: msg || 'Revisa los campos.' });
        },
    });
};

// ── Variety Cost Params ───────────────────────────────────────────────────────
const buildCostParams = () => {
    const map = {};
    props.varieties.forEach(v => { map[v.id] = { pct_embalaje: '', precio_proceso: '' }; });
    props.varietyCostParams.forEach(p => {
        map[p.variety_id] = {
            pct_embalaje: Math.round(Number(p.pct_embalaje)),
            precio_proceso: Math.round(Number(p.precio_proceso) * 100) / 100,
        };
    });
    return map;
};
const localCostParams = ref(buildCostParams());
watch(() => props.varietyCostParams, () => { localCostParams.value = buildCostParams(); }, { deep: true });

const savingCostParams = ref(false);
const saveCostParams = () => {
    savingCostParams.value = true;
    const params = props.varieties
        .filter(v => {
            const c = localCostParams.value[v.id];
            return c && c.pct_embalaje !== '' && c.pct_embalaje !== null;
        })
        .map(v => ({
            variety_id:     v.id,
            pct_embalaje:   Number(localCostParams.value[v.id].pct_embalaje),
            precio_proceso: Number(localCostParams.value[v.id].precio_proceso) || 0,
        }));
    if (!params.length) {
        savingCostParams.value = false;
        Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'Ingresa al menos una variedad con valores.', showConfirmButton: true });
        return;
    }
    router.post(route('project-evaluations.variety-costs.upsert'), { params }, {
        preserveScroll: true,
        onSuccess: () => {
            savingCostParams.value = false;
            activeTab.value = 'parametros';
            Swal.fire({ icon: 'success', title: 'Parámetros guardados', showConfirmButton: false, timer: 1000 });
        },
        onError: (errors) => {
            savingCostParams.value = false;
            const msg = Object.values(errors).flat().join('\n');
            Swal.fire({ icon: 'error', title: 'Error al guardar', text: msg || 'Revisa los campos.' });
        },
    });
};

// ── Kg/Yield Costs ────────────────────────────────────────────────────────────
const localKgCosts = ref(props.kgYieldCosts.map(k => ({ kg_ha: k.kg_ha, cost_usd: Math.round(Number(k.cost_usd) * 100) / 100 })));
watch(() => props.kgYieldCosts, () => {
    localKgCosts.value = props.kgYieldCosts.map(k => ({ kg_ha: k.kg_ha, cost_usd: Math.round(Number(k.cost_usd) * 100) / 100 }));
}, { deep: true });

const addKgRow = () => localKgCosts.value.push({ kg_ha: '', cost_usd: '' });
const removeKgRow = (i) => localKgCosts.value.splice(i, 1);

const savingKgCosts = ref(false);
const saveKgCosts = () => {
    savingKgCosts.value = true;
    const costs = localKgCosts.value
        .filter(c => c.kg_ha !== '' && c.cost_usd !== '')
        .map(c => ({ kg_ha: Number(c.kg_ha), cost_usd: Number(c.cost_usd) }));
    router.post(route('project-evaluations.kg-yield-costs.upsert'), { costs }, {
        preserveScroll: true,
        onSuccess: () => {
            savingKgCosts.value = false;
            activeTab.value = 'parametros';
            Swal.fire({ icon: 'success', title: 'Costos guardados', showConfirmButton: false, timer: 1000 });
        },
        onError: (errors) => {
            savingKgCosts.value = false;
            const msg = Object.values(errors).flat().join('\n');
            Swal.fire({ icon: 'error', title: 'Error al guardar costos', text: msg || 'Revisa los campos.' });
        },
    });
};

// ═══════════════════════════════════════════════════════════════════════════════
// TAB COMPOSICIÓN
// ═══════════════════════════════════════════════════════════════════════════════
// ─── Filtro de frutal en modal ────────────────────────────────────────────────
const filterFruitId = ref('');

const filteredVarieties = computed(() => {
    if (!filterFruitId.value) return props.varieties;
    return props.varieties.filter(v => String(v.fruit_id) === String(filterFruitId.value));
});

// Solo variedades que aparecen en las filas de esta evaluación
const evaluationVarieties = computed(() => {
    const usedIds = new Set(props.rows.map(r => String(r.variety_id)));
    return props.varieties.filter(v => usedIds.has(String(v.id)));
});

// Resetear variedad al cambiar frutal manualmente (solo en modo agregar)
const onFruitChange = () => {
    if (editRowId.value) return;
    if (rowForm.value.variety_id) {
        const stillValid = filteredVarieties.value.some(v => String(v.id) === String(rowForm.value.variety_id));
        if (!stillValid) rowForm.value.variety_id = '';
    }
};

const rowForm = ref({ variety_id: '', week: '', hectares: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' });
const editRowId = ref(null);

const openAddRow = () => {
    editRowId.value = null;
    filterFruitId.value = '';
    rowForm.value = { variety_id: '', week: '', hectares: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' };
    nextTick(() => $('#rowModal').modal('show'));
};

const openEditRow = (row) => {
    editRowId.value = row.id;
    const v = props.varieties.find(x => String(x.id) === String(row.variety_id));
    filterFruitId.value = v ? String(v.fruit_id) : '';
    rowForm.value = {
        variety_id:     String(row.variety_id),
        week:           Number(row.week),
        hectares:       Number(row.hectares),
        kg_pessimistic: Number(row.kg_pessimistic),
        kg_base:        Number(row.kg_base),
        kg_optimistic:  Number(row.kg_optimistic),
    };
    nextTick(() => $('#rowModal').modal('show'));
};

const submitRow = () => {
    const payload = {
        variety_id:     Number(rowForm.value.variety_id),
        week:           Number(rowForm.value.week),
        hectares:       Number(rowForm.value.hectares),
        kg_pessimistic: Number(rowForm.value.kg_pessimistic),
        kg_base:        Number(rowForm.value.kg_base),
        kg_optimistic:  Number(rowForm.value.kg_optimistic),
    };
    const onError = (errors) => {
        const msg = Object.values(errors).flat().join('\n');
        Swal.fire({ icon: 'error', title: 'Error de validación', text: msg || 'Revisa los campos.' });
    };

    if (editRowId.value) {
        $('#rowModal').modal('hide');
        router.put(route('project-evaluations.rows.update', { projectEvaluation: props.evaluation.id, row: editRowId.value }), payload, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Fila actualizada', showConfirmButton: false, timer: 1000 });
            },
            onError,
        });
    } else {
        $('#rowModal').modal('hide');
        router.post(route('project-evaluations.rows.store', props.evaluation.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                rowForm.value = { variety_id: '', week: '', hectares: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' };
            },
            onError,
        });
    }
};

const deleteRow = (rowId) => {
    Swal.fire({
        title: '¿Eliminar fila?', icon: 'warning',
        showCancelButton: true, cancelButtonText: 'Cancelar', confirmButtonText: 'Eliminar',
        confirmButtonColor: 'rgb(0,158,247)',
    }).then(r => {
        if (r.isConfirmed) {
            router.delete(route('project-evaluations.rows.delete', { projectEvaluation: props.evaluation.id, row: rowId }), { preserveScroll: true });
        }
    });
};

// ─── Carga masiva de filas ────────────────────────────────────────────────────
const bulkFruitId   = ref('');
const bulkVarietyId = ref('');
const bulkHectares  = ref('');
const bulkRows      = ref([{ week: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' }]);
const submittingBulk = ref(false);

const bulkFilteredVarieties = computed(() => {
    if (!bulkFruitId.value) return props.varieties;
    return props.varieties.filter(v => String(v.fruit_id) === String(bulkFruitId.value));
});

watch(bulkFruitId, () => {
    if (bulkVarietyId.value) {
        const stillValid = bulkFilteredVarieties.value.some(v => String(v.id) === String(bulkVarietyId.value));
        if (!stillValid) bulkVarietyId.value = '';
    }
});

const openBulkModal = () => {
    bulkFruitId.value   = '';
    bulkVarietyId.value = '';
    bulkHectares.value  = '';
    bulkRows.value = [{ week: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' }];
    setTimeout(() => $('#bulkRowModal').modal('show'), 50);
};

const addBulkRow = () => {
    bulkRows.value.push({ week: '', kg_pessimistic: '', kg_base: '', kg_optimistic: '' });
};

const addAllWeeks = () => {
    const existingWeeks = new Set(bulkRows.value.map(r => String(r.week)));
    const toAdd = weeks.filter(w => !existingWeeks.has(String(w)));
    toAdd.forEach(w => bulkRows.value.push({ week: w, kg_pessimistic: '', kg_base: '', kg_optimistic: '' }));
};

// Copia los kg de la primera fila que tenga valores a todas las demás filas
const copyAllKgFromFirst = () => {
    const src = bulkRows.value.find(r => r.kg_base !== '' || r.kg_pessimistic !== '' || r.kg_optimistic !== '');
    if (!src) return;
    bulkRows.value.forEach((r, i) => {
        if (bulkRows.value[i] === src) return;
        r.kg_pessimistic = src.kg_pessimistic;
        r.kg_base        = src.kg_base;
        r.kg_optimistic  = src.kg_optimistic;
    });
};

const removeBulkRow = (i) => {
    if (bulkRows.value.length > 1) bulkRows.value.splice(i, 1);
};

// Copia kg de la fila i a la fila i+1 (la crea si no existe)
const copyRowDown = (i) => {
    const src = bulkRows.value[i];
    if (i + 1 < bulkRows.value.length) {
        bulkRows.value[i + 1].kg_pessimistic = src.kg_pessimistic;
        bulkRows.value[i + 1].kg_base        = src.kg_base;
        bulkRows.value[i + 1].kg_optimistic  = src.kg_optimistic;
    } else {
        bulkRows.value.push({
            week: '',
            kg_pessimistic: src.kg_pessimistic,
            kg_base:        src.kg_base,
            kg_optimistic:  src.kg_optimistic,
        });
    }
};

const submitBulk = () => {
    const validRows = bulkRows.value.filter(r => r.week !== '' && r.week !== null);
    if (!bulkVarietyId.value || bulkHectares.value === '' || validRows.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Completa variedad, hectáreas y al menos una semana.', showConfirmButton: true });
        return;
    }
    const payload = {
        rows: validRows.map(r => ({
            variety_id:     Number(bulkVarietyId.value),
            week:           Number(r.week),
            hectares:       Number(bulkHectares.value),
            kg_pessimistic: Number(r.kg_pessimistic) || 0,
            kg_base:        Number(r.kg_base) || 0,
            kg_optimistic:  Number(r.kg_optimistic) || 0,
        })),
    };
    submittingBulk.value = true;
    $('#bulkRowModal').modal('hide');
    router.post(route('project-evaluations.rows.bulk-store', props.evaluation.id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            submittingBulk.value = false;
            Swal.fire({ icon: 'success', title: `${validRows.length} fila(s) agregada(s)`, showConfirmButton: false, timer: 1200 });
        },
        onError: (errors) => {
            submittingBulk.value = false;
            const msg = Object.values(errors).flat().join('\n');
            Swal.fire({ icon: 'error', title: 'Error de validación', text: msg || 'Revisa los campos.' });
        },
    });
};

// ═══════════════════════════════════════════════════════════════════════════════
// TAB RESULTADOS — Cálculos
// ═══════════════════════════════════════════════════════════════════════════════

// Lookup: dado kg/há → cost_usd (interpolación por el más cercano inferior)
const lookupKgCost = (kgPerHa) => {
    const sorted = [...props.kgYieldCosts].sort((a, b) => a.kg_ha - b.kg_ha);
    if (sorted.length === 0) return 0;
    // Buscar el mayor kg_ha que sea <= kgPerHa
    let best = sorted[0];
    for (const entry of sorted) {
        if (entry.kg_ha <= kgPerHa) best = entry;
        else break;
    }
    return Number(best.cost_usd);
};

// Para un escenario ("pessimistic" | "base" | "optimistic"), calcular por fila
const calcRow = (row, scenario) => {
    const kg_per_ha = Number(row[`kg_${scenario}`]);   // El usuario ingresa KG/HÁ
    const ha = Number(row.hectares);
    if (!ha || !kg_per_ha) return null;

    const kg_total = kg_per_ha * ha;                    // KG totales = KG/HÁ × hectáreas

    // Obtener parámetros de variedad
    const cp = props.varietyCostParams.find(p => String(p.variety_id) === String(row.variety_id));
    const pct_emb = cp ? Number(cp.pct_embalaje) / 100 : 0;
    const precio_proceso = cp ? Number(cp.precio_proceso) : 0;

    // Obtener precio RNP para esa variedad y semana
    const rnp = props.rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week));
    const rnp_usd = rnp ? Number(rnp.price_usd) : 0;

    // Flags de diagnóstico
    const missingRnp = !rnp || rnp_usd === 0;
    const missingCostParams = !cp;

    // Fórmulas
    const IFE      = rnp_usd * kg_total * pct_emb;
    const FCNE     = kg_total * (1 - pct_emb) * precio_proceso;
    const AbonoCC  = IFE - FCNE;

    const costHa   = lookupKgCost(kg_per_ha);
    const CostoHA  = costHa * kg_total;

    const MargenBruto = AbonoCC - CostoHA;   // Total (ya incluye todas las hectáreas)
    const MargenTotal = MargenBruto;          // Es lo mismo, AbonoCC y CostoHA ya son totales

    return { kg_total, kg_per_ha, rnp_usd, pct_emb, IFE, FCNE, AbonoCC, CostoHA, MargenBruto, MargenTotal, ha, missingRnp, missingCostParams };
};

// Totales por escenario
const scenarioTotals = computed(() => {
    const scenarios = ['pessimistic', 'base', 'optimistic'];
    const result = {};
    scenarios.forEach(sc => {
        let totalHa = 0, totalIFE = 0, totalFCNE = 0, totalAbonoCC = 0, totalCostoHA = 0, totalMargenBruto = 0, totalMargenTotal = 0, totalKg = 0;
        props.rows.forEach(row => {
            const c = calcRow(row, sc);
            if (!c) return;
            totalHa         += c.ha;
            totalIFE        += c.IFE;
            totalFCNE       += c.FCNE;
            totalAbonoCC    += c.AbonoCC;
            totalCostoHA    += c.CostoHA;
            totalMargenBruto += c.MargenBruto;
            totalMargenTotal += c.MargenTotal;
            totalKg         += c.kg_total;
        });
        const targetMargin = Number(localTargetMargin.value) / 100;
        const maxArriendo  = totalHa > 0 ? totalMargenTotal / totalHa : 0;
        const ofertaMaxConMargen = maxArriendo * (1 - targetMargin);

        result[sc] = { totalHa, totalIFE, totalFCNE, totalAbonoCC, totalCostoHA, totalMargenBruto, totalMargenTotal, totalKg, maxArriendo, ofertaMaxConMargen };
    });
    return result;
});

const rowDetailsBySc = (scenario) => {
    return props.rows.map(row => {
        const c = calcRow(row, scenario);
        return { row, calc: c };
    }).sort((a, b) => {
        const mA = a.calc ? a.calc.MargenBruto / (a.calc.ha || 1) : -Infinity;
        const mB = b.calc ? b.calc.MargenBruto / (b.calc.ha || 1) : -Infinity;
        return mB - mA;
    });
};

const excelDetalleHeaders = computed(() => {
    const perHa = showPerHa.value;
    return [
        { label: 'Variedad', key: 'variedad' },
        { label: 'Sem.', key: 'semana' },
        { label: 'HÁ', key: 'ha' },
        { label: 'KG/HÁ', key: 'kg_per_ha' },
        { label: '% Emb.', key: 'pct_emb' },
        { label: 'RNP (USD)', key: 'rnp_usd' },
        ...(!perHa ? [{ label: 'KG Totales', key: 'kg_total' }] : []),
        { label: perHa ? 'IFE/HÁ (USD)' : 'IFE (USD)', key: 'ife' },
        { label: perHa ? 'FCNE/HÁ (USD)' : 'FCNE (USD)', key: 'fcne' },
        { label: perHa ? 'Abono CC/HÁ (USD)' : 'Abono CC (USD)', key: 'abono_cc' },
        { label: perHa ? 'Costo/HÁ (USD)' : 'Costo (USD)', key: 'costo' },
        { label: perHa ? 'Margen/HÁ (USD)' : 'Margen Total (USD)', key: 'margen' },
    ];
});

const excelDetalleData = computed(() => {
    const perHa = showPerHa.value;
    return rowDetailsBySc(activeScenario.value)
        .filter(({ calc }) => calc)
        .map(({ row, calc }) => {
            const ha = calc.ha || 1;
            return {
                variedad:  varietyName(row.variety_id),
                semana:    row.week,
                ha:        calc.ha,
                kg_per_ha: calc.kg_per_ha,
                pct_emb:   Math.round(calc.pct_emb * 100),
                rnp_usd:   calc.rnp_usd,
                kg_total:  calc.kg_total,
                ife:       Math.round(perHa ? calc.IFE / ha : calc.IFE),
                fcne:      Math.round(perHa ? calc.FCNE / ha : calc.FCNE),
                abono_cc:  Math.round(perHa ? calc.AbonoCC / ha : calc.AbonoCC),
                costo:     Math.round(perHa ? calc.CostoHA / ha : calc.CostoHA),
                margen:    Math.round(perHa ? calc.MargenBruto / ha : calc.MargenBruto),
            };
        });
});
</script>

<template>
    <AppLayout :title="`Evaluación: ${evaluation.name}`">
        <div class="card my-3">
            <!-- Header -->
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center pe-0 gap-2">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-seedling me-2"></i>{{ evaluation.name }}
                        </h5>
                        <span v-if="evaluation.description" class="text-muted small d-none d-md-inline">— {{ evaluation.description }}</span>
                    </div>
                    <div class="col-auto ms-auto text-end ps-0 d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark">
                            Margen objetivo: {{ localTargetMargin }}%
                        </span>
                        <Link :href="route('project-evaluations.index')" class="btn btn-sm btn-falcon-default">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Tabs Nav -->
            <div class="card-header border-0 pb-0 pt-2 mb-2">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'composicion' }"
                            @click="activeTab = 'composicion'"
                        >
                            <i class="fas fa-list me-1"></i>Composición
                            <span v-if="rows.length" class="badge bg-secondary ms-1" style="font-size:0.65rem;">{{ rows.length }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'parametros' }"
                            @click="activeTab = 'parametros'"
                        >
                            <i class="fas fa-sliders-h me-1"></i>Parámetros
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            class="nav-link"
                            :class="{ active: activeTab === 'resultados' }"
                            @click="activeTab = 'resultados'"
                        >
                            <i class="fas fa-chart-bar me-1"></i>Resultados
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- ══════════════════════════════════════════════════════════
                     TAB COMPOSICIÓN
                     ══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'composicion'">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="mb-0 text-muted small">
                            Define las variedades y semanas que componen esta evaluación junto con sus estimaciones de producción.
                        </p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openBulkModal">
                                <span class="fas fa-layer-group" data-fa-transform="shrink-3 down-2"></span>
                                <span class="ms-1">Múltiples filas</span>
                            </button>
                            <button class="btn btn-falcon-default btn-sm" @click="openAddRow">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="ms-1">Agregar fila</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="rows.length === 0" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay filas aún. Agrega una combinación variedad + semana.</p>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variedad</th>
                                    <th class="text-center">Semana</th>
                                    <th class="text-center">Hectáreas</th>
                                    <th class="text-end">KG/HÁ Pesimista</th>
                                    <th class="text-end">KG/HÁ Base</th>
                                    <th class="text-end">KG/HÁ Optimista</th>
                                    <th class="text-end">KG Tot. Pesim.</th>
                                    <th class="text-end">KG Tot. Base</th>
                                    <th class="text-end">KG Tot. Optim.</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.id">
                                    <td class="fw-semibold">
                                        {{ varietyName(row.variety_id) }}
                                        <span
                                            v-if="!varietyCostParams.find(p => String(p.variety_id) === String(row.variety_id))"
                                            class="badge bg-warning text-dark ms-1"
                                            style="font-size:0.6rem;"
                                            v-tooltip="'Sin % embalaje / precio proceso en Parámetros'"
                                        >sin params</span>
                                        <span
                                            v-else-if="!rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week))"
                                            class="badge bg-info text-dark ms-1"
                                            style="font-size:0.6rem;"
                                            v-tooltip="'Sin precio RNP para esta semana en Parámetros'"
                                        >sin RNP</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" :class="rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week)) ? 'bg-secondary' : 'bg-danger'">
                                            S{{ row.week }}
                                        </span>
                                        <div v-if="rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week))" class="text-muted" style="font-size:0.65rem;">
                                            RNP: {{ fmtUsd(rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week)).price_usd, 1) }}
                                        </div>
                                        <div v-else class="text-danger" style="font-size:0.65rem;">¡Sin precio RNP!</div>
                                    </td>
                                    <td class="text-center">{{ fmt(row.hectares, 1) }}</td>
                                    <td class="text-end text-warning">{{ fmt(row.kg_pessimistic) }}</td>
                                    <td class="text-end text-primary fw-semibold">{{ fmt(row.kg_base) }}</td>
                                    <td class="text-end text-success">{{ fmt(row.kg_optimistic) }}</td>
                                    <td class="text-end text-muted small">
                                        {{ row.hectares > 0 ? fmt(row.kg_pessimistic * row.hectares) : '—' }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ row.hectares > 0 ? fmt(row.kg_base * row.hectares) : '—' }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ row.hectares > 0 ? fmt(row.kg_optimistic * row.hectares) : '—' }}
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-icon btn-active-light-primary w-25px h-25px" @click="openEditRow(row)">
                                            <i class="fas fa-edit" style="font-size:0.65rem;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon btn-active-light-danger w-25px h-25px ms-1" @click="deleteRow(row.id)">
                                            <i class="fas fa-trash-alt" style="font-size:0.65rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2">Totales</td>
                                    <td class="text-center text-muted" v-tooltip="'Las há no se suman: un mismo lote puede aparecer en varias semanas'">—</td>
                                    <td class="text-end text-muted" v-tooltip="'KG/HÁ es un rendimiento, no se suma'">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-warning">{{ fmt(rows.reduce((s, r) => s + Number(r.kg_pessimistic) * Number(r.hectares), 0)) }}</td>
                                    <td class="text-end text-primary">{{ fmt(rows.reduce((s, r) => s + Number(r.kg_base) * Number(r.hectares), 0)) }}</td>
                                    <td class="text-end text-success">{{ fmt(rows.reduce((s, r) => s + Number(r.kg_optimistic) * Number(r.hectares), 0)) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     TAB PARÁMETROS
                     ══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'parametros'">

                    <!-- ── RNP por variedad × semana ───────────────────── -->
                    <div class="card mb-3" style="border-left: 4px solid #a3c4f3;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background-color: #eaf2fd; cursor: pointer;" @click="openRnp = !openRnp">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2 text-primary"></i>Precios RNP (USD/KG) — Variedad × Semana
                                <i class="fas fa-chevron-down ms-2 text-muted" style="font-size:0.7rem; transition: transform 0.2s;" :style="{ transform: openRnp ? 'rotate(0deg)' : 'rotate(-90deg)' }"></i>
                            </h6>
                            <button v-if="openRnp" class="btn btn-sm btn-falcon-default" @click.stop="saveRnpPrices" :disabled="savingRnp">
                                <i class="fas fa-save me-1"></i>Guardar RNP
                                <span v-if="savingRnp" class="spinner-border spinner-border-sm ms-1"></span>
                            </button>
                        </div>
                        <div v-show="openRnp" class="card-body py-2 px-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Variedad</th>
                                            <th v-for="w in weeks" :key="w" class="text-center" style="min-width:70px;">S{{ w }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="v in evaluationVarieties" :key="v.id">
                                            <td class="fw-semibold text-nowrap">{{ v.name }}</td>
                                            <td v-for="w in weeks" :key="w" class="p-1">
                                                <input
                                                    v-model="localRnp[v.id][w]"
                                                    type="number" step="0.01" min="0"
                                                    @change="localRnp[v.id][w] = localRnp[v.id][w] !== '' ? Math.round(localRnp[v.id][w] * 100) / 100 : ''"
                                                    class="form-control form-control-sm text-center p-1"
                                                    style="min-width:60px;"
                                                    placeholder="—"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ── Parámetros de costo por variedad ────────────── -->
                    <div class="card mb-3" style="border-left: 4px solid #b5e8d5;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background-color: #e8f8f0; cursor: pointer;" @click="openCostParams = !openCostParams">
                            <h6 class="mb-0">
                                <i class="fas fa-boxes me-2 text-info"></i>Parámetros de Costo por Variedad
                                <i class="fas fa-chevron-down ms-2 text-muted" style="font-size:0.7rem; transition: transform 0.2s;" :style="{ transform: openCostParams ? 'rotate(0deg)' : 'rotate(-90deg)' }"></i>
                            </h6>
                            <button v-if="openCostParams" class="btn btn-sm btn-falcon-default" @click.stop="saveCostParams" :disabled="savingCostParams">
                                <i class="fas fa-save me-1"></i>Guardar
                                <span v-if="savingCostParams" class="spinner-border spinner-border-sm ms-1"></span>
                            </button>
                        </div>
                        <div v-show="openCostParams" class="card-body py-2 px-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Variedad</th>
                                            <th class="text-center" style="min-width:130px;">% Embalaje</th>
                                            <th class="text-center" style="min-width:140px;">Precio Proceso (USD/KG)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="v in evaluationVarieties" :key="v.id">
                                            <td class="fw-semibold">{{ v.name }}</td>
                                            <td class="p-1">
                                                <input v-model="localCostParams[v.id].pct_embalaje"
                                                    type="number" step="1" min="0" max="100"
                                                    @change="localCostParams[v.id].pct_embalaje = localCostParams[v.id].pct_embalaje !== '' ? Math.round(Number(localCostParams[v.id].pct_embalaje)) : ''"
                                                    class="form-control form-control-sm text-center"
                                                    placeholder="0–100" />
                                            </td>
                                            <td class="p-1">
                                                <input v-model="localCostParams[v.id].precio_proceso"
                                                    type="number" step="0.01" min="0"
                                                    @change="localCostParams[v.id].precio_proceso = localCostParams[v.id].precio_proceso !== '' ? Math.round(localCostParams[v.id].precio_proceso * 100) / 100 : ''"
                                                    class="form-control form-control-sm text-center"
                                                    placeholder="0.00" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ── Tabla costo KG/Rendimiento ──────────────────── -->
                    <div class="card mb-3" style="border-left: 4px solid #f3d5a3;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background-color: #fdf5ea; cursor: pointer;" @click="openKgLookup = !openKgLookup">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-2 text-success"></i>Costo segun KG/HÁ
                                <i class="fas fa-chevron-down ms-2 text-muted" style="font-size:0.7rem; transition: transform 0.2s;" :style="{ transform: openKgLookup ? 'rotate(0deg)' : 'rotate(-90deg)' }"></i>
                            </h6>
                            <div v-if="openKgLookup" class="d-flex gap-2">
                                <button class="btn btn-sm btn-falcon-default" @click.stop="addKgRow">
                                    <i class="fas fa-plus me-1"></i>Fila
                                </button>
                                <button class="btn btn-sm btn-falcon-default" @click.stop="saveKgCosts" :disabled="savingKgCosts">
                                    <i class="fas fa-save me-1"></i>Guardar
                                    <span v-if="savingKgCosts" class="spinner-border spinner-border-sm ms-1"></span>
                                </button>
                            </div>
                        </div>
                        <div v-show="openKgLookup" class="card-body py-2 px-3">
                            <small class="text-muted d-block mb-2">
                                Se usa el costo del escalón inferior más cercano al KG/HÁ real de cada fila.
                            </small>
                            <div class="table-responsive" style="max-width:400px;">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">KG/HÁ (desde)</th>
                                            <th class="text-center">Costo (USD/KG)</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in localKgCosts" :key="i">
                                            <td class="p-1">
                                                <input v-model="row.kg_ha" type="number" min="0" class="form-control form-control-sm text-center" placeholder="Ej: 5000" />
                                            </td>
                                            <td class="p-1">
                                                <input v-model="row.cost_usd" type="number" step="0.01" min="0"
                                                    @change="row.cost_usd = row.cost_usd !== '' ? Math.round(row.cost_usd * 100) / 100 : ''"
                                                    class="form-control form-control-sm text-center" placeholder="0.00" />
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-icon btn-active-light-danger w-25px h-25px" @click="removeKgRow(i)">
                                                    <i class="fas fa-times" style="font-size:0.6rem;"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="localKgCosts.length === 0">
                                            <td colspan="3" class="text-center text-muted py-3">Sin datos. Agrega filas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     TAB RESULTADOS
                     ══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'resultados'">
                    <div v-if="rows.length === 0" class="text-center py-5 text-muted">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>Agrega filas en la tab <strong>Composición</strong> para ver resultados.</p>
                    </div>

                    <div v-else>
                        <!-- ── Alertas de parámetros faltantes ─────── -->
                        <template v-for="row in rows" :key="'alert-'+row.id">
                            <div v-if="!varietyCostParams.find(p => String(p.variety_id) === String(row.variety_id))"
                                class="alert alert-warning py-2 px-3 mb-2 d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><strong>{{ varietyName(row.variety_id) }}</strong> — falta configurar
                                    <strong>% Embalaje</strong> y <strong>Precio Proceso</strong> en la tab
                                    <a href="#" @click.prevent="activeTab='parametros'" class="alert-link">Parámetros</a>.
                                    Mientras tanto, IFE y AbonoCC serán 0.
                                </span>
                            </div>
                            <div v-else-if="!rnpPrices.find(p => String(p.variety_id) === String(row.variety_id) && String(p.week) === String(row.week))"
                                class="alert alert-info py-2 px-3 mb-2 d-flex align-items-center gap-2" style="font-size:0.82rem;">
                                <i class="fas fa-info-circle"></i>
                                <span><strong>{{ varietyName(row.variety_id) }} / S{{ row.week }}</strong> — sin precio RNP cargado.
                                    Configúralo en la tab
                                    <a href="#" @click.prevent="activeTab='parametros'" class="alert-link">Parámetros</a>.
                                </span>
                            </div>
                        </template>

                        <!-- Controles: Margen + Toggle -->
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-secondary"></i>KPI por Escenario</h6>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label small mb-0 text-nowrap">Margen objetivo:</label>
                                    <div class="input-group input-group-sm" style="width:120px;">
                                        <input type="number" v-model.number="localTargetMargin" class="form-control form-control-sm text-end" min="0" max="100" step="1">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn" :class="!showPerHa ? 'btn-primary text-white shadow-sm' : 'btn-falcon-default'" @click="showPerHa = false">
                                    <i class="fas fa-globe me-1"></i>Totales
                                </button>
                                <button type="button" class="btn" :class="showPerHa ? 'btn-primary text-white shadow-sm' : 'btn-falcon-default'" @click="showPerHa = true">
                                    <i class="fas fa-ruler-combined me-1"></i>Por Hectárea
                                </button>
                            </div>
                            </div>
                        </div>

                        <!-- KPI Cards por escenario -->
                        <div class="row g-3 mb-4">
                            <!-- Pesimista -->
                            <div class="col-12 col-md-4">
                                <div class="card border border-warning h-100" :class="{'shadow-sm ring-2': activeScenario === 'pessimistic'}" style="cursor:pointer;" @click="activeScenario = 'pessimistic'">
                                    <div class="card-header py-2 bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-warning fw-bold"><i class="fas fa-arrow-down me-1"></i>Pesimista</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'KG/HÁ' : 'KG Totales' }}</small>
                                                <strong>{{ fmt(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalKg / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalKg) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Abono CC/HÁ' : 'Abono CC' }}</small>
                                                <strong>{{ fmtUsd(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalAbonoCC / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalAbonoCC) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Costo/HÁ' : 'Costo Total' }}</small>
                                                <strong class="text-danger">{{ fmtUsd(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalCostoHA / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalCostoHA) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Bruto/HÁ' : 'Margen Bruto' }}</small>
                                                <strong :class="scenarioTotals.pessimistic.totalMargenBruto >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalMargenBruto / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalMargenBruto) }}
                                                </strong>
                                            </div>
                                            <div class="col-12 mt-1 border-top pt-1">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Total/HÁ' : 'Margen Total' }}</small>
                                                <strong class="fs-6" :class="scenarioTotals.pessimistic.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalMargenTotal / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalMargenTotal) }}
                                                </strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Máx Arriendo/HÁ' : 'Máx Arriendo Total' }}</small>
                                                <strong>{{ fmtUsd(showPerHa ? scenarioTotals.pessimistic.maxArriendo : scenarioTotals.pessimistic.maxArriendo * scenarioTotals.pessimistic.totalHa) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Oferta/HÁ c/' + localTargetMargin + '%' : 'Oferta Total c/' + localTargetMargin + '%' }}</small>
                                                <strong class="text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.pessimistic.ofertaMaxConMargen : scenarioTotals.pessimistic.ofertaMaxConMargen * scenarioTotals.pessimistic.totalHa) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Base -->
                            <div class="col-12 col-md-4">
                                <div class="card border border-primary h-100" :class="{'shadow-sm ring-2': activeScenario === 'base'}" style="cursor:pointer;" @click="activeScenario = 'base'">
                                    <div class="card-header py-2 bg-primary bg-opacity-10">
                                        <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-equals me-1"></i>Base</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'KG/HÁ' : 'KG Totales' }}</small>
                                                <strong>{{ fmt(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalKg / scenarioTotals.base.totalHa : scenarioTotals.base.totalKg) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Abono CC/HÁ' : 'Abono CC' }}</small>
                                                <strong>{{ fmtUsd(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalAbonoCC / scenarioTotals.base.totalHa : scenarioTotals.base.totalAbonoCC) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Costo/HÁ' : 'Costo Total' }}</small>
                                                <strong class="text-danger">{{ fmtUsd(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalCostoHA / scenarioTotals.base.totalHa : scenarioTotals.base.totalCostoHA) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Bruto/HÁ' : 'Margen Bruto' }}</small>
                                                <strong :class="scenarioTotals.base.totalMargenBruto >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalMargenBruto / scenarioTotals.base.totalHa : scenarioTotals.base.totalMargenBruto) }}
                                                </strong>
                                            </div>
                                            <div class="col-12 mt-1 border-top pt-1">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Total/HÁ' : 'Margen Total' }}</small>
                                                <strong class="fs-6" :class="scenarioTotals.base.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalMargenTotal / scenarioTotals.base.totalHa : scenarioTotals.base.totalMargenTotal) }}
                                                </strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Máx Arriendo/HÁ' : 'Máx Arriendo Total' }}</small>
                                                <strong>{{ fmtUsd(showPerHa ? scenarioTotals.base.maxArriendo : scenarioTotals.base.maxArriendo * scenarioTotals.base.totalHa) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Oferta/HÁ c/' + localTargetMargin + '%' : 'Oferta Total c/' + localTargetMargin + '%' }}</small>
                                                <strong class="text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.base.ofertaMaxConMargen : scenarioTotals.base.ofertaMaxConMargen * scenarioTotals.base.totalHa) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optimista -->
                            <div class="col-12 col-md-4">
                                <div class="card border border-success h-100" :class="{'shadow-sm ring-2': activeScenario === 'optimistic'}" style="cursor:pointer;" @click="activeScenario = 'optimistic'">
                                    <div class="card-header py-2 bg-success bg-opacity-10">
                                        <h6 class="mb-0 text-success fw-bold"><i class="fas fa-arrow-up me-1"></i>Optimista</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'KG/HÁ' : 'KG Totales' }}</small>
                                                <strong>{{ fmt(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalKg / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalKg) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Abono CC/HÁ' : 'Abono CC' }}</small>
                                                <strong>{{ fmtUsd(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalAbonoCC / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalAbonoCC) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Costo/HÁ' : 'Costo Total' }}</small>
                                                <strong class="text-danger">{{ fmtUsd(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalCostoHA / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalCostoHA) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Bruto/HÁ' : 'Margen Bruto' }}</small>
                                                <strong :class="scenarioTotals.optimistic.totalMargenBruto >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalMargenBruto / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalMargenBruto) }}
                                                </strong>
                                            </div>
                                            <div class="col-12 mt-1 border-top pt-1">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Margen Total/HÁ' : 'Margen Total' }}</small>
                                                <strong class="fs-6" :class="scenarioTotals.optimistic.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ fmtUsd(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalMargenTotal / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalMargenTotal) }}
                                                </strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Máx Arriendo/HÁ' : 'Máx Arriendo Total' }}</small>
                                                <strong>{{ fmtUsd(showPerHa ? scenarioTotals.optimistic.maxArriendo : scenarioTotals.optimistic.maxArriendo * scenarioTotals.optimistic.totalHa) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">{{ showPerHa ? 'Oferta/HÁ c/' + localTargetMargin + '%' : 'Oferta Total c/' + localTargetMargin + '%' }}</small>
                                                <strong class="text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.optimistic.ofertaMaxConMargen : scenarioTotals.optimistic.ofertaMaxConMargen * scenarioTotals.optimistic.totalHa) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla comparativa detallada -->
                        <div class="border rounded-2 p-3 mb-4" style="background:#fafbfc; border-color:#dee2e6 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-table me-2 text-secondary"></i>Detalle por Fila — <span :class="scenarioColors[activeScenario]" class="fw-bold">Escenario {{ scenarioLabels[activeScenario] }}</span> <small class="text-muted fw-normal">({{ showPerHa ? 'por hectárea' : 'totales' }})</small></h6>
                            <ExportExcelButton
                                :data="excelDetalleData"
                                :headers="excelDetalleHeaders"
                                :filename="`detalle-${activeScenario}-${evaluation.name}.xlsx`"
                            />
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Variedad</th>
                                        <th class="text-center">Sem.</th>
                                        <th class="text-center">HÁ</th>
                                        <th class="text-end">KG/HÁ</th>
                                        <th class="text-center">% Emb.</th>
                                        <th class="text-end">RNP (USD)</th>
                                        <th v-show="!showPerHa" class="text-end">KG Totales</th>
                                        <th class="text-end">{{ showPerHa ? 'IFE/HÁ' : 'IFE' }} (USD)</th>
                                        <th class="text-end">{{ showPerHa ? 'FCNE/HÁ' : 'FCNE' }} (USD)</th>
                                        <th class="text-end">{{ showPerHa ? 'Abono CC/HÁ' : 'Abono CC' }} (USD)</th>
                                        <th class="text-end">{{ showPerHa ? 'Costo/HÁ' : 'Costo' }} (USD)</th>
                                        <th class="text-end">{{ showPerHa ? 'Margen/HÁ' : 'Margen Total' }} (USD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="{row, calc} in rowDetailsBySc(activeScenario)" :key="row.id">
                                        <tr v-if="calc">
                                            <td class="fw-semibold">
                                                {{ varietyName(row.variety_id) }}
                                                <span v-if="calc.missingRnp" v-tooltip="'Sin precio RNP para esta semana'" class="text-warning ms-1"><i class="fas fa-exclamation-triangle fa-xs"></i></span>
                                                <span v-if="calc.missingCostParams" v-tooltip="'Sin parámetros de costo (% embalaje / precio proceso)'" class="text-danger ms-1"><i class="fas fa-times-circle fa-xs"></i></span>
                                            </td>
                                            <td class="text-center"><span class="badge bg-secondary">S{{ row.week }}</span></td>
                                            <td class="text-center">{{ fmt(calc.ha, 1) }}</td>
                                            <td class="text-end text-muted">{{ fmt(calc.kg_per_ha) }}</td>
                                            <td class="text-center">{{ fmt(calc.pct_emb * 100, 0) }}%</td>
                                            <td class="text-end">{{ fmtUsd(calc.rnp_usd, 1) }}</td>
                                            <td v-show="!showPerHa" class="text-end">{{ fmt(calc.kg_total) }}</td>
                                            <td class="text-end">{{ fmtUsd(showPerHa && calc.ha > 0 ? calc.IFE / calc.ha : calc.IFE) }}</td>
                                            <td class="text-end text-danger">{{ fmtUsd(showPerHa && calc.ha > 0 ? calc.FCNE / calc.ha : calc.FCNE) }}</td>
                                            <td class="text-end fw-semibold">{{ fmtUsd(showPerHa && calc.ha > 0 ? calc.AbonoCC / calc.ha : calc.AbonoCC) }}</td>
                                            <td class="text-end text-danger">{{ fmtUsd(showPerHa && calc.ha > 0 ? calc.CostoHA / calc.ha : calc.CostoHA) }}</td>
                                            <td class="text-end fw-bold" :class="calc.MargenTotal >= 0 ? 'text-success' : 'text-danger'">
                                                {{ fmtUsd(showPerHa && calc.ha > 0 ? calc.MargenTotal / calc.ha : calc.MargenTotal) }}
                                            </td>
                                        </tr>
                                        <tr v-else>
                                            <td class="fw-semibold">{{ varietyName(row.variety_id) }}</td>
                                            <td class="text-center"><span class="badge bg-secondary">S{{ row.week }}</span></td>
                                            <td :colspan="10" class="text-muted small text-center">Sin parámetros configurados</td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="fw-bold">
                                    <tr style="background-color:#e8edf7; border-top: 2px solid #c0cfe8;">
                                        <td colspan="2" class="text-end">TOTALES</td>
                                        <td class="text-center">{{ fmt(scenarioTotals[activeScenario].totalHa, 1) }}</td>
                                        <td colspan="3"></td>
                                        <td v-show="!showPerHa" class="text-end">{{ fmt(scenarioTotals[activeScenario].totalKg) }}</td>
                                        <td class="text-end">{{ fmtUsd(showPerHa && scenarioTotals[activeScenario].totalHa > 0 ? scenarioTotals[activeScenario].totalIFE / scenarioTotals[activeScenario].totalHa : scenarioTotals[activeScenario].totalIFE) }}</td>
                                        <td class="text-end text-danger">{{ fmtUsd(showPerHa && scenarioTotals[activeScenario].totalHa > 0 ? scenarioTotals[activeScenario].totalFCNE / scenarioTotals[activeScenario].totalHa : scenarioTotals[activeScenario].totalFCNE) }}</td>
                                        <td class="text-end">{{ fmtUsd(showPerHa && scenarioTotals[activeScenario].totalHa > 0 ? scenarioTotals[activeScenario].totalAbonoCC / scenarioTotals[activeScenario].totalHa : scenarioTotals[activeScenario].totalAbonoCC) }}</td>
                                        <td class="text-end text-danger">{{ fmtUsd(showPerHa && scenarioTotals[activeScenario].totalHa > 0 ? scenarioTotals[activeScenario].totalCostoHA / scenarioTotals[activeScenario].totalHa : scenarioTotals[activeScenario].totalCostoHA) }}</td>
                                        <td class="text-end" :class="scenarioTotals[activeScenario].totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">
                                            {{ fmtUsd(showPerHa && scenarioTotals[activeScenario].totalHa > 0 ? scenarioTotals[activeScenario].totalMargenTotal / scenarioTotals[activeScenario].totalHa : scenarioTotals[activeScenario].totalMargenTotal) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        </div><!-- /detalle wrapper -->

                        <!-- Resumen de oferta max por escenario -->
                        <div class="border rounded-2 p-3" style="background:#fafbfc; border-color:#dee2e6 !important; display:inline-block; max-width:650px; width:100%;">
                        <h6 class="mb-2"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Resumen Oferta Máxima de Arriendo <small class="text-muted fw-normal">({{ showPerHa ? 'por hectárea' : 'totales' }})</small></h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Escenario</th>
                                        <th class="text-center">HÁ Totales</th>
                                        <th class="text-end">{{ showPerHa ? 'Margen/HÁ (USD)' : 'Margen Total (USD)' }}</th>
                                        <th class="text-end">{{ showPerHa ? 'Máx Arriendo/HÁ' : 'Máx Arriendo Total' }}</th>
                                        <th class="text-end">{{ showPerHa ? 'Oferta/HÁ c/' + localTargetMargin + '%' : 'Oferta Total c/' + localTargetMargin + '%' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr :class="{'table-warning': activeScenario === 'pessimistic'}" style="cursor:pointer;" @click="activeScenario = 'pessimistic'">
                                        <td><span class="badge bg-warning text-dark">Pesimista</span></td>
                                        <td class="text-center">{{ fmt(scenarioTotals.pessimistic.totalHa, 1) }}</td>
                                        <td class="text-end" :class="scenarioTotals.pessimistic.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">{{ fmtUsd(showPerHa && scenarioTotals.pessimistic.totalHa > 0 ? scenarioTotals.pessimistic.totalMargenTotal / scenarioTotals.pessimistic.totalHa : scenarioTotals.pessimistic.totalMargenTotal) }}</td>
                                        <td class="text-end">{{ fmtUsd(showPerHa ? scenarioTotals.pessimistic.maxArriendo : scenarioTotals.pessimistic.maxArriendo * scenarioTotals.pessimistic.totalHa) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.pessimistic.ofertaMaxConMargen : scenarioTotals.pessimistic.ofertaMaxConMargen * scenarioTotals.pessimistic.totalHa) }}</td>
                                    </tr>
                                    <tr :class="{'table-primary': activeScenario === 'base'}" style="cursor:pointer;" @click="activeScenario = 'base'">
                                        <td><span class="badge bg-primary">Base</span></td>
                                        <td class="text-center">{{ fmt(scenarioTotals.base.totalHa, 1) }}</td>
                                        <td class="text-end" :class="scenarioTotals.base.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">{{ fmtUsd(showPerHa && scenarioTotals.base.totalHa > 0 ? scenarioTotals.base.totalMargenTotal / scenarioTotals.base.totalHa : scenarioTotals.base.totalMargenTotal) }}</td>
                                        <td class="text-end">{{ fmtUsd(showPerHa ? scenarioTotals.base.maxArriendo : scenarioTotals.base.maxArriendo * scenarioTotals.base.totalHa) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.base.ofertaMaxConMargen : scenarioTotals.base.ofertaMaxConMargen * scenarioTotals.base.totalHa) }}</td>
                                    </tr>
                                    <tr :class="{'table-success': activeScenario === 'optimistic'}" style="cursor:pointer;" @click="activeScenario = 'optimistic'">
                                        <td><span class="badge bg-success">Optimista</span></td>
                                        <td class="text-center">{{ fmt(scenarioTotals.optimistic.totalHa, 1) }}</td>
                                        <td class="text-end" :class="scenarioTotals.optimistic.totalMargenTotal >= 0 ? 'text-success' : 'text-danger'">{{ fmtUsd(showPerHa && scenarioTotals.optimistic.totalHa > 0 ? scenarioTotals.optimistic.totalMargenTotal / scenarioTotals.optimistic.totalHa : scenarioTotals.optimistic.totalMargenTotal) }}</td>
                                        <td class="text-end">{{ fmtUsd(showPerHa ? scenarioTotals.optimistic.maxArriendo : scenarioTotals.optimistic.maxArriendo * scenarioTotals.optimistic.totalHa) }}</td>
                                        <td class="text-end fw-bold text-primary">{{ fmtUsd(showPerHa ? scenarioTotals.optimistic.ofertaMaxConMargen : scenarioTotals.optimistic.ofertaMaxConMargen * scenarioTotals.optimistic.totalHa) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div><!-- /resumen wrapper -->
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Carga Masiva de Filas ─────────────────────────────────── -->
        <div class="modal fade" id="bulkRowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="fas fa-layer-group me-2 text-primary"></i>Agregar múltiples filas
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Campos compartidos -->
                        <div class="row g-3 mb-3 pb-3 border-bottom">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Frutal <span class="text-muted small">(filtro)</span></label>
                                <select v-model="bulkFruitId" class="form-select form-select-sm">
                                    <option value="">Todos los frutales</option>
                                    <option v-for="f in fruits" :key="f.id" :value="String(f.id)">{{ f.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Variedad <span class="text-danger">*</span></label>
                                <select v-model="bulkVarietyId" class="form-select form-select-sm">
                                    <option value="" disabled>Seleccione variedad...</option>
                                    <option v-for="v in bulkFilteredVarieties" :key="v.id" :value="String(v.id)">{{ v.name }}</option>
                                </select>
                                <small v-if="bulkFruitId && bulkFilteredVarieties.length === 0" class="text-warning">Sin variedades para este frutal.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Hectáreas <span class="text-danger">*</span></label>
                                <input v-model.number="bulkHectares" type="number" min="0" step="0.1" class="form-control form-control-sm" placeholder="0.0" />
                                <small class="text-muted">Se aplica a todas las semanas.</small>
                            </div>
                        </div>

                        <!-- Tabla de semanas -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0 small text-muted">
                                Ingresa una fila por semana. El botón
                                <i class="fas fa-arrow-down fa-xs text-primary"></i>
                                copia los KG a la siguiente fila.
                            </p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-falcon-default" @click="copyAllKgFromFirst" v-tooltip="'Copia los KG de la primera fila con valores a todas las demás'">
                                    <i class="fas fa-copy me-1"></i>Copiar KG a todas
                                </button>
                                <button type="button" class="btn btn-sm btn-falcon-default" @click="addAllWeeks" v-tooltip="'Agrega las semanas S42–S52 que aún no estén en la tabla'">
                                    <i class="fas fa-calendar-alt me-1"></i>Todas las semanas
                                </button>
                                <button type="button" class="btn btn-sm btn-falcon-default" @click="addBulkRow">
                                    <i class="fas fa-plus me-1"></i>Agregar semana
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:110px;">Semana <span class="text-danger">*</span></th>
                                        <th class="text-center text-warning" style="min-width:120px;">KG/HÁ Pesimista</th>
                                        <th class="text-center text-primary" style="min-width:120px;">KG/HÁ Base</th>
                                        <th class="text-center text-success" style="min-width:120px;">KG/HÁ Optimista</th>
                                        <th class="text-center" style="width:80px;">Copiar ↓</th>
                                        <th class="text-center" style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(bRow, i) in bulkRows" :key="i">
                                        <td class="p-1">
                                            <select v-model="bRow.week" class="form-select form-select-sm text-center">
                                                <option value="" disabled>Sem...</option>
                                                <option v-for="w in weeks" :key="w" :value="w">S{{ w }}</option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <input v-model.number="bRow.kg_pessimistic" type="number" min="0" class="form-control form-control-sm text-center" placeholder="0" />
                                        </td>
                                        <td class="p-1">
                                            <input v-model.number="bRow.kg_base" type="number" min="0" class="form-control form-control-sm text-center" placeholder="0" />
                                        </td>
                                        <td class="p-1">
                                            <input v-model.number="bRow.kg_optimistic" type="number" min="0" class="form-control form-control-sm text-center" placeholder="0" />
                                        </td>
                                        <td class="text-center p-1">
                                            <button
                                                type="button"
                                                class="btn btn-icon btn-active-light-primary w-28px h-28px"
                                                @click="copyRowDown(i)"
                                                v-tooltip="i + 1 < bulkRows.length ? 'Copiar KG a la fila siguiente' : 'Copiar KG y agregar nueva fila'"
                                            >
                                                <i class="fas fa-arrow-down" style="font-size:0.7rem;"></i>
                                            </button>
                                        </td>
                                        <td class="text-center p-1">
                                            <button
                                                type="button"
                                                class="btn btn-icon btn-active-light-danger w-28px h-28px"
                                                @click="removeBulkRow(i)"
                                                :disabled="bulkRows.length === 1"
                                            >
                                                <i class="fas fa-times" style="font-size:0.7rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <span class="text-muted small me-auto">
                            {{ bulkRows.filter(r => r.week !== '').length }} semana(s) lista(s) para guardar
                        </span>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="submitBulk"
                            :disabled="submittingBulk || !bulkVarietyId || bulkHectares === '' || bulkRows.filter(r => r.week !== '').length === 0"
                        >
                            <i class="fas fa-save me-1"></i>
                            Guardar {{ bulkRows.filter(r => r.week !== '').length > 0 ? bulkRows.filter(r => r.week !== '').length : '' }} fila(s)
                            <span v-if="submittingBulk" class="spinner-border spinner-border-sm ms-1"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Fila de Composición ───────────────────────────────────── -->
        <div class="modal fade" id="rowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" :key="'rowModal-' + (editRowId || 'new')">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="fas fa-plus me-2 text-primary" v-if="!editRowId"></i>
                            <i class="fas fa-edit me-2 text-primary" v-else></i>
                            {{ editRowId ? 'Editar fila' : 'Nueva fila de composición' }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Filtro Frutal -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Frutal <span class="text-muted small">(filtro)</span></label>
                                <select v-model="filterFruitId" @change="onFruitChange" class="form-select form-select-sm">
                                    <option value="">Todos los frutales</option>
                                    <option v-for="f in fruits" :key="f.id" :value="String(f.id)">{{ f.name }}</option>
                                </select>
                            </div>
                            <!-- Variedad filtrada -->
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Variedad <span class="text-danger">*</span></label>
                                <select v-model="rowForm.variety_id" class="form-select form-select-sm">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="v in filteredVarieties" :key="v.id" :value="String(v.id)">{{ v.name }}</option>
                                </select>
                                <small v-if="filterFruitId && filteredVarieties.length === 0" class="text-warning">
                                    Sin variedades para este frutal.
                                </small>
                            </div>
                            <!-- Semana -->
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Semana <span class="text-danger">*</span></label>
                                <select v-model="rowForm.week" class="form-select form-select-sm">
                                    <option value="" disabled>Sem...</option>
                                    <option v-for="w in weeks" :key="w" :value="w">S{{ w }}</option>
                                </select>
                            </div>
                            <!-- Números -->
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Hectáreas <span class="text-danger">*</span></label>
                                <input v-model.number="rowForm.hectares" type="number" min="0" step="0.1" class="form-control form-control-sm" placeholder="0.0" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-warning">KG/HÁ Pesimista</label>
                                <input v-model.number="rowForm.kg_pessimistic" type="number" min="0" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-primary">KG/HÁ Base</label>
                                <input v-model.number="rowForm.kg_base" type="number" min="0" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-success">KG/HÁ Optimista</label>
                                <input v-model.number="rowForm.kg_optimistic" type="number" min="0" class="form-control form-control-sm" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="submitRow"
                            :disabled="!rowForm.variety_id || rowForm.variety_id === '' || !rowForm.week || rowForm.week === '' || rowForm.hectares === '' || rowForm.hectares === null || rowForm.hectares === undefined"
                        >
                            <i class="fas fa-save me-1"></i>{{ editRowId ? 'Actualizar' : 'Agregar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
