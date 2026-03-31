<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Empty from '@/Components/Empty.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import CreateProjectModal from '@/Components/Projects/CreateProjectModal.vue';
import EditProjectModal from '@/Components/Projects/EditProjectModal.vue';

const props = defineProps({
    projects:   { type: Array, default: () => [] },
    operations: { type: Array, default: () => [] },
});

const title = 'Proyectos';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: title, active: true },
];

// ─── Búsqueda ───
const search = ref('');
const filtered = computed(() => {
    if (!search.value) return props.projects;
    const term = search.value.toLowerCase();
    return props.projects.filter(p =>
        (p.name ?? '').toLowerCase().includes(term) ||
        (p.operation?.name ?? '').toLowerCase().includes(term)
    );
});

// ─── Formulario compartido ───
const form = useForm({
    id:           '',
    name:         '',
    date:         '',
    observations: '',
    budget:       '',
    operation_id: null,
});

// ─── Crear ───
const openCreate = () => {
    form.reset();
    form.clearErrors();
    $('#createProjectModal').modal('show');
};

const storeProject = () => {
    form.post(route('projects.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createProjectModal').modal('hide');
            msgSuccess('Proyecto creado correctamente');
        },
    });
};

// ─── Editar ───
const openEdit = (project) => {
    form.clearErrors();
    form.id           = project.id;
    form.name         = project.name;
    form.date         = project.date ?? '';
    form.observations = project.observations ?? '';
    form.budget       = project.budget ?? '';
    form.operation_id = project.operation_id ?? null;
    $('#editProjectModal').modal('show');
};

const updateProject = () => {
    form.post(route('projects.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editProjectModal').modal('hide');
            msgSuccess('Proyecto actualizado correctamente');
        },
    });
};

// ─── Eliminar ───
const deleteProject = (project) => {
    Swal.fire({
        title: '¿Eliminar este proyecto?',
        text: `"${project.name}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('projects.delete', project.id), {
                preserveScroll: true,
                onSuccess: () => msgSuccess('Proyecto eliminado correctamente'),
            });
        }
    });
};

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000,
    });
};

// ─── Formato moneda CLP ───
const formatCLP = (val) => {
    if (val === null || val === undefined || val === '') return '—';
    return Number(val).toLocaleString('es-CL', { minimumFractionDigits: 0 });
};
</script>

<template>
    <AppLayout :title="title">
        <Breadcrumb :links="links" />

        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-folder-open me-2"></i>Proyectos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                :data="filtered"
                                :headers="[
                                    { label: 'Nombre', key: 'name' },
                                    { label: 'Fecha', key: 'date' },
                                    { label: 'Presupuesto', key: 'budget' },
                                    { label: 'Operación', key: 'operation.name' },
                                    { label: 'Observaciones', key: 'observations' },
                                ]"
                                filename="Proyectos.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <button
                                class="btn btn-falcon-default btn-sm"
                                type="button"
                                @click="openCreate"
                            >
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <SearchInput v-model="search" placeholder="Buscar por nombre u operación..." />
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover fs-10 mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Fecha</th>
                                <th class="text-end">Presupuesto</th>
                                <th>Operación</th>
                                <th>Observaciones</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filtered.length === 0">
                                <td colspan="6"><Empty /></td>
                            </tr>
                            <tr v-for="project in filtered" :key="project.id">
                                <td>{{ project.name }}</td>
                                <td>{{ project.date ?? '—' }}</td>
                                <td class="text-end">
                                    <span v-if="project.budget !== null && project.budget !== ''">
                                        $ {{ formatCLP(project.budget) }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td>
                                    <span v-if="project.operation" class="badge bg-info text-white">
                                        {{ project.operation.name }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-truncate" style="max-width: 200px;" :title="project.observations">
                                    {{ project.observations ?? '—' }}
                                </td>
                                <td class="text-center">
                                    <button
                                        v-tooltip="'Editar'"
                                        type="button"
                                        class="btn btn-link p-0 me-2"
                                        @click="openEdit(project)"
                                    >
                                        <span class="text-500 fas fa-edit"></span>
                                    </button>
                                    <button
                                        v-tooltip="'Eliminar'"
                                        type="button"
                                        class="btn btn-link p-0"
                                        @click="deleteProject(project)"
                                    >
                                        <span class="text-danger fas fa-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateProjectModal
            :form="form"
            :operations="props.operations"
            @store="storeProject"
        />
        <EditProjectModal
            :form="form"
            :operations="props.operations"
            @update="updateProject"
        />
    </AppLayout>
</template>
