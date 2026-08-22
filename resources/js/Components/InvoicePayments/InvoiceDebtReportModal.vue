<script setup>
import { ref, computed, watch } from 'vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    show: Boolean,
    filters: {
        type: Object,
        default: () => ({}),
    },
});
const emit = defineEmits(['close']);

const reportData = ref(null);
const loading = ref(false);

const filterCompanyReason = ref(null);
const filterMonth = ref(null);
const filterSupplier = ref(null);

const groupBy = ref('reason'); // 'reason' | 'supplier'
const columnMode = ref('month'); // 'month' | 'aging'
const expandedRows = ref(new Set());

const AGING_BUCKETS = [
    { id: 'current', name: 'Al día' },
    { id: '1-30', name: '1-30 días' },
    { id: '31-60', name: '31-60 días' },
    { id: '61-90', name: '61-90 días' },
    { id: '90+', name: '+90 días' },
];

watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        filterCompanyReason.value = null;
        filterMonth.value = null;
        filterSupplier.value = null;
        expandedRows.value = new Set();
        await loadReport();
    }
});

async function loadReport() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        Object.entries(props.filters || {}).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.append(key, value);
            }
        });
        const response = await fetch(`${route('invoice-payments.debt-report')}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Error al cargar el informe');
        reportData.value = await response.json();
    } catch (error) {
        console.error('Error loading debt report:', error);
        reportData.value = { invoices: [], company_reasons: [], months: [], suppliers: [] };
    } finally {
        loading.value = false;
    }
}

const filteredInvoices = computed(() => {
    if (!reportData.value) return [];
    return reportData.value.invoices.filter(inv => {
        if (filterCompanyReason.value && String(inv.company_reason_id) !== String(filterCompanyReason.value)) return false;
        if (filterMonth.value && String(inv.month_id) !== String(filterMonth.value)) return false;
        if (filterSupplier.value && String(inv.supplier_id) !== String(filterSupplier.value)) return false;
        return true;
    });
});

const rowLabel = computed(() => groupBy.value === 'supplier' ? 'Proveedor' : 'Razón Social');

// Tabla pivote: filas = razón social o proveedor, columnas = mes o antigüedad, celdas = saldo adeudado
const pivot = computed(() => {
    const rowsMap = new Map();
    const monthsMap = new Map();
    const cellMap = new Map();

    filteredInvoices.value.forEach(inv => {
        const rid = groupBy.value === 'supplier' ? inv.supplier_id : inv.company_reason_id;
        const rname = groupBy.value === 'supplier' ? inv.supplier_name : inv.company_reason_name;
        if (!rowsMap.has(rid)) rowsMap.set(rid, { id: rid, name: rname, invoices: [] });
        rowsMap.get(rid).invoices.push(inv);

        const cid = columnMode.value === 'aging' ? inv.aging_bucket : inv.month_id;
        if (columnMode.value !== 'aging') monthsMap.set(cid, inv.month_name);

        const key = rid + '|' + cid;
        cellMap.set(key, (cellMap.get(key) || 0) + Number(inv.balance));
    });

    const columns = columnMode.value === 'aging'
        ? AGING_BUCKETS
        : [...monthsMap.entries()].map(([id, name]) => ({ id, name })).sort((a, b) => (a.id ?? 0) - (b.id ?? 0));

    const rows = [...rowsMap.values()]
        .sort((a, b) => a.name.localeCompare(b.name))
        .map(r => {
            const cells = columns.map(c => cellMap.get(r.id + '|' + c.id) || 0);
            return { ...r, cells, total: cells.reduce((a, b) => a + b, 0) };
        });

    const colTotals = columns.map((c, i) => rows.reduce((sum, row) => sum + row.cells[i], 0));
    const grandTotal = colTotals.reduce((a, b) => a + b, 0);

    return { columns, rows, colTotals, grandTotal };
});

// KPIs resumen del informe (respetan los filtros aplicados)
const kpis = computed(() => {
    const invoices = filteredInvoices.value;
    const totalDebt = invoices.reduce((s, i) => s + Number(i.balance), 0);
    const pendingCount = invoices.length;

    let oldest = null;
    invoices.forEach(inv => {
        if (inv.days_overdue > 0 && (!oldest || inv.days_overdue > oldest.days_overdue)) oldest = inv;
    });

    const bySupplier = new Map();
    invoices.forEach(inv => {
        bySupplier.set(inv.supplier_id, (bySupplier.get(inv.supplier_id) || 0) + Number(inv.balance));
    });
    let topSupplierId = null, topSupplierAmount = 0;
    bySupplier.forEach((amount, id) => {
        if (amount > topSupplierAmount) { topSupplierAmount = amount; topSupplierId = id; }
    });
    const topSupplierName = topSupplierId != null
        ? invoices.find(inv => inv.supplier_id === topSupplierId)?.supplier_name
        : null;

    return { totalDebt, pendingCount, oldest, topSupplierName, topSupplierAmount };
});

const excelHeaders = computed(() => {
    const headers = [{ label: rowLabel.value, key: 'name' }];
    pivot.value.columns.forEach(c => headers.push({ label: c.name, key: 'col_' + c.id }));
    headers.push({ label: 'Total', key: 'total' });
    return headers;
});

const excelData = computed(() => {
    const rows = pivot.value.rows.map(r => {
        const obj = { name: r.name, total: r.total };
        pivot.value.columns.forEach((c, i) => { obj['col_' + c.id] = r.cells[i]; });
        return obj;
    });
    const totalsRow = { name: 'Total', total: pivot.value.grandTotal };
    pivot.value.columns.forEach((c, i) => { totalsRow['col_' + c.id] = pivot.value.colTotals[i]; });
    rows.push(totalsRow);
    return rows;
});

function fmt(n) {
    return '$' + Number(n || 0).toLocaleString('es-ES', { maximumFractionDigits: 0 });
}

function fmtDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('es-CL');
}

// Clase Bootstrap para el semáforo de antigüedad (celdas de la tabla y detalle expandido)
function agingClass(bucketId) {
    return {
        current: 'bg-success bg-opacity-25',
        '1-30': 'bg-warning bg-opacity-25',
        '31-60': 'bg-warning bg-opacity-50',
        '61-90': 'bg-danger bg-opacity-25',
        '90+': 'bg-danger bg-opacity-50',
    }[bucketId] || '';
}

function toggleExpand(id) {
    const s = new Set(expandedRows.value);
    if (s.has(id)) s.delete(id); else s.add(id);
    expandedRows.value = s;
}

function clearFilters() {
    filterCompanyReason.value = null;
    filterMonth.value = null;
    filterSupplier.value = null;
}

function closeModal() {
    emit('close');
}
</script>

<template>
    <div
        v-if="show"
        class="modal fade show"
        style="display: block; background-color: rgba(0,0,0,0.5);"
        tabindex="-1"
        @click.self="closeModal"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1400px; width: 95%;">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-table me-2"></i>
                        Informe de Deuda por Razón Social
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Cargando informe...</div>
                    </div>

                    <template v-else>
                        <!-- KPIs -->
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="card h-100 border-start border-danger border-3">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-truncate">
                                                <small class="text-muted text-uppercase d-block mb-1">Total Adeudado</small>
                                                <h4 class="mb-0 fw-bold text-danger">{{ fmt(kpis.totalDebt) }}</h4>
                                                <small class="text-muted fs-10">{{ kpis.pendingCount }} facturas pendientes</small>
                                            </div>
                                            <div class="text-danger flex-shrink-0 ms-2">
                                                <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card h-100 border-start border-primary border-3">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-truncate">
                                                <small class="text-muted text-uppercase d-block mb-1">Facturas Pendientes</small>
                                                <h4 class="mb-0 fw-bold text-primary">{{ kpis.pendingCount }}</h4>
                                                <small class="text-muted fs-10">Con saldo por pagar</small>
                                            </div>
                                            <div class="text-primary flex-shrink-0 ms-2">
                                                <i class="fas fa-list-check fa-2x opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card h-100 border-start border-warning border-3">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-truncate">
                                                <small class="text-muted text-uppercase d-block mb-1">Más Antigua Vencida</small>
                                                <h6 v-if="kpis.oldest" class="mb-0 fw-bold text-warning text-truncate" :title="kpis.oldest.supplier_name">{{ kpis.oldest.supplier_name }}</h6>
                                                <h6 v-else class="mb-0 fw-bold text-muted">Sin vencidas</h6>
                                                <small v-if="kpis.oldest" class="text-muted fs-10">{{ kpis.oldest.days_overdue }} días vencida</small>
                                            </div>
                                            <div class="text-warning flex-shrink-0 ms-2">
                                                <i class="fas fa-clock fa-2x opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card h-100 border-start border-info border-3">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-truncate">
                                                <small class="text-muted text-uppercase d-block mb-1">Mayor Deuda</small>
                                                <h6 v-if="kpis.topSupplierName" class="mb-0 fw-bold text-info text-truncate" :title="kpis.topSupplierName">{{ kpis.topSupplierName }}</h6>
                                                <h6 v-else class="mb-0 fw-bold text-muted">—</h6>
                                                <small v-if="kpis.topSupplierName" class="text-muted fs-10">{{ fmt(kpis.topSupplierAmount) }}</small>
                                            </div>
                                            <div class="text-info flex-shrink-0 ms-2">
                                                <i class="fas fa-truck fa-2x opacity-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                            <div style="min-width:180px; flex:1 1 220px;">
                                <label class="form-label small mb-1">Razón Social</label>
                                <select v-model="filterCompanyReason" class="form-select form-select-sm">
                                    <option :value="null">Todas</option>
                                    <option v-for="o in reportData?.company_reasons ?? []" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                            </div>
                            <div style="min-width:150px; flex:1 1 180px;">
                                <label class="form-label small mb-1">Mes</label>
                                <select v-model="filterMonth" class="form-select form-select-sm">
                                    <option :value="null">Todos</option>
                                    <option v-for="o in reportData?.months ?? []" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                            </div>
                            <div style="min-width:180px; flex:1 1 220px;">
                                <label class="form-label small mb-1">Proveedor</label>
                                <select v-model="filterSupplier" class="form-select form-select-sm">
                                    <option :value="null">Todos</option>
                                    <option v-for="o in reportData?.suppliers ?? []" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                            </div>
                            <button
                                v-if="filterCompanyReason || filterMonth || filterSupplier"
                                type="button"
                                class="btn btn-falcon-default btn-sm"
                                @click="clearFilters"
                                title="Limpiar filtros"
                            ><i class="fas fa-times"></i></button>
                        </div>

                        <!-- Opciones de vista -->
                        <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                            <div>
                                <small class="text-muted text-uppercase d-block mb-1">Agrupar por</small>
                                <div class="segmented-control">
                                    <button type="button" class="segmented-option" :class="{ active: groupBy === 'reason' }" @click="groupBy = 'reason'">
                                        <i class="fas fa-building me-1"></i>Razón Social
                                    </button>
                                    <button type="button" class="segmented-option" :class="{ active: groupBy === 'supplier' }" @click="groupBy = 'supplier'">
                                        <i class="fas fa-truck me-1"></i>Proveedor
                                    </button>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase d-block mb-1">Columnas</small>
                                <div class="segmented-control">
                                    <button type="button" class="segmented-option" :class="{ active: columnMode === 'month' }" @click="columnMode = 'month'">
                                        <i class="fas fa-calendar-days me-1"></i>Mes
                                    </button>
                                    <button type="button" class="segmented-option" :class="{ active: columnMode === 'aging' }" @click="columnMode = 'aging'">
                                        <i class="fas fa-hourglass-half me-1"></i>Antigüedad
                                    </button>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <ExportExcelButton :data="excelData" :headers="excelHeaders" filename="informe-deuda.xlsx" class="btn btn-falcon-default btn-sm">
                                    <i class="fas fa-file-excel me-1"></i>Exportar
                                </ExportExcelButton>
                            </div>
                        </div>

                        <div v-if="!pivot.rows.length" class="text-center text-muted py-4">
                            No hay deuda pendiente para los filtros seleccionados.
                        </div>

                        <div v-else style="max-height: 50vh; overflow: auto;">
                            <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.78rem;">
                                <thead class="table-primary" style="position: sticky; top: 0; z-index: 5;">
                                    <tr>
                                        <th style="white-space:nowrap;">{{ rowLabel }}</th>
                                        <th v-for="c in pivot.columns" :key="c.id" class="text-end" style="white-space:nowrap;">{{ c.name }}</th>
                                        <th class="text-end" style="white-space:nowrap; background:#d4e6f1;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="row in pivot.rows" :key="row.id">
                                        <tr style="cursor:pointer;" @click="toggleExpand(row.id)">
                                            <td style="white-space:nowrap;">
                                                <i class="fas me-1 text-muted" :class="expandedRows.has(row.id) ? 'fa-chevron-down' : 'fa-chevron-right'" style="font-size:0.65rem;"></i>
                                                {{ row.name }}
                                            </td>
                                            <td
                                                v-for="(cell, i) in row.cells" :key="i"
                                                class="text-end"
                                                style="white-space:nowrap;"
                                                :class="columnMode === 'aging' && cell > 0 ? agingClass(pivot.columns[i].id) : ''"
                                            >
                                                <template v-if="cell > 0">{{ fmt(cell) }}</template>
                                                <template v-else><span class="text-muted">—</span></template>
                                            </td>
                                            <td class="text-end fw-bold" style="white-space:nowrap;">{{ fmt(row.total) }}</td>
                                        </tr>
                                        <tr v-if="expandedRows.has(row.id)">
                                            <td :colspan="pivot.columns.length + 2" class="p-0 bg-body-tertiary">
                                                <table class="table table-sm mb-0" style="font-size:0.74rem;">
                                                    <thead>
                                                        <tr class="text-muted">
                                                            <th>N° Documento</th>
                                                            <th>{{ groupBy === 'supplier' ? 'Razón Social' : 'Proveedor' }}</th>
                                                            <th>Fecha</th>
                                                            <th>Vencimiento</th>
                                                            <th>Antigüedad</th>
                                                            <th>Mes</th>
                                                            <th class="text-end">Saldo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="inv in row.invoices" :key="inv.id">
                                                            <td>{{ inv.number_document }}</td>
                                                            <td>{{ groupBy === 'supplier' ? inv.company_reason_name : inv.supplier_name }}</td>
                                                            <td>{{ fmtDate(inv.date) }}</td>
                                                            <td>{{ fmtDate(inv.due_date) }}</td>
                                                            <td :class="agingClass(inv.aging_bucket)">
                                                                {{ inv.days_overdue > 0 ? inv.days_overdue + ' días' : 'Al día' }}
                                                            </td>
                                                            <td>{{ inv.month_name }}</td>
                                                            <td class="text-end">{{ fmt(inv.balance) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot style="position:sticky; bottom:0; z-index:5;">
                                    <tr class="table-secondary fw-bold">
                                        <td style="white-space:nowrap;">Total</td>
                                        <td v-for="(t, i) in pivot.colTotals" :key="i" class="text-end" style="white-space:nowrap;">{{ fmt(t) }}</td>
                                        <td class="text-end" style="white-space:nowrap;">{{ fmt(pivot.grandTotal) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </template>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="closeModal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.segmented-control {
    display: inline-flex;
    background: var(--bs-tertiary-bg, #edf2f9);
    border: 1px solid var(--bs-border-color, #e3e6ed);
    border-radius: 0.5rem;
    padding: 3px;
    gap: 2px;
}
.segmented-option {
    display: inline-flex;
    align-items: center;
    border: none;
    background: transparent;
    padding: 0.3rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 500;
    line-height: 1;
    color: var(--bs-secondary-color, #6c757d);
    border-radius: 0.4rem;
    white-space: nowrap;
    transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}
.segmented-option:hover:not(.active) {
    color: var(--bs-emphasis-color, #212529);
    background: rgba(0, 0, 0, 0.06);
}
.segmented-option.active {
    background: var(--bs-body-bg, #fff);
    color: var(--bs-primary, #2c7be5);
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}
</style>
