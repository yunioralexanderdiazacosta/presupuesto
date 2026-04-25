<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    employees: Array,  // con saldo de vacaciones ya calculado
    vacations: Array,  // historial
});

const title = 'Vacaciones';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const activeTab = ref('saldos');

// ─── Años anteriores (edición inline) ────────────────────────────────────────
const editingEntitlement = ref(null); // employee_id en edición
const entitlementValue   = ref(0);

function startEditEntitlement(emp) {
    editingEntitlement.value = emp.id;
    entitlementValue.value   = emp.anos_anteriores ?? 0;
}

function cancelEditEntitlement() {
    editingEntitlement.value = null;
}

function saveEntitlement(emp) {
    router.patch(route('vacation-entitlement.update', emp.id), {
        anos_anteriores: entitlementValue.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingEntitlement.value = null;
            Swal.fire({ icon: 'success', title: 'Actualizado', showConfirmButton: false, timer: 1000 });
        },
        onError: () => {
            Swal.fire('Error', 'No se pudo actualizar.', 'error');
        },
    });
}

// ─── Pestaña Registrar ────────────────────────────────────────────────────────
const form = useForm({
    employee_id:  '',
    contract_id:  '',
    fecha_inicio: '',
    fecha_fin:    '',
    notas:        '',
});

const selectedEmployee = computed(() =>
    props.employees?.find(e => String(e.id) === String(form.employee_id)) ?? null
);

// Auto-rellenar contract_id al seleccionar empleado
watch(() => form.employee_id, (val) => {
    const e = props.employees?.find(e => String(e.id) === String(val));
    form.contract_id = e?.contract_id ?? '';
    form.fecha_inicio = '';
    form.fecha_fin    = '';
    calculatedDays.value = null;
});

// Cálculo automático de días hábiles vía API
const calculatedDays   = ref(null);
const loadingDays      = ref(false);

async function fetchBusinessDays() {
    if (!form.fecha_inicio || !form.fecha_fin) return;
    loadingDays.value = true;
    try {
        const { data } = await axios.get(route('api.business-days'), {
            params: { start: form.fecha_inicio, end: form.fecha_fin },
        });
        calculatedDays.value = data.business_days;
    } catch (e) {
        calculatedDays.value = null;
    } finally {
        loadingDays.value = false;
    }
}

watch(() => form.fecha_inicio, fetchBusinessDays);
watch(() => form.fecha_fin,    fetchBusinessDays);

function submitVacation() {
    if (!form.employee_id || !form.fecha_inicio || !form.fecha_fin) {
        Swal.fire('Atención', 'Selecciona empleado y rango de fechas.', 'warning');
        return;
    }
    Swal.fire({
        title:              '¿Registrar período de vacaciones?',
        html:               selectedEmployee.value
            ? `<strong>${selectedEmployee.value.name}</strong><br>${form.fecha_inicio} → ${form.fecha_fin}<br><br><strong>${calculatedDays.value ?? '?'} días hábiles</strong>`
            : '',
        icon:               'question',
        showCancelButton:   true,
        confirmButtonText:  'Sí, registrar',
        cancelButtonText:   'Cancelar',
    }).then(result => {
        if (!result.isConfirmed) return;
        form.post(route('vacations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Vacaciones registradas', showConfirmButton: false, timer: 1500 });
                form.reset();
                calculatedDays.value = null;
                activeTab.value = 'historial';
            },
            onError: errors => {
                Swal.fire('Error', Object.values(errors).flat().join('<br>'), 'error');
            },
        });
    });
}

// ─── Pestaña Historial ────────────────────────────────────────────────────────
const filterEmployee = ref('');
const filteredHistory = computed(() => {
    if (!filterEmployee.value) return props.vacations ?? [];
    const q = filterEmployee.value.toLowerCase();
    return (props.vacations ?? []).filter(v =>
        v.employee.toLowerCase().includes(q) ||
        v.rut.toLowerCase().includes(q)
    );
});

function deleteVacation(id) {
    Swal.fire({
        title:             '¿Eliminar registro?',
        text:              'Esta acción no se puede deshacer.',
        icon:              'warning',
        showCancelButton:  true,
        confirmButtonColor:'#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText:  'Cancelar',
    }).then(result => {
        if (result.isConfirmed) {
            router.delete(route('vacations.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
}

async function printVacationVoucher(v) {
    const url = route('vacations.pdf', v.id);
    window.open(url, '_blank');
}

// Color de saldo (balance)
function balanceColor(balance) {
    if (balance === null || balance === undefined) return '';
    if (balance < 0)  return 'text-danger fw-bold';
    if (balance <= 5) return 'text-warning fw-semibold';
    return 'text-success fw-semibold';
}
</script>

<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <Breadcrumb :links="links" />

        <div class="card my-3">
            <!-- Header -->
            <div class="card-header" style="background: linear-gradient(135deg, #e9f7f2 0%, #f4faf8 100%); border-bottom: 2px solid #1a9e6e;">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0" style="color: #0e6655;">
                            <i class="fas fa-umbrella-beach me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="card-header border-bottom" style="background:#f4faf8; padding: 0 1rem;">
                <ul class="nav nav-tabs border-0" style="margin-bottom: -1px;">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 fs-10"
                            :class="activeTab === 'saldos' ? 'active fw-semibold' : ''"
                            :style="activeTab === 'saldos' ? 'color:#0e6655; border-bottom: 2px solid #1a9e6e; background:#fff;' : 'color:#5d6d7e;'"
                            href="#" @click.prevent="activeTab = 'saldos'">
                            <i class="fas fa-chart-bar me-1"></i>Saldos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 fs-10"
                            :class="activeTab === 'registrar' ? 'active fw-semibold' : ''"
                            :style="activeTab === 'registrar' ? 'color:#0e6655; border-bottom: 2px solid #1a9e6e; background:#fff;' : 'color:#5d6d7e;'"
                            href="#" @click.prevent="activeTab = 'registrar'">
                            <i class="fas fa-plus-circle me-1"></i>Registrar Período
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 fs-10"
                            :class="activeTab === 'historial' ? 'active fw-semibold' : ''"
                            :style="activeTab === 'historial' ? 'color:#0e6655; border-bottom: 2px solid #1a9e6e; background:#fff;' : 'color:#5d6d7e;'"
                            href="#" @click.prevent="activeTab = 'historial'">
                            <i class="fas fa-history me-1"></i>Historial
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- ── Tab Saldos ──────────────────────────────────────────── -->
                <div v-show="activeTab === 'saldos'">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Solo se muestran empleados con contrato <strong>Indefinido</strong> activo.
                        Saldo = Días ganados − Días tomados.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead>
                                <tr style="background-color: #e9f7f2; color: #0e6655;">
                                    <th>Empleado</th>
                                    <th>RUT</th>
                                    <th class="text-center">Meses trabajados</th>
                                    <th class="text-center">Años anteriores</th>
                                    <th class="text-center">Días/año</th>
                                    <th class="text-center">Días ganados</th>
                                    <th class="text-center">Días tomados</th>
                                    <th class="text-center">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="e in employees" :key="e.id">
                                    <td>{{ e.name }}</td>
                                    <td>{{ e.rut }}</td>
                                    <td class="text-center" v-tooltip="'Meses completos para cálculo legal: ' + e.months_worked">{{ e.months_worked_decimal }}</td>
                                    <!-- Años anteriores reconocidos (editable inline) -->
                                    <td class="text-center">
                                        <template v-if="editingEntitlement === e.id">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <input
                                                    v-model.number="entitlementValue"
                                                    type="number" min="0" max="50"
                                                    class="form-control form-control-sm text-center"
                                                    style="width:60px;"
                                                    @keyup.enter="saveEntitlement(e)"
                                                    @keyup.escape="cancelEditEntitlement"
                                                />
                                                <button class="btn btn-sm btn-success py-0 px-1" @click="saveEntitlement(e)" title="Guardar">
                                                    <i class="fas fa-check fa-xs"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light py-0 px-1" @click="cancelEditEntitlement" title="Cancelar">
                                                    <i class="fas fa-times fa-xs"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span
                                                class="badge bg-soft-secondary text-secondary"
                                                style="cursor:pointer;"
                                                v-tooltip="'Click para editar'"
                                                @click="startEditEntitlement(e)"
                                            >{{ e.anos_anteriores ?? 0 }} años</span>
                                        </template>
                                    </td>
                                    <td class="text-center">{{ e.rate_per_year }}</td>
                                    <td class="text-center">{{ Number(e.days_earned).toFixed(1) }}</td>
                                    <td class="text-center">{{ e.days_taken }}</td>
                                    <td class="text-center" :class="balanceColor(e.balance)">
                                        {{ Number(e.balance).toFixed(1) }}
                                    </td>
                                </tr>
                                <tr v-if="!employees || !employees.length">
                                    <td colspan="8" class="text-center text-muted py-3">Sin empleados con contrato indefinido.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Tab Registrar ──────────────────────────────────────── -->
                <div v-show="activeTab === 'registrar'">
                    <div class="row g-3" style="max-width: 600px;">

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Empleado</label>
                            <select v-model="form.employee_id" class="form-select form-select-sm">
                                <option value="" disabled>Seleccione empleado…</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">
                                    {{ e.name }} — Saldo: {{ Number(e.balance).toFixed(1) }} días
                                </option>
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Fecha inicio</label>
                            <input v-model="form.fecha_inicio" type="date" class="form-control form-control-sm" />
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Fecha fin</label>
                            <input v-model="form.fecha_fin" type="date" class="form-control form-control-sm" />
                        </div>

                        <!-- Indicador días hábiles -->
                        <div class="col-12" v-if="form.fecha_inicio && form.fecha_fin">
                            <div class="alert py-2 px-3 mb-0 d-flex align-items-center gap-2"
                                :style="'background:#e9f7f2; border:1px solid #a9dfbf; color:#0e6655; font-size:0.85rem;'">
                                <span v-if="loadingDays"><i class="fas fa-spinner fa-spin me-1"></i>Calculando días hábiles…</span>
                                <span v-else-if="calculatedDays !== null">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <strong>{{ calculatedDays }} días hábiles</strong>
                                    (excluye sábados, domingos y feriados)
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notas (opcional)</label>
                            <textarea v-model="form.notas" class="form-control form-control-sm" rows="2" placeholder="Observaciones…"></textarea>
                        </div>

                        <div class="col-12">
                            <button
                                class="btn btn-sm"
                                style="background-color: #1a9e6e; color:#fff; border-color:#1a9e6e;"
                                @click="submitVacation"
                                :disabled="form.processing"
                            >
                                <i class="fas fa-save me-1"></i>Registrar Vacaciones
                            </button>
                        </div>

                    </div>
                </div>

                <!-- ── Tab Historial ──────────────────────────────────────── -->
                <div v-show="activeTab === 'historial'">
                    <div class="mb-2" style="max-width: 300px;">
                        <input v-model="filterEmployee" type="text" class="form-control form-control-sm"
                            placeholder="Buscar por nombre o RUT…" />
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead>
                                <tr style="background-color: #e9f7f2; color: #0e6655;">
                                    <th>Empleado</th>
                                    <th>RUT</th>
                                    <th class="text-center">Inicio</th>
                                    <th class="text-center">Fin</th>
                                    <th class="text-center">Días hábiles</th>
                                    <th>Notas</th>
                                    <th>Registrado por</th>
                                    <th class="text-center">Fecha registro</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="v in filteredHistory" :key="v.id">
                                    <td>{{ v.employee }}</td>
                                    <td>{{ v.rut }}</td>
                                    <td class="text-center text-nowrap">{{ v.fecha_inicio }}</td>
                                    <td class="text-center text-nowrap">{{ v.fecha_fin }}</td>
                                    <td class="text-center fw-semibold">{{ v.dias_habiles }}</td>
                                    <td>{{ v.notas }}</td>
                                    <td>{{ v.created_by }}</td>
                                    <td class="text-center text-nowrap">{{ v.created_at }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-falcon-default" @click="printVacationVoucher(v)" title="Comprobante PDF">
                                                <i class="fas fa-file-pdf text-danger fa-xs"></i>
                                            </button>
                                            <button class="btn btn-sm btn-falcon-default" @click="deleteVacation(v.id)" title="Eliminar">
                                                <i class="fas fa-trash text-danger fa-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!filteredHistory.length">
                                    <td colspan="9" class="text-center text-muted py-3">Sin registros de vacaciones.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
