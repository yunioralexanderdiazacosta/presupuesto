<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateLaborTypeModal from '@/Components/LaborTypes/CreateLaborTypeModal.vue';
import EditLaborTypeModal from '@/Components/LaborTypes/EditLaborTypeModal.vue';

const props = defineProps({
    laborTypes: Array,
    level3s: Array,
    units: Array,
});

const title = 'Tipos de Labor';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');

const filteredRows = computed(() => {
    if (!props.laborTypes) return [];
    let rows = props.laborTypes;

    if (statusFilter.value !== '') {
        const isActive = statusFilter.value === 'active';
        rows = rows.filter(item => item.is_active === isActive);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const name = item.name?.toLowerCase() || '';
            const level3 = item.level3?.name?.toLowerCase() || '';
            const unit = item.unit?.name?.toLowerCase() || '';
            return name.includes(search) || level3.includes(search) || unit.includes(search);
        });
    }

    return rows;
});

const totalActive = computed(() => {
    if (!props.laborTypes) return 0;
    return props.laborTypes.filter(e => e.is_active).length;
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingItem = ref(null);

function openCreateModal() { showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; }

function openEditModal(item) {
    editingItem.value = item;
    showEditModal.value = true;
}
function closeEditModal() {
    showEditModal.value = false;
    editingItem.value = null;
}

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    router.reload({ preserveScroll: true });
}

function deleteItem(id) {
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
            router.delete(route('labor-types.delete', id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Labor eliminada correctamente', timer: 1500, showConfirmButton: false });
                },
                onError: () => {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el registro' });
                }
            });
        }
    });
}

function formatCurrency(value) {
    if (!value && value !== 0) return '-';
    return '$' + Number(value).toLocaleString('es-CL');
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
                            <i class="fas fa-hard-hat me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nueva Labor</span>
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
                                        <h6 class="mb-0 text-muted fs-10">Total Labores</h6>
                                        <h4 class="mb-0 fw-bold">{{ props.laborTypes?.length || 0 }}</h4>
                                    </div>
                                    <i class="fas fa-hard-hat fa-2x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Activas</h6>
                                        <h4 class="mb-0 fw-bold text-success">{{ totalActive }}</h4>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-8">
                        <input v-model="term" class="form-control form-control-sm" placeholder="Buscar por nombre, subfamilia, unidad..." />
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
                                <th>Nombre</th>
                                <th>Subfamilia (Level3)</th>
                                <th>Unidad</th>
                                <th class="text-end">Tarifa Ref.</th>
                                <th class="text-end">Bono Ref.</th>
                                <th class="text-center">Estado</th>
                                <th style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="fw-semibold">{{ item.name }}</td>
                                <td>{{ item.level3?.name || '-' }}</td>
                                <td>{{ item.unit?.name || '-' }}</td>
                                <td class="text-end">{{ formatCurrency(item.default_rate) }}</td>
                                <td class="text-end">{{ formatCurrency(item.default_bonus) }}</td>
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
                                        <button @click="deleteItem(item.id)" class="btn btn-sm btn-falcon-default p-1" title="Eliminar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="7" class="text-center text-muted">No hay labores registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateLaborTypeModal
            :show="showCreateModal"
            :level3s="props.level3s"
            :units="props.units"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditLaborTypeModal
            :show="showEditModal"
            :laborType="editingItem"
            :level3s="props.level3s"
            :units="props.units"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
