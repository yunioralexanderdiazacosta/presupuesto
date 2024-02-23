<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateFertilizerModal from '@/Components/Fertilizers/CreateFertilizerModal.vue';
import EditFertilizerModal from '@/Components/Fertilizers/EditFertilizerModal.vue';

const props = defineProps({
    fertilizers: Object
});

const form = useForm({
    product_name: '',
    dose: '',
    price: '',
    subfamily_id: '',
    unit_id: '',
    observations: '',
    cc: [],
    months: []
});

const title = 'Fertilizantes';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createFertilizerModal').modal('show');
}

const openEdit = (fertilizer) => {
    form.reset();
    form.id = fertilizer.id;
    form.product_name = fertilizer.product_name;
    form.dose = fertilizer.dose;
    form.price = fertilizer.price;
    form.subfamily_id = fertilizer.subfamily_id;
    form.unit_id = fertilizer.unit_id;
    form.observations = fertilizer.observations;
    form.cc = fertilizer.cc;
    form.months = fertilizer.months; 
    $('#editFertilizerModal').modal('show');
}

const storeFertilizer = () => {
    form.post(route('fertilizers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createFertilizerModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateFertilizer = () => {
    form.post(route('fertilizers.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editFertilizerModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
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
            router.delete(route('fertilizers.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

/*
const onFilter = () => {
  router.get(route('manage.providers', {term: term.value, plan: plan.value, membership: membership.value}), { preserveState: true});  
}
*/
</script>
<template>
    <Head :title="title" />
	<AppLayout>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
            <!--begin::Content wrapper-->
            <div class="d-flex flex-column flex-column-fluid">
                <!--begin::Toolbar-->
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <!--begin::Toolbar container-->
                    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                        <!--begin::Page title-->
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <!--begin::Title-->
                            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{title}}</h1>
                            <!--end::Title-->
                            <!--begin::Breadcrumb-->
                            <Breadcrumb :links="links" />
                            <!--end::Breadcrumb-->
                        </div>
                        <!--end::Page title-->
                        <!--begin::Actions-->
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            <button type="button" @click="openAdd()" class="btn btn-sm fw-bold btn-primary">Agregar fertilizante</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Toolbar container-->
                </div>
                <!--end::Toolbar-->
                <!--begin::Content-->
                <div id="kt_app_content" class="app-content flex-column-fluid">
                    <!--begin::Content container-->
                    <div id="kt_app_content_container" class="app-container container-xxl">
                        <!--begin::Card-->
                        <div class="card card-flush">
                            <!--begin::Card header-->
                            <div class="card-header mt-6">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <!--begin::Search-->
                                    <div class="d-flex align-items-center position-relative my-1 me-5">
                                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                                <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                        <input type="text" class="form-control form-control-solid w-250px ps-15">
                                    </div>
                                    <!--end::Search-->
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <Table :id="'fertilizers'" :total="fertilizers.length" :links="fertilizers.links">
                                    <!--begin::Table head-->
                                    <template #header>
                                        <!--begin::Table row-->
                                        <th width="min-w-100px">Nombre</th>
                                        <th width="min-w-100px">Familia</th>
                                        <th width="min-w-100px">Unidad</th>
                                        <th width="min-w-100px">Precio</th>
                                        <th width="min-w-150px" class="text-end">Acciones</th>
                                        <!--end::Table row-->
                                    </template>
                                    <!--end::Table head-->
                                    <!--begin::Table body-->
                                    <template #body>
                                        <template v-if="fertilizers.total == 0">
                                            <Empty colspan="5" />
                                        </template>
                                        <template v-else>
                                            <tr v-for="(fertilizer, index) in fertilizers.data" :key="index">
                                                <td>
                                                    <span class="text-dark  fw-bold mb-1">{{fertilizer.product_name}}</span>
                                                </td>
                                                <td>{{fertilizer.subfamily.name}}</td>
                                                <td>{{fertilizer.unit.name}}</td>
                                                <td>{{fertilizer.price}}</td>
                                                <td class="text-end">
                                                    <!--begin::Update-->
                                                    <button type="button" @click="openEdit(fertilizer)" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                                                        
                                                        <span class="svg-icon svg-icon-3">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                                            <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                                        </svg>
                                                        </span>
                                                    </button>
                                                    <!--end::Update-->
                                                    <!--begin::Delete-->
                                                    <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(fertilizer.id)" class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                                        <span class="svg-icon svg-icon-3">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
                                                                <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
                                                                <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                    <!--end::Delete-->
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                    <!--end::Table body-->
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <CreateFertilizerModal @store="storeFertilizer" :form="form" />
        <EditFertilizerModal @update="updateFertilizer" :form="form" />
    </AppLayout>
</template>