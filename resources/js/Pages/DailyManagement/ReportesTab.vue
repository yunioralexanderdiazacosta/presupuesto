<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    selectedDate: String,
    branches: { type: Array, default: () => [] },
});

// Estado
const currentMonth = ref(props.selectedDate ? props.selectedDate.substring(0, 7) : new Date().toISOString().substring(0, 7));
const viewMode = ref('planilla'); // 'planilla' | 'detalle' | 'labor'
const selectedBranchId = ref(null);
const selectedLaborTypeId = ref(null);
const selectedEmployeeIds = ref([]);
const loading = ref(false);
const reportData = ref(null);

// Cargar datos al montar y al cambiar mes
async function fetchReport() {
    loading.value = true;
    try {
        const response = await axios.get(route('daily-management.monthly-report'), {
            params: { month: currentMonth.value }
        });
        reportData.value = response.data;
        selectedEmployeeIds.value = [];
        // No resetear sucursal al cambiar mes
    } catch (error) {
        console.error('Error al cargar reporte:', error);
        Swal.fire('Error', 'No se pudo cargar el reporte mensual.', 'error');
    } finally {
        loading.value = false;
    }
}

// Cargar al inicio
fetchReport();

function changeMonth() {
    fetchReport();
}

// Opciones de labor derivadas de los datos cargados (union de todas las líneas)
const laborTypeOptions = computed(() => {
    if (!reportData.value) return [];
    const map = new Map();
    for (const emp of reportData.value.employees) {
        for (const date of reportData.value.dates) {
            const day = emp.days[date];
            if (!day?.lines?.length) continue;
            for (const line of day.lines) {
                if (line.labor_type_id && !map.has(line.labor_type_id)) {
                    map.set(line.labor_type_id, line.labor_type || '—');
                }
            }
        }
    }
    return [...map.entries()]
        .map(([id, name]) => ({ value: id, label: name }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

// Empleados filtrados por sucursal (base para todos los modos de vista)
const filteredEmployees = computed(() => {
    if (!reportData.value) return [];
    let emps = reportData.value.employees;
    if (selectedBranchId.value) {
        emps = emps.filter(e => String(e.branch_id) === String(selectedBranchId.value));
    }
    if (selectedLaborTypeId.value) {
        emps = emps.filter(e =>
            reportData.value.dates.some(date =>
                e.days[date]?.lines?.some(l => String(l.labor_type_id) === String(selectedLaborTypeId.value))
            )
        );
    }
    return emps;
});

// Empleados seleccionados para detalle
const selectedEmployees = computed(() => {
    if (selectedEmployeeIds.value.length === 0) return [];
    return filteredEmployees.value.filter(e => selectedEmployeeIds.value.includes(e.id));
});

// Empleados para el select (solo los filtrados por sucursal)
const employeeOptions = computed(() => {
    return filteredEmployees.value.map(e => ({ value: e.id, label: e.full_name + ' (' + e.rut + ')' }));
});

// Totales filtrados por sucursal
const filteredTotals = computed(() => {
    return filteredEmployees.value.reduce((acc, e) => {
        acc.amount       += e.grand_total_amount;
        acc.bonus        += e.grand_total_bonus;
        acc.target_bonus += e.grand_total_target_bonus;
        acc.workdays     += e.grand_total_workdays;
        return acc;
    }, { amount: 0, bonus: 0, target_bonus: 0, workdays: 0 });
});

// Días cortos para header planilla
function dayShort(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return d.getDate();
}

function dayName(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    const names = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'];
    return names[d.getDay()];
}

function isWeekend(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return d.getDay() === 0 || d.getDay() === 6;
}

function fmt(val) {
    if (!val) return '';
    return Number(val).toLocaleString('es-CL');
}

// Mes formateado
const monthLabel = computed(() => {
    if (!currentMonth.value) return '';
    const [y, m] = currentMonth.value.split('-');
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return months[parseInt(m) - 1] + ' ' + y;
});

// Totales diarios para la planilla
function dayColumnTotal(date) {
    if (!reportData.value) return 0;
    return filteredEmployees.value.reduce((sum, e) => {
        const day = e.days[date];
        return sum + (day ? (day.amount || 0) + (day.bonus || 0) + (day.target_bonus || 0) : 0);
    }, 0);
}

// Popover desglose por celda diaria
const popover = reactive({ show: false, x: 0, y: 0, day: null, empName: '' });
let popoverTimeout = null;

function showPopover(event, day, empName) {
    if (!day || !day.lines || day.lines.length === 0) return;
    clearTimeout(popoverTimeout);
    const rect = event.target.getBoundingClientRect();
    popover.x = rect.left + rect.width / 2;
    popover.y = rect.top - 6;
    popover.day = day;
    popover.empName = empName;
    popover.show = true;
}

function hidePopover() {
    popoverTimeout = setTimeout(() => { popover.show = false; }, 150);
}

function keepPopover() {
    clearTimeout(popoverTimeout);
}

function goToYield(date, day) {
    if (!day) return;
    router.get(route('daily-management.index'), {
        date: date,
        tab: 'yields',
    }, { preserveState: false });
}

// ── Reporte por Labor ─────────────────────────────────────────────────────
// Aplana todas las líneas de todos los empleados/días con campos de contexto
const laborReportRows = computed(() => {
    if (!reportData.value) return [];
    const rows = [];
    for (const emp of filteredEmployees.value) {
        for (const date of reportData.value.dates) {
            const day = emp.days[date];
            if (!day?.lines?.length) continue;
            for (const line of day.lines) {
                if (selectedLaborTypeId.value && String(line.labor_type_id) !== String(selectedLaborTypeId.value)) continue;
                rows.push({
                    labor_type:          line.labor_type || '—',
                    labor_type_id:       line.labor_type_id,
                    level3_name:         line.level3_name || '—',
                    full_name:           emp.full_name,
                    date,
                    payment_type:        line.payment_type,
                    labor_rate:          line.labor_rate,
                    rate:                line.rate,
                    quantity:            line.quantity,
                    amount:              line.amount,
                    workdays:            line.workdays,
                    bonus_amount:        line.bonus_amount,
                    target_price_bonus:  line.target_price_bonus,
                });
            }
        }
    }
    return rows;
});

// Agrupa las filas por Labor; dentro de cada grupo calcula totales
const laborReportGrouped = computed(() => {
    const map = new Map();
    for (const row of laborReportRows.value) {
        const key = row.labor_type;
        if (!map.has(key)) {
            map.set(key, { name: key, level3: row.level3_name, rows: [], total_amount: 0, total_workdays: 0, total_bonus: 0, total_target_bonus: 0 });
        }
        const g = map.get(key);
        g.rows.push(row);
        g.total_amount       += Number(row.amount || 0);
        g.total_workdays     += Number(row.workdays || 0);
        g.total_bonus        += Number(row.bonus_amount || 0);
        g.total_target_bonus += Number(row.target_price_bonus || 0);
    }
    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
});

// Totales generales del reporte por labor
const laborGrandTotals = computed(() => {
    return laborReportGrouped.value.reduce((acc, g) => {
        acc.amount       += g.total_amount;
        acc.workdays     += g.total_workdays;
        acc.bonus        += g.total_bonus;
        acc.target_bonus += g.total_target_bonus;
        return acc;
    }, { amount: 0, workdays: 0, bonus: 0, target_bonus: 0 });
});

// Export URLs
function exportExcel(mode) {    const url = route('daily-management.export-excel') + '?month=' + currentMonth.value + '&mode=' + mode;
    window.open(url, '_blank');
}

function exportPdf(mode) {
    let url = route('daily-management.export-pdf') + '?month=' + currentMonth.value + '&mode=' + mode;
    if (mode === 'detalle' && selectedEmployeeIds.value.length > 0) {
        selectedEmployeeIds.value.forEach(id => { url += '&contract_ids[]=' + id; });
    }
    window.open(url, '_blank');
}
</script>

<template>
    <div>
        <!-- Controles -->
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Mes</label>
                <input type="month" v-model="currentMonth" @change="changeMonth"
                    class="form-control form-control-sm" />
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Sucursal</label>
                <select v-model="selectedBranchId" class="form-select form-select-sm">
                    <option :value="null">Todas</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Labor</label>
                <select v-model="selectedLaborTypeId" class="form-select form-select-sm">
                    <option :value="null">Todas</option>
                    <option v-for="l in laborTypeOptions" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Vista</label>
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="button" class="btn" :class="viewMode === 'planilla' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'planilla'">
                        <i class="fas fa-th me-1"></i>Planilla
                    </button>
                    <button type="button" class="btn" :class="viewMode === 'detalle' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'detalle'">
                        <i class="fas fa-user me-1"></i>Detalle
                    </button>
                    <button type="button" class="btn" :class="viewMode === 'labor' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'labor'">
                        <i class="fas fa-tasks me-1"></i>Por Labor
                    </button>
                </div>
            </div>
            <div class="col-md-4" v-if="viewMode === 'detalle'">
                <label class="form-label small mb-1">Colaboradores</label>
                <Multiselect
                    v-model="selectedEmployeeIds"
                    :options="employeeOptions"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    placeholder="Todos los colaboradores"
                    no-results-text="Sin resultados"
                    no-options-text="Sin colaboradores"
                    class="multiselect-sm"
                />
            </div>
            <div class="col-md-3 ms-auto">
                <label class="form-label small mb-1">Exportar</label>
                <div class="d-flex gap-1">
                    <button class="btn btn-falcon-default btn-sm" @click="exportExcel(viewMode)" :disabled="loading">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-falcon-default btn-sm" @click="exportPdf(viewMode)" :disabled="loading">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="text-muted mt-2">Cargando reporte de {{ monthLabel }}...</p>
        </div>

        <!-- Sin datos -->
        <div v-else-if="reportData && filteredEmployees.length === 0" class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay tarjas registradas en {{ monthLabel }}</p>
        </div>

        <!-- PLANILLA GENERAL -->
        <div v-else-if="reportData && viewMode === 'planilla'">
            <!-- Resumen -->
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-primary text-center p-2">
                        <small class="text-muted">Trabajadores</small>
                        <strong>{{ filteredEmployees.length }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-success text-center p-2">
                        <small class="text-muted">Total Monto</small>
                        <strong>${{ fmt(filteredTotals.amount) }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-info text-center p-2">
                        <small class="text-muted">Total Bonos</small>
                        <strong>${{ fmt(filteredTotals.bonus) }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-warning text-center p-2">
                        <small class="text-muted">Bono P.Objetivo</small>
                        <strong>${{ fmt(filteredTotals.target_bonus) }}</strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover fs--2 mb-0" id="planilla-table">
                    <thead class="bg-200">
                        <tr>
                            <th class="sticky-col bg-200" style="min-width:150px;">Colaborador</th>
                            <th v-for="date in reportData.dates" :key="date"
                                class="text-center"
                                :class="{ 'bg-100': isWeekend(date) }"
                                style="min-width:38px; font-size:0.65rem;">
                                <div>{{ dayName(date) }}</div>
                                <div class="fw-bold">{{ dayShort(date) }}</div>
                            </th>
                            <th class="text-end bg-200" style="min-width:70px;">Monto $</th>
                            <th class="text-end bg-200" style="min-width:60px;">Bono $</th>
                            <th class="text-end bg-200" style="min-width:60px;">P.Obj $</th>
                            <th class="text-end bg-200" style="min-width:70px;">Total $</th>
                            <th class="text-center bg-200" style="min-width:40px;">JH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="emp in filteredEmployees" :key="emp.id">
                            <td class="fw-semi-bold sticky-col bg-white" style="white-space:nowrap; font-size:0.7rem;">
                                <span class="badge bg-soft-primary text-primary me-1" style="font-size:0.6rem;">#{{ emp.contract_id }}</span>
                                {{ emp.full_name }}
                            </td>
                            <td v-for="date in reportData.dates" :key="date"
                                class="text-end"
                                :class="{ 'bg-100': isWeekend(date), 'cursor-pointer': emp.days[date] }"
                                @mouseenter="showPopover($event, emp.days[date], emp.full_name)"
                                @mouseleave="hidePopover"
                                @click="goToYield(date, emp.days[date])"
                                style="font-size:0.65rem;"
                                :title="emp.days[date] ? 'Ir a tarjas del ' + date : ''">
                                {{ emp.days[date] ? fmt((emp.days[date].amount || 0) + (emp.days[date].bonus || 0) + (emp.days[date].target_bonus || 0)) : '' }}
                            </td>
                            <td class="text-end fw-bold" style="font-size:0.7rem;">{{ fmt(emp.grand_total_amount) }}</td>
                            <td class="text-end" style="font-size:0.7rem;">{{ emp.grand_total_bonus ? fmt(emp.grand_total_bonus) : '' }}</td>
                            <td class="text-end text-warning" style="font-size:0.7rem;">{{ emp.grand_total_target_bonus ? fmt(emp.grand_total_target_bonus) : '' }}</td>
                            <td class="text-end fw-bold text-primary" style="font-size:0.7rem;">{{ fmt(emp.grand_total_amount + (emp.grand_total_bonus || 0) + (emp.grand_total_target_bonus || 0)) }}</td>
                            <td class="text-center" style="font-size:0.7rem;">{{ emp.grand_total_workdays }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-100">
                        <tr class="fw-bold">
                            <td class="sticky-col bg-100">TOTAL</td>
                            <td v-for="date in reportData.dates" :key="date"
                                class="text-end"
                                :class="{ 'bg-100': isWeekend(date) }"
                                style="font-size:0.65rem;">
                                {{ dayColumnTotal(date) ? fmt(dayColumnTotal(date)) : '' }}
                            </td>
                            <td class="text-end" style="font-size:0.75rem;">{{ fmt(filteredTotals.amount) }}</td>
                            <td class="text-end" style="font-size:0.75rem;">{{ fmt(filteredTotals.bonus) }}</td>
                            <td class="text-end text-warning" style="font-size:0.75rem;">{{ fmt(filteredTotals.target_bonus) }}</td>
                            <td class="text-end text-primary" style="font-size:0.75rem;">{{ fmt(filteredTotals.amount + filteredTotals.bonus + filteredTotals.target_bonus) }}</td>
                            <td class="text-center" style="font-size:0.75rem;">{{ Math.round(filteredTotals.workdays * 100) / 100 }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- DETALLE INDIVIDUAL -->
        <div v-else-if="reportData && viewMode === 'detalle'">
            <!-- Si hay empleados seleccionados: detalle de cada uno -->
            <template v-if="selectedEmployees.length > 0">
                <div v-for="emp in selectedEmployees" :key="emp.id" class="card border mb-3">
                    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-light text-primary me-2" style="font-size:0.7rem;">#{{ emp.contract_id }}</span>
                            <strong>{{ emp.full_name }}</strong>
                            <span class="ms-2 small">{{ emp.rut }} | {{ emp.position || '-' }}</span>
                        </div>
                        <button
                            class="btn btn-sm btn-light py-0 px-2"
                            style="font-size:0.75rem;"
                            @click="selectedEmployeeIds = selectedEmployeeIds.filter(id => id !== emp.id)"
                            title="Cerrar detalle"
                        >
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </button>
                    </div>
                    <div class="card-body p-2">
                        <!-- Resumen personal -->
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <div class="card bg-soft-success text-center p-2">
                                    <small class="text-muted">Total Ganado</small>
                                    <strong class="fs-8">${{ fmt(emp.grand_total_amount) }}</strong>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card bg-soft-info text-center p-2">
                                    <small class="text-muted">Total Bonos</small>
                                    <strong>${{ fmt(emp.grand_total_bonus) }}</strong>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card bg-soft-warning text-center p-2">
                                    <small class="text-muted">Bono P.Obj</small>
                                    <strong>${{ fmt(emp.grand_total_target_bonus) }}</strong>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card bg-soft-warning text-center p-2">
                                    <small class="text-muted">Total Jornadas</small>
                                    <strong>{{ emp.grand_total_workdays }} JH</strong>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card bg-soft-primary text-center p-2">
                                    <small class="text-muted">Días Trabajados</small>
                                    <strong>{{ emp.days_worked }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla detalle día a día -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs--2 mb-0">
                                <thead class="bg-200">
                                    <tr>
                                        <th style="width:60px">Fecha</th>
                                        <th style="width:45px">Tipo</th>
                                        <th>Labor</th>
                                        <th>Trato</th>
                                        <th class="text-end" style="width:55px">Tarifa</th>
                                        <th class="text-center" style="width:40px">Cant.</th>
                                        <th class="text-end" style="width:65px">Monto</th>
                                        <th class="text-center" style="width:40px">JH</th>
                                        <th class="text-end" style="width:55px">Bono</th>
                                        <th class="text-end" style="width:55px">P.Obj</th>
                                        <th>C.Costo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="date in reportData.dates" :key="date">
                                        <template v-if="emp.days[date]?.lines?.length > 0">
                                            <tr v-for="(line, idx) in emp.days[date].lines" :key="date + '-' + idx">
                                                <td>{{ new Date(date + 'T12:00:00').toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit' }) }}</td>
                                                <td class="text-center">
                                                    <span class="badge" :class="line.payment_type === 'dia' ? 'bg-info' : 'bg-primary'" style="font-size:0.6rem;">
                                                        {{ line.payment_type === 'dia' ? 'Día' : 'Trato' }}
                                                    </span>
                                                </td>
                                                <td>{{ line.labor_type }}</td>
                                                <td>{{ line.labor_rate || '-' }}</td>
                                                <td class="text-end">{{ fmt(line.rate) }}</td>
                                                <td class="text-center">{{ line.quantity }}</td>
                                                <td class="text-end fw-semi-bold">{{ fmt(line.amount) }}</td>
                                                <td class="text-center">{{ line.workdays }}</td>
                                                <td class="text-end">{{ line.bonus_amount ? fmt(line.bonus_amount) : '' }}</td>
                                                <td class="text-end text-warning">{{ line.target_price_bonus ? fmt(line.target_price_bonus) : '' }}</td>
                                                <td>{{ line.cost_center }}</td>
                                            </tr>
                                            <!-- Subtotal del día (solo si hay más de 1 línea) -->
                                            <tr v-if="emp.days[date].lines.length > 1" class="bg-100">
                                                <td colspan="6" class="fw-semi-bold small text-end">
                                                    {{ new Date(date + 'T12:00:00').toLocaleDateString('es-CL', { weekday: 'short', day: '2-digit', month: '2-digit' }) }}
                                                    — Subtotal:
                                                </td>
                                                <td class="text-end fw-bold small">${{ fmt(emp.days[date].amount) }}</td>
                                                <td class="text-center fw-bold small">{{ emp.days[date].workdays }} JH</td>
                                                <td class="text-end fw-bold small">{{ emp.days[date].bonus ? '$' + fmt(emp.days[date].bonus) : '' }}</td>
                                                <td class="text-end fw-bold small text-warning">{{ emp.days[date].target_bonus ? '$' + fmt(emp.days[date].target_bonus) : '' }}</td>
                                                <td></td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                                <tfoot class="bg-200">
                                    <tr class="fw-bold">
                                        <td colspan="6" class="text-end">TOTAL MES</td>
                                        <td class="text-end">${{ fmt(emp.grand_total_amount) }}</td>
                                        <td class="text-center">{{ emp.grand_total_workdays }} JH</td>
                                        <td class="text-end">${{ fmt(emp.grand_total_bonus) }}</td>
                                        <td class="text-end text-warning">${{ fmt(emp.grand_total_target_bonus) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Si no hay empleado seleccionado: mostrar resumen de todos -->
            <template v-else>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover fs--1 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th>Colaborador</th>
                                <th>RUT</th>
                                <th class="text-center">Días Trabajados</th>
                                <th class="text-center">Jornada</th>
                                <th class="text-end">Total Monto</th>
                                <th class="text-end">Total Bonos</th>
                                <th class="text-end">P.Obj</th>
                                <th class="text-end">Gran Total</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="emp in filteredEmployees" :key="emp.id">
                                <td>
                                    <span class="badge bg-soft-primary text-primary me-1" style="font-size:0.6rem;">#{{ emp.contract_id }}</span>
                                    <span class="fw-semi-bold">{{ emp.full_name }}</span>
                                </td>
                                <td>{{ emp.rut }}</td>
                                <td class="text-center">{{ emp.days_worked }}</td>
                                <td class="text-center">{{ emp.grand_total_workdays }} JH</td>
                                <td class="text-end">{{ fmt(emp.grand_total_amount) }}</td>
                                <td class="text-end">{{ emp.grand_total_bonus ? fmt(emp.grand_total_bonus) : '-' }}</td>
                                <td class="text-end text-warning">{{ emp.grand_total_target_bonus ? fmt(emp.grand_total_target_bonus) : '-' }}</td>
                                <td class="text-end fw-bold">{{ fmt(emp.grand_total_amount + emp.grand_total_bonus + (emp.grand_total_target_bonus || 0)) }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-falcon-default p-0 px-1" @click="selectedEmployeeIds = [emp.id]" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-100">
                            <tr class="fw-bold">
                                <td colspan="3">TOTAL</td>
                                <td class="text-center">{{ Math.round(filteredTotals.workdays * 100) / 100 }} JH</td>
                                <td class="text-end">{{ fmt(filteredTotals.amount) }}</td>
                                <td class="text-end">{{ fmt(filteredTotals.bonus) }}</td>
                                <td class="text-end text-warning">{{ fmt(filteredTotals.target_bonus) }}</td>
                                <td class="text-end">{{ fmt(filteredTotals.amount + filteredTotals.bonus + filteredTotals.target_bonus) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
        </div>

        <!-- REPORTE POR LABOR -->
        <div v-else-if="reportData && viewMode === 'labor'">
            <div class="table-responsive">
                <table class="table table-sm table-bordered fs--2 mb-0">
                    <thead class="bg-200">
                        <tr>
                            <th style="min-width:130px;">Labor</th>
                            <th style="min-width:100px;">Nivel 3</th>
                            <th style="min-width:130px;">Nombre</th>
                            <th style="width:60px;">Fecha</th>
                            <th style="width:50px;">Trato</th>
                            <th class="text-end" style="width:65px;">Tarifa</th>
                            <th class="text-center" style="width:50px;">Cant.</th>
                            <th class="text-end" style="width:70px;">Monto</th>
                            <th class="text-center" style="width:45px;">JH</th>
                            <th class="text-end" style="width:60px;">Bono</th>
                            <th class="text-end" style="width:60px;">P.Obj.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in laborReportGrouped" :key="group.name">
                            <!-- Sub-cabecera de Labor -->
                            <tr class="table-primary">
                                <td colspan="11" class="fw-bold py-1" style="font-size:0.72rem;">
                                    <i class="fas fa-layer-group me-1"></i>{{ group.name }}
                                    <span class="text-muted ms-2" style="font-weight:400;">{{ group.level3 }}</span>
                                </td>
                            </tr>
                            <!-- Filas de esa labor -->
                            <tr v-for="(row, idx) in group.rows" :key="group.name + idx">
                                <td>{{ row.labor_type }}</td>
                                <td>{{ row.level3_name }}</td>
                                <td style="white-space:nowrap;">{{ row.full_name }}</td>
                                <td style="white-space:nowrap;">{{ new Date(row.date + 'T12:00:00').toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit' }) }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="row.payment_type === 'dia' ? 'bg-info' : 'bg-primary'" style="font-size:0.58rem;">
                                        {{ row.payment_type === 'dia' ? 'Día' : 'Trato' }}
                                    </span>
                                </td>
                                <td class="text-end">${{ fmt(row.rate) }}</td>
                                <td class="text-center">{{ row.quantity }}</td>
                                <td class="text-end fw-semi-bold">${{ fmt(row.amount) }}</td>
                                <td class="text-center">{{ row.workdays }}</td>
                                <td class="text-end">{{ row.bonus_amount ? '$' + fmt(row.bonus_amount) : '' }}</td>
                                <td class="text-end text-warning">{{ row.target_price_bonus ? '$' + fmt(row.target_price_bonus) : '' }}</td>
                            </tr>
                            <!-- Subtotal por labor -->
                            <tr class="bg-100 fw-bold" style="font-size:0.7rem;">
                                <td colspan="7" class="text-end">Subtotal {{ group.name }}</td>
                                <td class="text-end">${{ fmt(group.total_amount) }}</td>
                                <td class="text-center">{{ Math.round(group.total_workdays * 100) / 100 }}</td>
                                <td class="text-end">{{ group.total_bonus ? '$' + fmt(group.total_bonus) : '' }}</td>
                                <td class="text-end text-warning">{{ group.total_target_bonus ? '$' + fmt(group.total_target_bonus) : '' }}</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-200 fw-bold">
                        <tr>
                            <td colspan="7" class="text-end">TOTAL MES</td>
                            <td class="text-end">${{ fmt(laborGrandTotals.amount) }}</td>
                            <td class="text-center">{{ Math.round(laborGrandTotals.workdays * 100) / 100 }}</td>
                            <td class="text-end">${{ fmt(laborGrandTotals.bonus) }}</td>
                            <td class="text-end text-warning">${{ fmt(laborGrandTotals.target_bonus) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Popover desglose diario -->
    <Teleport to="body">
        <div v-if="popover.show && popover.day"
            class="day-popover shadow-lg"
            :style="{ left: popover.x + 'px', top: popover.y + 'px' }"
            @mouseenter="keepPopover"
            @mouseleave="hidePopover">
            <div class="popover-header-custom">
                <i class="fas fa-user-circle me-1"></i>{{ popover.empName }}
            </div>
            <div class="popover-body-custom">
                <div v-for="(line, i) in popover.day.lines" :key="i" class="popover-line">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <span class="badge me-1" :class="line.payment_type === 'dia' ? 'bg-info' : 'bg-primary'"
                                style="font-size:0.55rem; padding:1px 4px;">
                                {{ line.payment_type === 'dia' ? 'Día' : 'Trato' }}
                            </span>
                            <span style="font-size:0.78rem;">{{ line.labor_type || 'Labor' }}</span>
                        </span>
                        <span class="fw-bold" style="font-size:0.78rem;">${{ (line.amount || 0).toLocaleString('es-CL') }}</span>
                    </div>
                    <div v-if="line.bonus_amount" class="d-flex justify-content-between ps-3" style="font-size:0.72rem;">
                        <span><i class="fas fa-gift me-1"></i>Bono</span>
                        <span>+${{ line.bonus_amount.toLocaleString('es-CL') }}</span>
                    </div>
                    <div v-if="line.target_price_bonus" class="d-flex justify-content-between ps-3" style="font-size:0.72rem; color:#e6a700;">
                        <span><i class="fas fa-bullseye me-1"></i>P.Obj (${{ line.target_price.toLocaleString('es-CL') }})</span>
                        <span>+${{ line.target_price_bonus.toLocaleString('es-CL') }}</span>
                    </div>
                </div>
                <hr class="my-1" style="border-color:#e3e6f0;">
                <div class="d-flex justify-content-between fw-bold" style="font-size:0.82rem; color:#2c7be5;">
                    <span>Total</span>
                    <span>${{ ((popover.day.amount || 0) + (popover.day.bonus || 0) + (popover.day.target_bonus || 0)).toLocaleString('es-CL') }}</span>
                </div>
                <div class="text-muted text-end" style="font-size:0.68rem;">{{ popover.day.workdays }} JH trabajadas</div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.day-popover {
    position: fixed;
    transform: translate(-50%, -100%);
    z-index: 9999;
    background: white;
    border-radius: 6px;
    border: 1px solid #d8e2ef;
    min-width: 220px;
    max-width: 280px;
    pointer-events: auto;
}
.popover-header-custom {
    background: #2c7be5;
    color: white;
    padding: 4px 10px;
    border-radius: 5px 5px 0 0;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.popover-body-custom {
    padding: 6px 10px 8px;
}
.popover-line {
    padding: 2px 0;
    border-bottom: 0.5px dashed #edf2f9;
}
.popover-line:last-of-type {
    border-bottom: none;
}
</style>

<style scoped>
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 1;
}
.cursor-pointer {
    cursor: pointer;
}
.cursor-pointer:hover {
    background-color: rgba(44, 123, 229, 0.12) !important;
    text-decoration: underline;
}
</style>

<style>
.multiselect-sm {
    font-size: 0.75rem;
    min-height: 0;
    --ms-py: 0.1rem;
    --ms-px: 0.4rem;
    --ms-tag-py: 0rem;
    --ms-tag-px: 0.3rem;
    --ms-tag-font-size: 0.7rem;
    --ms-option-py: 0.2rem;
    --ms-option-px: 0.5rem;
    --ms-option-font-size: 0.75rem;
}
.multiselect-sm .multiselect-option {
    font-size: 0.8rem;
    padding: 3px 8px;
    line-height: 1.9;
}
.multiselect-sm .multiselect-tag {
    font-size: 0.75rem;
    padding: 1px 4px;
}
.multiselect-sm .multiselect-search input {
    font-size: 0.7rem;
}
</style>
