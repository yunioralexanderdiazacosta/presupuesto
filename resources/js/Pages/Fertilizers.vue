<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateFertilizerModal from '@/Components/Fertilizers/CreateFertilizerModal.vue';
import EditFertilizerModal from '@/Components/Fertilizers/EditFertilizerModal.vue';

const props = defineProps({
    fertilizers: Object,
    data: Array,
    data2: Array,
    data3: Array,
    totalData1: String,
    totalData2:String,
    percentage: String
});

const formMultiple = useForm({
    subfamily_id: '',
    cc: [],
    products: [
        {
            product_name: '',
            dose: '',
            price: '',
            unit_id: '',
            unit_id_price: '',
            observations: '',
            months: []
        }
    ]
});

const form = useForm({
    product_name: '',
    dose: '',
    price: '',
    subfamily_id: '',
    unit_id: '',
    unit_id_price: '',
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
    form.unit_id_price = fertilizer.unit_id_price;
    form.observations = fertilizer.observations;
    form.cc = fertilizer.cc;
    form.months = fertilizer.months; 
    $('#editFertilizerModal').modal('show');
}

const storeFertilizer = () => {
    formMultiple.post(route('fertilizers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
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
        <!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->
       
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Fertilizantes</h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                      <div id="table-purchases-replace-element">
                        <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
                      </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                    <li class="nav-item"><a class="nav-link" id="pill-detalles" data-bs-toggle="tab" href="#pill-tab-detalles" role="tab" aria-controls="pill-tab-detalles" aria-selected="false">Detalles</a></li>
                    <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">Gastos por Hectarea</a></li>
                     <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">Detalle de compra</a></li>
                </ul>
                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="edicion-tab">        
                        <Table :id="'fertilizers'" :total="fertilizers.length" :links="fertilizers.links">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th width="min-w-100px">Nombre</th>
                                <th width="min-w-100px">SubFamilia</th>
                                <th width="min-w-100px">Dosis</th>
                                <th width="min-w-100px">Unidad dosis</th>
                                <th width="min-w-100px">Precio</th>
                                <th width="min-w-100px">Unidad de $</th>
                                <th width="min-w-150px" class="text-end text-center">Acciones</th>
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
                                        <td>{{fertilizer.dose}}</td>
                                        <td>{{fertilizer.unit.name}}</td>
                                        <td>{{fertilizer.price}}</td>
                                        <td>{{fertilizer.unit2.name}}</td>
                                        <td class="text-end text-center">
                                            <!--begin::Update-->
                                            <button type="button" @click="openEdit(fertilizer)" v-tooltip="'Editar'" class="btn btn-link me-3 p-0">
                                                <span class="text-500 fas fa-edit"></span>
                                            </button>
                                            <!--end::Update-->
                                            <!--begin::Delete-->
                                            <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(fertilizer.id)" class="btn btn-link p-0">
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

                    <div class="tab-pane fade" id="pill-tab-detalles" role="tabpanel" aria-labelledby="detalles-tab">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{totalData1}}</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{percentage}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                        <div class="table-responsive">
                            <table id="table_4" class="table">
                                <!--begin::Table head-->
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th class="yunior min-w-150px">Centro de costo</th>
                                        <th>Subfamilia</th>
                                        <th class="yunior hmin-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="yunior text-dark">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="text-primary yunior">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                    <template v-for="cc in data">
                                        <template v-for="(subfamily, index2) in cc.subfamilies">
                                            <tr>
                                                <td v-if="index2 == 0" :rowspan="cc.total" style="vertical-align:top">{{cc.name}}</td>
                                                <td style="vertical-align:top;" :rowspan="subfamily.products.length">{{subfamily.name}}</td>
                                                <td>{{subfamily.products[0].name}}</td>
                                                <td>{{subfamily.products[0].totalQuantity}}</td>
                                                <td>{{subfamily.products[0].unit}}</td>
                                                <td>{{subfamily.products[0].totalAmount}}</td>
                                                <td class="bg-opacity-5 table-primary" v-for="value in subfamily.products[0].months">{{value}}</td>
                                            </tr>
                                            <template v-for="(product, index3) in subfamily.products">
                                                <tr v-if="index3 > 0">
                                                    <td>{{product.name}}</td>
                                                    <td>{{product.totalQuantity}}</td>
                                                    <td>{{product.unit}}</td>
                                                    <td>{{product.totalAmount}}</td>
                                                    <td class="bg-opacity-5 table-primary" v-for="value in product.months">{{value}}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </template>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pill-tab-gastos" role="tabpanel" aria-labelledby="gastos-tab">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{totalData1}}</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{percentage}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                        <div class="table-responsive">
                            <table id="table_2" class="table">
                                <!--begin::Table head-->
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th class="yunior min-w-150px">Centro de costo</th>
                                        <th>Subfamilia</th>
                                        <th class="yunior hmin-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="yunior text-dark">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="text-primary yunior">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                    <template v-for="cc in data3">
                                        <template v-for="(subfamily, index2) in cc.subfamilies">
                                            <tr>
                                                <td v-if="index2 == 0" :rowspan="cc.total" style="vertical-align:top">{{cc.name}}</td>
                                                <td style="vertical-align:top;" :rowspan="subfamily.products.length">{{subfamily.name}}</td>
                                                <td>{{subfamily.products[0].name}}</td>
                                                <td>{{subfamily.products[0].totalQuantity}}</td>
                                                <td>{{subfamily.products[0].unit}}</td>
                                                <td>{{subfamily.products[0].totalAmount}}</td>
                                                <td class="bg-opacity-5 table-primary" v-for="value in subfamily.products[0].months">{{value}}</td>
                                            </tr>
                                            <template v-for="(product, index3) in subfamily.products">
                                                <tr v-if="index3 > 0">
                                                    <td>{{product.name}}</td>
                                                    <td>{{product.totalQuantity}}</td>
                                                    <td>{{product.unit}}</td>
                                                    <td>{{product.totalAmount}}</td>
                                                    <td class="bg-opacity-5 table-primary" v-for="value in product.months">{{value}}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </template>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pill-tab-detalles-compra" role="tabpanel" aria-labelledby="detalles-compra-tab">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{totalData2}}</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-4">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-5">{{percentage}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <!--begin::Table head-->
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark">Monto Total</th>
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                    <template v-for="(subfamily, index2) in data2">
                                        <tr>
                                            <td  style="vertical-align:top;" :rowspan="subfamily.products.length">{{subfamily.name}}</td>
                                            <td>{{subfamily.products[0].name}}</td>
                                            <td>{{subfamily.products[0].totalQuantity}}</td>
                                            <td>{{subfamily.products[0].unit}}</td>
                                            <td class="text-dark">{{subfamily.products[0].totalAmount}}</td>
                                        </tr>

                                        <template v-for="(product, index3) in subfamily.products">
                                            <tr v-if="index3 > 0">
                                                <td>{{product.name}}</td>
                                                <td>{{product.totalQuantity}}</td>
                                                <td>{{product.unit}}</td>
                                                <td class="text-dark">{{product.totalAmount}}</td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                        </div>
                    </div>               
                </div>
            </div>
        </div>
        <CreateFertilizerModal @store="storeFertilizer" :form="formMultiple" />
        <EditFertilizerModal @update="updateFertilizer" :form="form" />
    </AppLayout>
</template>