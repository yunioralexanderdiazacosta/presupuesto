<script setup>
import { ref, computed, reactive, watch } from 'vue';
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
    branches: { type: Array, default: () => [] },
    groupings: { type: Array, default: () => [] },
    selectedDate: String,
    hasAttendance: Boolean,
    maxWorkdayPerDay: { type: Number, default: 1 },
    summary: Object,
});

const searchQuery = ref('');
const selectedParcelId = ref('');
const selectedBranchId = ref('');
const expandedEmployee = ref(null);
const addingLineFor = ref(null);
const editingYieldId = ref(null);

// === REGISTRO MASIVO ===
const showBulkPanel = ref(false);
const bulkSelectedIds = ref([]);
const bulkGrouping = ref('');
const bulkExpandedCC = ref(false);
const bulkLine = reactive({
    labor_type_id: '',
    workdays: 1,
    bonus_type_id: '',
    bonus_amount: 0,
    cost_center_ids: [],
    observations: '',
});

watch(bulkGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        bulkLine.cost_center_ids = grouping.cost_centers.map(cc => cc.id);
    }
});

// === REGISTRO MASIVO POR FECHAS ===
const showBulkByDatesPanel = ref(false);
const bulkDateSelectedIds = ref([]);
const bulkSelectedDates = ref([]);
const bulkDateGrouping = ref('');
const bulkDateLine = reactive({
    labor_type_id: '',
    workdays: 1,
    bonus_type_id: '',
    bonus_amount: 0,
    cost_center_ids: [],
    observations: '',
});

watch(bulkDateGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        bulkDateLine.cost_center_ids = grouping.cost_centers.map(cc => cc.id);
    }
});

// Generar todos los días del mes del selectedDate
const daysOfCurrentMonth = computed(() => {
    if (!props.selectedDate) return [];
    const [year, month] = props.selectedDate.split('-').map(Number);
    const daysInMonth = new Date(year, month, 0).getDate();
    const days = [];
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dayOfWeek = new Date(year, month - 1, d).getDay(); // 0=Dom, 6=Sab
        days.push({
            value: dateStr,
            day: d,
            label: dateStr,
            isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
        });
    }
    return days;
});

function toggleDate(dateValue) {
    const idx = bulkSelectedDates.value.indexOf(dateValue);
    if (idx >= 0) {
        bulkSelectedDates.value.splice(idx, 1);
    } else {
        bulkSelectedDates.value.push(dateValue);
    }
}

function selectAllDates() {
    bulkSelectedDates.value = daysOfCurrentMonth.value
        .filter(d => !d.isWeekend)
        .map(d => d.value);
}

const bulkDateAllSelected = computed(() =>
    props.employees.length > 0 &&
    props.employees.every(e => bulkDateSelectedIds.value.includes(e.id))
);

function toggleBulkDateAll() {
    if (bulkDateAllSelected.value) {
        bulkDateSelectedIds.value = [];
    } else {
        bulkDateSelectedIds.value = props.employees.map(e => e.id);
    }
}

function openBulkByDatesPanel() {
    showBulkByDatesPanel.value = true;
    bulkDateSelectedIds.value = props.employees.map(e => e.id);
    bulkSelectedDates.value = [];
    Object.assign(bulkDateLine, {
        labor_type_id: '',
        workdays: 1,
        bonus_type_id: '',
        bonus_amount: 0,
        cost_center_ids: [],
        observations: '',
    });
    bulkDateGrouping.value = '';
}

function saveBulkByDates() {
    if (!bulkDateLine.labor_type_id) {
        Swal.fire({ icon: 'warning', title: 'Selecciona un tipo de labor', timer: 1500, showConfirmButton: false });
        return;
    }
    if (bulkDateSelectedIds.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Selecciona al menos un colaborador', timer: 1500, showConfirmButton: false });
        return;
    }
    if (bulkSelectedDates.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Selecciona al menos una fecha', timer: 1500, showConfirmButton: false });
        return;
    }

    const employees = bulkDateSelectedIds.value.map(id => {
        const emp = props.employees.find(e => e.id === id);
        const selectedLabor = props.laborTypes.find(lt => String(lt.value) === String(bulkDateLine.labor_type_id));
        const rate = (selectedLabor?.is_absence && !selectedLabor?.is_paid)
            ? 0
            : Math.round((emp?.daily_rate || 0) * (bulkDateLine.workdays || 1));
        return { employee_id: id, rate };
    });

    const total = employees.length * bulkSelectedDates.value.length;

    Swal.fire({
        icon: 'question',
        title: '¿Confirmar registro masivo?',
        html: `Se crearán hasta <strong>${total}</strong> tarjas en <strong>${bulkSelectedDates.value.length}</strong> fecha(s) para <strong>${employees.length}</strong> colaborador(es).<br><small class="text-muted">Las que ya existan se omitirán.</small>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = useForm({
            dates: bulkSelectedDates.value,
            labor_type_id: bulkDateLine.labor_type_id,
            workdays: bulkDateLine.workdays,
            bonus_type_id: bulkDateLine.bonus_type_id || null,
            bonus_amount: bulkDateLine.bonus_amount || 0,
            cost_center_ids: bulkDateLine.cost_center_ids,
            observations: bulkDateLine.observations || null,
            employees,
        });

        form.post(route('daily-yields.bulk-store-by-dates'), {
            preserveScroll: true,
            onSuccess: () => {
                showBulkByDatesPanel.value = false;
                Swal.fire({ icon: 'success', title: `Tarjas guardadas`, timer: 1500, showConfirmButton: false });
            },
            onError: (errors) => {
                const msg = Object.values(errors)[0] || 'Revisa los campos.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            },
        });
    });
}

// Empleados elegibles para registro masivo: sin tarja "al día" ese día
const bulkEligibleEmployees = computed(() => {
    return props.employees.filter(emp => {
        if (!emp.yields) return true;
        return !emp.yields.some(y => y.payment_type === 'dia');
    });
});

const bulkAllSelected = computed(() =>
    bulkEligibleEmployees.value.length > 0 &&
    bulkEligibleEmployees.value.every(e => bulkSelectedIds.value.includes(e.id))
);

function toggleBulkAll() {
    if (bulkAllSelected.value) {
        bulkSelectedIds.value = [];
    } else {
        bulkSelectedIds.value = bulkEligibleEmployees.value.map(e => e.id);
    }
}

function bulkRate(emp) {
    const selectedLabor = props.laborTypes.find(lt => String(lt.value) === String(bulkLine.labor_type_id));
    if (selectedLabor?.is_absence && !selectedLabor?.is_paid) return 0;
    return Math.round((emp.daily_rate || 0) * (bulkLine.workdays || 1));
}

function openBulkPanel() {
    showBulkPanel.value = true;
    bulkSelectedIds.value = bulkEligibleEmployees.value.map(e => e.id);
    Object.assign(bulkLine, {
        labor_type_id: '',
        workdays: 1,
        bonus_type_id: '',
        bonus_amount: 0,
        cost_center_ids: [],
        observations: '',
    });
    bulkGrouping.value = '';
    bulkExpandedCC.value = false;
}

function saveBulk() {
    if (!bulkLine.labor_type_id) {
        Swal.fire({ icon: 'warning', title: 'Selecciona un tipo de labor', timer: 1500, showConfirmButton: false });
        return;
    }
    if (bulkSelectedIds.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Selecciona al menos un colaborador', timer: 1500, showConfirmButton: false });
        return;
    }

    const employees = bulkSelectedIds.value.map(id => {
        const emp = props.employees.find(e => e.id === id);
        return { employee_id: id, rate: bulkRate(emp) };
    });

    const form = useForm({
        date: props.selectedDate,
        labor_type_id: bulkLine.labor_type_id,
        workdays: bulkLine.workdays,
        bonus_type_id: bulkLine.bonus_type_id || null,
        bonus_amount: bulkLine.bonus_amount || 0,
        cost_center_ids: bulkLine.cost_center_ids,
        observations: bulkLine.observations || null,
        employees,
    });

    form.post(route('daily-yields.bulk-store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBulkPanel.value = false;
            Swal.fire({ icon: 'success', title: `${employees.length} tarjas guardadas`, timer: 1200, showConfirmButton: false });
        },
        onError: (errors) => {
            const msg = Object.values(errors)[0] || 'Revisa los campos.';
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        },
    });
}

// Agrupación rápida de CC para nueva línea y para edición
const newLineGrouping = ref('');
const editLineGrouping = ref('');
const newLineExpandedCC = ref(false);
const editLineExpandedCC = ref(false);

watch(newLineGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        newLine.cost_center_ids = grouping.cost_centers.map(cc => cc.id);
    }
});

watch(editLineGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        editLine.cost_center_ids = grouping.cost_centers.map(cc => cc.id);
    }
});

// Formulario inline para editar linea existente
const editLine = reactive({
    payment_type: 'dia',
    labor_type_id: '', labor_rate_id: '', rate: 0, quantity: 0, workdays: 0,
    bonus_type_id: '', bonus_amount: 0, target_price: null, target_price_bonus: null, cost_center_ids: [], observations: '',
});

const filteredEmployees = computed(() => {
    let result = props.employees;
    if (selectedParcelId.value) {
        result = result.filter(e => String(e.parcel_id) === String(selectedParcelId.value));
    }
    if (selectedBranchId.value) {
        result = result.filter(e => String(e.branch_id) === String(selectedBranchId.value));
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
    payment_type: 'dia',
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
        payment_type: 'dia',
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
    onPaymentTypeChange();
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

function onBulkBonusChange() {
    const bt = props.bonusTypes.find(b => String(b.value) === String(bulkLine.bonus_type_id));
    bulkLine.bonus_amount = bt ? (bt.default_amount || 0) : 0;
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
                <button @click="openBulkPanel" class="btn btn-falcon-default btn-sm">
                    <i class="fas fa-layer-group me-1"></i>Registro masivo
                </button>
            </div>
            <div class="col-auto">
                <button @click="openBulkByDatesPanel" class="btn btn-falcon-default btn-sm">
                    <i class="fas fa-calendar-plus me-1"></i>Masivo por fechas
                </button>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center gap-2 border rounded px-2 py-1 bg-light">
                    <small class="text-muted text-nowrap"><i class="fas fa-print me-1"></i></small>
                    <select v-if="branches && branches.length" v-model="selectedBranchId" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                    <select v-model="selectedParcelId" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">Todas las parcelas</option>
                        <option v-for="p in parcels" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                    <a :href="route('daily-management.yield-template-pdf', { date: selectedDate, ...(selectedParcelId ? { parcel_id: selectedParcelId } : {}), ...(selectedBranchId ? { branch_id: selectedBranchId } : {}) })"
                        target="_blank" class="btn btn-falcon-default btn-sm text-nowrap">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a :href="route('daily-management.yield-template-excel', { date: selectedDate, ...(selectedParcelId ? { parcel_id: selectedParcelId } : {}), ...(selectedBranchId ? { branch_id: selectedBranchId } : {}) })"
                        class="btn btn-falcon-default btn-sm text-nowrap">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Panel Registro Masivo -->
        <div v-if="showBulkPanel" class="card mb-3" style="border: 2px solid #8B6914;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #fdf3dc;">
                <span class="fw-semi-bold fs--1" style="color:#7a5c0f;"><i class="fas fa-layer-group me-2"></i>Registro masivo — Al día</span>
                <button type="button" @click="showBulkPanel = false" class="btn-close btn-close-sm"></button>
            </div>
            <div class="card-body py-2">
                <!-- Datos compartidos -->
                <div class="row g-2 mb-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Labor <span class="text-danger">*</span></label>
                        <select v-model="bulkLine.labor_type_id" class="form-select form-select-sm">
                            <option value="" disabled>Seleccione</option>
                            <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-1">Jornadas</label>
                        <input type="number" v-model="bulkLine.workdays" class="form-control form-control-sm text-center"
                            min="0.1" max="1" step="0.1" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Agrupación CC</label>
                        <select v-model="bulkGrouping" class="form-select form-select-sm">
                            <option value="">Sin agrupación</option>
                            <option v-for="g in props.groupings" :key="g.id" :value="g.id">{{ g.id }}-{{ g.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">
                                Centros de costo
                                <span v-if="bulkLine.cost_center_ids && bulkLine.cost_center_ids.length > 0" class="badge bg-primary ms-1" style="font-size:0.6rem; vertical-align:middle;">{{ bulkLine.cost_center_ids.length }}</span>
                            </label>
                            <button
                                v-if="bulkLine.cost_center_ids && bulkLine.cost_center_ids.length > 3"
                                type="button"
                                @click.stop="bulkExpandedCC = !bulkExpandedCC"
                                class="btn btn-link btn-sm p-0 text-muted"
                                style="font-size:0.65rem; text-decoration:none;"
                            >
                                <i class="fas" :class="bulkExpandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size:0.6rem;"></i>
                                {{ bulkExpandedCC ? 'Colapsar' : 'Ver' }}
                            </button>
                        </div>
                        <Multiselect
                            v-model="bulkLine.cost_center_ids"
                            :options="costCenters"
                            mode="tags"
                            :searchable="true"
                            :close-on-select="false"
                            placeholder="Seleccione CC..."
                            :class="['multiselect-sm', 'multiselect-tags-limited', { 'multiselect-tags-expanded': bulkExpandedCC }]"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Bono</label>
                        <select v-model="bulkLine.bonus_type_id" class="form-select form-select-sm" @change="onBulkBonusChange">
                            <option value="">Sin bono</option>
                            <option v-for="b in bonusTypes" :key="b.value" :value="b.value">{{ b.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Observaciones</label>
                        <input type="text" v-model="bulkLine.observations" class="form-control form-control-sm" maxlength="500" />
                    </div>
                </div>

                <!-- Lista de empleados elegibles -->
                <div class="border rounded" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-sm table-hover fs--1 mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width:36px" class="text-center">
                                    <input type="checkbox" class="form-check-input" :checked="bulkAllSelected" @change="toggleBulkAll" />
                                </th>
                                <th>Colaborador</th>
                                <th>RUT</th>
                                <th class="text-end">Monto calculado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="bulkEligibleEmployees.length === 0">
                                <td colspan="4" class="text-center text-muted py-2">Todos los colaboradores ya tienen tarja al día registrada.</td>
                            </tr>
                            <tr v-for="emp in bulkEligibleEmployees" :key="emp.id"
                                :class="bulkSelectedIds.includes(emp.id) ? 'table-primary bg-opacity-25' : ''"
                                style="cursor:pointer"
                                @click="bulkSelectedIds.includes(emp.id) ? bulkSelectedIds.splice(bulkSelectedIds.indexOf(emp.id), 1) : bulkSelectedIds.push(emp.id)">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" :checked="bulkSelectedIds.includes(emp.id)" @click.stop />
                                </td>
                                <td class="fw-semi-bold">{{ emp.full_name }}</td>
                                <td class="text-muted">{{ emp.rut }}</td>
                                <td class="text-end fw-semi-bold">
                                    <span v-if="bulkLine.labor_type_id">
                                        ${{ bulkRate(emp).toLocaleString('es-CL') }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">{{ bulkSelectedIds.length }} colaborador(es) seleccionado(s)</small>
                    <div class="d-flex gap-2">
                        <button type="button" @click="showBulkPanel = false" class="btn btn-falcon-default btn-sm">Cancelar</button>
                        <button type="button" @click="saveBulk" class="btn btn-primary btn-sm" :disabled="bulkSelectedIds.length === 0 || !bulkLine.labor_type_id">
                            <i class="fas fa-save me-1"></i>Guardar {{ bulkSelectedIds.length }} tarja(s)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Registro Masivo por Fechas -->
        <div v-if="showBulkByDatesPanel" class="card mb-3" style="border: 2px solid #0d6efd;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #e8f0fe;">
                <span class="fw-semi-bold fs--1" style="color:#0d47a1;"><i class="fas fa-calendar-plus me-2"></i>Registro masivo por fechas — Al día</span>
                <button type="button" @click="showBulkByDatesPanel = false" class="btn-close btn-close-sm"></button>
            </div>
            <div class="card-body py-2">
                <!-- Configuración compartida -->
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Labor <span class="text-danger">*</span></label>
                        <select v-model="bulkDateLine.labor_type_id" class="form-select form-select-sm">
                            <option value="" disabled>Seleccione</option>
                            <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-1">Jornadas</label>
                        <input type="number" v-model="bulkDateLine.workdays" class="form-control form-control-sm text-center"
                            min="0.1" max="1" step="0.1" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Agrupación CC</label>
                        <select v-model="bulkDateGrouping" class="form-select form-select-sm">
                            <option value="">Sin agrupación</option>
                            <option v-for="g in props.groupings" :key="g.id" :value="g.id">{{ g.id }}-{{ g.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0">
                            Centros de costo
                            <span v-if="bulkDateLine.cost_center_ids.length" class="badge bg-primary ms-1" style="font-size:0.6rem;">{{ bulkDateLine.cost_center_ids.length }}</span>
                        </label>
                        <Multiselect
                            v-model="bulkDateLine.cost_center_ids"
                            :options="costCenters"
                            mode="tags"
                            :searchable="true"
                            :close-on-select="false"
                            placeholder="Seleccione CC..."
                            class="multiselect-sm"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Observaciones</label>
                        <input type="text" v-model="bulkDateLine.observations" class="form-control form-control-sm" maxlength="500" />
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Selector de fechas del mes -->
                    <div class="col-md-5">
                        <label class="form-label small mb-1 fw-semibold">
                            <i class="fas fa-calendar-alt me-1 text-primary"></i>Seleccionar fechas del mes
                            <span class="badge bg-primary ms-1" style="font-size:0.65rem;">{{ bulkSelectedDates.length }} seleccionadas</span>
                        </label>
                        <div class="border rounded p-2 bg-white" style="max-height: 200px; overflow-y: auto;">
                            <div class="d-flex flex-wrap gap-1">
                                <button
                                    v-for="day in daysOfCurrentMonth"
                                    :key="day.value"
                                    type="button"
                                    @click="toggleDate(day.value)"
                                    :class="[
                                        'btn btn-sm px-2 py-1',
                                        bulkSelectedDates.includes(day.value)
                                            ? 'btn-primary'
                                            : day.isWeekend
                                                ? 'btn-light text-muted border'
                                                : 'btn-outline-secondary'
                                    ]"
                                    style="min-width: 36px; font-size: 0.72rem;"
                                    :title="day.label"
                                >
                                    {{ day.day }}
                                </button>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" @click="selectAllDates" class="btn btn-outline-primary btn-sm" style="font-size:0.72rem;">
                                <i class="fas fa-check-double me-1"></i>Todos los días hábiles
                            </button>
                            <button type="button" @click="bulkSelectedDates = []" class="btn btn-outline-secondary btn-sm" style="font-size:0.72rem;">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Selector de empleados -->
                    <div class="col-md-7">
                        <label class="form-label small mb-1 fw-semibold">
                            <i class="fas fa-users me-1 text-primary"></i>Colaboradores
                            <span class="badge bg-primary ms-1" style="font-size:0.65rem;">{{ bulkDateSelectedIds.length }} seleccionados</span>
                        </label>
                        <div class="border rounded" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-hover fs--1 mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width:36px" class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                :checked="bulkDateAllSelected"
                                                @change="toggleBulkDateAll" />
                                        </th>
                                        <th>Colaborador</th>
                                        <th class="text-end">Tarifa/día</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="props.employees.length === 0">
                                        <td colspan="3" class="text-center text-muted py-2">Sin colaboradores.</td>
                                    </tr>
                                    <tr v-for="emp in props.employees" :key="emp.id"
                                        :class="bulkDateSelectedIds.includes(emp.id) ? 'table-primary bg-opacity-25' : ''"
                                        style="cursor:pointer"
                                        @click="bulkDateSelectedIds.includes(emp.id) ? bulkDateSelectedIds.splice(bulkDateSelectedIds.indexOf(emp.id), 1) : bulkDateSelectedIds.push(emp.id)">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                :checked="bulkDateSelectedIds.includes(emp.id)" @click.stop />
                                        </td>
                                        <td class="fw-semi-bold">{{ emp.full_name }}</td>
                                        <td class="text-end text-muted">
                                            ${{ (emp.daily_rate || 0).toLocaleString('es-CL') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Se crearán <strong>{{ bulkDateSelectedIds.length * bulkSelectedDates.length }}</strong> tarjas en total.
                                Las fechas donde ya exista tarja "al día" se omitirán automáticamente.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-2">
                    <small class="text-muted">
                        {{ bulkDateSelectedIds.length }} colaborador(es) × {{ bulkSelectedDates.length }} fecha(s)
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button" @click="showBulkByDatesPanel = false" class="btn btn-falcon-default btn-sm">Cancelar</button>
                        <button type="button" @click="saveBulkByDates"
                            class="btn btn-primary btn-sm"
                            :disabled="bulkDateSelectedIds.length === 0 || bulkSelectedDates.length === 0 || !bulkDateLine.labor_type_id">
                            <i class="fas fa-save me-1"></i>
                            Guardar {{ bulkDateSelectedIds.length * bulkSelectedDates.length }} tarja(s)
                        </button>
                    </div>
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
                        <th class="text-center">Contrato</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Lineas</th>
                        <th class="text-center">Jornadas</th>
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
                            <td class="text-center">
                                <span v-if="emp.contract_id" class="badge bg-soft-primary text-primary" style="font-size:0.7rem">#{{ emp.contract_id }}</span>
                                <span v-else class="text-muted">-</span>
                            </td>
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
                                                        <div class="col-md-1" v-if="editLine.payment_type === 'dia'">
                                                            <label class="form-label small mb-0 text-warning fw-semi-bold">
                                                                <i class="fas fa-bullseye fa-xs me-1"></i>Precio Objetivo
                                                            </label>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">$</span>
                                                                <input
                                                                    type="number"
                                                                    v-model.number="editLine.target_price"
                                                                    @input="onEditTargetPriceChange"
                                                                    class="form-control form-control-sm no-spinner"
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
                                                            <label class="form-label small mb-0">
                                                                <i class="fas fa-layer-group fa-xs me-1 text-muted"></i>Agrup.
                                                            </label>
                                                            <select v-model="editLineGrouping" class="form-select form-select-sm" style="font-size:0.7rem;">
                                                                <option value="">Agrupación...</option>
                                                                <option v-for="g in groupings" :key="g.id" :value="g.id">{{ g.id }}-{{ g.name }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex align-items-center justify-content-between mb-0">
                                                                <label class="form-label small mb-0">
                                                                    C.Costo
                                                                    <span v-if="editLine.cost_center_ids && editLine.cost_center_ids.length > 0" class="badge bg-primary ms-1" style="font-size:0.6rem; vertical-align:middle;">{{ editLine.cost_center_ids.length }}</span>
                                                                </label>
                                                                <button
                                                                    v-if="editLine.cost_center_ids && editLine.cost_center_ids.length > 3"
                                                                    type="button"
                                                                    @click.stop="editLineExpandedCC = !editLineExpandedCC"
                                                                    class="btn btn-link btn-sm p-0 text-muted"
                                                                    style="font-size:0.65rem; text-decoration:none;"
                                                                >
                                                                    <i class="fas" :class="editLineExpandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size:0.6rem;"></i>
                                                                    {{ editLineExpandedCC ? 'Colapsar' : 'Ver' }}
                                                                </button>
                                                            </div>
                                                            <Multiselect v-model="editLine.cost_center_ids" :options="costCenters" mode="tags" :searchable="true" :close-on-select="false" placeholder="Seleccione" :class="['multiselect-sm', 'multiselect-tags-limited', { 'multiselect-tags-expanded': editLineExpandedCC }]" />
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
                                            <div class="col-md-1" v-if="newLine.payment_type === 'dia' && !isAbsenceSelected">
                                                <label class="form-label small mb-0 text-warning fw-semi-bold">
                                                    <i class="fas fa-bullseye fa-xs me-1"></i>Precio Objetivo
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">$</span>
                                                    <input
                                                        type="number"
                                                        v-model.number="newLine.target_price"
                                                        @input="onTargetPriceChange"
                                                        class="form-control form-control-sm no-spinner"
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
                                                <label class="form-label small mb-0">
                                                    <i class="fas fa-layer-group fa-xs me-1 text-muted"></i>Agrup.
                                                </label>
                                                <select v-model="newLineGrouping" class="form-select form-select-sm" style="font-size:0.7rem;">
                                                    <option value="">Agrupación...</option>
                                                    <option v-for="g in groupings" :key="g.id" :value="g.id">{{ g.id }}-{{ g.name }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2" v-if="!isUnpaidAbsence">
                                                <div class="d-flex align-items-center justify-content-between mb-0">
                                                    <label class="form-label small mb-0">
                                                        C.Costo
                                                        <span v-if="newLine.cost_center_ids && newLine.cost_center_ids.length > 0" class="badge bg-primary ms-1" style="font-size:0.6rem; vertical-align:middle;">{{ newLine.cost_center_ids.length }}</span>
                                                    </label>
                                                    <button
                                                        v-if="newLine.cost_center_ids && newLine.cost_center_ids.length > 3"
                                                        type="button"
                                                        @click.stop="newLineExpandedCC = !newLineExpandedCC"
                                                        class="btn btn-link btn-sm p-0 text-muted"
                                                        style="font-size:0.65rem; text-decoration:none;"
                                                    >
                                                        <i class="fas" :class="newLineExpandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size:0.6rem;"></i>
                                                        {{ newLineExpandedCC ? 'Colapsar' : 'Ver' }}
                                                    </button>
                                                </div>
                                                <Multiselect v-model="newLine.cost_center_ids" :options="costCenters" mode="tags" :searchable="true" :close-on-select="false" placeholder="Seleccione" :class="['multiselect-sm', 'multiselect-tags-limited', { 'multiselect-tags-expanded': newLineExpandedCC }]" />
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

<style scoped>
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.no-spinner {
    -moz-appearance: textfield;
}

/* Estado colapsado: el componente raíz NO crece, pero overflow visible para que el dropdown no quede clipado */
.multiselect-tags-limited {
    max-height: 36px !important;
    min-height: 26px !important;
    overflow: visible !important;
    transition: max-height 0.3s ease;
}

/* El wrapper interno tampoco crece */
.multiselect-tags-limited :deep(.multiselect-wrapper) {
    max-height: 34px !important;
    overflow: hidden !important;
    align-items: flex-start !important;
}

/* Los tags quedan en una sola fila recortada */
.multiselect-tags-limited :deep(.multiselect-tags) {
    max-height: 30px !important;
    overflow: hidden !important;
    flex-wrap: nowrap !important;
}

/* Estado expandido: permite crecer */
.multiselect-tags-expanded {
    max-height: 210px !important;
    overflow: visible !important;
}

.multiselect-tags-expanded :deep(.multiselect-wrapper) {
    max-height: 200px !important;
    overflow: visible !important;
    height: auto !important;
    align-items: flex-start !important;
}

.multiselect-tags-expanded :deep(.multiselect-tags) {
    max-height: 190px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    flex-wrap: wrap !important;
}

.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar {
    width: 4px;
}
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}
</style>
