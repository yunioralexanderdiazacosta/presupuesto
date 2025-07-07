<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateServiceModal from '@/Components/Services/CreateServiceModal.vue';
import EditServiceModal from '@/Components/Services/EditServiceModal.vue';

const props = defineProps({
    services: Object,
    data: Array,
    data2: Array,
    data3: Array,
    totalData1: String,
    totalData2: String,
    percentage: String,
     varieties: {
      type: Array,
      default: () => []
    },
    fruits: {
      type: Array,
      default: () => []
    }
});

// Filtro por especie (fruta) y variedad
const selectedFruit = ref('');
const selectedVariety = ref('');

// Variedades filtradas por fruta
const filteredVarieties = computed(() => {
  if (!selectedFruit.value) {
    if (selectedVariety.value) selectedVariety.value = '';
    return props.varieties;
  }
  return props.varieties.filter(v => v.fruit_id == selectedFruit.value);
});

// Filtra los cost centers por fruit_id y variety_id para la pestaña Detalles
const filteredData = computed(() => {
  let data = props.data;
  if (selectedFruit.value) {
    data = data.filter(cc => {
      // Si no hay variety_id en el cost center, no lo mostramos
      if (!cc.variety_id) return false;
      // Si hay variedad seleccionada, filtramos por esa variedad
      if (selectedVariety.value) {
        return cc.variety_id == selectedVariety.value;
      }
      // Si no hay variedad seleccionada, mostramos todas las variedades de la fruta seleccionada
      const variety = props.varieties.find(v => v.id == cc.variety_id);
      return variety && variety.fruit_id == selectedFruit.value;
    });
  }
  return data;
});

// Monto total dinámico para la pestaña Detalles (de filteredData)
const totalFilteredData = computed(() => {
  let total = 0;
  filteredData.value.forEach(cc => {
    cc.subfamilies.forEach(subfamily => {
      subfamily.products.forEach(product => {
        let amount = typeof product.totalAmount === 'string' ? Number(product.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(product.totalAmount);
        if (!isNaN(amount)) total += amount;
      });
    });
  });
  return total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});

// Filtro por variedad para Gastos por Hectarea
const selectedVarietyGastos = ref('');
const filteredVarietiesGastos = computed(() => {
  if (!selectedFruit.value) {
    if (selectedVarietyGastos.value) selectedVarietyGastos.value = '';
    return props.varieties;
  }
  return props.varieties.filter(v => v.fruit_id == selectedFruit.value);
});
const filteredDataGastos = computed(() => {
  let data = props.data3;
  if (selectedFruit.value) {
    data = data.filter(cc => {
      if (!cc.variety_id) return false;
      if (selectedVarietyGastos.value) {
        return cc.variety_id == selectedVarietyGastos.value;
      }
      const variety = props.varieties.find(v => v.id == cc.variety_id);
      return variety && variety.fruit_id == selectedFruit.value;
    });
  }
  return data;
});

// Monto total dinámico para la pestaña Gastos por Hectarea (de filteredDataGastos)
const totalFilteredDataGastos = computed(() => {
  let total = 0;
  filteredDataGastos.value.forEach(cc => {
    cc.subfamilies.forEach(subfamily => {
      subfamily.products.forEach(product => {
        let amount = typeof product.totalAmount === 'string' ? Number(product.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(product.totalAmount);
        if (!isNaN(amount)) total += amount;
      });
    });
  });
  return total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});



// Filtro para Detalle de compra 
const filteredDataCompra = computed(() => {
  let data = props.data2;
  if (selectedFruit.value) {
    // Filtra por fruta
    data = data.filter(subfamily => {
      // Busca si algún producto de la subfamilia pertenece a la fruta seleccionada
      return subfamily.products.some(product => {
        const variety = props.varieties.find(v => v.id == product.variety_id);
        return variety && variety.fruit_id == selectedFruit.value;
      });
    });
    // Si hay variedad seleccionada, filtra por variedad
    if (selectedVariety.value) {
      data = data.map(subfamily => {
        return {
          ...subfamily,
          products: subfamily.products.filter(product => product.variety_id == selectedVariety.value)
        };
      }).filter(subfamily => subfamily.products.length > 0);
    }
  }
  return data;
});

// Monto total dinámico para Detalle de compra
const totalFilteredDataCompra = computed(() => {
  let total = 0;
  filteredDataCompra.value.forEach(subfamily => {
    subfamily.products.forEach(product => {
      let amount = typeof product.totalAmount === 'string' ? Number(product.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(product.totalAmount);
      if (!isNaN(amount)) total += amount;
    });
  });
  return total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});

var acum = ref(0);

const formMultiple = useForm({
    subfamily_id: '',
    cc: [],
    products: [
        {
            product_name: '',
            quantity: '',
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
    quantity: '',
    price: '',
    subfamily_id: '',
    unit_id: '',
    unit_id_price: '',
    observations: '',
    cc: [],
    months: []
});

const title = 'Servicios';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createServiceModal').modal('show');
}

const openEdit = (service) => {
    form.reset();
    form.id = service.id;
    form.product_name = service.product_name;
    form.price = service.price;
    form.quantity = service.quantity;
    form.subfamily_id = service.subfamily_id;
    form.unit_id = service.unit_id;
    form.unit_id_price = service.unit_id_price;
    form.observations = service.observations;
    form.cc = service.cc;
    form.months = service.months; 
    $('#editServiceModal').modal('show');
}

const storeService = () => {
    formMultiple.post(route('services.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
            $('#createServiceModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateService = () => {
    form.post(route('services.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editServiceModal').modal('hide');
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
            router.delete(route('serervice.delete', id), {
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
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Servicios</h5>
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
                    <Table sticky-header :id="'services'" :total="services.length" :links="services.links">
                        <!--begin::Table head-->
                        <template #header>
                            <!--begin::Table row-->
                            <th width="min-w-100px">Nombre</th>
                            <th width="min-w-100px">SubFamilia</th>
                            <th width="min-w-100px">Cantidad</th>
                            <th width="min-w-100px">Unidad</th>
                            <th width="min-w-100px">Precio</th>
                            <th width="min-w-100px">Unidad de $</th>
                            <th width="min-w-150px" class="text-end text-center">Acciones</th>
                            <!--end::Table row-->
                        </template>
                        <!--end::Table head-->
                        <!--begin::Table body-->
                        <template #body>
                            <template v-if="services.total == 0">
                                <Empty colspan="7" />
                            </template>
                            <template v-else>
                                <tr v-for="(service, index) in services.data" :key="index">
                                    <td>
                                        <span class="text-dark  fw-bold mb-1">{{service.product_name}}</span>
                                    </td>
                                    <td>{{service.subfamily.name}}</td>
                                    <td>{{service.quantity}}</td>
                                    <td>{{service.unit.name}}</td>
                                    <td>{{service.price}}</td>
                                    <td>{{service.unit2.name}}</td>
                                    <td class="text-end text-center">
                                        <!--begin::Update-->
                                        <button type="button" @click="openEdit(service)" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                                            
                                            <span class="svg-icon svg-icon-3">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                                <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                            </svg>
                                            </span>
                                        </button>
                                        <!--end::Update-->
                                        <!--begin::Delete-->
                                        <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(service.id)" class="btn btn-icon btn-active-light-primary w-30px h-30px">
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
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalFilteredData}}</p>
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

                     <!-- Select de especie (fruta) y variedades, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                          <div>
                            <label for="fruitSelect" class="form-label">Filtrar por especie:</label>
                            <select id="fruitSelect" v-model="selectedFruit" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="fruit in fruits" :key="fruit.id" :value="fruit.id">
                                {{ fruit.name }}
                              </option>
                            </select>
                          </div>
                          <div>
                            <label for="varietySelect" class="form-label">Filtrar por variedad:</label>
                            <select id="varietySelect" v-model="selectedVariety" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;" :disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarieties" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
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
                                <template v-for="cc in filteredData">
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
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalFilteredDataGastos}}</p>
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


  <!-- Select de especie (fruta) y variedades para Gastos por Hectarea, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                          <div>
                            <label for="fruitSelectGastos" class="form-label">Filtrar por especie:</label>
                            <select id="fruitSelectGastos" v-model="selectedFruit" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="fruit in fruits" :key="fruit.id" :value="fruit.id">
                                {{ fruit.name }}
                              </option>
                            </select>
                          </div>
                          <div>
                            <label for="varietySelectGastos" class="form-label">Filtrar por variedad:</label>
                            <select id="varietySelectGastos" v-model="selectedVarietyGastos" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;" :disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarietiesGastos" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
                          </div>
                        </div>

                   <div class="table-responsive mt-1">
                        <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
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
                                <template v-for="cc in filteredDataGastos">
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
                    <div class="row  mb-3">
                        <div class="col-md-6 col-lg-3 col-xl-6 col-xxl-3">
                          <div class="card h-md-100 ecommerce-card-min-width">
                            <div class="card-header pb-0">
                              <h6 class="mb-0 mt-2 d-flex align-items-center">Monto Total</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end">
                              <div class="row">
                                <div class="col">
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">
                                    {{ props.data2.reduce((acc, subfamily) => {
                                      return acc + subfamily.products.reduce((acc2, p) => {
                                        let amount = typeof p.totalAmount === 'string' ? Number(p.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(p.totalAmount);
                                        return !isNaN(amount) ? acc2 + amount : acc2;
                                      }, 0);
                                    }, 0).toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }) }}
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
                                  <p class="font-sans-serif lh-1 mb-1 fs-6">{{percentage}}%</p>
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
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark">Monto Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(subfamily, index2) in props.data2" :key="index2">
                                        <tr>
                                            <td  style="vertical-align:top;" :rowspan="subfamily.products.length + 1">{{subfamily.name}}</td>
                                            <td>{{subfamily.products[0].name}}</td>
                                            <td>{{subfamily.products[0].totalQuantity}}</td>
                                            <td>{{subfamily.products[0].unit}}</td>
                                            <td class="text-dark">{{subfamily.products[0].totalAmount}}</td>
                                        </tr>

                                        <template v-for="(product, index3) in subfamily.products" :key="index3">
                                            <tr v-if="index3 > 0">
                                                <td>{{product.name}}</td>
                                                <td>{{product.totalQuantity}}</td>
                                                <td>{{product.unit}}</td>
                                                <td class="text-dark">{{product.totalAmount}}</td>
                                            </tr>
                                        </template>
                                        <!-- Subtotal row -->
                                        <tr class="table-secondary">
                                            <td colspan="3" class="text-end fw-bold">Subtotal</td>
                                            <td colspan="2" class="fw-bold text-dark">
                                              {{ subfamily.products.reduce((acc, p) => {
                                                let amount = typeof p.totalAmount === 'string' ? Number(p.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(p.totalAmount);
                                                return !isNaN(amount) ? acc + amount : acc;
                                              }, 0).toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }) }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
    <CreateServiceModal @store="storeService" :form="formMultiple" />
    <EditServiceModal @update="updateService" :form="form" />
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