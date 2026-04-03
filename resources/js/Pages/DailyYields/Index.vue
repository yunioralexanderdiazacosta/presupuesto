<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import CreateDailyYieldModal from '@/Components/DailyYields/CreateDailyYieldModal.vue';
import EditDailyYieldModal from '@/Components/DailyYields/EditDailyYieldModal.vue';

const props = defineProps({
    yields: Array,
    presentEmployees: Array,
    laborTypes: Array,
    bonusTypes: Array,
    costCenters: Array,
    selectedDate: String,
    hasAttendance: Boolean,
    summary: Object,
});

const dateFilter = ref(props.selectedDate);
const searchQuery = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingYield = ref(null);

// Filtrar tarjas por búsqueda
const filteredYields = computed(() => {
    if (!searchQuery.value) return props.yields;
    const q = searchQuery.value.toLowerCase();
    return props.yields.filter(y =>
        y.employee?.full_name?.toLowerCase().includes(q) ||
        y.labor_type?.name?.toLowerCase().includes(q) ||
        y.cost_center?.name?.toLowerCase().includes(q)
    );
});

// Agrupar tarjas por empleado
const yieldsByEmployee = computed(() => {
    const grouped = {};
    filteredYields.value.forEach(y => {
        const empId = y.employee_id;
        if (!grouped[empId]) {
            grouped[empId] = {
                employee: y.employee,
                yields: [],
                totalAmount: 0,
                totalBonus: 0,
                totalHours: 0,
            };
        }
        grouped[empId].yields.push(y);
        grouped[empId].totalAmount += y.amount;
        grouped[empId].totalBonus += y.bonus_amount;
        grouped[empId].totalHours += parseFloat(y.hours);
    });
    return Object.values(grouped);
});

function changeDate() {
    router.get(route('daily-yields.index'), { date: dateFilter.value }, { preserveState: false });
}

function openCreate() {
    if (!props.hasAttendance) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin asistencia',
            text: 'Debe registrar la asistencia del día antes de ingresar tarjas.',
            confirmButtonText: 'Ir a Asistencia',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.get(route('daily-attendances.index'), { date: dateFilter.value });
            }
        });
        return;
    }
    if (props.presentEmployees.length === 0) {
        Swal.fire({ icon: 'info', title: 'Sin presentes', text: 'No hay trabajadores marcados como presentes para esta fecha.' });
        return;
    }
    showCreateModal.value = true;
}

function openEdit(yieldItem) {
    editingYield.value = yieldItem;
    showEditModal.value = true;
}

function deleteYield(yieldItem) {
    Swal.fire({
        title: '¿Eliminar tarja?',
        text: `${yieldItem.employee?.full_name} — ${yieldItem.labor_type?.name}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('daily-yields.delete', yieldItem.id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminada', timer: 1000, showConfirmButton: false });
                }
            });
        }
    });
}
</script>

<template>
    <AppLayout title="Tarjas Diarias">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-clipboard-list me-2"></i>Tarjas Diarias
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCreate">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nueva Tarja</span>
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
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Buscar</label>
                        <input type="text" v-model="searchQuery" class="form-control form-control-sm" placeholder="Nombre, labor o centro de costo..." />
                    </div>
                </div>

                <!-- Alerta si no hay asistencia -->
                <div v-if="!hasAttendance" class="alert alert-warning py-2 small">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    No hay asistencia registrada para esta fecha.
                    <a href="#" @click.prevent="() => router.get(route('daily-attendances.index'), { date: dateFilter })" class="alert-link">
                        Registrar asistencia
                    </a>
                </div>

                <!-- Resumen -->
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <div class="card bg-primary bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-primary">{{ summary.totalPresent }}</div>
                                <div class="small text-muted">Presentes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-success bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-success">{{ summary.employeesWithYields }}</div>
                                <div class="small text-muted">Con Tarja</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-info">${{ (summary.totalAmount || 0).toLocaleString('es-CL') }}</div>
                                <div class="small text-muted">Total Tratos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning bg-opacity-10 border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold text-warning">${{ (summary.totalBonus || 0).toLocaleString('es-CL') }}</div>
                                <div class="small text-muted">Total Bonos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2 text-center">
                                <div class="fs-8 fw-bold">{{ (summary.totalHours || 0).toLocaleString('es-CL') }}</div>
                                <div class="small text-muted">Total Horas</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla agrupada por empleado -->
                <div v-for="group in yieldsByEmployee" :key="group.employee?.id" class="card mb-2">
                    <div class="card-header py-2 bg-200">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-1"></i>
                                <strong>{{ group.employee?.full_name }}</strong>
                                <span class="text-muted ms-2 small">{{ group.yields.length }} línea(s)</span>
                            </div>
                            <div class="small">
                                <span class="badge bg-primary me-1">${{ group.totalAmount.toLocaleString('es-CL') }}</span>
                                <span v-if="group.totalBonus > 0" class="badge bg-warning me-1">Bono: ${{ group.totalBonus.toLocaleString('es-CL') }}</span>
                                <span class="badge bg-secondary">{{ group.totalHours }}h</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm fs-10 mb-0">
                            <thead>
                                <tr>
                                    <th>Labor</th>
                                    <th class="text-end">Tarifa</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-end">Horas</th>
                                    <th>Bono</th>
                                    <th>Centro Costo</th>
                                    <th class="text-center" style="width: 80px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="y in group.yields" :key="y.id">
                                    <td>{{ y.labor_type?.name }}</td>
                                    <td class="text-end">${{ (y.rate || 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ y.quantity }}</td>
                                    <td class="text-end fw-semi-bold">${{ (y.amount || 0).toLocaleString('es-CL') }}</td>
                                    <td class="text-end">{{ y.hours }}h</td>
                                    <td>
                                        <span v-if="y.bonus_type">{{ y.bonus_type.name }} (${{ (y.bonus_amount || 0).toLocaleString('es-CL') }})</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>{{ y.cost_center?.name }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-link text-primary p-0 me-2" @click="openEdit(y)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-link text-danger p-0" @click="deleteYield(y)" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sin tarjas -->
                <div v-if="yields.length === 0" class="text-center text-muted py-4">
                    <i class="fas fa-clipboard fa-2x mb-2"></i>
                    <p>No hay tarjas registradas para esta fecha.</p>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateDailyYieldModal
            :show="showCreateModal"
            :employees="presentEmployees"
            :laborTypes="laborTypes"
            :bonusTypes="bonusTypes"
            :costCenters="costCenters"
            :date="dateFilter"
            @close="showCreateModal = false"
            @saved="showCreateModal = false"
        />

        <EditDailyYieldModal
            :show="showEditModal"
            :dailyYield="editingYield"
            :laborTypes="laborTypes"
            :bonusTypes="bonusTypes"
            :costCenters="costCenters"
            @close="showEditModal = false; editingYield = null"
            @saved="showEditModal = false; editingYield = null"
        />
    </AppLayout>
</template>
