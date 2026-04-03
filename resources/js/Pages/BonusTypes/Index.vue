<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateBonusTypeModal from '@/Components/BonusTypes/CreateBonusTypeModal.vue';
import EditBonusTypeModal from '@/Components/BonusTypes/EditBonusTypeModal.vue';

const props = defineProps({
    bonusTypes: Array,
});

const title = 'Tipos de Bono';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');

const filteredRows = computed(() => {
    if (!props.bonusTypes) return [];
    let rows = props.bonusTypes;

    if (statusFilter.value !== '') {
        const isActive = statusFilter.value === 'active';
        rows = rows.filter(item => item.is_active === isActive);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => item.name?.toLowerCase().includes(search));
    }

    return rows;
});

const totalActive = computed(() => {
    if (!props.bonusTypes) return 0;
    return props.bonusTypes.filter(e => e.is_active).length;
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
            router.delete(route('bonus-types.delete', id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Tipo de bono eliminado correctamente', timer: 1500, showConfirmButton: false });
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
                            <i class="fas fa-gift me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo Bono</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-8">
                        <input v-model="term" class="form-control form-control-sm" placeholder="Buscar por nombre..." />
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
                                <th class="text-end">Monto Referencia</th>
                                <th class="text-center">Estado</th>
                                <th style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="fw-semibold">{{ item.name }}</td>
                                <td class="text-end">{{ formatCurrency(item.default_amount) }}</td>
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
                                <td colspan="4" class="text-center text-muted">No hay tipos de bono registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateBonusTypeModal
            :show="showCreateModal"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditBonusTypeModal
            :show="showEditModal"
            :bonusType="editingItem"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
