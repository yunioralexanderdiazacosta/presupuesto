<script setup>
import { computed, ref } from 'vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateSupplierModal from '@/Components/Suppliers/CreateSupplierModal.vue';
import EditSupplierModal from '@/Components/Suppliers/EditSupplierModal.vue';

const props = defineProps({
    suppliers: Object,
    term: String
});

const form = useForm({
    id: '',
    name: '',
    rut: '',
    contact: '',
    email: '',
    phone: ''
});

const title = 'Proveedores';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

// Buscador global para la tabla de proveedores
const search = ref("");

// Computed para filtrar los proveedores según el texto de búsqueda
const filteredSuppliers = computed(() => {
    if (!props.suppliers || !props.suppliers.data) return [];
    if (!search.value) return props.suppliers.data;
    const term = search.value.toLowerCase();
    return props.suppliers.data.filter(item => {
        const name = item.name ? item.name.toLowerCase() : "";
        const rut = item.rut ? item.rut.toLowerCase() : "";
        const contact = item.contact ? item.contact.toLowerCase() : "";
        const email = item.email ? item.email.toLowerCase() : "";
        const phone = item.phone ? String(item.phone).toLowerCase() : "";
        return (
            name.includes(term) ||
            rut.includes(term) ||
            contact.includes(term) ||
            email.includes(term) ||
            phone.includes(term)
        );
    });
});

const openAdd = () => {
    form.reset();
    $('#createSupplierModal').modal('show');
}

const openEdit = (supplier) => {
    form.reset();
    form.id = supplier.id;
    form.name = supplier.name;
    form.rut = supplier.rut;
    form.contact = supplier.contact;
    form.email = supplier.email;
    form.phone = supplier.phone;
    $('#editSupplierModal').modal('show');
}

const storeSupplier = () => {
    form.post(route('suppliers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createSupplierModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateSupplier = () => {
    form.post(route('suppliers.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editSupplierModal').modal('hide');
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
            router.delete(route('suppliers.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

</script>
<template>

    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card mb-3 mt-2">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0 text-nowrap py-1 py-xl-0 fs-9">
                            <i class="fas fa-truck text-primary me-2"></i>
                            Proveedores
                        </h5>
                    </div>
                    <div class="col-auto ms-auto">

                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            
                            <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()"><span
                                    class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span
                                    class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card-body bg-body-tertiary pt-2">
                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                        <SearchInput v-model="search"
                            placeholder="Buscar por nombre, rut, contacto, email, teléfono..." />
                   

                    <div class="d-flex align-items-center gap-1 mt-2 mb-2">
                        <ExportExcelButton :data="filteredSuppliers" :headers="[
                            { label: 'Nombre', key: 'name' },
                            { label: 'Rut', key: 'rut' },
                            { label: 'Contacto', key: 'contact' }
                        ]" class="btn btn-light-primary me-3 d-flex align-items-center p-0" filename="Proveedores.xlsx" />
                        <ExportPdfButton :data="filteredSuppliers" :headers="[
                            { label: 'Nombre', key: 'name' },
                            { label: 'Rut', key: 'rut' },
                            { label: 'Contacto', key: 'contact' }
                        ]" class="btn btn-light-primary me-3 d-flex align-items-center p-0" filename="Proveedores.pdf" />
                    </div>
                </div>
                <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                    <Table sticky-header :id="'suppliers'" :total="filteredSuppliers.length" :links="suppliers.links">
                        <template #header>
                            <th style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                Nombre</th>
                            <th width="min-w-150px">Rut</th>
                            <th width="min-w-150px">Contacto</th>
                            <th width="min-w-150px" class="text-center">Acciones</th>
                        </template>
                        <template #body>
                            <template v-if="filteredSuppliers.length === 0">
                                <Empty colspan="4" />
                            </template>
                            <template v-else>
                                <tr v-for="(supplier, index) in filteredSuppliers" :key="index">
                                    <td
                                        style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ supplier.name }}</td>
                                    <td>{{ supplier.rut }}</td>
                                    <td>{{ supplier.contact }}</td>
                                    <td class="text-center">
                                        <button type="button" v-tooltip="'Editar'" class="btn btn-link me-2 p-0"
                                            @click="openEdit(supplier)">
                                            <span class="text-500 fas fa-edit"></span>
                                        </button>
                                        <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(supplier.id)"
                                            class="btn btn-link p-0">
                                            <span class="text-500 fas fa-trash-alt"></span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </template>
                    </Table>
                </div>
            </div>
        </div>
 </div>
        <CreateSupplierModal @store="storeSupplier" :form="form" />
        <EditSupplierModal @update="updateSupplier" :form="form" />
    </AppLayout>
</template>

<style>
.table.agrochem-details>thead>tr {
    background-color: #e7ebee !important;
}

.table.agrochem-details> :not(caption)>*>* {
    border-width: 1px !important;
    border-color: #cdcdd3 !important;
}
</style>