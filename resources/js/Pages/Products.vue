<script setup>
import { computed, ref } from 'vue';
import SearchInput from '@/Components/SearchInput.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateProductModal from '@/Components/Products/CreateProductModal.vue';
import EditProductModal from '@/Components/Products/EditProductModal.vue';

const props = defineProps({
    products: Object,
    term: String
});

const form = useForm({
    id: '',
    name: '',
    unit_id: '',
    level1_id: '',
    level2_id: '',
    level3_id: '',
    level4_id: '',
    level2s: [],
    level3s: [],
    level4s: []

});

const title = 'Productos';

// Buscador global para la tabla de productos
const search = ref("");

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createProductModal').modal('show');
}

const openEdit = (product) => {
    form.reset();
    form.id = product.id;
    form.name = product.name;
    form.unit_id = product.unit_id;
    form.level1_id = product.level1_id;
    form.level2_id = product.level2_id;
    form.level3_id = product.level3_id;
    form.level4_id = product.level4_id;
    getLevel2s(form.level1_id);
    getLevel3s(form.level2_id);
    getLevel4s(form.level3_id);
    $('#editProductModal').modal('show');
}

const storeProduct = () => {
    form.post(route('products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createProductModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateProduct = () => {
    form.post(route('products.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editProductModal').modal('hide');
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
            router.delete(route('products.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

const getLevel2s = (event) => {
    if (event && event != "") {
        axios.get(route('levels2.get', event))
            .then(response => {
                form.level2s = response.data;
            }).catch(error => console.log(error));
    }
}

const getLevel3s = (event) => {
    if (event && event != "") {
        axios.get(route('levels3.get', event))
            .then(response => {
                form.level3s = response.data;
            }).catch(error => console.log(error));
    }
}

const getLevel4s = (event) => {
    if (event && event != "") {
        axios.get(route('levels4.get', event))
            .then(response => {
                form.level4s = response.data;
            }).catch(error => console.log(error));
    }
}


// Computed para filtrar los productos según el texto de búsqueda
const filteredProducts = computed(() => {
    if (!props.products || !props.products.data) return [];
    if (!search.value) return props.products.data;
    const term = search.value.toLowerCase();
    return props.products.data.filter(item => {
        const name = item.name ? item.name.toLowerCase() : "";
        const unit = item.unit && item.unit.name ? item.unit.name.toLowerCase() : "";
        const level2 = item.level2 && item.level2.name ? item.level2.name.toLowerCase() : "";
        const level3 = item.level3 && item.level3.name ? item.level3.name.toLowerCase() : "";
        return (
            name.includes(term) ||
            unit.includes(term) ||
            level2.includes(term) ||
            level3.includes(term)
        );
    });
});
</script>
<template>

    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card mb-3 mt-2">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                                                <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                                <i class="fas fa-boxes text-primary me-2"></i>
                                                Productos
                                            </h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                            <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

              <div class="card-body bg-body-tertiary pt-2">
                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                        <SearchInput v-model="search" placeholder="Buscar por nombre, unidad, nivel 2, nivel 3..." />
                  

                        <div class="d-flex align-items-center gap-1">
                        <ExportExcelButton
                            :data="filteredProducts"
                            :headers="[
                                { label: 'Nombre', key: 'name' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Nivel 2', key: 'level2.name' },
                                { label: 'Nivel 3', key: 'level3.name' }
                            ]"
                            class="btn btn-light-primary d-flex align-items-center p-0"
                            filename="Productos.xlsx"
                        />
                        <ExportPdfButton
                            :data="filteredProducts"
                            :headers="[
                                { label: 'Nombre', key: 'name' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Nivel 2', key: 'level2.name' },
                                { label: 'Nivel 3', key: 'level3.name' }
                            ]"
                            class="btn btn-light-primary d-flex align-items-center p-0"
                            filename="Productos.pdf"
                        />
                    </div>


                 </div>
           


                <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                    <Table sticky-header :id="'products'" :total="filteredProducts.length" :links="products.links">
                        <!--begin::Table head-->
                        <template #header>
                            <!--begin::Table row-->
                            <th width="min-w-150px">Nombre</th>
                            <th width="min-w-150px">Unidad</th>
                            <th width="min-w-150px">nivel 2</th>
                            <th width="min-w-150px">nivel 3</th>
                            <th width="min-w-150px" class="text-center">Acciones</th>
                            <!--end::Table row-->
                        </template>
                        <!--end::Table head-->
                        <!--begin::Table body-->
                        <template #body>
                            <template v-if="filteredProducts.length === 0">
                                <Empty colspan="5" />
                            </template>
                            <template v-else>
                                <tr v-for="(product, index) in filteredProducts" :key="index">
                                    <td>{{ product.name }}</td>
                                    <td>{{ product.unit ? product.unit.name : '—' }}</td>
                                    <td>{{ product.level2 ? product.level2.name : '—' }}</td>
                                    <td>{{ product.level3 ? product.level3.name : '—' }}</td>

                                    <td class="text-center">
                                        <!--begin::Update-->
                                        <button type="button" v-tooltip="'Editar'"
                                            class="btn btn-link me-2 p-0"
                                            @click="openEdit(product)">
                                            <span class="text-500 fas fa-edit"></span>
                                        </button>
                                        <!--end::Update-->
                                        <!--begin::Delete-->
                                        <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(product.id)"
                                            class="btn btn-link p-0">
                                            <span class="text-500 fas fa-trash-alt"></span>
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
        <CreateProductModal @store="storeProduct" :form="form" />
        <EditProductModal @update="updateProduct" :form="form" />
    </AppLayout>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
