<script setup>
import { ref } from 'vue';
import { router, Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import SearchInput from '@/Components/SearchInput.vue';
import CreateRootstockModal from '@/Components/Rootstocks/CreateRootstockModal.vue';
import EditRootstockModal from '@/Components/Rootstocks/EditRootstockModal.vue';

const props = defineProps({
    rootstocks: Object,
    term: String
});

const title = 'Portainjertos';
const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];
const term  = ref(props.term);

const form = useForm({
    id: '',
    name: '',
    observations: '',
});

const openAdd = () => {
    form.reset();
    $('#createRootstockModal').modal('show');
};

const openEdit = (rootstock) => {
    form.reset();
    form.id           = rootstock.id;
    form.name         = rootstock.name;
    form.observations = rootstock.observations;
    $('#editRootstockModal').modal('show');
};

const storeRootstock = () => {
    form.post(route('rootstocks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createRootstockModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
};

const updateRootstock = () => {
    form.post(route('rootstocks.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editRootstockModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
};

const onDeleted = (id) => {
    Swal.fire({
        title: '¿Estás seguro de que quieres eliminar el registro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('rootstocks.delete', id), {
                preserveScroll: true,
                onSuccess: () => msgSuccess('Registro eliminado correctamente'),
            });
        }
    });
};

const msgSuccess = (msg) => {
    Swal.fire({ position: 'center', icon: 'success', title: msg, showConfirmButton: false, timer: 1000 });
};

const onFilter = () => {
    router.get(route('rootstocks.index', { term: term.value }), { preserveState: true });
};
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0 fs-9">
                            <i class="fas fa-tree me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body bg-body-tertiary pt-2">
                <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                    <SearchInput v-model="term" placeholder="Buscar..." @keyup.enter="onFilter" />
                </div>

                <Table :id="'rootstocks'" :total="rootstocks.data.length" :links="rootstocks.links">
                    <template #header>
                        <th>Nombre</th>
                        <th>Observaciones</th>
                        <th class="text-end">Acciones</th>
                    </template>
                    <template #body>
                        <template v-if="rootstocks.total === 0">
                            <Empty colspan="3" />
                        </template>
                        <template v-else>
                            <tr v-for="rootstock in rootstocks.data" :key="rootstock.id">
                                <td>{{ rootstock.name }}</td>
                                <td class="text-muted small">{{ rootstock.observations ?? '—' }}</td>
                                <td class="text-end">
                                    <button type="button" v-tooltip="'Editar'"
                                        class="btn btn-icon btn-active-light-primary w-30px h-30px me-2"
                                        @click="openEdit(rootstock)">
                                        <span class="svg-icon svg-icon-3">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"/>
                                                <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <button type="button" v-tooltip="'Eliminar'"
                                        class="btn btn-icon btn-active-light-primary w-30px h-30px"
                                        @click="onDeleted(rootstock.id)">
                                        <span class="svg-icon svg-icon-3">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"/>
                                                <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"/>
                                                <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </template>
                </Table>
            </div>
        </div>

        <CreateRootstockModal :form="form" @store="storeRootstock" />
        <EditRootstockModal   :form="form" @update="updateRootstock" />
    </AppLayout>
</template>
