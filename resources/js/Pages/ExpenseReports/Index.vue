<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    reports: Array,
    suppliers: Array,
    products: Array,
});

// ─── Filtro de búsqueda ─────────────────────
const search = ref('');
const statusFilter = ref('');

const filteredReports = computed(() => {
    return props.reports.filter(r => {
        const matchSearch = !search.value || 
            r.number.toLowerCase().includes(search.value.toLowerCase()) ||
            r.user_name.toLowerCase().includes(search.value.toLowerCase()) ||
            (r.description && r.description.toLowerCase().includes(search.value.toLowerCase()));
        const matchStatus = !statusFilter.value || r.status === statusFilter.value;
        return matchSearch && matchStatus;
    });
});

// ─── Crear rendición ────────────────────────
const createForm = useForm({
    description: '',
});

const openCreateModal = () => {
    createForm.reset();
    $('#createExpenseReportModal').modal('show');
};

const submitCreate = () => {
    createForm.post(route('expense-reports.store'), {
        onSuccess: () => {
            $('#createExpenseReportModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Rendición creada',
                showConfirmButton: false,
                timer: 1500,
            });
        },
        onError: () => {
            Swal.fire('Error', 'No se pudo crear la rendición', 'error');
        },
    });
};

// ─── Eliminar rendición ─────────────────────
const deleteReport = (report) => {
    Swal.fire({
        title: `¿Eliminar ${report.number}?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('expense-reports.delete', report.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminada',
                        showConfirmButton: false,
                        timer: 1500,
                    });
                },
            });
        }
    });
};

// ─── Helpers ────────────────────────────────
const formatCurrency = (value) => {
    return '$ ' + Math.round(value).toLocaleString('es-CL');
};

const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'borrador', label: 'Borrador' },
    { value: 'enviada', label: 'Enviada' },
    { value: 'aprobada', label: 'Aprobada' },
    { value: 'pagada', label: 'Pagada' },
    { value: 'rechazada', label: 'Rechazada' },
];
</script>

<template>
    <AppLayout title="Rendiciones de Gastos">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-receipt me-2"></i>Rendiciones de Gastos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <a :href="route('expense-reports.export')" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Excel</span>
                            </a>
                            <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nueva Rendición</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-12 col-md-6">
                        <input 
                            type="text" 
                            v-model="search" 
                            class="form-control form-control-sm" 
                            placeholder="Buscar por número, usuario o descripción..."
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <select v-model="statusFilter" class="form-select form-select-sm">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 text-end text-muted small pt-1">
                        {{ filteredReports.length }} rendición(es)
                    </div>
                </div>

                <!-- Tabla desktop -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-sm table-hover fs-10 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th>Nº</th>
                                <th>Fecha</th>
                                <th>Rendidor</th>
                                <th>Descripción</th>
                                <th class="text-center">Docs</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in filteredReports" :key="report.id">
                                <td>
                                    <Link :href="route('expense-reports.show', report.id)" class="fw-semi-bold text-primary">
                                        {{ report.number }}
                                    </Link>
                                </td>
                                <td>{{ report.created_at }}</td>
                                <td>{{ report.user_name }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ report.description || '—' }}</td>
                                <td class="text-center">{{ report.items_count }}</td>
                                <td class="text-end">{{ formatCurrency(report.total_amount) }}</td>
                                <td class="text-center">
                                    <span :class="'badge bg-' + report.status_color">
                                        {{ report.status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <Link :href="route('expense-reports.show', report.id)" class="btn btn-sm btn-falcon-default me-1" v-tooltip="'Ver detalle'">
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                    <button 
                                        v-if="report.status === 'borrador'"
                                        class="btn btn-sm btn-falcon-default" 
                                        @click="deleteReport(report)"
                                        v-tooltip="'Eliminar'"
                                    >
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredReports.length === 0">
                                <td colspan="8" class="text-center text-muted py-4">
                                    No hay rendiciones registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cards mobile -->
                <div class="d-md-none">
                    <div v-for="report in filteredReports" :key="report.id" class="card mb-2 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <Link :href="route('expense-reports.show', report.id)" class="fw-bold text-primary">
                                        {{ report.number }}
                                    </Link>
                                    <div class="text-muted small">{{ report.created_at }} · {{ report.user_name }}</div>
                                </div>
                                <span :class="'badge bg-' + report.status_color">
                                    {{ report.status_label }}
                                </span>
                            </div>
                            <div v-if="report.description" class="small mb-2">{{ report.description }}</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">{{ report.items_count }} doc(s)</span>
                                <span class="fw-bold">{{ formatCurrency(report.total_amount) }}</span>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <Link :href="route('expense-reports.show', report.id)" class="btn btn-sm btn-falcon-default flex-fill">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </Link>
                                <button 
                                    v-if="report.status === 'borrador'"
                                    class="btn btn-sm btn-falcon-default" 
                                    @click="deleteReport(report)"
                                >
                                    <i class="fas fa-trash-alt text-danger"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="filteredReports.length === 0" class="text-center text-muted py-4">
                        No hay rendiciones registradas
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear Rendición -->
        <div class="modal fade" id="createExpenseReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2 border-bottom">
                        <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                            <i class="fas fa-plus-circle text-primary"></i>Nueva Rendición
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitCreate">
                            <div class="mb-3">
                                <label class="form-label small">Descripción (opcional)</label>
                                <textarea 
                                    v-model="createForm.description" 
                                    class="form-control form-control-sm" 
                                    rows="3"
                                    placeholder="Ej: Gastos operación semana 9..."
                                ></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button 
                            type="button" 
                            class="btn btn-sm btn-primary" 
                            @click="submitCreate"
                            :disabled="createForm.processing"
                        >
                            <i class="fas fa-plus me-1"></i>
                            {{ createForm.processing ? 'Creando...' : 'Crear Rendición' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
