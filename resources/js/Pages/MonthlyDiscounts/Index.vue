<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateMonthlyDiscountModal from '@/Components/MonthlyDiscounts/CreateMonthlyDiscountModal.vue';
import EditMonthlyDiscountModal from '@/Components/MonthlyDiscounts/EditMonthlyDiscountModal.vue';

const props = defineProps({
    discounts: Array,
    contracts: Array,
    discountTypes: Array,
    months: Array,
});

const title = 'Descuentos Mensuales';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const term = ref('');
const monthFilter = ref('');

const filteredRows = computed(() => {
    let rows = props.discounts ?? [];
    if (monthFilter.value) {
        rows = rows.filter(r => String(r.month_id) === String(monthFilter.value));
    }
    if (term.value) {
        const s = term.value.toLowerCase();
        rows = rows.filter(r =>
            r.employee_name?.toLowerCase().includes(s) ||
            r.discount_type_name?.toLowerCase().includes(s)
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
        title: '¿Eliminar descuento?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('monthly-discounts.delete', id), {
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
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-minus-circle me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo Descuento</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-6">
                        <input v-model="term" class="form-control form-control-sm"
                            placeholder="Buscar por colaborador o tipo de descuento..." />
                    </div>
                    <div class="col-md-3">
                        <select v-model="monthFilter" class="form-select form-select-sm">
                            <option value="">Todos los meses</option>
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Colaborador</th>
                                <th>Tipo de Descuento</th>
                                <th>Mes</th>
                                <th class="text-end">Monto</th>
                                <th>Observaciones</th>
                                <th style="width: 80px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="fw-semibold">{{ item.employee_name }}</td>
                                <td>{{ item.discount_type_name }}</td>
                                <td>{{ item.month_name }}</td>
                                <td class="text-end">{{ formatCurrency(item.amount) }}</td>
                                <td class="text-muted small">{{ item.observations || '-' }}</td>
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
                                <td colspan="6" class="text-center text-muted">No hay descuentos registrados.</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="filteredRows.length > 0">
                            <tr class="table-primary fw-semibold">
                                <td colspan="3">Total</td>
                                <td class="text-end">{{ formatCurrency(totalAmount) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <CreateMonthlyDiscountModal
            :show="showCreateModal"
            :contracts="contracts"
            :discountTypes="discountTypes"
            :months="months"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditMonthlyDiscountModal
            :show="showEditModal"
            :discount="editingItem"
            :contracts="contracts"
            :discountTypes="discountTypes"
            :months="months"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
