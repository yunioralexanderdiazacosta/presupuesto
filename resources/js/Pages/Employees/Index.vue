<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateEmployeeModal from '@/Components/Employees/CreateEmployeeModal.vue';
import EditEmployeeModal from '@/Components/Employees/EditEmployeeModal.vue';

const props = defineProps({
    employees: Array,
    nationalities: Array,
});

const title = 'Colaboradores';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');

const filteredRows = computed(() => {
    if (!props.employees) return [];
    let rows = props.employees;

    if (statusFilter.value !== '') {
        const isActive = statusFilter.value === 'active';
        rows = rows.filter(item => item.is_active === isActive);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const fullName = item.full_name?.toLowerCase() || '';
            const rut = item.rut?.toLowerCase() || '';
            const nationality = item.nationality?.toLowerCase() || '';
            return fullName.includes(search) || rut.includes(search) || nationality.includes(search);
        });
    }

    return rows;
});

const totalActive = computed(() => {
    if (!props.employees) return 0;
    return props.employees.filter(e => e.is_active).length;
});

const totalInactive = computed(() => {
    if (!props.employees) return 0;
    return props.employees.filter(e => !e.is_active).length;
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingEmployee = ref(null);

function openCreateModal() { showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; }

function openEditModal(employee) {
    editingEmployee.value = employee;
    showEditModal.value = true;
}
function closeEditModal() {
    showEditModal.value = false;
    editingEmployee.value = null;
}

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    router.reload({ preserveScroll: true });
}

function deleteEmployee(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('employees.delete', id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Colaborador eliminado correctamente', timer: 1500, showConfirmButton: false });
                },
                onError: () => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el registro' });
                }
            });
        }
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-CL');
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-users me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Resumen -->
                <div class="row mb-3">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Total Colaboradores</h6>
                                        <h4 class="mb-0 fw-bold">{{ props.employees?.length || 0 }}</h4>
                                    </div>
                                    <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Activos</h6>
                                        <h4 class="mb-0 fw-bold text-success">{{ totalActive }}</h4>
                                    </div>
                                    <i class="fas fa-user-check fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Inactivos</h6>
                                        <h4 class="mb-0 fw-bold text-danger">{{ totalInactive }}</h4>
                                    </div>
                                    <i class="fas fa-user-times fa-2x text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-8">
                        <input v-model="term" class="form-control form-control-sm" placeholder="Buscar por nombre, RUT, nacionalidad..." />
                    </div>
                    <div class="col-md-4">
                        <select v-model="statusFilter" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>RUT</th>
                                <th>Nombre</th>
                                <th>Segundo Nombre</th>
                                <th>Ap. Paterno</th>
                                <th>Ap. Materno</th>
                                <th>Fecha Nac.</th>
                                <th>Nacionalidad</th>
                                <th class="text-center">Estado</th>
                                <th style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="fw-semibold">{{ item.rut }}</td>
                                <td>{{ item.first_name }}</td>
                                <td>{{ item.second_name || '-' }}</td>
                                <td>{{ item.paternal_surname }}</td>
                                <td>{{ item.maternal_surname || '-' }}</td>
                                <td>{{ formatDate(item.birth_date) }}</td>
                                <td>{{ item.nationality || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button @click="openEditModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Editar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteEmployee(item.id)" class="btn btn-sm btn-falcon-default p-1" title="Eliminar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="9" class="text-center text-muted">No hay colaboradores registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateEmployeeModal
            :show="showCreateModal"
            :nationalities="props.nationalities"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditEmployeeModal
            :show="showEditModal"
            :employee="editingEmployee"
            :nationalities="props.nationalities"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
