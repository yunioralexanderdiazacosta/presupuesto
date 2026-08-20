<script setup>
import { ref, computed, watch } from 'vue';

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

watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        filterCompanyReason.value = null;
        filterMonth.value = null;
        filterSupplier.value = null;
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

// Tabla pivote: filas = razón social, columnas = mes, celdas = saldo adeudado
const pivot = computed(() => {
    const reasonsMap = new Map();
    const monthsMap = new Map();
    const cellMap = new Map();

    filteredInvoices.value.forEach(inv => {
        reasonsMap.set(inv.company_reason_id, inv.company_reason_name);
        monthsMap.set(inv.month_id, inv.month_name);
        const key = inv.company_reason_id + '|' + inv.month_id;
        cellMap.set(key, (cellMap.get(key) || 0) + Number(inv.balance));
    });

    const months = [...monthsMap.entries()]
        .map(([id, name]) => ({ id, name }))
        .sort((a, b) => (a.id ?? 0) - (b.id ?? 0));

    const reasons = [...reasonsMap.entries()]
        .map(([id, name]) => ({ id, name }))
        .sort((a, b) => a.name.localeCompare(b.name));

    const rows = reasons.map(r => {
        const cells = months.map(m => cellMap.get(r.id + '|' + m.id) || 0);
        return { id: r.id, name: r.name, cells, total: cells.reduce((a, b) => a + b, 0) };
    });

    const colTotals = months.map((m, i) => rows.reduce((sum, row) => sum + row.cells[i], 0));
    const grandTotal = colTotals.reduce((a, b) => a + b, 0);

    return { months, rows, colTotals, grandTotal };
});

function fmt(n) {
    return '$' + Number(n || 0).toLocaleString('es-ES', { maximumFractionDigits: 0 });
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
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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
                        <!-- Filtros -->
                        <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
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

                        <div v-if="!pivot.rows.length" class="text-center text-muted py-4">
                            No hay deuda pendiente para los filtros seleccionados.
                        </div>

                        <div v-else style="max-height: 55vh; overflow: auto;">
                            <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.78rem;">
                                <thead class="table-primary" style="position: sticky; top: 0; z-index: 5;">
                                    <tr>
                                        <th style="white-space:nowrap;">Razón Social</th>
                                        <th v-for="m in pivot.months" :key="m.id" class="text-end" style="white-space:nowrap;">{{ m.name }}</th>
                                        <th class="text-end" style="white-space:nowrap; background:#d4e6f1;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in pivot.rows" :key="row.id">
                                        <td style="white-space:nowrap;">{{ row.name }}</td>
                                        <td v-for="(cell, i) in row.cells" :key="i" class="text-end" style="white-space:nowrap;">
                                            <template v-if="cell > 0">{{ fmt(cell) }}</template>
                                            <template v-else><span class="text-muted">—</span></template>
                                        </td>
                                        <td class="text-end fw-bold" style="white-space:nowrap;">{{ fmt(row.total) }}</td>
                                    </tr>
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
