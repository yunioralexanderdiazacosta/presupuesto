<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import CreateMonthlyBonusModal from '@/Components/MonthlyBonuses/CreateMonthlyBonusModal.vue';
import EditMonthlyBonusModal from '@/Components/MonthlyBonuses/EditMonthlyBonusModal.vue';

const props = defineProps({
    bonuses: Array,
    contracts: Array,
    bonusTypes: Array,
    months: Array,
    costCenters: Array,
    groupings: Array,
    laborTypes: Array,
    level3s: Array,
});

const term = ref('');
const monthFilter = ref('');

const filteredRows = computed(() => {
    let rows = props.bonuses ?? [];
    if (monthFilter.value) {
        rows = rows.filter(r => String(r.month_id) === String(monthFilter.value));
    }
    if (term.value) {
        const s = term.value.toLowerCase();
        rows = rows.filter(r =>
            r.employee_name?.toLowerCase().includes(s) ||
            r.bonus_type_name?.toLowerCase().includes(s)
        );
    }
    return rows;
});

const totalAmount = computed(() =>
    filteredRows.value.reduce((sum, r) => sum + (r.amount || 0), 0)
);

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
        title: '¿Eliminar bono?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('monthly-bonuses.delete', id), {
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false }),
                onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el registro.' }),
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
    <div>
        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                <input v-model="term" class="form-control form-control-sm" style="width:250px;"
                    placeholder="Buscar por colaborador o tipo..." />
                <select v-model="monthFilter" class="form-select form-select-sm" style="width:160px;">
                    <option value="">Todos los meses</option>
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
            </div>
            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Nuevo Bono</span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead style="background-color: #f5f0e8; color: #7a6a3e;">
                    <tr>
                        <th>Colaborador</th>
                        <th>Tipo de Bono</th>
                        <th>Mes</th>
                        <th>Centro de Costo</th>
                        <th>Labor</th>
                        <th class="text-end">Monto</th>
                        <th>Observaciones</th>
                        <th>Ingresado por</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filteredRows" :key="item.id">
                        <td class="fw-semibold">{{ item.employee_name }}</td>
                        <td>{{ item.bonus_type_name }}</td>
                        <td>{{ item.month_name }}</td>
                        <td>
                            <template v-if="item.cost_center_names">
                                <span v-for="(cc, idx) in item.cost_center_names.split(', ').slice(0, 3)" :key="idx"
                                    class="badge bg-light text-dark border me-1" style="font-size:0.7rem;">
                                    {{ cc }}
                                </span>
                                <span v-if="item.cost_center_names.split(', ').length > 3"
                                    class="badge bg-secondary text-white"
                                    style="font-size:0.7rem; cursor:default;"
                                    v-tooltip="item.cost_center_names.split(', ').slice(3).join('\n')">
                                    +{{ item.cost_center_names.split(', ').length - 3 }}
                                </span>
                            </template>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td>{{ item.labor_type_name }}</td>
                        <td class="text-end">{{ formatCurrency(item.amount) }}</td>
                        <td class="text-muted small">{{ item.observations || '-' }}</td>
                        <td class="text-muted small" v-tooltip="item.created_at">{{ item.created_by }}</td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <button @click="openEditModal(item)"
                                    class="btn btn-sm btn-falcon-default p-1"
                                    title="Editar"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="deleteItem(item.id)"
                                    class="btn btn-sm btn-falcon-default p-1"
                                    title="Eliminar"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredRows.length === 0">
                        <td colspan="9" class="text-center text-muted">No hay bonos registrados.</td>
                    </tr>
                </tbody>
                <tfoot v-if="filteredRows.length > 0">
                    <tr style="background-color: #f5f0e8; color: #7a6a3e;" class="fw-semibold">
                        <td colspan="5">Total</td>
                        <td class="text-end">{{ formatCurrency(totalAmount) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <CreateMonthlyBonusModal
            :show="showCreateModal"
            :contracts="contracts"
            :bonusTypes="bonusTypes"
            :months="months"
            :costCenters="costCenters"
            :groupings="groupings"
            :laborTypes="laborTypes"
            :level3s="level3s"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />
        <EditMonthlyBonusModal
            :show="showEditModal"
            :bonus="editingItem"
            :contracts="contracts"
            :bonusTypes="bonusTypes"
            :months="months"
            :costCenters="costCenters"
            :groupings="groupings"
            :laborTypes="laborTypes"
            :level3s="level3s"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </div>
</template>
