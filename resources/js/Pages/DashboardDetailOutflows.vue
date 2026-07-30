<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    consumoPorSucursal: { type: Array, default: () => [] },
    stockValorizado: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
});

const title = 'Detalle de Salidas por Sucursal';

// Normaliza el stock valorizado para usar el mismo campo 'amount' que el consumo
const normalizedStock = computed(() => props.stockValorizado.map(r => ({ ...r, amount: r.valor })));

const formatNumber = (value) => new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(value || 0));

// Cards apiladas: Consumo + Stock Valorizado por sucursal
const branchTotals = computed(() => {
    const map = {};
    props.consumoPorSucursal.forEach(r => {
        const name = r.branch_name || 'Sin sucursal';
        if (!map[name]) map[name] = { name, consumo: 0, stock: 0 };
        map[name].consumo += Number(r.amount || 0);
    });
    props.stockValorizado.forEach(r => {
        const name = r.branch_name || 'Sin sucursal';
        if (!map[name]) map[name] = { name, consumo: 0, stock: 0 };
        map[name].stock += Number(r.valor || 0);
    });
    return Object.values(map)
        .map(b => ({ ...b, total: b.consumo + b.stock }))
        .sort((a, b) => b.total - a.total);
});

const totalConsumo = computed(() => props.consumoPorSucursal.reduce((sum, r) => sum + Number(r.amount || 0), 0));
const totalStock = computed(() => props.stockValorizado.reduce((sum, r) => sum + Number(r.valor || 0), 0));
const totalGeneral = computed(() => totalConsumo.value + totalStock.value);

// Filtro de sucursal (local) y selector de indicador para la tabla por nivel
const selectedBranch = ref('');
const tableMode = ref('consumo'); // 'consumo' | 'stock'

// Árbol Nivel1 -> Nivel2 -> Nivel3 según el indicador y sucursal seleccionados
const levelTree = computed(() => {
    const source = tableMode.value === 'consumo' ? props.consumoPorSucursal : normalizedStock.value;
    // Se filtra por nombre de sucursal (no por id) porque algunas salidas antiguas quedaron
    // asociadas al id de la sucursal de una temporada previa con el mismo nombre.
    const rows = selectedBranch.value
        ? source.filter(r => (r.branch_name || 'Sin sucursal') === selectedBranch.value)
        : source;

    const l1Map = {};
    rows.forEach(r => {
        const amount = Number(r.amount || 0);
        const l1Key = r.level1_id ?? 'null';
        if (!l1Map[l1Key]) {
            l1Map[l1Key] = { level1_id: r.level1_id, level1_name: r.level1_name || 'Sin Clasificar', total: 0, level2s: {} };
        }
        l1Map[l1Key].total += amount;

        const l2Key = r.level2_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key]) {
            l1Map[l1Key].level2s[l2Key] = { level2_id: r.level2_id, level2_name: r.level2_name || 'Sin Clasificar', total: 0, level3s: {} };
        }
        l1Map[l1Key].level2s[l2Key].total += amount;

        const l3Key = r.level3_id ?? 'null';
        if (!l1Map[l1Key].level2s[l2Key].level3s[l3Key]) {
            l1Map[l1Key].level2s[l2Key].level3s[l3Key] = { level3_id: r.level3_id, level3_name: r.level3_name || 'Sin Clasificar', total: 0 };
        }
        l1Map[l1Key].level2s[l2Key].level3s[l3Key].total += amount;
    });

    return Object.values(l1Map).map(g => ({
        ...g,
        level2s: Object.values(g.level2s).map(l2 => ({
            ...l2,
            level3s: Object.values(l2.level3s).sort((a, b) => b.total - a.total),
        })).sort((a, b) => b.total - a.total),
    })).sort((a, b) => b.total - a.total);
});

const levelTreeGrandTotal = computed(() => levelTree.value.reduce((sum, g) => sum + g.total, 0));

// Control de expandir/colapsar (Nivel1 -> Nivel2 y Nivel2 -> Nivel3)
const expandedL1 = ref(new Set());
const expandedL2 = ref(new Set());

const toggleL1 = (key) => {
    if (expandedL1.value.has(key)) expandedL1.value.delete(key);
    else expandedL1.value.add(key);
    expandedL1.value = new Set(expandedL1.value);
};
const toggleL2 = (key) => {
    if (expandedL2.value.has(key)) expandedL2.value.delete(key);
    else expandedL2.value.add(key);
    expandedL2.value = new Set(expandedL2.value);
};
const expandAllLevels = () => {
    expandedL1.value = new Set(levelTree.value.map(g => 'l1-' + g.level1_id));
    const l2Keys = [];
    levelTree.value.forEach(g => g.level2s.forEach(l2 => l2Keys.push('l2-' + g.level1_id + '-' + l2.level2_id)));
    expandedL2.value = new Set(l2Keys);
};
const collapseAllLevels = () => {
    expandedL1.value = new Set();
    expandedL2.value = new Set();
};
</script>

<template>
    <Head :title="title" />
    <AppLayout title="Detalle de Salidas por Sucursal">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-code-branch me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Cards apiladas por sucursal: Consumo + Stock Valorizado -->
                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3" v-for="b in branchTotals" :key="b.name">
                        <div class="card h-100 border border-primary">
                            <div class="card-header py-2 bg-primary bg-opacity-10">
                                <h6 class="mb-0 text-primary fw-bold fs-10 text-nowrap"><i class="fas fa-code-branch me-1"></i>{{ b.name }}</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <small class="text-muted">Consumo</small>
                                    <strong>{{ formatNumber(b.consumo) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted">Stock Valorizado</small>
                                    <strong>{{ formatNumber(b.stock) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted fw-semibold">Total</small>
                                    <strong class="text-primary">{{ formatNumber(b.total) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                        <div class="card h-100 border border-success">
                            <div class="card-header py-2 bg-success bg-opacity-10">
                                <h6 class="mb-0 text-success fw-bold fs-10 text-nowrap"><i class="fas fa-layer-group me-1"></i>Totales Generales</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <small class="text-muted">Total Consumo</small>
                                    <strong>{{ formatNumber(totalConsumo) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted">Total Stock Valorizado</small>
                                    <strong>{{ formatNumber(totalStock) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-top">
                                    <small class="text-muted fw-semibold">Total General</small>
                                    <strong class="text-success">{{ formatNumber(totalGeneral) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros para la tabla por nivel -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <label class="form-label mb-0 small fw-semibold text-muted">Sucursal:</label>
                    <select v-model="selectedBranch" class="form-select form-select-sm" style="width:220px;">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.value" :value="b.label">{{ b.label }}</option>
                    </select>

                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn" :class="tableMode === 'consumo' ? 'btn-primary' : 'btn-outline-primary'" @click="tableMode = 'consumo'">Consumo</button>
                        <button type="button" class="btn" :class="tableMode === 'stock' ? 'btn-primary' : 'btn-outline-primary'" @click="tableMode = 'stock'">Stock Valorizado</button>
                    </div>

                    <div class="btn-group btn-group-sm ms-auto" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllLevels" v-tooltip="'Expandir todo'">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllLevels" v-tooltip="'Colapsar todo'">
                            <i class="fas fa-compress-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabla Nivel 1 / Nivel 2 / Nivel 3 -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-primary">
                            <tr>
                                <th class="border-0 py-2">Nivel 1 / Nivel 2 / Nivel 3</th>
                                <th class="border-0 py-2 text-end">Monto</th>
                                <th class="border-0 py-2 text-end">% Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!levelTree.length">
                                <td colspan="3" class="text-center py-4 text-muted">No hay datos para mostrar</td>
                            </tr>
                            <template v-for="g in levelTree" :key="'l1-' + g.level1_id">
                                <tr class="table-light" style="cursor:pointer;" @click="toggleL1('l1-' + g.level1_id)">
                                    <td class="py-2 fw-bold text-primary">
                                        <i class="fas me-2" :class="expandedL1.has('l1-' + g.level1_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ g.level1_name }}
                                        <small class="text-muted ms-1">({{ g.level2s.length }})</small>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-primary">{{ formatNumber(g.total) }}</td>
                                    <td class="py-2 text-end">
                                        <span class="badge bg-primary">{{ levelTreeGrandTotal > 0 ? ((g.total / levelTreeGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                                    </td>
                                </tr>
                                <template v-if="expandedL1.has('l1-' + g.level1_id)">
                                    <template v-for="l2 in g.level2s" :key="'l2-' + g.level1_id + '-' + l2.level2_id">
                                        <tr style="cursor:pointer;" @click="toggleL2('l2-' + g.level1_id + '-' + l2.level2_id)">
                                            <td class="py-2 ps-5">
                                                <i class="fas me-2" :class="expandedL2.has('l2-' + g.level1_id + '-' + l2.level2_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                {{ l2.level2_name }}
                                                <small class="text-muted ms-1">({{ l2.level3s.length }})</small>
                                            </td>
                                            <td class="py-2 text-end">{{ formatNumber(l2.total) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-secondary">{{ g.total > 0 ? ((l2.total / g.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                        <tr v-if="expandedL2.has('l2-' + g.level1_id + '-' + l2.level2_id)" v-for="l3 in l2.level3s" :key="'l3-' + g.level1_id + '-' + l2.level2_id + '-' + l3.level3_id">
                                            <td class="py-2 ps-7">{{ l3.level3_name }}</td>
                                            <td class="py-2 text-end">{{ formatNumber(l3.total) }}</td>
                                            <td class="py-2 text-end">
                                                <span class="badge bg-light text-dark">{{ l2.total > 0 ? ((l3.total / l2.total) * 100).toFixed(1) : '0.0' }}%</span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                        <tfoot v-if="levelTree.length">
                            <tr class="table-primary fw-bold">
                                <td class="py-2">Total</td>
                                <td class="py-2 text-end">{{ formatNumber(levelTreeGrandTotal) }}</td>
                                <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
