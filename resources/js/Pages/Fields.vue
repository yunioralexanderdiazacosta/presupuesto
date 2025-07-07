<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateFieldModal from '@/Components/Fields/CreateFieldModal.vue';
import EditFieldModal from '@/Components/Fields/EditFieldModal.vue';

const props = defineProps({
    fields: Object,
    data: Array,
    data1: Array,
    data2: Array,
    data3: Array, // <-- Declarado correctamente
    team_id: [Number, String],
    season_id: [Number, String],
    percentageField: Number
});

const selectedTeamId = ref(props.team_id || null);
const selectedSeasonId = ref(props.season_id || null);

var acum = ref(0);

const formMultiple = useForm({
    subfamily_id: '',
    products: [
        {
            product_name: '',
            price: '',
            quantity: '',
            unit_id: 5,
            observations: '',
            months: []
        }
    ]
});

const form = useForm({
    product_name: '',
    subfamily_id: '',
    price: '',
    quantity: '',
    unit_id: 5,
    observations: '',
    months: []
});

const title = 'Generales Campo';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createFieldModal').modal('show');
}

const openEdit = (field) => {
    form.reset();
    form.id = field.id;
    form.product_name = field.product_name;
    form.price = field.price;
    form.quantity = field.quantity;
    form.subfamily_id = field.subfamily_id;
    form.unit_id = field.unit_id;
    form.observations = field.observations;
    form.months = field.months; 
    $('#editFieldModal').modal('show');
}

const fetchFields = () => {
    router.get(route('fields.index'), {
        team_id: selectedTeamId.value,
        season_id: selectedSeasonId.value
    }, {
        preserveState: true
    });
};

const storeField = () => {
    formMultiple.team_id = selectedTeamId.value;
    formMultiple.season_id = selectedSeasonId.value;
    formMultiple.post(route('fields.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
            $('#createFieldModal').modal('hide');
            msgSuccess('Guardado correctamente');
            fetchFields();
        }
    });
};

const updateField = () => {
    form.team_id = selectedTeamId.value;
    form.season_id = selectedSeasonId.value;
    form.post(route('fields.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editFieldModal').modal('hide');
            msgSuccess('Guardado correctamente');
            fetchFields();
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
            router.delete(route('fields.delete', id), {
                data: {
                    team_id: selectedTeamId.value,
                    season_id: selectedSeasonId.value
                },
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                    fetchFields();
                }
            });
        }
    });
}

const acum_products = (quantity) => {
    acum.value = acum.value + quantity;
    return acum.value;
}

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
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">General Campo</h5>
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
                        <Table sticky-header :id="'fields'" :total="fields.length" :links="fields.links">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th scope="col" width="min-w-100px">Nombre</th>
                                <th scope="col" width="min-w-100px">SubFamilia</th>
                                <th scope="col" width="min-w-100px">Cantidad</th>
                                <th scope="col" width="min-w-100px">Unidad</th>
                                <th scope="col" width="min-w-100px">Precio</th>
                                <th scope="col" width="min-w-150px" class="text-end text-center">Acciones</th>
                                <!--end::Table row-->
                            </template>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <template #body>
                                <template v-if="fields.total == 0">
                                    <Empty colspan="6" />
                                </template>
                                <template v-else>
                                    <tr v-for="(field, index) in fields.data" :key="index">
                                        <td>
                                            <span class="text-dark  fw-bold mb-1">{{field.product_name}}</span>
                                        </td>
                                       
                                        <td>{{field.subfamily.name}}</td>
                                        <td>{{field.quantity}}</td>
                                        <td>{{field.unit.name}}</td>
                                        <td>{{field.price}}</td>
                                        <td class="text-end text-center">
                                            <!--begin::Update-->
                                            <button type="button" @click="openEdit(field)" v-tooltip="'Editar'" class="btn btn-link me-3 p-0">
                                                <span class="text-500 fas fa-edit"></span>
                                            </button>
                                            <!--end::Update-->
                                            <!--begin::Delete-->
                                            <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(field.id)" class="btn btn-link p-0">
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
                        
                        <div class="row mb-3">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-6">
                                        
                                         {{
                                          data1.reduce((sum, level) =>
                                            sum + level.subfamilies.reduce((sfSum, subfamily) =>
                                              sfSum + subfamily.products.reduce((pSum, product) => {
                                                const val = product.totalAmount ? parseFloat((product.totalAmount+'').replace(/\./g,'').replace(',','.')) : 0;
                                                return pSum + val;
                                              }, 0)
                                          , 0)
                                        , 0).toLocaleString('es-ES')
                                      }}
                                        
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h5 class="mb-0 mt-2 d-flex align-items-center">Porc. Monto</h5>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
<p class="font-sans-serif lh-1 mb-1 fs-6">{{percentageField}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                        <div class="table-responsive mt-1">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <thead>
                                    <tr>
                                        <th class="min-w-150px">Level 2</th>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="text-primary">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <tbody>
  <template v-for="(level, lidx) in data1">
    <template v-for="(subfamily, sfidx) in level.subfamilies">
      <template v-for="(product, pidx) in subfamily.products">
        <tr>
          <td v-if="sfidx === 0 && pidx === 0" :rowspan="level.subfamilies.reduce((acc, sf) => acc + (sf.products?.length || 1), 0)" style="vertical-align:top">{{ level.name }}</td>
          <td v-if="pidx === 0" :rowspan="subfamily.products.length > 0 ? subfamily.products.length : 1" style="vertical-align:top">{{ subfamily.name }}</td>
          <td>{{ product.name }}</td>
          <td>{{ product.totalQuantity }}</td>
          <td>{{ product.unit }}</td>
          <td class="text-dark">{{ product.totalAmount }}</td>
          <td class="bg-opacity-5 table-primary" v-for="value in product.months">{{ value }}</td>
        </tr>
      </template>
      <tr v-if="!subfamily.products || subfamily.products.length === 0">
        <td v-if="sfidx === 0" :rowspan="level.subfamilies.length" style="vertical-align:top">{{ level.name }}</td>
        <td style="vertical-align:top">{{ subfamily.name }}</td>
        <td colspan="5 + $page.props.months.length">Sin productos</td>
      </tr>
    </template>
  </template>
</tbody>
 <!-- Fila de totales por mes -->
  <tr class="table-dark fw-bold">
    <td colspan="6" class="text-end text-dark">Total por mes</td>
    <td v-for="(month, mIdx) in $page.props.months" class="bg-opacity-5 table-primary">
      {{
        data1.reduce((sum, level) =>
          sum + level.subfamilies.reduce((sfSum, subfamily) =>
            sfSum + subfamily.products.reduce((pSum, product) => {
              const val = product.months && product.months[mIdx] ? parseFloat((product.months[mIdx]+'').replace(/\./g,'').replace(',','.')) : 0;
              return pSum + val;
            }, 0)
          , 0)
        , 0).toLocaleString('es-ES')
      }}
    </td>
  </tr>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pill-tab-gastos" role="tabpanel" aria-labelledby="gastos-tab">
                        
                        <div class="row mb-3">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-6">
                                         {{
                                          data1.reduce((sum, level) =>
                                            sum + level.subfamilies.reduce((sfSum, subfamily) =>
                                              sfSum + subfamily.products.reduce((pSum, product) => {
                                                const val = product.totalAmount ? parseFloat((product.totalAmount+'').replace(/\./g,'').replace(',','.')) : 0;
                                                return pSum + val;
                                              }, 0)
                                          , 0)
                                        , 0).toLocaleString('es-ES')
                                      }}
                                      </p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentageField}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                      
                        <div class="table-responsive mt-1">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <thead>
                                    <tr>
                                        <th>Nivel 2</th>
                                        <th>Nivel 3</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th class="text-dark">Monto Total</th>
                                        <th v-for="(month, mIdx) in $page.props.months" class="text-primary">{{ month.label }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(level2, l2Idx) in data3">
                                      <template v-for="(level3, l3Idx) in level2.level3s">
                                        <template v-for="(product, pIdx) in level3.products">
                                          <template v-for="(item, idx) in product.items" :key="'item-' + l2Idx + '-' + l3Idx + '-' + pIdx + '-' + idx">
                                            <tr>
                                              <td v-if="l3Idx === 0 && pIdx === 0 && idx === 0" :rowspan="level2.level3s.reduce((acc, l3) => acc + l3.products.reduce((a, p) => a + p.items.length, 0), 0)">{{ level2.name }}</td>
                                              <td v-if="pIdx === 0 && idx === 0" :rowspan="level3.products.reduce((a, p) => a + p.items.length, 0)">{{ level3.name }}</td>
                                              <td v-if="idx === 0" :rowspan="product.items.length">{{ product.name }}</td>
                                              <td>{{ Number(item.quantity).toLocaleString('es-ES') }}</td>
                                              <td class="text-dark">{{ Number(item.total).toLocaleString('es-ES') }}</td>
                                              <td v-for="(value, mIdx) in item.months" class="bg-opacity-5 table-primary">{{ Number(value).toLocaleString('es-ES') }}</td>
                                            </tr>
                                          </template>
                                        </template>
                                      </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
 </div> 
                    </div>


                    </div>
                    <div class="tab-pane fade" id="pill-tab-detalles-compra" role="tabpanel" aria-labelledby="detalles-compra-tab">
                        
                        <div class="row mb-3">
                            <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                              <div class="card h-md-100 ecommerce-card-min-width">
                                <div class="card-header pb-0">
                                  <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-6">
                                           {{
                                          data1.reduce((sum, level) =>
                                            sum + level.subfamilies.reduce((sfSum, subfamily) =>
                                              sfSum + subfamily.products.reduce((pSum, product) => {
                                                const val = product.totalAmount ? parseFloat((product.totalAmount+'').replace(/\./g,'').replace(',','.')) : 0;
                                                return pSum + val;
                                              }, 0)
                                          , 0)
                                        , 0).toLocaleString('es-ES')
                                      }}
                                      </p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentageField}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>  
                        </div>

                        <!--begin::Table-->
                        <div class="table-responsive mt-1">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <thead>
                                    <tr>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark">Monto Total</th>
                                    </tr>
                                </thead>
                               
                                <tbody>
                                    <template v-for="(subfamily, index2) in data2">
                                        <template v-for="(product, index3) in subfamily.products">
                                          <tr>
                                            <td v-if="index3 === 0" :rowspan="subfamily.products.length">{{subfamily.name}}</td>
                                            <td>{{product.name}}</td>
                                            <td>{{product.totalQuantity}}</td>
                                            <td>{{product.unit}}</td>
                                            <td class="text-dark">{{product.totalAmount}}</td>
                                          </tr>
                                        </template>
                                        <!-- Fila de subtotal solo monto total -->
                                        <tr class="table-secondary fw-bold">
                                          <td colspan="4" class="text-end">Subtotal {{subfamily.name}}</td>
                                          <td class="text-dark">
                                            {{
                                              subfamily.products.reduce((sum, p) => sum + parseFloat((p.totalAmount+'').replace(/\./g,'').replace(',','.')), 0).toLocaleString('es-ES')
                                            }}
                                          </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

        <CreateFieldModal @store="storeField" :form="formMultiple" />
        <EditFieldModal @update="updateField" :form="form" />
    </AppLayout>
</template>

<style>
.table.agrochem-details > thead > tr {
  background-color: #e7ebee !important; /* azul claro, puedes ajustar el color */
}

.table.agrochem-details > :not(caption) > * > * {
  border-width: 1px !important;
  border-color: #cdcdd3 !important;
}

.table.agrochem-details th.min-w-150px,
.table.agrochem-details td.min-w-150px {
  min-width: 150px;
  width: 150px;
  max-width: 200px;
  word-break: break-word;
}
</style>