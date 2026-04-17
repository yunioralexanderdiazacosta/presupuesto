<script setup>
import { ref, computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    employees: Array,
    laborTypes: Array,
    laborRates: Array,
    bonusTypes: Array,
    costCenters: Array,
    parcels: { type: Array, default: () => [] },
    selectedDate: String,
    hasAttendance: Boolean,
    maxWorkdayPerDay: { type: Number, default: 1 },
    summary: Object,
});

const searchQuery = ref('');
const selectedParcelId = ref('');
const expandedEmployee = ref(null);
const addingLineFor = ref(null);
const editingYieldId = ref(null);

// Formulario inline para editar linea existente
const editLine = reactive({
    payment_type: 'trato',
    labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, workdays: 0,
    bonus_type_id: '', bonus_amount: 0, target_price: null, target_price_bonus: null, cost_center_ids: [], observations: '',
});

const filteredEmployees = computed(() => {
    let result = props.employees;
    if (selectedParcelId.value) {
        result = result.filter(e => String(e.parcel_id) === String(selectedParcelId.value));
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(e =>
            e.full_name.toLowerCase().includes(q) || e.rut.toLowerCase().includes(q)
        );
    }
    return result;
});

function toggleExpand(empId) {
    expandedEmployee.value = expandedEmployee.value === empId ? null : empId;
    addingLineFor.value = null;
}

function statusClass(emp) {
    if (emp.is_present === false) return 'table-danger bg-opacity-50';
    if (emp.is_present === true && emp.remaining_workdays <= 0) return 'table-success bg-opacity-25';
    return '';
}

function statusBadge(emp) {
    if (emp.is_present === null) return 'bg-secondary';
    if (!emp.is_present) return 'bg-danger';
    if (emp.remaining_workdays === null) return 'bg-info';
    if (emp.remaining_workdays <= 0) return 'bg-success';
    return 'bg-warning text-dark';
}

function statusText(emp) {
    if (emp.is_present === null) return 'Sin asist.';
    if (!emp.is_present) return 'Ausente';
    if (emp.remaining_workdays === null) return 'Sin límite';
    if (emp.remaining_workdays <= 0) return 'Completo';
    return emp.remaining_workdays + ' JH pend.';
}

// Formulario inline para nueva linea
const newLine = reactive({
    payment_type: 'trato',
    labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, workdays: 0,
    bonus_type_id: '', bonus_amount: 0, target_price: null, target_price_bonus: null, cost_center_ids: [], observations: '',
});

function onTargetPriceChange() {
    if (!newLine.target_price) {
        newLine.target_price_bonus = null;
        return;
    }
    const emp = props.employees.find(e => e.id === addingLineFor.value);
    const dailyRate = emp ? emp.daily_rate : 0;
    if (newLine.target_price < dailyRate) {
        newLine.target_price_bonus = null;
        return;
    }
    newLine.target_price_bonus = newLine.target_price - dailyRate;
}

function startAddLine(empId) {
    addingLineFor.value = empId;
    Object.assign(newLine, {
        payment_type: 'trato',
        labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, workdays: 0,
        bonus_type_id: '', bonus_amount: 0, target_price: null, target_price_bonus: null, cost_center_ids: [], observations: '',
    });
    const emp = props.employees.find(e => e.id === empId);
    if (emp) {
        if (emp.remaining_workdays === null) {
            newLine.workdays = 1.0;
        } else {
            newLine.workdays = Math.min(1.0, emp.remaining_workdays > 0 ? emp.remaining_workdays : 1.0);
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
    // Si es ausencia no remunerada, rate siempre es 0
    const selectedLabor = props.laborTypes.find(lt => String(lt.value) === String(newLine.labor_type_id));
    if (selectedLabor?.is_absence && !selectedLabor?.is_paid) {
        newLine.rate = 0;
        return;
    }
    const emp = props.employees.find(e => e.id === addingLineFor.value);
    const fullDayRate = emp ? emp.daily_rate : 0;
    newLine.rate = Math.round(fullDayRate * (newLine.workdays || 0));
}

function onWorkdayChange() {
    recalcDailyRate();
}

function onLaborChange() {
    newLine.labor_rate_id = '';
    // Detectar si es ausencia y auto-configurar
    const selectedLabor = props.laborTypes.find(lt => String(lt.value) === String(newLine.labor_type_id));
    if (selectedLabor?.is_absence) {
        newLine.payment_type = 'dia';
        newLine.quantity = 1;
        newLine.labor_rate_id = '';
        newLine.bonus_type_id = '';
        newLine.bonus_amount = 0;
        newLine.target_price = null;
        newLine.target_price_bonus = null;
        if (selectedLabor.is_paid) {
            // Setear jornada completa para calcular tarifa
            const emp = props.employees.find(e => e.id === addingLineFor.value);
            if (emp && emp.remaining_workdays !== null) {
                newLine.workdays = Math.min(1.0, emp.remaining_workdays > 0 ? emp.remaining_workdays : 1.0);
            } else {
                newLine.workdays = 1.0;
            }
            recalcDailyRate();
        } else {
            const emp = props.employees.find(e => e.id === addingLineFor.value);
            if (emp && emp.remaining_workdays !== null) {
                newLine.workdays = Math.min(1.0, emp.remaining_workdays > 0 ? emp.remaining_workdays : 1.0);
            } else {
                newLine.workdays = 1.0;
            }
            newLine.rate = 0;
            newLine.cost_center_ids = [];
        }
        return;
    }
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

const isAbsenceSelected = computed(() => {
    if (!newLine.labor_type_id) return false;
    const lt = props.laborTypes.find(l => String(l.value) === String(newLine.labor_type_id));
    return lt?.is_absence ?? false;
});

const isUnpaidAbsence = computed(() => {
    if (!newLine.labor_type_id) return false;
    const lt = props.laborTypes.find(l => String(l.value) === String(newLine.labor_type_id));
    return lt?.is_absence && !lt?.is_paid;
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

function getRemainingWorkdays(empId) {
    const emp = props.employees.find(e => e.id === empId);
    if (!emp || emp.remaining_workdays === null) return 1.0;
    return emp.remaining_workdays;
}

function hasWorkdayLimit() {
    return props.maxWorkdayPerDay > 0;
}

function saveLine(empId) {
    const emp = props.employees.find(e => e.id === empId);
    const remaining = (!emp || emp.remaining_workdays === null) ? null : emp.remaining_workdays;

    if (remaining !== null && remaining >= 0) {
        if (newLine.workdays > remaining) {
            Swal.fire({
                icon: 'warning',
                title: 'Jornada excedida',
                html: `Solo quedan <b>${remaining} JH</b> disponibles para este día.`,
            });
            return;
        }

        if (newLine.workdays > props.maxWorkdayPerDay) {
            Swal.fire({ icon: 'warning', title: 'Jornada excedida', text: `El máximo para este día es ${props.maxWorkdayPerDay} JH.` });
            return;
        }
    }

    // Validar precio objetivo > sueldo diario
    if (newLine.payment_type === 'dia' && newLine.target_price) {
        const dailyRate = emp ? emp.daily_rate : 0;
        if (newLine.target_price <= dailyRate) {
            Swal.fire({
                icon: 'warning',
                title: 'Precio objetivo inválido',
                html: `El precio objetivo (<b>$${newLine.target_price.toLocaleString('es-CL')}</b>) debe ser mayor al sueldo diario (<b>$${dailyRate.toLocaleString('es-CL')}</b>).`,
            });
            return;
        }
    }

    const form = useForm({
        employee_id: empId, date: props.selectedDate,
        payment_type: newLine.payment_type,
        labor_type_id: newLine.labor_type_id,
        labor_rate_id: newLine.payment_type === 'trato' ? newLine.labor_rate_id : null,
        rate: newLine.rate,
        quantity: newLine.quantity, workdays: newLine.workdays,
        bonus_type_id: newLine.bonus_type_id || null,
        bonus_amount: newLine.bonus_amount || 0,
        target_price: (newLine.payment_type === 'dia' && newLine.target_price) ? newLine.target_price : null,
        target_price_bonus: (newLine.payment_type === 'dia' && newLine.target_price_bonus) ? newLine.target_price_bonus : null,
        cost_center_ids: newLine.cost_center_ids, observations: newLine.observations,
    });
    form.post(route('daily-yields.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addingLineFor.value = null;
            Swal.fire({ icon: 'success', title: 'Tarja guardada', timer: 900, showConfirmButton: false });
        },
        onError: (errors) => {
            const msg = errors.workdays || 'Revisa los campos.';
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        },
    });
}

// === EDICIÓN INLINE ===
function startEditLine(yieldData) {
    addingLineFor.value = null;
    editingYieldId.value = yieldData.id;
    Object.assign(editLine, {
        payment_type: yieldData.payment_type || 'trato',
        labor_type_id: yieldData.labor_type_id || '',
        labor_rate_id: yieldData.labor_rate_id || '',
        rate: yieldData.rate || 0,
        quantity: yieldData.quantity || 0,
        workdays: yieldData.workdays || 0,
        bonus_type_id: yieldData.bonus_type_id || '',
        bonus_amount: yieldData.bonus_amount || 0,
        target_price: yieldData.target_price || null,
        target_price_bonus: yieldData.target_price_bonus || null,
        cost_center_ids: yieldData.cost_center_ids || [],
        observations: yieldData.observations || '',
    });
}

function cancelEdit() {
    editingYieldId.value = null;
}

const editFilteredRates = computed(() => {
    if (!editLine.labor_type_id) return props.laborRates;
    return props.laborRates.filter(lr => String(lr.labor_type_id) === String(editLine.labor_type_id));
});

const editLineAmount = computed(() => Math.round((editLine.rate || 0) * (editLine.quantity || 0)));

function onEditPaymentTypeChange() {
    editLine.labor_rate_id = '';
    if (editLine.payment_type === 'dia') {
        editLine.quantity = 1;
        recalcEditDailyRate();
    } else {
        editLine.rate = 0;
        editLine.quantity = 0;
        editLine.target_price = null;
        editLine.target_price_bonus = null;
    }
}

function recalcEditDailyRate() {
    if (editLine.payment_type !== 'dia') return;
    const emp = props.employees.find(e => e.yields && e.yields.some(y => y.id === editingYieldId.value));
    const fullDayRate = emp ? emp.daily_rate : 0;
    editLine.rate = Math.round(fullDayRate * (editLine.workdays || 0));
}

function onEditWorkdayChange() {
    recalcEditDailyRate();
}

function onEditLaborChange() {
    editLine.labor_rate_id = '';
    if (editLine.payment_type === 'dia') {
        recalcEditDailyRate();
    } else {
        editLine.rate = 0;
    }
}

function onEditRateChange() {
    const lr = props.laborRates.find(r => String(r.value) === String(editLine.labor_rate_id));
    editLine.rate = lr ? lr.rate : 0;
}

function onEditBonusChange() {
    const bt = props.bonusTypes.find(b => String(b.value) === String(editLine.bonus_type_id));
    editLine.bonus_amount = bt ? (bt.default_amount || 0) : 0;
}

function onEditTargetPriceChange() {
    if (!editLine.target_price) {
        editLine.target_price_bonus = null;
        return;
    }
    const emp = props.employees.find(e => e.yields && e.yields.some(y => y.id === editingYieldId.value));
    const dailyRate = emp ? emp.daily_rate : 0;
    if (editLine.target_price < dailyRate) {
        editLine.target_price_bonus = null;
        return;
    }
    editLine.target_price_bonus = editLine.target_price - dailyRate;
}

function saveEdit() {
    const emp = props.employees.find(e => e.yields && e.yields.some(y => y.id === editingYieldId.value));

    // Validar precio objetivo
    if (editLine.payment_type === 'dia' && editLine.target_price) {
        const dailyRate = emp ? emp.daily_rate : 0;
        if (editLine.target_price <= dailyRate) {
            Swal.fire({
                icon: 'warning',
                title: 'Precio objetivo inválido',
                html: `El precio objetivo (<b>$${editLine.target_price.toLocaleString('es-CL')}</b>) debe ser mayor al sueldo diario (<b>$${dailyRate.toLocaleString('es-CL')}</b>).`,
            });
            return;
        }
    }

    const form = useForm({
        payment_type: editLine.payment_type,
        labor_type_id: editLine.labor_type_id,
        labor_rate_id: editLine.payment_type === 'trato' ? editLine.labor_rate_id : null,
        rate: editLine.rate,
        quantity: editLine.quantity,
        workdays: editLine.workdays,
        bonus_type_id: editLine.bonus_type_id || null,
        bonus_amount: editLine.bonus_amount || 0,
        target_price: (editLine.payment_type === 'dia' && editLine.target_price) ? editLine.target_price : null,
        target_price_bonus: (editLine.payment_type === 'dia' && editLine.target_price_bonus) ? editLine.target_price_bonus : null,
        cost_center_ids: editLine.cost_center_ids,
        observations: editLine.observations,
    });
    form.put(route('daily-yields.update', editingYieldId.value), {
        preserveScroll: true,
        onSuccess: () => {
            editingYieldId.value = null;
            Swal.fire({ icon: 'success', title: 'Tarja actualizada', timer: 900, showConfirmButton: false });
        },
        onError: (errors) => {
            const msg = errors.workdays || 'Revisa los campos.';
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
                    <small class="text-muted">Total JH</small>
                    <strong>{{ summary.totalWorkdays }}</strong>
                </div>
            </div>
        </div>

        <!-- Buscar + Imprimir Plantilla -->
        <div class="row g-2 mb-3 align-items-center">
            <div class="col">
                <input type="text" v-model="searchQuery" class="form-control form-control-sm"
                    placeholder="Buscar colaborador..." />
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center gap-2 border rounded px-2 py-1 bg-light">
                    <small class="text-muted text-nowrap"><i class="fas fa-print me-1"></i></small>
                    <select v-model="selectedParcelId" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">Todas las parcelas</option>
                        <option v-for="p in parcels" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                    <a :href="route('daily-management.yield-template-pdf', { date: selectedDate, ...(selectedParcelId ? { parcel_id: selectedParcelId } : {}) })"
                        target="_blank" class="btn btn-falcon-default btn-sm text-nowrap">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                </div>
            </div>
        </div>

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
                        <th class="text-end">P.Obj $</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="emp in filteredEmployees" :key="emp.id">
                        <!-- Fila principal -->
                        <tr :class="[statusClass(emp), expandedEmployee===emp.id ? 'bg-success bg-opacity-10 border-start border-3 border-success' : '']" style="cursor:pointer" @click="toggleExpand(emp.id)">
                            <td><i class="fas fa-fw" :class="expandedEmployee===emp.id?'fa-chevron-down':'fa-chevron-right'"></i></td>
                            <td class="fw-semi-bold">
                                {{ emp.full_name }}
                                <span v-if="emp.yields && emp.yields.some(y => (!y.cost_center_ids || y.cost_center_ids.length === 0) && !y.is_absence)"
                                    class="badge bg-soft-warning text-warning ms-1" style="font-size: 0.6rem; vertical-align: middle;">
                                    <i class="fas fa-exclamation-triangle fa-xs me-1"></i>Falta CC
                                </span>
                            </td>
                            <td>{{ emp.rut }}</td>
                            <td class="text-center"><span class="badge" :class="statusBadge(emp)">{{ statusText(emp) }}</span></td>
                            <td class="text-center">{{ emp.yield_count }}</td>
                            <td class="text-center">
                                <span>{{ emp.total_workdays }} JH</span>
                                <div class="progress mt-1" style="height:4px">
                                    <div class="progress-bar" :class="emp.remaining_workdays<=0?'bg-success':'bg-warning'"
                                        :style="{width: Math.min(100, emp.total_workdays/maxWorkdayPerDay*100)+'%'}"></div>
                                </div>
                            </td>
                            <td class="text-end">{{ (emp.total_amount||0).toLocaleString('es-CL') }}</td>
                            <td class="text-end">{{ (emp.total_bonus||0).toLocaleString('es-CL') }}</td>
                            <td class="text-end">
                                <span v-if="emp.total_target_bonus" class="text-warning">
                                    {{ emp.total_target_bonus.toLocaleString('es-CL') }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                        <!-- Fila expandida: sub-tabla de tarjas -->
                        <tr v-if="expandedEmployee===emp.id">
                            <td colspan="9" class="p-0">
                                <div class="bg-light p-2">
                                    <table class="table table-sm table-bordered fs--2 mb-2" v-if="emp.yields && emp.yields.length">
                                        <thead>
                                            <tr class="bg-200">
                                                <th>Tipo</th><th>Labor</th><th>Trato</th><th class="text-end">Valor</th><th class="text-end">Cant.</th><th class="text-end">Monto</th>
                                                <th class="text-center">Jornada</th><th>C.Costo</th><th class="text-end">Bono</th><th class="text-end">P.Objetivo</th><th class="text-end">Bono Obj.</th><th>Obs.</th><th style="width:60px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-for="y in emp.yields" :key="y.id">
                                            <!-- MODO LECTURA -->
                                            <tr v-if="editingYieldId !== y.id" :class="{ 'bg-warning bg-opacity-10': y.is_absence }">
                                                <td><span class="badge" :class="y.payment_type==='dia'?'bg-info':'bg-primary'">{{ y.payment_type==='dia'?'Al día':'A trato' }}</span></td>
                                                <td>
                                                    <span v-if="y.is_absence" class="badge bg-warning text-dark me-1"><i class="fas fa-user-slash fa-xs"></i></span>
                                                    {{ y.labor_type_name }}
                                                </td>
                                                <td>{{ y.labor_rate_name || '-' }}</td>
                                                <td class="text-end">{{ (y.rate||0).toLocaleString('es-CL') }}</td>
                                                <td class="text-end">{{ y.quantity }}</td>
                                                <td class="text-end fw-semi-bold">{{ (y.amount||0).toLocaleString('es-CL') }}</td>
                                                <td class="text-center">{{ y.workdays }}</td>
                                                <td>{{ y.cost_center_names || '-' }}</td>
                                                <td class="text-end">{{ y.bonus_amount ? y.bonus_amount.toLocaleString('es-CL') : '-' }}</td>
                                                <td class="text-end">
                                                    <span v-if="y.target_price" class="badge bg-soft-warning text-warning border border-warning">
                                                        ${{ y.target_price.toLocaleString('es-CL') }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td class="text-end">
                                                    <span v-if="y.target_price_bonus" class="fw-semi-bold text-warning">
                                                        {{ y.target_price_bonus.toLocaleString('es-CL') }}
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>{{ y.observations || '' }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <button class="btn btn-sm btn-link text-primary p-0" @click.stop="startEditLine(y)" title="Editar">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-link text-danger p-0" @click.stop="deleteLine(y.id)" title="Eliminar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- MODO EDICIÓN INLINE -->
                                            <tr v-else class="bg-warning bg-opacity-10">
                                                <td colspan="13" class="p-2">
                                                    <div class="d-flex align-items-center gap-3 mb-2">
                                                        <span class="small fw-semi-bold"><i class="fas fa-edit text-primary me-1"></i>Editando:</span>
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="radio" v-model="editLine.payment_type" value="trato" :id="'edit_pt_trato_'+y.id" @change="onEditPaymentTypeChange">
                                                            <label class="form-check-label small" :for="'edit_pt_trato_'+y.id">A trato</label>
                                                        </div>
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="radio" v-model="editLine.payment_type" value="dia" :id="'edit_pt_dia_'+y.id" @change="onEditPaymentTypeChange">
                                                            <label class="form-check-label small" :for="'edit_pt_dia_'+y.id">Al día <span v-if="emp.daily_rate" class="text-muted">({{ emp.daily_rate.toLocaleString('es-CL') }}/día)</span></label>
                                                        </div>
                                                    </div>
                                                    <div class="row g-1 align-items-end">
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-0">Labor</label>
                                                            <select v-model="editLine.labor_type_id" @change="onEditLaborChange" class="form-select form-select-sm">
                                                                <option value="" disabled>Seleccione</option>
                                                                <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2" v-if="editLine.payment_type==='trato'">
                                                            <label class="form-label small mb-0">Trato</label>
                                                            <select v-model="editLine.labor_rate_id" @change="onEditRateChange" class="form-select form-select-sm">
                                                                <option value="" disabled>Seleccione</option>
                                                                <option v-for="lr in editFilteredRates" :key="lr.value" :value="lr.value">{{ lr.label }} (${{ lr.rate.toLocaleString('es-CL') }})</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-1" v-if="editLine.payment_type==='dia'">
                                                            <label class="form-label small mb-0">Tarifa/día</label>
                                                            <input type="text" :value="editLine.rate.toLocaleString('es-CL')" class="form-control form-control-sm bg-light" readonly />
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label small mb-0">Cant.</label>
                                                            <input type="number" v-model.number="editLine.quantity" class="form-control form-control-sm" min="0" step="0.1" :readonly="editLine.payment_type==='dia'" :class="{'bg-light': editLine.payment_type==='dia'}" />
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label small mb-0">Monto</label>
                                                            <input type="text" :value="editLineAmount.toLocaleString('es-CL')" class="form-control form-control-sm bg-light" readonly />
                                                        </div>
                                                        <!-- Precio objetivo: solo en modo 'Al día' -->
                                                        <div class="col-md-2" v-if="editLine.payment_type === 'dia'">
                                                            <label class="form-label small mb-0 text-warning fw-semi-bold">
                                                                <i class="fas fa-bullseye fa-xs me-1"></i>Precio Objetivo
                                                            </label>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">$</span>
                                                                <input
                                                                    type="number"
                                                                    v-model.number="editLine.target_price"
                                                                    @input="onEditTargetPriceChange"
                                                                    class="form-control form-control-sm"
                                                                    :class="{'border-danger': editLine.target_price && editLine.target_price <= emp.daily_rate}"
                                                                    placeholder="ej: 30000"
                                                                    min="0"
                                                                />
                                                            </div>
                                                            <div v-if="editLine.target_price" class="mt-1">
                                                                <small v-if="editLine.target_price <= emp.daily_rate" class="text-danger">
                                                                    <i class="fas fa-exclamation-triangle fa-xs me-1"></i>Debe ser mayor al sueldo diario
                                                                </small>
                                                                <small v-else class="text-muted">
                                                                    Bono: <strong class="text-success">${{ (editLine.target_price - emp.daily_rate).toLocaleString('es-CL') }}</strong>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label small mb-0">Jornada</label>
                                                            <input type="number" v-model.number="editLine.workdays" @change="onEditWorkdayChange" class="form-control form-control-sm" min="0.1" max="1" step="0.25" />
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-0">C.Costo</label>
                                                            <Multiselect v-model="editLine.cost_center_ids" :options="costCenters" mode="tags" :searchable="true" :close-on-select="false" placeholder="Seleccione" class="multiselect-sm" />
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label small mb-0">Bono</label>
                                                            <select v-model="editLine.bonus_type_id" @change="onEditBonusChange" class="form-select form-select-sm">
                                                                <option value="">Sin bono</option>
                                                                <option v-for="bt in bonusTypes" :key="bt.value" :value="bt.value">{{ bt.label }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-1 d-flex gap-1 align-items-end">
                                                            <button class="btn btn-sm btn-falcon-default" @click.stop="saveEdit" title="Guardar">
                                                                <i class="fas fa-check text-success"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-falcon-default" @click.stop="cancelEdit" title="Cancelar">
                                                                <i class="fas fa-times text-danger"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <p v-else class="text-muted small mb-2">Sin tarjas registradas para esta fecha.</p>
                                    <!-- Formulario inline para agregar linea -->
                                    <div v-if="addingLineFor===emp.id" class="border-top pt-2">
                                        <!-- Aviso ausencia -->
                                        <div v-if="isAbsenceSelected" class="alert alert-warning py-1 px-2 mb-2 d-flex align-items-center gap-2 small">
                                            <i class="fas fa-user-slash"></i>
                                            <span>Ausencia — campos auto-completados</span>
                                        </div>
                                        <!-- Toggle Al día / A trato -->
                                        <div class="d-flex align-items-center gap-3 mb-2" v-if="!isAbsenceSelected">
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
                                            <!-- Precio objetivo: solo en modo 'Al día' y NO ausencia -->
                                            <div class="col-md-2" v-if="newLine.payment_type === 'dia' && !isAbsenceSelected">
                                                <label class="form-label small mb-0 text-warning fw-semi-bold">
                                                    <i class="fas fa-bullseye fa-xs me-1"></i>Precio Objetivo
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">$</span>
                                                    <input
                                                        type="number"
                                                        v-model.number="newLine.target_price"
                                                        @input="onTargetPriceChange"
                                                        class="form-control form-control-sm"
                                                        :class="{'border-danger': newLine.target_price && newLine.target_price <= (props.employees.find(e => e.id === addingLineFor)?.daily_rate || 0)}"
                                                        placeholder="ej: 30000"
                                                        min="0"
                                                    />
                                                </div>
                                                <div v-if="newLine.target_price" class="mt-1">
                                                    <small v-if="newLine.target_price <= (props.employees.find(e => e.id === addingLineFor)?.daily_rate || 0)" class="text-danger">
                                                        <i class="fas fa-exclamation-triangle fa-xs me-1"></i>Debe ser mayor al sueldo diario
                                                    </small>
                                                    <small v-else class="text-muted">
                                                        Bono resultante:
                                                        <strong class="text-success">${{ (newLine.target_price - (props.employees.find(e => e.id === addingLineFor)?.daily_rate || 0)).toLocaleString('es-CL') }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small mb-0">Jornada</label>
                                                <input type="number" v-model.number="newLine.workdays" @change="onWorkdayChange" class="form-control form-control-sm" min="0.1" :max="getRemainingWorkdays(emp.id)" step="0.25" />
                                            </div>
                                            <div class="col-md-2" v-if="!isUnpaidAbsence">
                                                <label class="form-label small mb-0">C.Costo</label>
                                                <Multiselect v-model="newLine.cost_center_ids" :options="costCenters" mode="tags" :searchable="true" :close-on-select="false" placeholder="Seleccione" class="multiselect-sm" />
                                            </div>
                                            <div class="col-md-1" v-if="!isAbsenceSelected">
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
                                        :disabled="hasWorkdayLimit() && emp.remaining_workdays !== null && emp.remaining_workdays <= 0"
                                        :title="(hasWorkdayLimit() && emp.remaining_workdays !== null && emp.remaining_workdays <= 0) ? 'Sin jornada disponible' : 'Agregar línea'">
                                        <i class="fas fa-plus me-1"></i>Agregar linea
                                        <span v-if="hasWorkdayLimit() && emp.remaining_workdays !== null && emp.remaining_workdays <= 0" class="ms-1 text-danger small">(sin JH)</span>
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
