<script setup>
import { computed, ref, watch } from 'vue';
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
import CopyProductsModal from '@/Components/Products/CopyProductsModal.vue';

const props = defineProps({
    products: Object,
    term: String,
    canCopyProducts: Boolean,
    teams: Array,
});

const form = useForm({
    id: '',
    name: '',
    active_ingredient: '',
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

const copyModalRef = ref(null);

const openCopy = () => {
    if (copyModalRef.value) copyModalRef.value.reset();
    $('#copyProductsModal').modal('show');
};

// Buscador global para la tabla de productos
const search = ref("");

// Estado para ordenamiento
const sortBy = ref('id');
const sortDesc = ref(true);
const sortUnclassifiedFirst = ref(false);

// Función para ejecutar la búsqueda cuando se presiona el botón o Enter
const executeSearch = () => {
    router.get(route('products.index', { term: search.value || '' }), {
        preserveState: true,
        preserveScroll: true,
        only: ['products']
    });
};

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createProductModal').modal('show');
}

const openEdit = (product) => {
    form.reset();
    form.id = product.id;
    form.name = product.name;
    form.active_ingredient = product.active_ingredient || '';
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


// Lista de productos directamente desde props (ya filtrados en el backend)
const filteredProducts = computed(() => {
    const data = props.products && props.products.data ? props.products.data : [];
    
    // Ordenar los productos
    const arr = [...data];
    arr.sort((a, b) => {
        // Si está activo el toggle, los sin clasificar con facturas van primero
        if (sortUnclassifiedFirst.value) {
            const aUnclassified = a.invoices_count > 0 && (!a.level1_id || !a.level2_id || !a.level3_id);
            const bUnclassified = b.invoices_count > 0 && (!b.level1_id || !b.level2_id || !b.level3_id);
            if (aUnclassified && !bUnclassified) return -1;
            if (!aUnclassified && bUnclassified) return 1;
        }
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];
        
        // Si es unit, level2 o level3, accede al nombre
        if (sortBy.value === 'unit') {
            aVal = a.unit?.name || '';
            bVal = b.unit?.name || '';
        }
        if (sortBy.value === 'level1') {
            aVal = a.level1?.name || '';
            bVal = b.level1?.name || '';
        }
        if (sortBy.value === 'level2') {
            aVal = a.level2?.name || '';
            bVal = b.level2?.name || '';
        }
        if (sortBy.value === 'level3') {
            aVal = a.level3?.name || '';
            bVal = b.level3?.name || '';
        }
        
        // Convertir a string para comparación case-insensitive
        if (typeof aVal === 'string') aVal = aVal.toLowerCase();
        if (typeof bVal === 'string') bVal = bVal.toLowerCase();
        
        if (sortDesc.value) {
            return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
        return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
    });
    
    return arr;
});

function setSort(field) {
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = field;
        sortDesc.value = false;
    }
}

// Genera clases para encabezados ordenables
const sortClass = (field) => ({
    sortable: true,
    'sorted-asc': sortBy.value === field && !sortDesc.value,
    'sorted-desc': sortBy.value === field && sortDesc.value,
});
</script>
<template>

    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                                                <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                                <i class="fas fa-boxes text-primary me-2"></i>
                                                Productos **----** Clasificación para Inventarios
                                            </h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                            <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                            <button 
                                v-if="canCopyProducts"
                                class="btn btn-falcon-default btn-sm" 
                                type="button" 
                                @click="openCopy()"
                            >
                                <span class="fas fa-copy" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Copiar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

              <div class="card-body bg-body-tertiary pt-2">
                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <SearchInput 
                                v-model="search" 
                                placeholder="Buscar por nombre, unidad, nivel 2, nivel 3..." 
                                @keyup.enter="executeSearch"
                            />
                            <button 
                                @click="executeSearch" 
                                class="btn btn-falcon-default btn-sm d-flex align-items-center justify-content-center mb-2"
                                type="button"
                                style="height: 30px; min-width: 30px;"
                            >
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                  

                        <div class="d-flex align-items-center gap-1">
                        <button
                            type="button"
                            @click="sortUnclassifiedFirst = !sortUnclassifiedFirst"
                            class="btn btn-sm mb-2"
                            :class="sortUnclassifiedFirst ? 'btn-warning text-dark' : 'btn-falcon-default'"
                            v-tooltip="'Mostrar sin clasificar primero'"
                        >
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <span class="d-none d-sm-inline-block">Sin clasificar</span>
                        </button>
                        <ExportExcelButton
                            :data="filteredProducts"
                            :headers="[
                                { label: 'ID', key: 'id' },
                                { label: 'Nombre', key: 'name' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Nivel 1', key: 'level1.name' },
                                { label: 'Nivel 2', key: 'level2.name' },
                                { label: 'Nivel 3', key: 'level3.name' }
                            ]"
                            class="btn btn-light-primary d-flex align-items-center p-0"
                            filename="Productos.xlsx"
                        />
                        <ExportPdfButton
                            :data="filteredProducts"
                            :headers="[
                                { label: 'ID', key: 'id' },
                                { label: 'Nombre', key: 'name' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Nivel 1', key: 'level1.name' },
                                { label: 'Nivel 2', key: 'level2.name' },
                                { label: 'Nivel 3', key: 'level3.name' }
                            ]"
                            class="btn btn-light-primary d-flex align-items-center p-0"
                            filename="Productos.pdf"
                        />
                    </div>


                 </div>
           


                <div class="table-responsive mt-1 scrollbar" style="max-height: 450px; overflow-y: auto;">
                    <Table sticky-header :id="'products'" :total="filteredProducts.length" :links="products.links">
                        <!--begin::Table head-->
                        <template #header>
                            <!--begin::Table row-->
                            <th width="80px" @click="setSort('id')" :class="sortClass('id')" style="cursor: pointer;">ID</th>
                            <th width="min-w-150px" @click="setSort('name')" :class="sortClass('name')" style="cursor: pointer;">Nombre</th>
                            <th width="min-w-150px" @click="setSort('unit')" :class="sortClass('unit')" style="cursor: pointer;">Unidad</th>
                            <th width="min-w-150px" @click="setSort('level1')" :class="sortClass('level1')" style="cursor: pointer;">Nivel 1</th>
                            <th width="min-w-150px" @click="setSort('level2')" :class="sortClass('level2')" style="cursor: pointer;">Nivel 2</th>
                            <th width="min-w-150px" @click="setSort('level3')" :class="sortClass('level3')" style="cursor: pointer;">Nivel 3</th>
                            <th width="min-w-150px" class="text-center">Acciones</th>
                            <!--end::Table row-->
                        </template>
                        <!--end::Table head-->
                        <!--begin::Table body-->
                        <template #body>
                            <template v-if="filteredProducts.length === 0">
                                <Empty colspan="7" />
                            </template>
                            <template v-else>
                                <tr v-for="(product, index) in filteredProducts" :key="index">
                                    <td>{{ product.id }}</td>
                                    <td>
                                        {{ product.name }}
                                        <span
                                            v-if="product.invoices_count > 0 && (!product.level1_id || !product.level2_id || !product.level3_id)"
                                            class="badge bg-warning text-dark ms-1"
                                            style="font-size: 0.65rem;"
                                            :title="'Tiene ' + product.invoices_count + ' factura(s) asociada(s) pero niveles incompletos'"
                                        >
                                            <i class="fas fa-exclamation-triangle me-1"></i>Sin clasificar
                                        </span>
                                    </td>
                                    <td>{{ product.unit ? product.unit.name : '—' }}</td>
                                    <td>{{ product.level1 ? product.level1.name : '—' }}</td>
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
        <CopyProductsModal v-if="canCopyProducts" ref="copyModalRef" :teams="teams" />
    </AppLayout>
</template>

<style>
/* Estilos para columnas ordenables */
.sortable {
    position: relative;
    cursor: pointer;
    user-select: none;
}
.sortable:hover {
    background-color: #f1f3f5;
}
.sortable:after {
    content: '\25B2'; /* triángulo hacia arriba por defecto */
    position: absolute;
    right: 8px;
    font-size: 0.6rem;
    opacity: 0.3;
}
.sorted-asc:after {
    content: '\25B2'; /* triángulo hacia arriba */
    opacity: 1;
}
.sorted-desc:after {
    content: '\25BC'; /* triángulo hacia abajo */
    opacity: 1;
}
</style>

