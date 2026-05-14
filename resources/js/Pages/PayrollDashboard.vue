<template>
    <AppLayout title="Dashboard de Remuneraciones">
        <div class="card my-3">
            <!-- HEADER -->
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center gap-2 pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-chart-bar me-2"></i>Dashboard de Remuneraciones
                        </h5>
                        <select v-model="selectedBranch" class="form-select form-select-sm" style="min-width:150px;">
                            <option value="">Todas las sucursales</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <select v-model="selectedMonth" class="form-select form-select-sm" style="min-width:160px;">
                            <option value="all">Temporada completa</option>
                            <option v-for="m in months" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto text-end ps-0"></div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- KPI CARDS -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card h-100 text-center py-2 px-2">
                            <div class="text-muted" style="font-size:0.7rem;">
                                {{ selectedMonth === 'all' ? 'Total Temporada' : 'Total ' + currentMonthName }}
                            </div>
                            <div class="fw-bold text-dark" style="font-size:0.85rem;">$ {{ fmt(currentData.amount) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 text-center py-2 px-2">
                            <div class="text-muted" style="font-size:0.7rem;">
                                {{ selectedMonth === 'all' ? 'Jornadas Temporada' : 'Jornadas ' + currentMonthName }}
                            </div>
                            <div class="fw-bold text-dark" style="font-size:0.85rem;">{{ fmtDec(currentData.workdays) }} JH</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 text-center py-2 px-2">
                            <div class="text-muted" style="font-size:0.7rem;">Total Acumulado Temporada</div>
                            <div class="fw-bold text-dark" style="font-size:0.85rem;">$ {{ fmt(seasonTotals.amount) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 text-center py-2 px-2">
                            <div class="text-muted" style="font-size:0.7rem;">Promedio $/JH (acumulado)</div>
                            <div class="fw-bold text-dark" style="font-size:0.85rem;">
                                {{ seasonTotals.workdays > 0 ? '$ ' + fmt(Math.round(seasonTotals.amount / seasonTotals.workdays)) : '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICOS -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="card p-2">
                            <div class="text-muted small mb-1 ps-1 fw-semibold">Total Mensual ($)</div>
                            <canvas ref="amountsChartRef" style="max-height:220px;"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card p-2">
                            <div class="text-muted small mb-1 ps-1 fw-semibold">Jornadas Mensuales (JH)</div>
                            <canvas ref="workdaysChartRef" style="max-height:220px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- TABLAS SUCURSAL + PARCELA (50/50) -->
                <div class="row g-3 mb-3">
                    <!-- Sucursal -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">
                                    <i class="fas fa-building me-1 text-muted"></i>Desglose por Sucursal
                                    <span v-if="selectedParcel" @click="selectedParcel = ''" title="Quitar filtro" class="badge bg-soft-success text-success ms-1" style="font-size:0.68rem;cursor:pointer;">
                                        {{ selectedParcelName }} ×
                                    </span>
                                </span>
                                <span class="badge bg-soft-secondary text-secondary" style="font-size:0.7rem;">{{ currentMonthLabel }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
                                        <thead style="background:#eaf0f6;">
                                            <tr>
                                                <th>Sucursal</th>
                                                <th class="text-end">Monto</th>
                                                <th class="text-end">Jornadas</th>
                                                <th class="text-end">$/JH</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="currentByBranch.length === 0">
                                                <tr><td colspan="4" class="text-center text-muted py-3">Sin datos para este período</td></tr>
                                            </template>
                                            <tr v-for="(row, idx) in currentByBranch" :key="idx"
                                                style="cursor:pointer;"
                                                :class="{ 'table-active fw-semibold': selectedBranch && String(selectedBranch) === String(row.branch_id) }"
                                                @click="selectBranch(row.branch_id)">
                                                <td>
                                                    <span v-if="String(selectedBranch) === String(row.branch_id)" class="me-1 text-primary" style="font-size:0.7rem;">▶</span>
                                                    {{ row.branch_name }}
                                                </td>
                                                <td class="text-end">$ {{ fmt(Math.round(row.amount)) }}</td>
                                                <td class="text-end">{{ fmtDec(row.workdays) }}</td>
                                                <td class="text-end text-muted">
                                                    {{ row.workdays > 0 ? '$ ' + fmt(Math.round(row.amount / row.workdays)) : '—' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot v-if="currentByBranch.length > 0">
                                            <tr class="fw-bold" style="background:#dce8f0;">
                                                <td>TOTAL</td>
                                                <td class="text-end">$ {{ fmt(Math.round(branchTotals.amount)) }}</td>
                                                <td class="text-end">{{ fmtDec(branchTotals.workdays) }}</td>
                                                <td class="text-end">
                                                    {{ branchTotals.workdays > 0 ? '$ ' + fmt(Math.round(branchTotals.amount / branchTotals.workdays)) : '—' }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parcela -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">
                                    <i class="fas fa-map-marked-alt me-1 text-muted"></i>Desglose por Parcela
                                </span>
                                <span v-if="selectedBranch" @click="selectedBranch = ''" title="Quitar filtro" class="badge bg-soft-primary text-primary ms-1" style="font-size:0.68rem;cursor:pointer;">
                                    {{ branches.find(b => String(b.id) === String(selectedBranch))?.name ?? 'Sucursal' }} ×
                                </span>
                                <span class="badge bg-soft-secondary text-secondary" style="font-size:0.7rem;">{{ currentMonthLabel }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
                                        <thead style="background:#eaf0f6;">
                                            <tr>
                                                <th>Parcela</th>
                                                <th class="text-end">Monto</th>
                                                <th class="text-end">Jornadas</th>
                                                <th class="text-end">$/JH</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="currentByParcel.length === 0">
                                                <tr><td colspan="4" class="text-center text-muted py-3">Sin datos para este período</td></tr>
                                            </template>
                                            <tr v-for="(row, idx) in currentByParcel" :key="idx"
                                                style="cursor:pointer;"
                                                :class="{ 'table-active fw-semibold': selectedParcel && String(selectedParcel) === String(row.parcel_id) }"
                                                @click="selectParcel(row.parcel_id)">
                                                <td class="fw-semibold">
                                                    <span v-if="String(selectedParcel) === String(row.parcel_id)" class="me-1 text-success" style="font-size:0.7rem;">▶</span>
                                                    {{ row.parcel_name }}
                                                </td>
                                                <td class="text-end">$ {{ fmt(Math.round(row.amount)) }}</td>
                                                <td class="text-end">{{ fmtDec(row.workdays) }}</td>
                                                <td class="text-end text-muted">
                                                    {{ row.workdays > 0 ? '$ ' + fmt(Math.round(row.amount / row.workdays)) : '—' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot v-if="currentByParcel.length > 0">
                                            <tr class="fw-bold" style="background:#dce8f0;">
                                                <td>TOTAL</td>
                                                <td class="text-end">$ {{ fmt(Math.round(parcelTotals.amount)) }}</td>
                                                <td class="text-end">{{ fmtDec(parcelTotals.workdays) }}</td>
                                                <td class="text-end">
                                                    {{ parcelTotals.workdays > 0 ? '$ ' + fmt(Math.round(parcelTotals.amount / parcelTotals.workdays)) : '—' }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- row sucursal/parcela -->

                <!-- TABLA LEVEL2 → LEVEL3 -->
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small">
                            <i class="fas fa-sitemap me-1 text-muted"></i>Desglose por Tipo de Mano de Obra
                            <span v-if="selectedBranch" @click="selectedBranch = ''" title="Quitar filtro" class="badge bg-soft-primary text-primary ms-1" style="font-size:0.68rem;cursor:pointer;">
                                {{ branches.find(b => String(b.id) === String(selectedBranch))?.name ?? 'Sucursal' }} ×
                            </span>
                            <span v-if="selectedParcel" @click="selectedParcel = ''" title="Quitar filtro" class="badge bg-soft-success text-success ms-1" style="font-size:0.68rem;cursor:pointer;">
                                {{ selectedParcelName }} ×
                            </span>
                        </span>
                        <span class="badge bg-soft-secondary text-secondary" style="font-size:0.7rem;">{{ currentMonthLabel }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
                                <thead style="background:#eaf0f6;">
                                    <tr>
                                        <th>Categoría (Level 2)</th>
                                        <th>Tipo de Labor (Level 3)</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end">Jornadas</th>
                                        <th class="text-end">$/JH</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="currentByLevel.length === 0">
                                        <tr><td colspan="5" class="text-center text-muted py-3">Sin datos para este período</td></tr>
                                    </template>
                                    <template v-else>
                                        <template v-for="(row, idx) in currentByLevel" :key="idx">
                                            <tr>
                                                <td class="text-muted">{{ row.level2 }}</td>
                                                <td class="fw-semibold">{{ row.level3 }}</td>
                                                <td class="text-end">$ {{ fmt(row.amount) }}</td>
                                                <td class="text-end">{{ fmtDec(row.workdays) }}</td>
                                                <td class="text-end text-muted">
                                                    {{ row.workdays > 0 ? '$ ' + fmt(Math.round(row.amount / row.workdays)) : '—' }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                                <tfoot v-if="currentByLevel.length > 0">
                                    <tr class="fw-bold" style="background:#dce8f0;">
                                        <td colspan="2">TOTAL</td>
                                        <td class="text-end">$ {{ fmt(levelTotals.amount) }}</td>
                                        <td class="text-end">{{ fmtDec(levelTotals.workdays) }}</td>
                                        <td class="text-end">
                                            {{ levelTotals.workdays > 0 ? '$ ' + fmt(Math.round(levelTotals.amount / levelTotals.workdays)) : '—' }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TABLA TRATOS -->
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small">
                            <i class="fas fa-handshake me-1 text-muted"></i>Tratos
                            <span v-if="selectedBranch" @click="selectedBranch = ''" title="Quitar filtro" class="badge bg-soft-primary text-primary ms-1" style="font-size:0.68rem;cursor:pointer;">
                                {{ branches.find(b => String(b.id) === String(selectedBranch))?.name ?? 'Sucursal' }} ×
                            </span>
                            <span v-if="selectedParcel" @click="selectedParcel = ''" title="Quitar filtro" class="badge bg-soft-success text-success ms-1" style="font-size:0.68rem;cursor:pointer;">
                                {{ selectedParcelName }} ×
                            </span>
                        </span>
                        <span class="badge bg-soft-secondary text-secondary" style="font-size:0.7rem;">{{ currentMonthLabel }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
                                <thead style="background:#eaf0f6;">
                                    <tr>
                                        <th>Nombre del Trato</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="currentByTrato.length === 0">
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin tratos para este período</td></tr>
                                    </template>
                                    <tr v-for="(row, idx) in currentByTrato" :key="idx">
                                        <td>{{ row.trato_name }}</td>
                                        <td class="text-end text-muted">$ {{ fmt(row.price) }}</td>
                                        <td class="text-end">{{ fmtDec(row.quantity) }}</td>
                                        <td class="text-end fw-semibold">$ {{ fmt(Math.round(row.amount)) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="currentByTrato.length > 0">
                                    <tr class="fw-bold" style="background:#dce8f0;">
                                        <td colspan="2">TOTAL</td>
                                        <td class="text-end">{{ fmtDec(tratoTotals.quantity) }}</td>
                                        <td class="text-end">$ {{ fmt(Math.round(tratoTotals.amount)) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- TABLA: LABORES POR CENTRO DE COSTO                       -->
                <!-- ======================================================= -->
                <div class="row g-2 mt-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0" style="font-size:0.82rem;">
                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                Labores por Centro de Costo
                                <span v-if="selectedMonth !== 'all'" class="badge bg-soft-primary text-primary ms-1" style="font-size:0.7rem;">
                                    {{ currentMonthName }}
                                </span>
                                <span v-if="selectedParcel" @click="selectedParcel = ''" title="Quitar filtro" class="badge bg-soft-success text-success ms-1" style="font-size:0.68rem;cursor:pointer;">
                                    {{ selectedParcelName }} ×
                                </span>
                            </h6>
                            <ExportExcelButton
                                :data="currentByCostCenter"
                                :headers="[
                                    { label: 'Sucursal',        key: 'branch' },
                                    { label: 'Parcela',         key: 'parcel' },
                                    { label: 'Centro de Costo', key: 'cost_center' },
                                    { label: 'Level 1',         key: 'level1' },
                                    { label: 'Level 2',         key: 'level2' },
                                    { label: 'Level 3',         key: 'level3' },
                                    { label: 'Labor',           key: 'labor_type' },
                                    { label: 'Jornadas',        key: 'workdays',  type: 'number' },
                                    { label: 'Valor Total',     key: 'amount',    type: 'number' },
                                ]"
                                filename="Labores_por_CC.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
                        </div>
                        <div class="table-responsive" style="max-height:500px; overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
                                <thead style="position:sticky; top:0; z-index:2; background:#eaf0f6;">
                                    <tr>
                                        <th>Sucursal</th>
                                        <th>Parcela</th>
                                        <th>Centro de Costo</th>
                                        <th>Level 1</th>
                                        <th>Level 2</th>
                                        <th>Level 3</th>
                                        <th>Labor</th>
                                        <th class="text-end">Jornadas</th>
                                        <th class="text-end">Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="currentByCostCenter.length === 0">
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-3">Sin datos para el período seleccionado</td>
                                        </tr>
                                    </template>
                                    <tr v-for="(row, idx) in currentByCostCenter" :key="idx">
                                        <td>{{ row.branch }}</td>
                                        <td>{{ row.parcel }}</td>
                                        <td class="fw-semibold">{{ row.cost_center }}</td>
                                        <td>{{ row.level1 }}</td>
                                        <td>{{ row.level2 }}</td>
                                        <td>{{ row.level3 }}</td>
                                        <td>{{ row.labor_type }}</td>
                                        <td class="text-end">{{ fmtDec(row.workdays) }}</td>
                                        <td class="text-end">$ {{ fmt(Math.round(row.amount)) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="currentByCostCenter.length > 0" style="background:#dce8f5; font-weight:600;">
                                    <tr>
                                        <td colspan="7" class="text-end">Totales</td>
                                        <td class="text-end">{{ fmtDec(ccTotals.workdays) }}</td>
                                        <td class="text-end">$ {{ fmt(Math.round(ccTotals.amount)) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- card-body -->
        </div><!-- card -->
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Chart, registerables } from 'chart.js';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

Chart.register(...registerables);

const props = defineProps({
    byMonth:          { type: Object, default: () => ({}) },
    byLevel:          { type: Object, default: () => ({}) },
    byParcel:         { type: Object, default: () => ({}) },
    byBranch:         { type: Object, default: () => ({}) },
    byTrato:          { type: Object, default: () => ({}) },
    byCostCenter:     { type: Object, default: () => ({}) },
    seasonTotals:     { type: Object, default: () => ({ amount: 0, workdays: 0 }) },
    chartData:        { type: Object, default: () => ({ labels: [], amounts: [], workdays: [] }) },
    months:           { type: Array,  default: () => [] },
    seasonStartMonth: { type: Number, default: 1 },
    branches:         { type: Array,  default: () => [] },
    parcelBranchMap:  { type: Object, default: () => ({}) },
});

// ——— Estado ———
const selectedMonth    = ref('all');
const selectedBranch   = ref('');
const selectedParcel   = ref('');
const amountsChartRef  = ref(null);
const workdaysChartRef = ref(null);
let amountsChart  = null;
let workdaysChart = null;

// ——— Selección interactiva (exclusión mutua) ———
const selectBranch = (branchId) => {
    if (String(selectedBranch.value) === String(branchId)) {
        selectedBranch.value = '';
    } else {
        selectedBranch.value = branchId;
        selectedParcel.value = '';
    }
};

const selectParcel = (parcelId) => {
    if (String(selectedParcel.value) === String(parcelId)) {
        selectedParcel.value = '';
    } else {
        selectedParcel.value = parcelId;
        selectedBranch.value = '';
    }
};

const selectedParcelName = computed(() => {
    if (!selectedParcel.value) return '';
    const all = props.byParcel['all'] ?? [];
    return all.find(r => String(r.parcel_id) === String(selectedParcel.value))?.parcel_name ?? 'Parcela';
});

// ——— Computed: mes actual (para etiqueta) ———
const currentMonthName = computed(() => {
    if (selectedMonth.value === 'all') return '';
    return props.months.find(m => m.id === selectedMonth.value)?.name ?? '';
});

const currentMonthLabel = computed(() =>
    selectedMonth.value === 'all' ? 'Temporada completa' : currentMonthName.value
);

// ——— Computed: datos del período seleccionado ———
const currentData = computed(() => {
    if (selectedMonth.value === 'all') return props.seasonTotals;
    return props.byMonth[selectedMonth.value] ?? { amount: 0, workdays: 0 };
});

const currentByLevel = computed(() => {
    const key = selectedMonth.value === 'all' ? 'all' : selectedMonth.value;
    const rows = props.byLevel[key] ?? [];
    if (selectedParcel.value) {
        return rows.map(r => {
            const pData = (r.by_parcel ?? {})[String(selectedParcel.value)];
            return { ...r, amount: pData?.amount ?? 0, workdays: pData?.workdays ?? 0 };
        }).filter(r => r.amount > 0);
    }
    if (!selectedBranch.value) return rows;
    return rows
        .map(r => {
            const bData = (r.by_branch ?? {})[String(selectedBranch.value)];
            return {
                ...r,
                amount:   bData?.amount   ?? 0,
                workdays: bData?.workdays ?? 0,
            };
        })
        .filter(r => r.amount > 0);
});

const currentByBranch = computed(() => {
    const key = selectedMonth.value === 'all' ? 'all' : selectedMonth.value;
    const rows = props.byBranch[key] ?? [];
    if (selectedParcel.value) {
        return rows.map(r => {
            const pData = (r.by_parcel ?? {})[String(selectedParcel.value)];
            return { ...r, amount: pData?.amount ?? 0, workdays: pData?.workdays ?? 0 };
        }).filter(r => r.amount > 0);
    }
    if (!selectedBranch.value) return rows;
    return rows.filter(r => String(r.branch_id) === String(selectedBranch.value));
});

const currentByTrato = computed(() => {
    const key = selectedMonth.value === 'all' ? 'all' : selectedMonth.value;
    const rows = props.byTrato[key] ?? [];
    if (selectedParcel.value) {
        return rows.map(r => {
            const pData = (r.by_parcel ?? {})[String(selectedParcel.value)];
            return { ...r, quantity: pData?.quantity ?? 0, amount: pData?.amount ?? 0 };
        }).filter(r => r.amount > 0);
    }
    if (!selectedBranch.value) return rows;
    return rows
        .map(r => {
            const bData = (r.by_branch ?? {})[String(selectedBranch.value)];
            return {
                ...r,
                quantity: bData?.quantity ?? 0,
                amount:   bData?.amount   ?? 0,
            };
        })
        .filter(r => r.amount > 0);
});

const currentByParcel = computed(() => {
    const key = selectedMonth.value === 'all' ? 'all' : selectedMonth.value;
    const rows = props.byParcel[key] ?? [];
    if (selectedParcel.value) {
        return rows.filter(r => String(r.parcel_id) === String(selectedParcel.value));
    }
    if (!selectedBranch.value) return rows;
    return rows
        .map(r => {
            const bData = (r.by_branch ?? {})[String(selectedBranch.value)];
            return {
                ...r,
                amount:   bData?.amount   ?? 0,
                workdays: bData?.workdays ?? 0,
            };
        })
        .filter(r => r.amount > 0);
});

// ——— Computed: totales de tablas ———
const levelTotals = computed(() => currentByLevel.value.reduce(
    (acc, r) => ({ amount: acc.amount + r.amount, workdays: acc.workdays + r.workdays }),
    { amount: 0, workdays: 0 }
));

const branchTotals = computed(() => currentByBranch.value.reduce(
    (acc, r) => ({ amount: acc.amount + r.amount, workdays: acc.workdays + r.workdays }),
    { amount: 0, workdays: 0 }
));

const tratoTotals = computed(() => currentByTrato.value.reduce(
    (acc, r) => ({ quantity: acc.quantity + r.quantity, amount: acc.amount + r.amount }),
    { quantity: 0, amount: 0 }
));

const parcelTotals = computed(() => currentByParcel.value.reduce(
    (acc, r) => ({ amount: acc.amount + r.amount, workdays: acc.workdays + r.workdays }),
    { amount: 0, workdays: 0 }
));

const currentByCostCenter = computed(() => {
    const key = selectedMonth.value === 'all' ? 'all' : selectedMonth.value;
    let rows = props.byCostCenter[key] ?? [];
    if (selectedParcel.value) {
        rows = rows.filter(r => String(r.parcel_id) === String(selectedParcel.value));
    } else if (selectedBranch.value) {
        rows = rows.filter(r => String(r.branch_id) === String(selectedBranch.value));
    }
    return rows;
});

const ccTotals = computed(() => currentByCostCenter.value.reduce(
    (acc, r) => ({ amount: acc.amount + r.amount, workdays: acc.workdays + r.workdays }),
    { amount: 0, workdays: 0 }
));

// ——— Formatters ———
const fmt = (val) => {
    if (!val && val !== 0) return '0';
    return Math.round(val).toLocaleString('es-CL');
};
const fmtDec = (val) => {
    if (!val && val !== 0) return '0';
    return parseFloat(val).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// ——— Gráficos ———
const BAR_COLORS = {
    amount:   'rgba(54, 132, 220, 0.8)',
    workdays: 'rgba(40, 167, 100, 0.8)',
};

function createAmountsChart() {
    if (!amountsChartRef.value) return;
    if (amountsChart) { amountsChart.destroy(); amountsChart = null; }

    amountsChart = new Chart(amountsChartRef.value, {
        type: 'bar',
        data: {
            labels: props.chartData.labels,
            datasets: [{
                label: 'Total remuneraciones ($)',
                data: props.chartData.amounts,
                backgroundColor: BAR_COLORS.amount,
                borderRadius: 3,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => '$ ' + Math.round(ctx.parsed.y).toLocaleString('es-CL'),
                    },
                },
            },
            scales: {
                y: {
                    ticks: {
                        callback: (v) => '$ ' + Math.round(v).toLocaleString('es-CL'),
                        font: { size: 10 },
                    },
                },
                x: { ticks: { font: { size: 10 } } },
            },
        },
    });
}

function createWorkdaysChart() {
    if (!workdaysChartRef.value) return;
    if (workdaysChart) { workdaysChart.destroy(); workdaysChart = null; }

    workdaysChart = new Chart(workdaysChartRef.value, {
        type: 'bar',
        data: {
            labels: props.chartData.labels,
            datasets: [{
                label: 'Jornadas (JH)',
                data: props.chartData.workdays,
                backgroundColor: BAR_COLORS.workdays,
                borderRadius: 3,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => parseFloat(ctx.parsed.y).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' JH',
                    },
                },
            },
            scales: {
                y: { ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } },
            },
        },
    });
}

// Resaltar la barra del mes seleccionado en ambos gráficos
function highlightSelectedMonth() {
    const monthIdx = props.months.findIndex(m => m.id === selectedMonth.value);

    [amountsChart, workdaysChart].forEach((chart, ci) => {
        if (!chart) return;
        const baseColor = ci === 0 ? BAR_COLORS.amount : BAR_COLORS.workdays;
        const dimColor  = ci === 0 ? 'rgba(54, 132, 220, 0.25)' : 'rgba(40, 167, 100, 0.25)';
        const n = props.chartData.labels.length;

        if (selectedMonth.value === 'all') {
            chart.data.datasets[0].backgroundColor = baseColor;
        } else {
            chart.data.datasets[0].backgroundColor = Array.from({ length: n }, (_, i) =>
                i === monthIdx ? baseColor : dimColor
            );
        }
        chart.update();
    });
}

watch(selectedMonth, highlightSelectedMonth);

onMounted(() => {
    createAmountsChart();
    createWorkdaysChart();
});
</script>
