<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    employees: Array,
    attendances: Object,
    laborTypes: Array,
    costCenters: Array,
    selectedDate: String,
    summary: Object,
});

const dateFilter = ref(props.selectedDate);
const searchQuery = ref('');
const estimatedLaborTypeId = ref('');
const estimatedCostCenterId = ref('');

// Estado local de asistencia: { employee_id: boolean }
const localAttendance = ref({});

// Inicializar estado local desde datos del servidor
props.employees.forEach(emp => {
    const existing = props.attendances[emp.id];
    // Si ya tiene registro, usar ese valor; si no, default presente (true)
    localAttendance.value[emp.id] = existing ? existing.is_present : true;
});

// Saber si ya existe asistencia guardada para esta fecha
const hasExistingAttendance = computed(() => Object.keys(props.attendances).length > 0);

// Empleados filtrados por búsqueda
const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees;
    const q = searchQuery.value.toLowerCase();
    return props.employees.filter(e =>
        e.full_name.toLowerCase().includes(q) || e.rut.toLowerCase().includes(q)
    );
});

// Contadores en tiempo real
const presentCount = computed(() => {
    return props.employees.filter(e => localAttendance.value[e.id] === true).length;
});
const absentCount = computed(() => {
    return props.employees.filter(e => localAttendance.value[e.id] === false).length;
});

function changeDate() {
    router.get(route('daily-attendances.index'), { date: dateFilter.value }, { preserveState: false });
}

function selectAll() {
    props.employees.forEach(emp => {
        localAttendance.value[emp.id] = true;
    });
}

function deselectAll() {
    props.employees.forEach(emp => {
        localAttendance.value[emp.id] = false;
    });
}

function toggleAttendance(employeeId) {
    localAttendance.value[employeeId] = !localAttendance.value[employeeId];
}

function saveAttendance() {
    const attendances = props.employees.map(emp => ({
        employee_id: emp.id,
        is_present: localAttendance.value[emp.id],
    }));

    const form = useForm({
        date: dateFilter.value,
        estimated_labor_type_id: estimatedLaborTypeId.value || null,
        estimated_cost_center_id: estimatedCostCenterId.value || null,
        attendances: attendances,
    });

    Swal.fire({
        title: '¿Guardar asistencia?',
        html: `<b>${presentCount.value}</b> presentes, <b>${absentCount.value}</b> ausentes`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('daily-attendances.store'), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Asistencia guardada', timer: 1200, showConfirmButton: false });
                },
                onError: () => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la asistencia.' });
                }
            });
        }
    });
}

function deleteAttendance() {
    Swal.fire({
        title: '¿Eliminar asistencia del día?',
        text: 'Se borrarán todos los registros de asistencia de esta fecha.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('daily-attendances.delete'), {
                data: { date: dateFilter.value },
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false });
                }
            });
        }
    });
}
</script>

<template>
    <AppLayout title="Asistencia Diaria">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-clipboard-check me-2"></i>Asistencia Diaria
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button v-if="hasExistingAttendance" class="btn btn-falcon-default btn-sm" @click="deleteAttendance">
                                <span class="fas fa-trash" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Borrar Día</span>
                            </button>
                            <button class="btn btn-falcon-default btn-sm" @click="saveAttendance">
                                <span class="fas fa-save" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Guardar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Fecha</label>
                        <input type="date" v-model="dateFilter" @change="changeDate" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Labor Estimada</label>
                        <select v-model="estimatedLaborTypeId" class="form-select form-select-sm">
                            <option :value="''">Sin especificar</option>
                            <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">CC Estimado</label>
                        <select v-model="estimatedCostCenterId" class="form-select form-select-sm">
                            <option :value="''">Sin especificar</option>
                            <option v-for="cc in costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Buscar trabajador</label>
                        <input type="text" v-model="searchQuery" class="form-control form-control-sm" placeholder="Nombre o RUT..." />
                    </div>
                </div>

                <!-- Resumen -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-primary">{{ employees.length }}</div>
                                <div class="small text-muted">Total Trabajadores</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-success">{{ presentCount }}</div>
                                <div class="small text-muted">Presentes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-danger">{{ absentCount }}</div>
                                <div class="small text-muted">Ausentes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-success" @click="selectAll">
                                        <i class="fas fa-check-double me-1"></i>Todos presentes
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deselectAll">
                                        <i class="fas fa-times me-1"></i>Todos ausentes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de empleados -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm fs-10 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th style="width: 50px;" class="text-center">Presente</th>
                                <th>Nombre</th>
                                <th>RUT</th>
                                <th>Cargo</th>
                                <th class="text-end">Sueldo Base</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="emp in filteredEmployees" :key="emp.id"
                                :class="{ 'table-danger bg-opacity-25': !localAttendance[emp.id] }"
                                @click="toggleAttendance(emp.id)"
                                style="cursor: pointer;">
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input type="checkbox" class="form-check-input"
                                            :checked="localAttendance[emp.id]"
                                            @click.stop="toggleAttendance(emp.id)" />
                                    </div>
                                </td>
                                <td class="fw-semi-bold">{{ emp.full_name }}</td>
                                <td>{{ emp.rut }}</td>
                                <td>{{ emp.position }}</td>
                                <td class="text-end">${{ (emp.base_salary || 0).toLocaleString('es-CL') }}</td>
                            </tr>
                            <tr v-if="filteredEmployees.length === 0">
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-search me-2"></i>No se encontraron trabajadores
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Info si ya está guardada -->
                <div v-if="hasExistingAttendance" class="alert alert-info mt-3 mb-0 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Ya existe asistencia registrada para esta fecha. Al guardar se actualizarán los registros existentes.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
