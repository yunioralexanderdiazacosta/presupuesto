<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateManPowerModal from '@/Components/ManPowers/CreateManPowerModal.vue';
import EditManPowerModal from '@/Components/ManPowers/EditManPowerModal.vue';

const props = defineProps({
    manPowers: Object,
    data: Array,
    data2: Array,
    data3: Array,
    totalData1: String,
    totalData2: String,
    percentage: String
});

const formMultiple = useForm({
    cc: [],
    subfamily_id: '',
    products: [
        {
            product_name: '',
            price: '',
            workday: '',
            observations: '',
            months: []
        }
    ]
});

const form = useForm({
    subfamily_id: '',
    product_name: '',
    price: '',
    workday: '',
    observations: '',
    cc: [],
    months: []
});

const title = 'Mano de obra';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createManPowerModal').modal('show');
}

const openEdit = (manPower) => {
    form.reset();
    form.id = manPower.id;
    form.product_name = manPower.product_name;
    form.workday = manPower.workday;
    form.price = manPower.price;
    form.subfamily_id = manPower.subfamily_id;
    form.observations = manPower.observations;
    form.cc = manPower.cc;
    form.months = manPower.months;
    $('#editManPowerModal').modal('show');
}

const storeManPower = () => {
    formMultiple.post(route('man.powers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
            $('#createManPowerModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateManPower = () => {
    form.post(route('man.powers.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editManPowerModal').modal('hide');
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
            router.delete(route('man.powers.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

const acum_products = (quantity) => {
    acum.value = acum.value + quantity;
    return acum.value;
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
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Mano de obra</h5>
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
                        <Table sticky-header :id="'manPowers'" :total="manPowers.length" :links="manPowers.links">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th width="min-w-100px">Nombre</th>
                                <th width="min-w-100px">SubFamilia</th>
                                <th width="min-w-100px">Jornadas</th>
                                <th width="min-w-100px">Precio</th>
                                <th width="min-w-150px" class="text-end text-center">Acciones</th>
                                <!--end::Table row-->
                            </template>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <template #body>
                                <template v-if="manPowers.total == 0">
                                    <Empty colspan="5" />
                                </template>
                                <template v-else>
                                    <tr v-for="(manPower, index) in manPowers.data" :key="index">
                                        <td>
                                            <span class="text-dark  fw-bold mb-1">{{manPower.product_name}}</span>
                                        </td>
                                        <td>{{ manPower.subfamily?.name || '' }}</td>
                                        <td>{{manPower.workday}}</td>
                                        <td>{{manPower.price}}</td>
                                        <td class="text-end text-center">
                                            <!--begin::Update-->
                                            <button type="button" @click="openEdit(manPower)" v-tooltip="'Editar'" class="btn btn-link me-3 p-0">
                                                <span class="text-500 fas fa-edit"></span>
                                            </button>
                                            <!--end::Update-->
                                            <!--begin::Delete-->
                                            <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(manPower.id)" class="btn btn-link p-0">
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
                       
                       <div class="row  mb-3">
                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalData1}}</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentage}}%</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>  
                    </div>

                        <div class="table-responsive mt-1">
                           <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                            <!--begin::Table head-->
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-150px">Centro de costo</th>
                                    <th>Subfamilia</th>
                                    <th class="min-w-100px">Producto</th>
                                    <th>Cantidad Total</th>
                                    <th>Un</th>
                                    <th class="text-dark">Monto Total</th>
                                    <th v-for="month in $page.props.months" class="text-primary">{{month.label}}</th> 
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
                         <div class="row  mb-3">
                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalData1}}</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentage}}%</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>  
                    </div>

                        <div class="table-responsive mt-1">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <!--begin::Table head-->
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th class="min-w-150px">CC</th>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark">Monto Total</th>
                                        <th v-for="month in $page.props.months" :key="month.id" class="text-primary">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                    <template v-for="(cc, index) in data" :key="index">
                                        <template v-for="(subfamily, index2) in cc.subfamilies" :key="index2">
                                            <tr>
                                                <td v-if="index2 == 0" :rowspan="cc.total" style="vertical-align:top">{{cc.name}}</td>
                                                <td  style="vertical-align:top;" :rowspan="subfamily.products.length">{{subfamily.name}}</td>
                                                <td>{{subfamily.products[0].name}}</td>
                                                <td>{{subfamily.products[0].totalQuantity}}</td>
                                                <td>{{subfamily.products[0].unit}}</td>
                                                <td class="text-dark">{{subfamily.products[0].totalAmount}}</td>
                                                <td class="bg-opacity-5 table-primary" v-for="value in subfamily.products[0].months" :key="value">{{value}}</td>
                                            </tr>

                                            <template v-for="(product, index3) in subfamily.products" :key="index3">
                                                <tr v-if="index3 > 0">
                                                    <td>{{product.name}}</td>
                                                    <td>{{product.totalQuantity}}</td>
                                                    <td>{{product.unit}}</td>
                                                    <td class="text-dark">{{product.totalAmount}}</td>
                                                    <td class="bg-opacity-5 table-primary" v-for="value in product.months" :key="value">{{value}}</td>
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
                        <div class="row  mb-3">
                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalData1}}</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentage}}%</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>  
                    </div>

                        <div class="table-responsive mt-1">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
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
        <CreateManPowerModal @store="storeManPower" :form="formMultiple" />
        <EditManPowerModal @update="updateManPower" :form="form" />
    </AppLayout>
</template>

<style>
.table.agrochem-details > thead > tr {
  background-color: #e7ebee !important;
}

.table.agrochem-details > :not(caption) > * > * {
  border-width: 1px !important;
  border-color: #cdcdd3 !important;
}
</style>