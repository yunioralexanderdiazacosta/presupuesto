<script setup>
import { ref, computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    employees: Array,
    attendances: Object,
    laborTypes: Array,
    costCenters: Array,
    selectedDate: String,
    summary: Object,
});

const searchQuery = ref('');

const localData = reactive({});

props.employees.forEach(emp => {
    const existing = props.attendances[emp.id];
    localData[emp.id] = {
        is_present: existing ? existing.is_present : true,
        labor_type_id: existing?.estimated_labor_type_id ?? '',
        cost_center_id: existing?.estimated_cost_center_id ?? '',
    };
});

const hasExistingAttendance = computed(() => Object.keys(props.attendances).length > 0);

const globalLaborTypeId = ref('');
const globalCostCenterId = ref('');

function applyGlobalLaborType() {
    if (!globalLaborTypeId.value) return;
    props.employees.forEach(emp => {
        localData[emp.id].labor_type_id = globalLaborTypeId.value;
    });
    Swal.fire({ icon: 'success', title: 'Labor aplicada a todos', timer: 800, showConfirmButton: false });
}

function applyGlobalCostCenter() {
    if (!globalCostCenterId.value) return;
    props.employees.forEach(emp => {
        localData[emp.id].cost_center_id = globalCostCenterId.value;
    });
    Swal.fire({ icon: 'success', title: 'CC aplicado a todos', timer: 800, showConfirmButton: false });
}

const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees;
    const q = searchQuery.value.toLowerCase();
    return props.employees.filter(e =>
        e.full_name.toLowerCase().includes(q) || e.rut.toLowerCase().includes(q)
    );
});

const presentCount = computed(() => props.employees.filter(e => localData[e.id]?.is_present).length);
const absentCount = computed(() => props.employees.filter(e => !localData[e.id]?.is_present).length);

function selectAll() {
    props.employees.forEach(emp => { localData[emp.id].is_present = true; });
}

function deselectAll() {
    props.employees.forEach(emp => { localData[emp.id].is_present = false; });
}

function toggleAttendance(employeeId) {
    localData[employeeId].is_present = !localData[employeeId].is_present;
}

function saveAttendance() {
    const attendances = props.employees.map(emp => ({
        employee_id: emp.id,
        is_present: localData[emp.id].is_present,
        estimated_labor_type_id: localData[emp.id].labor_type_id || null,
        estimated_cost_center_id: localData[emp.id].cost_center_id || null,
    }));

    const form = useForm({ date: props.selectedDate, attendances });

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
                preserveScroll: true,
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
                data: { date: props.selectedDate },
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false });
                }
            });
        }
    });
}
</script>

<template>
    <div>
        <!-- Botones de acción -->
        <div class="d-flex justify-content-end gap-2 mb-3">
            <button v-if="hasExistingAttendance" class="btn btn-falcon-default btn-sm" @click="deleteAttendance">
                <span class="fas fa-trash" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Borrar Día</span>
            </button>
            <button class="btn btn-falcon-default btn-sm" @click="saveAttendance">
                <span class="fas fa-save" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Guardar</span>
            </button>
        </div>

        <!-- Filtros y atajos globales -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label small mb-1">Buscar trabajador</label>
                <input type="text" v-model="searchQuery" class="form-control form-control-sm" placeholder="Nombre o RUT..." />
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Aplicar labor a todos</label>
                <div class="input-group input-group-sm">
                    <select v-model="globalLaborTypeId" class="form-select form-select-sm">
                        <option :value="''">Seleccione...</option>
                        <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" @click="applyGlobalLaborType" :disabled="!globalLaborTypeId">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Aplicar CC a todos</label>
                <div class="input-group input-group-sm">
                    <select v-model="globalCostCenterId" class="form-select form-select-sm">
                        <option :value="''">Seleccione...</option>
                        <option v-for="cc in costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" @click="applyGlobalCostCenter" :disabled="!globalCostCenterId">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
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
                                <i class="fas fa-check-double me-1"></i>Todos P
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deselectAll">
                                <i class="fas fa-times me-1"></i>Todos A
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
                        <th style="width: 50px;" class="text-center">P/A</th>
                        <th>Nombre</th>
                        <th>RUT</th>
                        <th style="min-width: 180px;">Labor Estimada</th>
                        <th style="min-width: 180px;">CC Estimado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="emp in filteredEmployees" :key="emp.id"
                        :class="{ 'table-danger bg-opacity-25': !localData[emp.id]?.is_present }">
                        <td class="text-center">
                            <div class="form-check d-flex justify-content-center mb-0">
                                <input type="checkbox" class="form-check-input"
                                    :checked="localData[emp.id]?.is_present"
                                    @change="toggleAttendance(emp.id)" />
                            </div>
                        </td>
                        <td class="fw-semi-bold" style="cursor:pointer;" @click="toggleAttendance(emp.id)">
                            {{ emp.full_name }}
                        </td>
                        <td>{{ emp.rut }}</td>
                        <td>
                            <select v-model="localData[emp.id].labor_type_id" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                                <option :value="''">—</option>
                                <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                            </select>
                        </td>
                        <td>
                            <select v-model="localData[emp.id].cost_center_id" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                                <option :value="''">—</option>
                                <option v-for="cc in costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr v-if="filteredEmployees.length === 0">
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="fas fa-search me-2"></i>No se encontraron trabajadores
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="hasExistingAttendance" class="alert alert-info mt-3 mb-0 py-2 small">
            <i class="fas fa-info-circle me-1"></i>
            Asistencia ya registrada para esta fecha. Al guardar se actualizarán los registros.
        </div>
    </div>
</template>
