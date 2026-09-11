<script setup>
import { ref, computed } from 'vue';
import FalconPieChart from '@/Components/FalconPieChart.vue';

const props = defineProps({
    show: Boolean,
    costCenters: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

// Umbral de diferencia (%) a partir del cual se resalta con color
const THRESHOLD = 10;

const colClass = computed(() => {
    const n = props.costCenters.length || 1;
    return 'col-lg-' + Math.max(3, Math.floor(12 / n));
});

// ── Categorías de costo (unión de todas las presentes en los cuarteles comparados) ──
const allCategorias = computed(() => {
    const set = new Set();
    props.costCenters.forEach(cc => (cc.costos || []).forEach(c => set.add(c.categoria)));
    // Agroquímicos y Fertilizantes siempre primero, por ser el foco del análisis
    const arr = Array.from(set);
    const priority = ['Agroquimicos', 'Fertilizantes'];
    return [
        ...priority.filter(p => arr.includes(p)),
        ...arr.filter(a => !priority.includes(a)).sort(),
    ];
});

function costoHaFor(cc, categoria) {
    const found = (cc.costos || []).find(c => c.categoria === categoria);
    return found ? found.per_ha : 0;
}

function avgCostoHa(categoria) {
    if (!props.costCenters.length) return 0;
    const vals = props.costCenters.map(cc => costoHaFor(cc, categoria));
    return vals.reduce((a, b) => a + b, 0) / vals.length;
}

function avgKgHa() {
    if (!props.costCenters.length) return 0;
    const vals = props.costCenters.map(cc => cc.kg?.per_ha || 0);
    return vals.reduce((a, b) => a + b, 0) / vals.length;
}

// higherIsBetter=false para costos (más costo = peor, rojo); true para kg/ha (más kg = mejor, verde)
function deltaInfo(value, avg, higherIsBetter = false) {
    if (!avg) return { class: 'text-muted', icon: '', pct: 0 };
    const diffPct = ((value - avg) / avg) * 100;
    if (Math.abs(diffPct) < THRESHOLD) return { class: 'text-muted', icon: 'fa-minus', pct: diffPct };
    const isGood = higherIsBetter ? diffPct > 0 : diffPct < 0;
    return {
        class: isGood ? 'text-success' : 'text-danger',
        icon: diffPct > 0 ? 'fa-caret-up' : 'fa-caret-down',
        pct: diffPct,
    };
}

function formatNumber(val, decimals = 0) {
    if (val === null || val === undefined) return '-';
    return Number(val).toLocaleString('es-CL', { maximumFractionDigits: decimals, minimumFractionDigits: decimals });
}

// ── Expandir detalle de producto por cuartel ──────────────────────────────────
const expanded = ref({}); // { [ccId + '-agro']: true, [ccId + '-fert']: true }

function toggleDetail(ccId, tipo) {
    const key = `${ccId}-${tipo}`;
    expanded.value = { ...expanded.value, [key]: !expanded.value[key] };
}

function isExpanded(ccId, tipo) {
    return !!expanded.value[`${ccId}-${tipo}`];
}

function calibrePie(cc) {
    return [{ data: (cc.calibre || []).map(c => c.kg) }];
}
function calibreLabels(cc) {
    return (cc.calibre || []).map(c => c.value);
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.35);" v-if="show">
        <div class="modal-dialog modal-fullscreen-lg-down" style="max-width: 1400px; width: 95%;">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-balance-scale-right text-primary me-2 fs-8"></i>
                        Comparación de Cuarteles
                    </h5>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                    <div class="row g-3">
                        <div v-for="cc in costCenters" :key="cc.id" :class="colClass">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-header bg-white border-bottom py-2">
                                    <h6 class="mb-0 fw-bold">{{ cc.name }}</h6>
                                    <small class="text-muted">
                                        {{ cc.fruit_name }} · {{ cc.variety_name }}
                                        <span v-if="cc.branch_name"> · {{ cc.branch_name }}</span>
                                        · {{ formatNumber(cc.surface, 2) }} ha
                                    </small>
                                </div>
                                <div class="card-body">
                                    <!-- Kg / Ha -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="text-muted small">Kg Cosechados</span>
                                            <span class="fw-bold">{{ formatNumber(cc.kg?.total) }} kg</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="text-muted small">Kg / Ha</span>
                                            <span class="fw-bold" :class="deltaInfo(cc.kg?.per_ha, avgKgHa(), true).class">
                                                <i class="fas me-1" :class="deltaInfo(cc.kg?.per_ha, avgKgHa(), true).icon"></i>
                                                {{ formatNumber(cc.kg?.per_ha) }}
                                            </span>
                                        </div>
                                        <div v-if="!cc.kg?.total" class="text-muted small fst-italic mt-1">
                                            <i class="fas fa-info-circle me-1"></i>Sin datos de producción procesada
                                        </div>
                                    </div>

                                    <!-- Calibre -->
                                    <div class="mb-3" v-if="cc.calibre && cc.calibre.length">
                                        <span class="text-muted small d-block mb-1">Distribución de Calibre</span>
                                        <FalconPieChart :key="cc.id" :pie-labels="calibreLabels(cc)" :pie-datasets="calibrePie(cc)" :show-percentage="true" />
                                    </div>

                                    <hr />

                                    <!-- Costos por categoría -->
                                    <div class="mb-2">
                                        <span class="text-muted small d-block mb-2">Costo por Hectárea (según categoría)</span>
                                        <div v-for="categoria in allCategorias" :key="categoria" class="d-flex justify-content-between align-items-baseline mb-1">
                                            <span class="small" :class="{ 'fw-bold': ['Agroquimicos', 'Fertilizantes'].includes(categoria) }">
                                                {{ categoria }}
                                            </span>
                                            <span
                                                class="small"
                                                :class="deltaInfo(costoHaFor(cc, categoria), avgCostoHa(categoria), false).class"
                                            >
                                                <i class="fas me-1" :class="deltaInfo(costoHaFor(cc, categoria), avgCostoHa(categoria), false).icon"></i>
                                                ${{ formatNumber(costoHaFor(cc, categoria)) }}
                                            </span>
                                        </div>
                                    </div>

                                    <hr />

                                    <!-- Detalle Agroquímicos -->
                                    <div class="mb-2">
                                        <button
                                            class="btn btn-sm btn-falcon-default w-100 d-flex align-items-center justify-content-between"
                                            @click="toggleDetail(cc.id, 'agro')"
                                        >
                                            <span><i class="fas fa-flask me-1"></i>Detalle Agroquímicos</span>
                                            <i class="fas" :class="isExpanded(cc.id, 'agro') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </button>
                                        <div v-if="isExpanded(cc.id, 'agro')" class="mt-2">
                                            <div v-if="!cc.agroquimicos || cc.agroquimicos.length === 0" class="text-muted small fst-italic">
                                                Sin aplicaciones registradas
                                            </div>
                                            <div v-for="sub in cc.agroquimicos" :key="sub.subfamilia" class="mb-2">
                                                <div class="fw-bold small text-uppercase text-primary">{{ sub.subfamilia }}</div>
                                                <table class="table table-sm mb-1">
                                                    <thead>
                                                        <tr class="text-muted" style="font-size: 0.7rem;">
                                                            <th>Producto</th>
                                                            <th class="text-end">Dosis/Ha</th>
                                                            <th class="text-end">N° Aplic.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="p in sub.productos" :key="p.producto" style="font-size: 0.75rem;">
                                                            <td>{{ p.producto }}</td>
                                                            <td class="text-end">{{ formatNumber(p.dosis_ha, 2) }} {{ p.unidad }}</td>
                                                            <td class="text-end">{{ p.n_aplicaciones }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Detalle Fertilizantes -->
                                    <div>
                                        <button
                                            class="btn btn-sm btn-falcon-default w-100 d-flex align-items-center justify-content-between"
                                            @click="toggleDetail(cc.id, 'fert')"
                                        >
                                            <span><i class="fas fa-leaf me-1"></i>Detalle Fertilizantes</span>
                                            <i class="fas" :class="isExpanded(cc.id, 'fert') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </button>
                                        <div v-if="isExpanded(cc.id, 'fert')" class="mt-2">
                                            <div v-if="!cc.fertilizantes || cc.fertilizantes.length === 0" class="text-muted small fst-italic">
                                                Sin aplicaciones registradas
                                            </div>
                                            <div v-for="sub in cc.fertilizantes" :key="sub.subfamilia" class="mb-2">
                                                <div class="fw-bold small text-uppercase text-success">{{ sub.subfamilia }}</div>
                                                <table class="table table-sm mb-1">
                                                    <thead>
                                                        <tr class="text-muted" style="font-size: 0.7rem;">
                                                            <th>Producto</th>
                                                            <th class="text-end">Dosis/Ha</th>
                                                            <th class="text-end">N° Aplic.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="p in sub.productos" :key="p.producto" style="font-size: 0.75rem;">
                                                            <td>{{ p.producto }}</td>
                                                            <td class="text-end">{{ formatNumber(p.dosis_ha, 2) }} {{ p.unidad }}</td>
                                                            <td class="text-end">{{ p.n_aplicaciones }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="emit('close')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>
