<script setup>
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateBudgetVersionModal from '@/Components/BudgetVersions/CreateBudgetVersionModal.vue';
import EditBudgetVersionModal from '@/Components/BudgetVersions/EditBudgetVersionModal.vue';

const props = defineProps({
    versions: { type: Array, default: () => [] },
});

const title = 'Versiones de Presupuesto';
const page = usePage();

const form = useForm({
    id: '',
    name: '',
    observations: '',
    source_version_id: '',
    activate: true,
});

const defaultSource = computed(() =>
    props.versions.find(v => v.is_official)?.id ?? props.versions[0]?.id ?? ''
);

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000,
    });
};

const openAdd = () => {
    form.reset();
    form.clearErrors();
    form.source_version_id = defaultSource.value;
    $('#createBudgetVersionModal').modal('show');
};

const openEdit = (version) => {
    form.reset();
    form.clearErrors();
    form.id = version.id;
    form.name = version.name;
    form.observations = version.observations || '';
    $('#editBudgetVersionModal').modal('show');
};

const storeVersion = () => {
    form.post(route('budget-versions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            $('#createBudgetVersionModal').modal('hide');
            form.reset();
            msgSuccess('Versión creada correctamente');
        },
    });
};

const updateVersion = () => {
    form.post(route('budget-versions.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            $('#editBudgetVersionModal').modal('hide');
            form.reset();
            msgSuccess('Guardado correctamente');
        },
    });
};

const activate = (version) => {
    router.post(route('budget-versions.activate', version.id), {}, {
        preserveScroll: true,
        onSuccess: () => msgSuccess(`Trabajando en "${version.name}"`),
    });
};

const setOfficial = (version) => {
    Swal.fire({
        title: '¿Marcar como versión oficial?',
        text: `"${version.name}" pasará a ser el presupuesto oficial de la temporada: los dashboards, reportes y todos los usuarios la usarán.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, marcar como oficial',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('budget-versions.set-official', version.id), {}, {
                preserveScroll: true,
                onSuccess: () => msgSuccess('Versión oficial actualizada'),
            });
        }
    });
};

const onDeleted = (version) => {
    Swal.fire({
        title: '¿Estás seguro de que quieres eliminar la versión?',
        text: `Se eliminará "${version.name}" con sus ${version.lines} líneas de presupuesto. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('budget-versions.delete', version.id), {
                preserveScroll: true,
                onSuccess: (response) => {
                    if (response.props.flash.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se puede eliminar',
                            text: response.props.flash.error,
                            confirmButtonColor: 'rgb(0, 158, 247)',
                        });
                    } else {
                        msgSuccess('Versión eliminada correctamente');
                    }
                },
            });
        }
    });
};
</script>
<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-code-branch me-2"></i>{{ title }}
                            <small class="text-muted ms-2">{{ page.props.temporada }}</small>
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-falcon-default btn-sm" type="button" :disabled="page.props.seasonLocked" @click="openAdd()">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nueva versión</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <p class="text-muted fs-10 mb-3">
                    La versión <strong>oficial</strong> es la que usan todos los usuarios y reportes. Las demás versiones son copias para simular ajustes:
                    solo los administradores pueden trabajar en ellas.
                </p>

                <div v-if="versions.length === 0" class="text-center text-muted py-4">
                    Esta temporada no tiene versiones de presupuesto.
                </div>

                <div v-else class="table-responsive bg-white rounded">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="bg-200">
                            <tr>
                                <th>Nombre</th>
                                <th>Observaciones</th>
                                <th class="text-center">Líneas</th>
                                <th>Creada por</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="version in versions" :key="version.id">
                                <td class="text-nowrap">
                                    <span class="fw-semibold">{{ version.name }}</span>
                                    <span v-if="version.is_official" class="badge bg-success ms-2">Oficial</span>
                                    <span v-if="version.is_active" class="badge bg-primary ms-1">En uso</span>
                                    <span v-if="version.is_locked" class="badge bg-danger ms-1"><i class="fas fa-lock me-1"></i>Bloqueada</span>
                                </td>
                                <td class="text-muted">{{ version.observations || '—' }}</td>
                                <td class="text-center">{{ version.lines }}</td>
                                <td>{{ version.created_by || '—' }}</td>
                                <td class="text-nowrap">{{ version.created_at }}</td>
                                <td class="text-end text-nowrap">
                                    <button
                                        v-if="!version.is_active"
                                        class="btn btn-falcon-default btn-sm me-1"
                                        type="button"
                                        v-tooltip="'Trabajar en esta versión'"
                                        @click="activate(version)"
                                    >
                                        <span class="fas fa-play" data-fa-transform="shrink-3"></span>
                                    </button>
                                    <button
                                        class="btn btn-falcon-default btn-sm me-1"
                                        type="button"
                                        v-tooltip="'Editar nombre y observaciones'"
                                        :disabled="page.props.seasonLocked"
                                        @click="openEdit(version)"
                                    >
                                        <span class="fas fa-pencil-alt" data-fa-transform="shrink-3"></span>
                                    </button>
                                    <button
                                        v-if="!version.is_official"
                                        class="btn btn-falcon-default btn-sm me-1"
                                        type="button"
                                        v-tooltip="'Marcar como versión oficial'"
                                        :disabled="page.props.seasonLocked"
                                        @click="setOfficial(version)"
                                    >
                                        <span class="fas fa-star" data-fa-transform="shrink-3"></span>
                                    </button>
                                    <button
                                        v-if="!version.is_official"
                                        class="btn btn-falcon-default btn-sm"
                                        type="button"
                                        v-tooltip="'Eliminar versión'"
                                        :disabled="page.props.seasonLocked"
                                        @click="onDeleted(version)"
                                    >
                                        <span class="fas fa-trash-alt" data-fa-transform="shrink-3"></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CreateBudgetVersionModal :form="form" :versions="versions" @store="storeVersion" />
        <EditBudgetVersionModal :form="form" @update="updateVersion" />
    </AppLayout>
</template>
