<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateContractModal from '@/Components/Contracts/CreateContractModal.vue';
import EditContractModal from '@/Components/Contracts/EditContractModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const page = usePage();

const props = defineProps({
    contracts: Array,
    employees: Array,
    companyReasons: Array,
    schedules: Array,
    contractTypes: Array,
    afps: Array,
    healthPlans: Array,
    cities: Array,
    parcels: Array,
    maritalStatuses: Array,
    banks: Array,
    paymentMethods: Array,
    accountTypes: Array,
});

const title = 'Contratos';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

const term = ref('');
const statusFilter = ref('');
const typeFilter = ref('');
const parcelFilter = ref('');
const companyReasonFilter = ref('');

const filteredRows = computed(() => {
    if (!props.contracts) return [];
    let rows = props.contracts;

    if (statusFilter.value !== '') {
        const isActive = statusFilter.value === 'active';
        rows = rows.filter(item => item.is_active === isActive);
    }

    if (parcelFilter.value) {
        rows = rows.filter(item => String(item.parcel_id) === String(parcelFilter.value));
    }

    if (companyReasonFilter.value) {
        rows = rows.filter(item => String(item.company_reason_id) === String(companyReasonFilter.value));
    }

    if (typeFilter.value) {
        rows = rows.filter(item => item.contract_type === typeFilter.value);
    }

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const empName = item.employee?.full_name?.toLowerCase() || '';
            const empRut = item.employee?.rut?.toLowerCase() || '';
            const company = item.company_reason?.name?.toLowerCase() || '';
            const position = item.position?.toLowerCase() || '';
            return empName.includes(search) || empRut.includes(search) || company.includes(search) || position.includes(search);
        });
    }

    return rows;
});

const totalActive = computed(() => {
    if (!props.contracts) return 0;
    return props.contracts.filter(c => c.is_active).length;
});

const totalInactive = computed(() => {
    if (!props.contracts) return 0;
    return props.contracts.filter(c => !c.is_active).length;
});

const totalBaseSalary = computed(() => {
    if (!props.contracts) return 0;
    return props.contracts.filter(c => c.is_active).reduce((sum, c) => sum + Number(c.base_salary || 0), 0);
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingContract = ref(null);

function openCreateModal() { showCreateModal.value = true; }
function closeCreateModal() { showCreateModal.value = false; }

function openEditModal(contract) {
    editingContract.value = contract;
    showEditModal.value = true;
}
function closeEditModal() {
    showEditModal.value = false;
    editingContract.value = null;
}

function reloadAfterSave() {
    closeCreateModal();
    closeEditModal();
    router.reload({ preserveScroll: true });
}

function deleteContract(id) {
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
            router.delete(route('contracts.delete', id), {
                onSuccess: () => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        Swal.fire({ icon: 'error', title: 'No se puede eliminar', text: flashError });
                    } else {
                        Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Contrato eliminado correctamente', timer: 1500, showConfirmButton: false });
                    }
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

function formatCurrency(val) {
    if (val === null || val === undefined) return '-';
    return '$' + Number(val).toLocaleString('es-CL');
}

const excelHeaders = [
    { label: 'Estado',          key: '__estado' },
    { label: 'Colaborador',     key: '__nombre' },
    { label: 'RUT',             key: '__rut' },
    { label: 'Empresa',         key: '__empresa' },
    { label: 'Tipo',            key: 'contract_type' },
    { label: 'Fecha Contrato',  key: 'contract_date' },
    { label: 'Fecha Término',   key: 'end_date' },
    { label: 'Cargo',           key: 'position' },
    { label: 'Sueldo Base',     key: 'base_salary',  type: 'number' },
    { label: 'Sueldo Líquido',  key: 'net_salary',   type: 'number' },
    { label: 'Horario',         key: '__horario' },
];

const excelData = computed(() =>
    filteredRows.value.map(c => ({
        __estado:  c.is_active ? 'Vigente' : 'Finalizado',
        __nombre:  c.employee?.full_name || '',
        __rut:     c.employee?.rut || '',
        __empresa: c.company_reason?.name || '',
        contract_type: c.contract_type || '',
        contract_date: c.contract_date || '',
        end_date:  c.end_date || '',
        position:  c.position || '',
        base_salary:  c.base_salary ? Number(c.base_salary) : 0,
        net_salary:   c.net_salary  ? Number(c.net_salary)  : 0,
        __horario: c.schedule?.name || '',
    }))
);
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
                            <i class="fas fa-file-contract me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                filename="contratos.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
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
                                        <h6 class="mb-0 text-muted fs-10">Total Contratos</h6>
                                        <h4 class="mb-0 fw-bold">{{ props.contracts?.length || 0 }}</h4>
                                    </div>
                                    <i class="fas fa-file-contract fa-2x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Vigentes</h6>
                                        <h4 class="mb-0 fw-bold text-success">{{ totalActive }}</h4>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Finalizados</h6>
                                        <h4 class="mb-0 fw-bold text-secondary">{{ totalInactive }}</h4>
                                    </div>
                                    <i class="fas fa-times-circle fa-2x text-secondary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="card bg-light border h-100">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-muted fs-10">Costo Bruto Mensual</h6>
                                        <h4 class="mb-0 fw-bold text-primary">{{ formatCurrency(totalBaseSalary) }}</h4>
                                    </div>
                                    <i class="fas fa-dollar-sign fa-2x text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-md-4">
                        <input v-model="term" class="form-control form-control-sm" placeholder="Buscar por nombre, RUT, cargo..." />
                    </div>
                    <div class="col-md-3">
                        <select v-model="companyReasonFilter" class="form-select form-select-sm">
                            <option value="">Todas las razones sociales</option>
                            <option v-for="c in companyReasons" :key="c.value" :value="c.value">{{ c.label.replace(/\s*\([^)]*\)/, '') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="typeFilter" class="form-select form-select-sm">
                            <option value="">Todos los tipos</option>
                            <option v-for="t in contractTypes" :key="t" :value="t">{{ t }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select v-model="statusFilter" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="active">Vigentes</option>
                            <option value="inactive">Finalizados</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">Estado</th>
                                <th>Colaborador</th>
                                <th>RUT</th>
                                <th>Empresa</th>
                                <th>Tipo</th>
                                <th>Fecha Contrato</th>
                                <th>Fecha Término</th>
                                <th>Cargo</th>
                                <th class="text-end">Sueldo Base</th>
                                <th class="text-end">Sueldo Líquido</th>
                                <th>Horario</th>
                                <th style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in filteredRows" :key="item.id">
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Vigente' : 'Finalizado' }}
                                    </span>
                                </td>
                                <td>{{ item.employee?.full_name || '-' }}</td>
                                <td class="fw-semibold">{{ item.employee?.rut || '-' }}</td>
                                <td>{{ item.company_reason?.name || '-' }}</td>
                                <td>
                                    <span class="badge" :class="{
                                        'bg-info': item.contract_type === 'Indefinido',
                                        'bg-warning text-dark': item.contract_type === 'Plazo Fijo',
                                        'bg-primary': item.contract_type === 'Faena',
                                    }">{{ item.contract_type }}</span>
                                </td>
                                <td>{{ formatDate(item.contract_date) }}</td>
                                <td>
                                    <span v-if="item.contract_type === 'Faena'">
                                        {{ item.terminations?.length ? formatDate(item.terminations[item.terminations.length-1].fecha_termino) : '-' }}
                                    </span>
                                    <span v-else>{{ formatDate(item.end_date) }}</span>
                                </td>
                                <td>{{ item.position || '-' }}</td>
                                <td class="text-end">{{ formatCurrency(item.base_salary) }}</td>
                                <td class="text-end">{{ formatCurrency(item.net_salary) }}</td>
                                <td>{{ item.schedule?.name || '-' }}</td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button @click="openEditModal(item)" class="btn btn-sm btn-falcon-default p-1" title="Editar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteContract(item.id)" class="btn btn-sm btn-falcon-default p-1" title="Eliminar" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRows.length === 0">
                                <td colspan="12" class="text-center text-muted">No hay contratos registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateContractModal
            :show="showCreateModal"
            :employees="props.employees"
            :companyReasons="props.companyReasons"
            :schedules="props.schedules"
            :contractTypes="props.contractTypes"
            :afps="props.afps"
            :healthPlans="props.healthPlans"
            :cities="props.cities"
            :parcels="props.parcels"
            :maritalStatuses="props.maritalStatuses"
            :banks="props.banks"
            :paymentMethods="props.paymentMethods"
            :accountTypes="props.accountTypes"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />

        <EditContractModal
            :show="showEditModal"
            :contract="editingContract"
            :employees="props.employees"
            :companyReasons="props.companyReasons"
            :schedules="props.schedules"
            :contractTypes="props.contractTypes"
            :afps="props.afps"
            :healthPlans="props.healthPlans"
            :cities="props.cities"
            :parcels="props.parcels"
            :maritalStatuses="props.maritalStatuses"
            :banks="props.banks"
            :paymentMethods="props.paymentMethods"
            :accountTypes="props.accountTypes"
            @close="closeEditModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
