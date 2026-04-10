<script setup>
import { ref, computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    employees: Array,
    laborTypes: Array,
    laborRates: Array,
    bonusTypes: Array,
    costCenters: Array,
    selectedDate: String,
    hasAttendance: Boolean,
    maxHoursPerDay: { type: Number, default: 8 },
    summary: Object,
});

const searchQuery = ref('');
const expandedEmployee = ref(null);
const addingLineFor = ref(null);

const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees;
    const q = searchQuery.value.toLowerCase();
    return props.employees.filter(e =>
        e.full_name.toLowerCase().includes(q) || e.rut.toLowerCase().includes(q)
    );
});

function toggleExpand(empId) {
    expandedEmployee.value = expandedEmployee.value === empId ? null : empId;
    addingLineFor.value = null;
}

function statusClass(emp) {
    if (emp.is_present === false) return 'table-danger bg-opacity-50';
    if (emp.is_present === true && emp.remaining_hours <= 0) return 'table-success bg-opacity-25';
    return '';
}

function statusBadge(emp) {
    if (emp.is_present === null) return 'bg-secondary';
    if (!emp.is_present) return 'bg-danger';
    if (emp.remaining_hours === null) return 'bg-info';
    if (emp.remaining_hours <= 0) return 'bg-success';
    return 'bg-warning text-dark';
}

function statusText(emp) {
    if (emp.is_present === null) return 'Sin asist.';
    if (!emp.is_present) return 'Ausente';
    if (emp.remaining_hours === null) return 'Sin límite';
    if (emp.remaining_hours <= 0) return 'Completo';
    return emp.remaining_hours + 'h pend.';
}

// Formulario inline para nueva linea
const newLine = reactive({
    payment_type: 'trato',
    labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, hours: 0,
    bonus_type_id: '', bonus_amount: 0, cost_center_id: '', observations: '',
});

function startAddLine(empId) {
    addingLineFor.value = empId;
    Object.assign(newLine, {
        payment_type: 'trato',
        labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, hours: 0,
        bonus_type_id: '', bonus_amount: 0, cost_center_id: '', observations: '',
    });
    const emp = props.employees.find(e => e.id === empId);
    if (emp) {
        if (emp.remaining_hours === null) {
            newLine.hours = props.maxHoursPerDay > 0 ? props.maxHoursPerDay : 8;
        } else {
            const maxH = props.maxHoursPerDay > 0 ? props.maxHoursPerDay : 8;
            newLine.hours = Math.min(maxH, emp.remaining_hours > 0 ? emp.remaining_hours : maxH);
        }
    }
}

function onPaymentTypeChange() {
    newLine.labor_rate_id = '';
    if (newLine.payment_type === 'dia') {
        newLine.quantity = 1;
        recalcDailyRate();
    } else {
        newLine.rate = 0;
        newLine.quantity = 0;
    }
}

function recalcDailyRate() {
    if (newLine.payment_type !== 'dia') return;
    const emp = props.employees.find(e => e.id === addingLineFor.value);
    const fullDayRate = emp ? emp.daily_rate : 0;
    newLine.rate = props.maxHoursPerDay > 0
        ? Math.round(fullDayRate * (newLine.hours || 0) / props.maxHoursPerDay)
        : fullDayRate;
}

function onHoursChange() {
    recalcDailyRate();
}

function onLaborChange() {
    newLine.labor_rate_id = '';
    if (newLine.payment_type === 'dia') {
        recalcDailyRate();
    } else {
        newLine.rate = 0;
    }
}

const filteredRates = computed(() => {
    if (!newLine.labor_type_id) return props.laborRates;
    return props.laborRates.filter(lr => String(lr.labor_type_id) === String(newLine.labor_type_id));
});

function onRateChange() {
    const lr = props.laborRates.find(r => String(r.value) === String(newLine.labor_rate_id));
    newLine.rate = lr ? lr.rate : 0;
}

function onBonusChange() {
    const bt = props.bonusTypes.find(b => String(b.value) === String(newLine.bonus_type_id));
    newLine.bonus_amount = bt ? (bt.default_amount || 0) : 0;
}

const newLineAmount = computed(() => Math.round((newLine.rate || 0) * (newLine.quantity || 0)));

function getRemainingHours(empId) {
    const emp = props.employees.find(e => e.id === empId);
    if (!emp || emp.remaining_hours === null) return 24;
    return emp.remaining_hours;
}

function hasHourLimit() {
    return props.maxHoursPerDay > 0;
}

function saveLine(empId) {
    const emp = props.employees.find(e => e.id === empId);
    const remaining = (!emp || emp.remaining_hours === null) ? null : emp.remaining_hours;

    if (remaining !== null && remaining >= 0) {
        if (newLine.hours > remaining) {
            Swal.fire({
                icon: 'warning',
                title: 'Horas excedidas',
                html: `Solo quedan <b>${remaining}h</b> disponibles de <b>${props.maxHoursPerDay}h</b> para este día.`,
            });
            return;
        }

        if (newLine.hours > props.maxHoursPerDay) {
            Swal.fire({ icon: 'warning', title: 'Horas excedidas', text: `El máximo para este día es ${props.maxHoursPerDay}h.` });
            return;
        }
    }

    const form = useForm({
        employee_id: empId, date: props.selectedDate,
        payment_type: newLine.payment_type,
        labor_type_id: newLine.labor_type_id,
        labor_rate_id: newLine.payment_type === 'trato' ? newLine.labor_rate_id : null,
        rate: newLine.rate,
        quantity: newLine.quantity, hours: newLine.hours,
        bonus_type_id: newLine.bonus_type_id || null,
        bonus_amount: newLine.bonus_amount || 0,
        cost_center_id: newLine.cost_center_id, observations: newLine.observations,
    });
    form.post(route('daily-yields.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addingLineFor.value = null;
            Swal.fire({ icon: 'success', title: 'Tarja guardada', timer: 900, showConfirmButton: false });
        },
        onError: (errors) => {
            const msg = errors.hours || 'Revisa los campos.';
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        },
    });
}

function deleteLine(yieldId) {
    Swal.fire({
        title: 'Eliminar linea?', icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Eliminar', confirmButtonColor: '#d33',
    }).then(r => {
        if (r.isConfirmed) router.delete(route('daily-yields.delete', yieldId), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Eliminada', timer: 800, showConfirmButton: false });
                router.reload({ preserveScroll: true, preserveState: true });
            },
        });
    });
}
</script>

<template>
    <div>
        <!-- Resumen -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card bg-soft-primary text-center p-2">
                    <small class="text-muted">Presentes</small>
                    <strong>{{ summary.presentCount }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-danger text-center p-2">
                    <small class="text-muted">Ausentes</small>
                    <strong>{{ summary.absentCount }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-success text-center p-2">
                    <small class="text-muted">Total $</small>
                    <strong>{{ (summary.totalAmount||0).toLocaleString('es-CL') }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-warning text-center p-2">
                    <small class="text-muted">Total Hrs</small>
                    <strong>{{ summary.totalHours }}</strong>
                </div>
            </div>
        </div>

        <!-- Buscar -->
        <input type="text" v-model="searchQuery" class="form-control form-control-sm mb-3"
            placeholder="Buscar colaborador..." />

        <!-- Tabla principal -->
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle fs--1 mb-0">
                <thead class="bg-200">
                    <tr>
                        <th style="width:30px"></th>
                        <th>Colaborador</th>
                        <th>RUT</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Lineas</th>
                        <th class="text-center">Horas</th>
                        <th class="text-end">Monto $</th>
                        <th class="text-end">Bono $</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="emp in filteredEmployees" :key="emp.id">
                        <!-- Fila principal -->
                        <tr :class="[statusClass(emp), expandedEmployee===emp.id ? 'bg-success bg-opacity-10 border-start border-3 border-success' : '']" style="cursor:pointer" @click="toggleExpand(emp.id)">
                            <td><i class="fas fa-fw" :class="expandedEmployee===emp.id?'fa-chevron-down':'fa-chevron-right'"></i></td>
                            <td class="fw-semi-bold">{{ emp.full_name }}</td>
                            <td>{{ emp.rut }}</td>
                            <td class="text-center"><span class="badge" :class="statusBadge(emp)">{{ statusText(emp) }}</span></td>
                            <td class="text-center">{{ emp.yield_count }}</td>
                            <td class="text-center">
                                <span>{{ emp.total_hours }}/{{ maxHoursPerDay }}h</span>
                                <div class="progress mt-1" style="height:4px">
                                    <div class="progress-bar" :class="emp.remaining_hours<=0?'bg-success':'bg-warning'"
                                        :style="{width: Math.min(100, emp.total_hours/maxHoursPerDay*100)+'%'}"></div>
                                </div>
                            </td>
                            <td class="text-end">{{ (emp.total_amount||0).toLocaleString('es-CL') }}</td>
                            <td class="text-end">{{ (emp.total_bonus||0).toLocaleString('es-CL') }}</td>
                        </tr>
                        <!-- Fila expandida: sub-tabla de tarjas -->
                        <tr v-if="expandedEmployee===emp.id">
                            <td colspan="8" class="p-0">
                                <div class="bg-light p-2">
                                    <table class="table table-sm table-bordered fs--2 mb-2" v-if="emp.yields && emp.yields.length">
                                        <thead>
                                            <tr class="bg-200">
                                                <th>Tipo</th><th>Labor</th><th>Trato</th><th>Valor</th><th>Cant.</th><th>Monto</th>
                                                <th>Horas</th><th>C.Costo</th><th>Bono</th><th>Obs.</th><th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="y in emp.yields" :key="y.id">
                                                <td><span class="badge" :class="y.payment_type==='dia'?'bg-info':'bg-primary'">{{ y.payment_type==='dia'?'Al día':'A trato' }}</span></td>
                                                <td>{{ y.labor_type_name }}</td>
                                                <td>{{ y.labor_rate_name || '-' }}</td>
                                                <td class="text-end">{{ (y.rate||0).toLocaleString('es-CL') }}</td>
                                                <td class="text-end">{{ y.quantity }}</td>
                                                <td class="text-end fw-semi-bold">{{ (y.amount||0).toLocaleString('es-CL') }}</td>
                                                <td class="text-center">{{ y.hours }}</td>
                                                <td>{{ y.cost_center_name }}</td>
                                                <td class="text-end">{{ y.bonus_amount ? y.bonus_amount.toLocaleString('es-CL') : '-' }}</td>
                                                <td>{{ y.observations || '' }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-link text-danger p-0" @click.stop="deleteLine(y.id)" title="Eliminar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p v-else class="text-muted small mb-2">Sin tarjas registradas para esta fecha.</p>
                                    <!-- Formulario inline para agregar linea -->
                                    <div v-if="addingLineFor===emp.id" class="border-top pt-2">
                                        <!-- Toggle Al día / A trato -->
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <span class="small fw-semi-bold">Tipo:</span>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" v-model="newLine.payment_type" value="trato" :id="'pt_trato_'+emp.id" @change="onPaymentTypeChange">
                                                <label class="form-check-label small" :for="'pt_trato_'+emp.id">A trato</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" v-model="newLine.payment_type" value="dia" :id="'pt_dia_'+emp.id" @change="onPaymentTypeChange">
                                                <label class="form-check-label small" :for="'pt_dia_'+emp.id">Al día <span v-if="emp.daily_rate" class="text-muted">({{ emp.daily_rate.toLocaleString('es-CL') }}/día)</span></label>
                                            </div>
                                        </div>
                                        <div class="row g-1 align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label small mb-0">Labor</label>
                                                <select v-model="newLine.labor_type_id" @change="onLaborChange" class="form-select form-select-sm">
                                                    <option value="" disabled>Seleccione</option>
                                                    <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2" v-if="newLine.payment_type==='trato'">
                                                <label class="form-label small mb-0">Trato</label>
                                                <select v-model="newLine.labor_rate_id" @change="onRateChange" class="form-select form-select-sm">
                                                    <option value="" disabled>Seleccione</option>
                                                    <option v-for="lr in filteredRates" :key="lr.value" :value="lr.value">{{ lr.label }} (${{ lr.rate.toLocaleString('es-CL') }})</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1" v-if="newLine.payment_type==='dia'">
                                                <label class="form-label small mb-0">Tarifa/día</label>
                                                <input type="text" :value="newLine.rate.toLocaleString('es-CL')" class="form-control form-control-sm bg-light" readonly />
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">Cant.</label>
                                                <input type="number" v-model.number="newLine.quantity" class="form-control form-control-sm" min="0" step="0.1" :readonly="newLine.payment_type==='dia'" :class="{'bg-light': newLine.payment_type==='dia'}" />
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">Monto</label>
                                                <input type="text" :value="newLineAmount.toLocaleString('es-CL')" class="form-control form-control-sm bg-light" readonly />
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">Horas</label>
                                                <input type="number" v-model.number="newLine.hours" @change="onHoursChange" class="form-control form-control-sm" min="0.5" :max="getRemainingHours(emp.id)" step="0.5" />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-0">C.Costo</label>
                                                <select v-model="newLine.cost_center_id" class="form-select form-select-sm">
                                                    <option value="" disabled>Seleccione</option>
                                                    <option v-for="cc in costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">Bono</label>
                                                <select v-model="newLine.bonus_type_id" @change="onBonusChange" class="form-select form-select-sm">
                                                    <option value="">Sin bono</option>
                                                    <option v-for="bt in bonusTypes" :key="bt.value" :value="bt.value">{{ bt.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex gap-1">
                                                <button class="btn btn-sm btn-falcon-default" @click.stop="saveLine(emp.id)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-falcon-default" @click.stop="addingLineFor=null">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button v-else class="btn btn-sm btn-falcon-default"
                                        @click.stop="startAddLine(emp.id)"
                                        :disabled="hasHourLimit() && emp.remaining_hours !== null && emp.remaining_hours <= 0"
                                        :title="(hasHourLimit() && emp.remaining_hours !== null && emp.remaining_hours <= 0) ? 'Sin horas disponibles' : 'Agregar línea'">
                                        <i class="fas fa-plus me-1"></i>Agregar linea
                                        <span v-if="hasHourLimit() && emp.remaining_hours !== null && emp.remaining_hours <= 0" class="ms-1 text-danger small">(sin horas)</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p v-if="!filteredEmployees.length" class="text-muted text-center py-4">No hay colaboradores para mostrar.</p>
    </div>
</template>
