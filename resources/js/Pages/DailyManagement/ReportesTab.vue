<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    selectedDate: String,
});

// Estado
const currentMonth = ref(props.selectedDate ? props.selectedDate.substring(0, 7) : new Date().toISOString().substring(0, 7));
const viewMode = ref('planilla'); // 'planilla' | 'detalle'
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

// Empleados seleccionados para detalle
const selectedEmployees = computed(() => {
    if (!reportData.value || selectedEmployeeIds.value.length === 0) return [];
    return reportData.value.employees.filter(e => selectedEmployeeIds.value.includes(e.id));
});

// Empleados para el select (solo los que tienen datos)
const employeeOptions = computed(() => {
    if (!reportData.value) return [];
    return reportData.value.employees.map(e => ({ value: e.id, label: e.full_name + ' (' + e.rut + ')' }));
});

// DÃ­as cortos para header planilla
function dayShort(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    return d.getDate();
}

function dayName(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    const names = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'SÃ¡'];
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
    return reportData.value.employees.reduce((sum, e) => {
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

// Export URLs
function exportExcel(mode) {
    const url = route('daily-management.export-excel') + '?month=' + currentMonth.value + '&mode=' + mode;
    window.open(url, '_blank');
}

function exportPdf(mode) {
    let url = route('daily-management.export-pdf') + '?month=' + currentMonth.value + '&mode=' + mode;
    if (mode === 'detalle' && selectedEmployeeIds.value.length > 0) {
        selectedEmployeeIds.value.forEach(id => { url += '&employee_ids[]=' + id; });
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
            <div class="col-md-3">
                <label class="form-label small mb-1">Vista</label>
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="button" class="btn" :class="viewMode === 'planilla' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'planilla'">
                        <i class="fas fa-th me-1"></i>Planilla
                    </button>
                    <button type="button" class="btn" :class="viewMode === 'detalle' ? 'btn-primary' : 'btn-outline-primary'" @click="viewMode = 'detalle'">
                        <i class="fas fa-user me-1"></i>Detalle
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
        <div v-else-if="reportData && reportData.employees.length === 0" class="text-center py-5">
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
                        <strong>{{ reportData.employees.length }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-success text-center p-2">
                        <small class="text-muted">Total Monto</small>
                        <strong>${{ fmt(reportData.totals.amount) }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-info text-center p-2">
                        <small class="text-muted">Total Bonos</small>
                        <strong>${{ fmt(reportData.totals.bonus) }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-soft-warning text-center p-2">
                        <small class="text-muted">Bono P.Objetivo</small>
                        <strong>${{ fmt(reportData.totals.target_bonus) }}</strong>
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
                        <tr v-for="emp in reportData.employees" :key="emp.id">
                            <td class="fw-semi-bold sticky-col bg-white" style="white-space:nowrap; font-size:0.7rem;">{{ emp.full_name }}</td>
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
                            <td class="text-end" style="font-size:0.75rem;">{{ fmt(reportData.totals.amount) }}</td>
                            <td class="text-end" style="font-size:0.75rem;">{{ fmt(reportData.totals.bonus) }}</td>
                            <td class="text-end text-warning" style="font-size:0.75rem;">{{ fmt(reportData.totals.target_bonus) }}</td>
                            <td class="text-end text-primary" style="font-size:0.75rem;">{{ fmt(reportData.totals.amount + reportData.totals.bonus + reportData.totals.target_bonus) }}</td>
                            <td class="text-center" style="font-size:0.75rem;">{{ reportData.totals.workdays }}</td>
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
                    <div class="card-header bg-primary text-white py-2">
                        <strong>{{ emp.full_name }}</strong>
                        <span class="ms-2 small">{{ emp.rut }} | {{ emp.position || '-' }}</span>
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
                                    <small class="text-muted">DÃ­as Trabajados</small>
                                    <strong>{{ emp.days_worked }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla detalle dÃ­a a dÃ­a -->
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
                                                        {{ line.payment_type === 'dia' ? 'DÃ­a' : 'Trato' }}
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
                                            <!-- Subtotal del dÃ­a (solo si hay mÃ¡s de 1 lÃ­nea) -->
                                            <tr v-if="emp.days[date].lines.length > 1" class="bg-100">
                                                <td colspan="6" class="fw-semi-bold small text-end">
                                                    {{ new Date(date + 'T12:00:00').toLocaleDateString('es-CL', { weekday: 'short', day: '2-digit', month: '2-digit' }) }}
                                                    â€” Subtotal:
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
                                <th class="text-center">DÃ­as Trabajados</th>
                                <th class="text-center">Jornada</th>
                                <th class="text-end">Total Monto</th>
                                <th class="text-end">Total Bonos</th>
                                <th class="text-end">P.Obj</th>
                                <th class="text-end">Gran Total</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="emp in reportData.employees" :key="emp.id">
                                <td class="fw-semi-bold">{{ emp.full_name }}</td>
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
                                <td class="text-center">{{ reportData.totals.workdays }} JH</td>
                                <td class="text-end">{{ fmt(reportData.totals.amount) }}</td>
                                <td class="text-end">{{ fmt(reportData.totals.bonus) }}</td>
                                <td class="text-end text-warning">{{ fmt(reportData.totals.target_bonus) }}</td>
                                <td class="text-end">{{ fmt(reportData.totals.amount + reportData.totals.bonus + reportData.totals.target_bonus) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
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
                                {{ line.payment_type === 'dia' ? 'DÃ­a' : 'Trato' }}
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
