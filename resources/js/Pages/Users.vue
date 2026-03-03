<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import moment from 'moment';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateUserModal from '@/Components/Users/CreateUserModal.vue';
import EditUserModal from '@/Components/Users/EditUserModal.vue';

const props = defineProps({
    users: Object,
    term: String,
    availableRoles: Array
});

const form = useForm({
    id: null,
    name: '',
    username: '',
    email: '',
    password: '',
    roles: []    
});

const title = 'Usuarios';

const term  = ref(props.term);

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createUserModal').modal('show');
}

const openEdit = (user) => {
    form.reset();
    form.id = user.id; 
    form.name = user.name;
    form.username = user.username;
    form.email = user.email;
    form.roles = user.roles || [];   
    $('#editUserModal').modal('show');
}

const storeUser = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createUserModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateUser = () => {
   form.post(route('users.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editUserModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
   }); 
}

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
    });
}

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
            router.delete(route('users.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

const onAction = (id, status) => {
    const msgStatus = status == 1 ? 'activar' : 'suspender';

    Swal.fire({
        title: '¿Estás seguro de que desea ' + msgStatus + ' esta cuenta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('users.activate.inactivate', id), {status: status}, {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Acción realizada correctamente');
                }
            });
        }
    });
}

const onFilter = () => {
  router.get(route('users.index', {term: term.value}), { preserveState: true});  
}
</script>
<template>
    <Head :title="title" />
	<AppLayout>
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0" data-anchor="data-anchor">Usuarios</h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <!--begin::Export-->
                            <a :href="route('users.pdf', {term: term})" target="_blank" class="btn btn-light-primary me-3">
                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr078.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect opacity="0.3" x="12.75" y="4.25" width="12" height="2" rx="1" transform="rotate(90 12.75 4.25)" fill="currentColor" />
                                    <path d="M12.0573 6.11875L13.5203 7.87435C13.9121 8.34457 14.6232 8.37683 15.056 7.94401C15.4457 7.5543 15.4641 6.92836 15.0979 6.51643L12.4974 3.59084C12.0996 3.14332 11.4004 3.14332 11.0026 3.59084L8.40206 6.51643C8.0359 6.92836 8.0543 7.5543 8.44401 7.94401C8.87683 8.37683 9.58785 8.34458 9.9797 7.87435L11.4427 6.11875C11.6026 5.92684 11.8974 5.92684 12.0573 6.11875Z" fill="currentColor" />
                                    <path opacity="0.3" d="M18.75 8.25H17.75C17.1977 8.25 16.75 8.69772 16.75 9.25C16.75 9.80228 17.1977 10.25 17.75 10.25C18.3023 10.25 18.75 10.6977 18.75 11.25V18.25C18.75 18.8023 18.3023 19.25 17.75 19.25H5.75C5.19772 19.25 4.75 18.8023 4.75 18.25V11.25C4.75 10.6977 5.19771 10.25 5.75 10.25C6.30229 10.25 6.75 9.80228 6.75 9.25C6.75 8.69772 6.30229 8.25 5.75 8.25H4.75C3.64543 8.25 2.75 9.14543 2.75 10.25V19.25C2.75 20.3546 3.64543 21.25 4.75 21.25H18.75C19.8546 21.25 20.75 20.3546 20.75 19.25V10.25C20.75 9.14543 19.8546 8.25 18.75 8.25Z" fill="currentColor" />
                                </svg>
                            </span>
                            <!--end::Svg Icon-->Exportar PDF
                            </a>
                            <!--end::Export-->

                           <!--begin::Export-->
                            <a :href="route('users.excel', {term: term})" target="_blank" class="btn btn-light-primary me-3">
                            <!--begin::Svg Icon | path: icons/duotune/arrows/arr078.svg-->
                            <span class="svg-icon svg-icon-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect opacity="0.3" x="12.75" y="4.25" width="12" height="2" rx="1" transform="rotate(90 12.75 4.25)" fill="currentColor" />
                                    <path d="M12.0573 6.11875L13.5203 7.87435C13.9121 8.34457 14.6232 8.37683 15.056 7.94401C15.4457 7.5543 15.4641 6.92836 15.0979 6.51643L12.4974 3.59084C12.0996 3.14332 11.4004 3.14332 11.0026 3.59084L8.40206 6.51643C8.0359 6.92836 8.0543 7.5543 8.44401 7.94401C8.87683 8.37683 9.58785 8.34458 9.9797 7.87435L11.4427 6.11875C11.6026 5.92684 11.8974 5.92684 12.0573 6.11875Z" fill="currentColor" />
                                    <path opacity="0.3" d="M18.75 8.25H17.75C17.1977 8.25 16.75 8.69772 16.75 9.25C16.75 9.80228 17.1977 10.25 17.75 10.25C18.3023 10.25 18.75 10.6977 18.75 11.25V18.25C18.75 18.8023 18.3023 19.25 17.75 19.25H5.75C5.19772 19.25 4.75 18.8023 4.75 18.25V11.25C4.75 10.6977 5.19771 10.25 5.75 10.25C6.30229 10.25 6.75 9.80228 6.75 9.25C6.75 8.69772 6.30229 8.25 5.75 8.25H4.75C3.64543 8.25 2.75 9.14543 2.75 10.25V19.25C2.75 20.3546 3.64543 21.25 4.75 21.25H18.75C19.8546 21.25 20.75 20.3546 20.75 19.25V10.25C20.75 9.14543 19.8546 8.25 18.75 8.25Z" fill="currentColor" />
                                </svg>
                            </span>
                            <!--end::Svg Icon-->Exportar Excel
                            </a>
                            <!--end::Export-->

                            <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0"> 
                <div class="row justify-content-end g-0">
                    <div class="col-auto col-sm-5 mb-3">
                        <div class="input-group">
                            <input class="form-control form-control-sm shadow-none search" type="text" placeholder=" Buscar..." @keyup.enter="onFilter()" v-model="term" />
                            <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                        </div>
                    </div>
                </div>
                <Table :id="'users'" :total="users.data.length" :links="users.links">
                    <!--begin::Table head-->
                    <template #header>
                        <!--begin::Table row-->
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>F. Registro</th>
                        <th>Estatus</th>
                        <th class="text-end" style="min-width: 130px;">Acciones</th>
                        <!--end::Table row-->
                    </template>
                    <!--end::Table head-->
                    <!--begin::Table body-->
                    <template #body>
                        <template v-if="users.total == 0">
                            <Empty colspan="7" />
                        </template>
                        <template v-else>
                            <tr v-for="(user, index) in users.data" :key="index">
                                <td>{{user.name}}</td>
                                <td><span class="text-primary fw-semibold">{{user.username}}</span></td>
                                <td>{{user.email}}</td>
                                <td>
                                    <span 
                                        v-for="(role, idx) in user.roles" 
                                        :key="idx" 
                                        class="badge rounded-pill badge-subtle-primary me-1"
                                    >
                                        {{ role == 'Normal' ? 'Digitador' : role }}
                                    </span>
                                    <span v-if="user.roles.length === 0" class="text-muted">Sin rol</span>
                                </td>
                                <td>{{moment(user.created_at).format('DD-MM-YYYY hh:mm A')}}</td>
                                <td>
                                    <span class="badge badge-subtle-success" v-if="user.status == 1">Activo</span>
                                    <span class="badge badge-subtle-danger" v-else>Suspendido</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1 flex-nowrap">
                                        <button type="button" v-tooltip="'Editar'" class="btn btn-sm btn-falcon-default py-1 px-2" @click="openEdit(user)">
                                            <i class="fas fa-edit fs-10"></i>
                                        </button>
                                        <button type="button" v-tooltip="'Suspender'" @click="onAction(user.id, 0)" v-if="user.status == 1" class="btn btn-sm btn-falcon-default py-1 px-2">
                                            <i class="fas fa-ban fs-10 text-warning"></i>
                                        </button>
                                        <button type="button" v-tooltip="'Activar'" @click="onAction(user.id, 1)" v-if="user.status == 0" class="btn btn-sm btn-falcon-default py-1 px-2">
                                            <i class="fas fa-check-circle fs-10 text-success"></i>
                                        </button>
                                        <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(user.id)" class="btn btn-sm btn-falcon-default py-1 px-2">
                                            <i class="fas fa-trash-alt fs-10 text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <!--end::Table body-->
                </Table>
            </div>
        </div>

        <CreateUserModal @store="storeUser" :form="form" :availableRoles="availableRoles" />
        <EditUserModal @update="updateUser" :form="form" :availableRoles="availableRoles" />
    </AppLayout>
</template>