<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import CreateMonthlyBonusTypeModal from '@/Components/MonthlyBonusTypes/CreateMonthlyBonusTypeModal.vue';
import EditMonthlyBonusTypeModal from '@/Components/MonthlyBonusTypes/EditMonthlyBonusTypeModal.vue';

const props = defineProps({ bonusTypes: Array });

const term = ref('');
const statusFilter = ref('');

const filteredRows = computed(() => {
    let rows = props.bonusTypes ?? [];
    if (statusFilter.value !== '') {
        const isActive = statusFilter.value === 'active';
        rows = rows.filter(r => r.is_active === isActive);
    }
    if (term.value) {
        const s = term.value.toLowerCase();
        rows = rows.filter(r => r.name?.toLowerCase().includes(s));
    }
    return rows;
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingItem = ref(null);

function openCreateModal() { showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; }
function openEditModal(item) { editingItem.value = item; showEditModal.value = true; }
function closeEditModal() { showEditModal.value = false; editingItem.value = null; }

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    router.reload({ preserveScroll: true });
}

function deleteItem(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('monthly-bonus-types.delete', id), {
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false }),
                onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar.' }),
            });
        }
    });
}
</script>

<template>
    <div>
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                <input v-model="term" class="form-control form-control-sm" style="width:250px;"
                    placeholder="Buscar por nombre..." />
                <select v-model="statusFilter" class="form-select form-select-sm" style="width:140px;">
                    <option value="">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Nuevo Tipo</span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Estado</th>
                        <th style="width: 90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filteredRows" :key="item.id">
                        <td class="fw-semibold">{{ item.name }}</td>
                        <td class="text-center">
                            <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                {{ item.is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <button @click="openEditModal(item)"
                                    class="btn btn-sm btn-falcon-default p-1"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deleteItem(item.id)"
                                    class="btn btn-sm btn-falcon-default p-1"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredRows.length === 0">
                        <td colspan="3" class="text-center text-muted">No hay tipos de bono registrados.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <CreateMonthlyBonusTypeModal :show="showCreateModal" @close="closeCreateModal" @saved="reloadAfterSave" />
        <EditMonthlyBonusTypeModal :show="showEditModal" :bonusType="editingItem" @close="closeEditModal" @saved="reloadAfterSave" />
    </div>
</template>
