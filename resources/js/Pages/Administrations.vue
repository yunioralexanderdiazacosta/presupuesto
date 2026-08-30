<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';

import CreateAdministrationModal from '@/Components/Administrations/CreateAdministrationModal.vue';
import EditAdministrationModal from '@/Components/Administrations/EditAdministrationModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
// Buscador global para la tabla de administración
const search = ref('');

// Computed para filtrar las administraciones según el texto de búsqueda
const filteredAdministrations = computed(() => {
  if (!props.administrations || !props.administrations.data) return [];
  if (!search.value) return props.administrations.data;
  const term = search.value.toLowerCase();
  return props.administrations.data.filter(item => {
    const name = item.product_name ? item.product_name.toLowerCase() : '';
    const subfamily = item.subfamily && item.subfamily.name ? item.subfamily.name.toLowerCase() : '';
    const operation = item.operation && item.operation.name ? item.operation.name.toLowerCase() : '';
    const unit = item.unit && item.unit.name ? item.unit.name.toLowerCase() : '';
    return (
      name.includes(term) ||
      subfamily.includes(term) ||
      operation.includes(term) ||
      unit.includes(term)
    );
  });
});

const props = defineProps({
    administrations: Object,
    data: Array,
    data1: Array,
    data2: Array,
    data3: Array,
    data4: Object,
    team_id: [Number, String],
    season_id: [Number, String],
    percentageAdministration: Number,
    branches: { type: Array, default: () => [] },
    selectedBranchId: { type: Number, default: null },
    operations: { type: Array, default: () => [] },
    defaultOperationId: { type: [Number, String, null], default: null },
    investments: { type: Array, default: () => [] },
});

const selectedTeamId   = ref(props.team_id || null);
const selectedSeasonId = ref(props.season_id || null);
const selectedBranch   = ref(props.selectedBranchId || '');
const activeTab = ref('pill-tab-edicion');

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        activeTab.value = tabParam;
        const tabLink = document.querySelector(`a[href="#${tabParam}"]`);
        if (tabLink && window.bootstrap) {
            window.bootstrap.Tab.getOrCreateInstance(tabLink).show();
        }
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(el => {
        el.addEventListener('show.bs.tab', (e) => {
            const target = e.target.getAttribute('href')?.replace('#', '');
            if (target) activeTab.value = target;
        });
    });
});

watch(selectedBranch, (val) => {
    router.get(route('administrations.index'), { branch_id: val || '', tab: activeTab.value }, { preserveState: false, replace: true });
});

var acum = ref(0);

const formMultiple = useForm({
    branch_id: null,
    subfamily_id: '',
    operation_id: props.defaultOperationId,
    investment_id: null,
    products: [
        {
            product_name: '',
            quantity: '',
            price: '',
            unit_id: 5,
            observations: '',
            months: []
        }
    ]
});

const form = useForm({
    product_name: '',
    quantity: '',
    price: '',
    branch_id: null,
    subfamily_id: '',
    operation_id: props.defaultOperationId,
    investment_id: null,
    unit_id: 5,
    observations: '',
    months: []
});

const title = 'Administracion';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
     form.unit_id = 5;
    $('#createAdministrationModal').modal('show');
}

const openEdit = (administration) => {
    form.reset();
    form.id = administration.id;
    form.product_name = administration.product_name;
    form.price = administration.price;
    form.quantity = administration.quantity;
    form.subfamily_id = administration.subfamily_id;
    form.operation_id = administration.operation_id;
    form.investment_id = administration.investment_id;
    form.unit_id = administration.unit_id;
    form.observations = administration.observations;
    form.branch_id = administration.branch_id ?? null;
    form.months = administration.months;
    $('#editAdministrationModal').modal('show');
}

const fetchAdministrations = () => {
    router.get(route('administrations.index'), {
        team_id: selectedTeamId.value,
        season_id: selectedSeasonId.value
    }, {
        preserveState: true
    });
};

// Si tienes selects para cambiar equipo/temporada, llama a fetchAdministrations al cambiar
// Ejemplo:
// <select v-model="selectedTeamId" @change="fetchAdministrations">
// <select v-model="selectedSeasonId" @change="fetchAdministrations">

// Modificar store, update y delete para enviar ambos filtros
const storeAdministration = () => {
    formMultiple.team_id = selectedTeamId.value;
    formMultiple.season_id = selectedSeasonId.value;
    formMultiple.post(route('administrations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
            $('#createAdministrationModal').modal('hide');
            msgSuccess('Guardado correctamente');
            fetchAdministrations(); // recarga datos filtrados
        }
    });
};

const updateAdministration = () => {
    form.team_id = selectedTeamId.value;
    form.season_id = selectedSeasonId.value;
    form.post(route('administrations.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editAdministrationModal').modal('hide');
            msgSuccess('Guardado correctamente');
            fetchAdministrations();
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
            router.delete(route('administrations.delete', id), {
                data: {
                    team_id: selectedTeamId.value,
                    season_id: selectedSeasonId.value
                },
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                    fetchAdministrations();
                }
            });
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

const acum_products = (quantity) => {
    acum.value = acum.value + quantity;
    return acum.value;
}

// Computed para exportar Resumen $/Ha a Excel
const excelDataResumen = computed(() => {
    if (!props.data4 || !props.data4.rows) return [];
    const rows = props.data4.rows.map(row => ({
        nivel3: row.subfamily_name,
        monto_total: row.total_cost,
        costo_por_ha: row.cost_per_ha,
    }));
    rows.push({
        nivel3: 'Total',
        monto_total: props.data4.totalCost,
        costo_por_ha: props.data4.totalCostPerHa,
    });
    return rows;
});

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
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-chess text-primary me-2"></i>Administracion</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                  <div id="table-purchases-replace-element">
                    <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
                  </div>
                </div>
            </div>
        </div>

        <div class="card-body bg-body-tertiary">
            <div class="d-flex align-items-center gap-3 mb-2">
                <select v-if="branches && branches.length > 0" v-model="selectedBranch" class="form-select form-select-sm" style="max-width:260px;">
                    <option value="">Todas las sucursales</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
                <ul class="nav nav-pills mb-0" id="pill-myTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-detalles" data-bs-toggle="tab" href="#pill-tab-detalles" role="tab" aria-controls="pill-tab-detalles" aria-selected="false">Detalles</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">Gastos por Hectarea</a></li>
                 <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">Detalle de compra</a></li>
                 <li class="nav-item"><a class="nav-link" id="pill-resumen" data-bs-toggle="tab" href="#pill-tab-resumen" role="tab" aria-controls="pill-tab-resumen" aria-selected="false">Resumen $/Ha</a></li>
            </ul>
            </div>
            <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="edicion-tab">
                    <!-- Buscador global y botones de exportación -->
                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                      <SearchInput
                        v-model="search"
                        placeholder="Buscar por nombre, subfamilia, unidad..."
                      />
                      <div class="d-flex align-items-center gap-1">
                        <ExportExcelButton
                          :data="administrations.data"
                          :headers="[
                            { label: 'Nombre', key: 'product_name' },
                            { label: 'SubFamilia', key: 'subfamily.name' },
                            { label: 'Cantidad', key: 'quantity' },
                            { label: 'Unidad', key: 'unit.name' },
                            { label: 'Precio', key: 'price' },
                            { label: 'Operación', key: 'operation.name' },
                            { label: 'Inversión', key: 'investment.name' }
                          ]"
                          class="btn btn-success btn-md d-flex align-items-center p-0"
                          filename="Administracion.xlsx"
                        />
                        <ExportPdfButton
                          :data="administrations.data"
                          :headers="[
                            { label: 'Nombre', key: 'product_name' },
                            { label: 'SubFamilia', key: 'subfamily.name' },
                            { label: 'Cantidad', key: 'quantity' },
                            { label: 'Unidad', key: 'unit.name' },
                            { label: 'Precio', key: 'price' },
                            { label: 'Operación', key: 'operation.name' },
                            { label: 'Inversión', key: 'investment.name' }
                          ]"
                          class="btn btn-danger btn-md d-flex align-items-center p-0"
                          filename="Administracion.pdf"
                        />
                       
                      </div>
                    </div>
                    <div class="table-responsive budget-table-wrapper mt-1">
                    <Table sticky-header :id="'administrations'" :total="filteredAdministrations.length" :links="administrations.links">
                        <!--begin::Table head-->
                        <template #header>
                            <!--begin::Table row-->
                            <th width="min-w-100px">Nombre</th>
                            <th width="min-w-100px">Sucursal</th>
                            <th width="min-w-100px">SubFamilia</th>
                            <th width="min-w-100px">Cantidad</th>
                            <th width="min-w-100px">Unidad</th>
                            <th width="min-w-100px">Precio</th>
                            <th width="min-w-100px">Operación</th>
                            <th width="min-w-100px">Inversión</th>
                            <th width="min-w-100px">Digitado por</th>
                            <th width="min-w-150px" class="text-end text-center">Acciones</th>
                            <!--end::Table row-->
                        </template>
                        <!--end::Table head-->
                        <!--begin::Table body-->
                        <template #body>
                            <template v-if="filteredAdministrations.length === 0">
                                <Empty colspan="8" />
                            </template>
                            <template v-else>
                                <tr v-for="(administration, index) in filteredAdministrations" :key="index">
                                    <td>
                                        <span class="text-dark  fw-bold mb-1">{{administration.product_name}}</span>
                                    </td>
                                    <td class="text-muted" style="font-size:0.8rem;">
                                        {{ branches.find(b => String(b.value) === String(administration.branch_id))?.label ?? '—' }}
                                    </td>
                                    <td>{{administration.subfamily.name}}</td>
                                    <td>{{administration.quantity}}</td>
                                    <td>{{administration.unit.name}}</td>
                                    <td>{{administration.price}}</td>
                                     <td>{{ administration.operation ? administration.operation.name : '—' }}</td>
                                     <td>{{ administration.investment ? administration.investment.name : '—' }}</td>
                                     <td>{{ administration.user ? administration.user.name : '—' }}</td>
                                    <td class="text-end text-center">
                                        <!--begin::Update-->
                                        <button type="button" @click="openEdit(administration)" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                                            <span class="svg-icon svg-icon-3">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                                <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                            </svg>
                                            </span>
                                        </button>
                                        <!--end::Update-->
                                        <!--begin::Delete-->
                                        <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(administration.id)" class="btn btn-icon btn-active-light-primary w-30px h-30px">
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

 <div class="tab-pane fade" id="pill-tab-detalles" role="tabpanel" aria-labelledby="detalles-tab">
                        
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">
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
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{percentageAdministration}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th class="min-w-150px">Level 2</th>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="col-amount">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="col-month">{{month.label}}</th> 
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
          <td class="col-month col-amount" v-for="value in product.months">{{ value }}</td>
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
  <tr class="fw-bold" style="background:#eaf0f6;">
    <td colspan="6" class="text-end text-dark">Total por mes</td>
    <td v-for="(month, mIdx) in $page.props.months" class="col-month col-amount">
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
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalData1 }}</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ percentageAdministration }}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th>Nivel 2</th>
                                        <th>Nivel 3</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th class="col-amount">Monto Total</th>
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
                                              <td v-for="(value, mIdx) in item.months" class="col-month col-amount">{{ Number(value).toLocaleString('es-ES') }}</td>
                                            </tr>
                                          </template>
                                        </template>
                                      </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>


                    </div>


              <div class="tab-pane fade" id="pill-tab-detalles-compra" role="tabpanel" aria-labelledby="detalles-compra-tab">
                        
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">
                                        {{
                                          data2.reduce((sum, subfamily) =>
                                            sum + subfamily.products.reduce((pSum, product) => {
                                              const val = product.totalAmount ? parseFloat((product.totalAmount+'').replace(/\./g,'').replace(',','.')) : 0;
                                              return pSum + val;
                                            }, 0)
                                        , 0).toLocaleString('es-ES')
                                      }}
                                    </p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ percentageAdministration }}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="col-amount">Monto Total</th>
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
                                        <tr>
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

                    <!-- Resumen $/Ha -->
                    <div class="tab-pane fade" id="pill-tab-resumen" role="tabpanel" aria-labelledby="resumen-tab">
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Total $/Ha</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ data4.totalCostPerHa?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) || 0 }}</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Superficie Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ data4.totalSurface?.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || 0 }} ha</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Porc. Monto</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ percentageAdministration }}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>

                        <!-- Botones de exportación -->
                        <div class="mb-3 row g-2 align-items-end flex-wrap">
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="excelDataResumen"
                              :headers="[
                                { label: 'Nivel 3', key: 'nivel3' },
                                { label: 'Monto Total', key: 'monto_total', type: 'number' },
                                { label: '$/Ha', key: 'costo_por_ha', type: 'number' }
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Administracion-ResumenHa.xlsx"
                            />
                            <ExportPdfButton
                              :data="excelDataResumen"
                              :headers="[
                                { label: 'Nivel 3', key: 'nivel3' },
                                { label: 'Monto Total', key: 'monto_total', type: 'number' },
                                { label: '$/Ha', key: 'costo_por_ha', type: 'number' }
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Administracion-ResumenHa.pdf"
                            />
                          </div>
                        </div>

                        <!--begin::Table-->
                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th>Nivel 3</th>
                                        <th class="text-end">Monto Total</th>
                                        <th class="text-end">$/Ha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in data4.rows" :key="row.subfamily_id">
                                        <td class="cell-group">{{ row.subfamily_name }}</td>
                                        <td class="text-end">{{ row.total_cost?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                        <td class="text-end">{{ row.cost_per_ha?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="text-end">{{ data4.totalCost?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                        <td class="text-end">{{ data4.totalCostPerHa?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                
                </div>

             </div>   

             </div>       
    <CreateAdministrationModal @store="storeAdministration" :form="formMultiple" />
    <EditAdministrationModal @update="updateAdministration" :form="form" />
    </AppLayout>
</template>

<style>
/* Estilos de tablas centralizados en budget-tables.css */
</style>