<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateAgrochemicalModal from '@/Components/Agrochemicals/CreateAgrochemicalModal.vue';
import EditAgrochemicalModal from '@/Components/Agrochemicals/EditAgrochemicalModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
    agrochemicals: Object,
    data: Array,
    data2: Array,
    data3: Array,
    totalData1: String,
    totalData2: String,
    percentageAgrochemical: String, // Nuevo prop para el porcentaje correcto
    costCenters: { type: Array, default: () => [] }, // <-- AGREGAR ESTA LÍNEA
    varieties: {type: Array, default: () => [] },
    fruits: { type: Array, default: () => []}
});

var acum = ref(0);

const formMultiple = useForm({
    subfamily_id: '',
    cc: [],
    products: [
        {
            product_name: '',
            dose: '',
            price: '',
            mojamiento: '',
            unit_id: '',
            unit_id_price: '',
            dose_type_id: '',
            observations: '',
            months: []
        }
    ]
});

const form = useForm({
    product_name: '',
    dose: '',
    price: '',
    mojamiento: '',
    subfamily_id: '',
    unit_id: '',
    unit_id_price: '',
    dose_type_id: '',
    observations: '',
    cc: [],
    months: []
});


// Buscador global para la tabla de agroquímicos
const search = ref('');

// Computed para filtrar los agroquímicos según el texto de búsqueda
const filteredAgrochemicals = computed(() => {
  // Si no hay datos, retorna array vacío para evitar errores
  if (!props.agrochemicals || !props.agrochemicals.data) return [];
  // Si no hay búsqueda, retorna todos
  if (!search.value) return props.agrochemicals.data;
  const term = search.value.toLowerCase();
  return props.agrochemicals.data.filter(item => {
    // Protege contra campos nulos
    const name = item.product_name ? item.product_name.toLowerCase() : '';
    const subfamily = item.subfamily && item.subfamily.name ? item.subfamily.name.toLowerCase() : '';
    const unit = item.unit && item.unit.name ? item.unit.name.toLowerCase() : '';
    const dosetype = item.dosetype && item.dosetype.name ? item.dosetype.name.toLowerCase() : '';
    return (
      name.includes(term) ||
      subfamily.includes(term) ||
      unit.includes(term) ||
      dosetype.includes(term)
    );
  });
});

const title = 'Agroquimicos';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createAgrochemicalModal').modal('show');
}

const openEdit = (agrochemical) => {
    form.reset();
    form.id = agrochemical.id;
    form.product_name = agrochemical.product_name;
    form.dose = agrochemical.dose;
    form.price = agrochemical.price;
    form.mojamiento = agrochemical.mojamiento;
    form.subfamily_id = agrochemical.subfamily_id;
    form.unit_id = agrochemical.unit_id;
    form.unit_id_price = agrochemical.unit_id_price;
    form.dose_type_id = agrochemical.dose_type_id;
    form.observations = agrochemical.observations;
    form.cc = agrochemical.cc;
    form.months = agrochemical.months; 
    $('#editAgrochemicalModal').modal('show');
}

const storeAgrochemical = () => {
    formMultiple.post(route('agrochemicals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formMultiple.reset();
            $('#createAgrochemicalModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

const updateAgrochemical = () => {
    form.post(route('agrochemicals.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editAgrochemicalModal').modal('hide');
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
            router.delete(route('agrochemicals.delete', id), {
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

// Filtro por especie (fruta) y variedad
const selectedFruit = ref('');
const selectedVariety = ref('');
const selectedCostCenter = ref('');

// Variedades filtradas por fruta
const filteredVarieties = computed(() => {
  if (!selectedFruit.value) {
    // Si está seleccionado "Todas" en especie, también forzar "Todas" en variedad
    if (selectedVariety.value) selectedVariety.value = '';
    return props.varieties;
  }
  return props.varieties.filter(v => v.fruit_id == selectedFruit.value);
});

// Filtra los cost centers por fruit_id y variedad_id para la pestaña Detalles
// Además, asegura que cc.total esté correctamente calculado para el rowspan
const filteredData = computed(() => {
  let data = props.data;
  if (selectedCostCenter.value) {
    data = data.filter(cc => cc.id == selectedCostCenter.value);
  }
  if (selectedFruit.value) {
    const filteredVarieties = props.varieties.filter(v => v.fruit_id == selectedFruit.value);
    data = data.filter(cc => {
      const variety = props.varieties.find(v => v.id == cc.variety_id);
      return variety && variety.fruit_id == selectedFruit.value;
    });
    if (selectedVariety.value) {
      data = data.filter(cc => cc.variety_id == selectedVariety.value);
    }
  }
  // Aseguramos que cada cc tenga la propiedad total igual a la suma de productos de todas sus subfamilias
  return data.map(cc => {
    const total = cc.subfamilies.reduce((acc, subfamily) => acc + (subfamily.products ? subfamily.products.length : 0), 0);
    return { ...cc, total };
  });
});

// Monto total dinámico para la pestaña Detalles (de filteredData)
const totalFilteredData = computed(() => {
  let total = 0;
  filteredData.value.forEach(cc => {
    cc.subfamilies.forEach(subfamily => {
      subfamily.products.forEach(product => {
        // totalAmount puede venir como string con puntos, lo limpiamos
        let amount = typeof product.totalAmount === 'string' ? Number(product.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(product.totalAmount);
        if (!isNaN(amount)) total += amount;
      });
    });
  });
  // Formatear igual que antes
  return total.toLocaleString('es-ES', { maximumFractionDigits: 0 });
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
// Filtro por variedad para Gastos por Hectarea
// Además, asegura que cc.total esté correctamente calculado para el rowspan
const filteredDataGastos = computed(() => {
  let data = props.data3;
  if (selectedCostCenter.value) {
    data = data.filter(cc => String(cc.id) === String(selectedCostCenter.value));
  }
  if (selectedFruit.value) {
    data = data.filter(cc => {
      const variety = props.varieties.find(v => v.id == cc.variety_id);
      return variety && variety.fruit_id == selectedFruit.value;
    });
    if (selectedVarietyGastos.value) {
      data = data.filter(cc => cc.variety_id == selectedVarietyGastos.value);
    }
  }
  // Aseguramos que cada cc tenga la propiedad total igual a la suma de productos de todas sus subfamilias
  return data.map(cc => {
    const total = cc.subfamilies.reduce((acc, subfamily) => acc + (subfamily.products ? subfamily.products.length : 0), 0);
    return { ...cc, total };
  });
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
  return total.toLocaleString('es-ES', { maximumFractionDigits: 0 });
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
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Agroquimicos</h5>
                    </div>
                    <!-- Botones de exportar y Nuevo solo deben ir en la pestaña Edición -->
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
                        <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                          <SearchInput
                            v-model="search"
                            placeholder="Buscar por nombre, nivel 2, unidad, tipo dosis..."
                          />
                          <div class="d-flex align-items-center gap-1">
                            <ExportExcelButton
                              :data="agrochemicals.data"
                              :headers="[
                                { label: 'Nombre', key: 'product_name' },
                                { label: 'SubFamilia', key: 'subfamily.name' },
                                { label: 'Dosis', key: 'dose' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Tipo Dosis', key: 'dosetype.name' },
                                { label: 'Mojamiento', key: 'mojamiento' },
                                { label: 'Precio', key: 'price' }
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos.xlsx"
                            />
                            <ExportPdfButton
                              :data="agrochemicals.data"
                              :headers="[
                                { label: 'Nombre', key: 'product_name' },
                                { label: 'SubFamilia', key: 'subfamily.name' },
                                { label: 'Dosis', key: 'dose' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Tipo Dosis', key: 'dosetype.name' },
                                { label: 'Mojamiento', key: 'mojamiento' },
                                { label: 'Precio', key: 'price' }
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos.pdf"
                            />
                            <button class="btn btn-falcon-default btn-sm ms-1" type="button" @click="openAdd()">
                              <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                              <span class="d-none d-sm-inline-block ms-2">Nuevo</span>
                            </button>
                          </div>
                        </div>
                        <Table sticky-header :id="'agrochemicals'" :total="filteredAgrochemicals.length" :links="agrochemicals.links">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th scope="col" width="min-w-100px">Nombre</th>
                                <th scope="col" width="min-w-100px">SubFamilia</th>
                                <th scope="col" width="min-w-100px">Dosis</th>
                                <th scope="col" width="min-w-100px">Unidad</th>
                                <th scope="col" width="min-w-100px">Tipo Dosis</th>
                                <th scope="col" width="min-w-100px">Mojamiento</th>
                                <th scope="col" width="min-w-100px">Precio</th>
                                <th scope="col" width="min-w-150px" class="text-end text-center">Acciones</th>
                                <!--end::Table row-->
                            </template>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <template #body>
                                <template v-if="filteredAgrochemicals.length == 0">
                                    <Empty colspan="7" />
                                </template>
                                <template v-else>
                                    <tr v-for="(agrochemical, index) in filteredAgrochemicals" :key="index">
                                        <td>
                                            <span class="text-dark  fw-bold mb-1">{{agrochemical.product_name}}</span>
                                        </td>
                                        <td>{{agrochemical.subfamily.name}}</td>
                                        <td>{{agrochemical.dose}}</td>
                                        <td>{{agrochemical.unit.name}}</td>
                                        <td>{{agrochemical.dosetype.name}}</td>
                                        <td>{{agrochemical.mojamiento}}</td>
                                        <td>{{agrochemical.price}}</td>
                                        <td class="text-end text-center">
                                            <!--begin::Update-->
                                            <button type="button" @click="openEdit(agrochemical)" v-tooltip="'Editar'" class="btn btn-link me-3 p-0">
                                                <span class="text-500 fas fa-edit"></span>
                                            </button>
                                            <!--end::Update-->
                                            <!--begin::Delete-->
                                            <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(agrochemical.id)" class="btn btn-link p-0">
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
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalFilteredData }}</p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{props.percentageAgrochemical}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>
                        <!-- Select de especie (fruta) y variedades, lado a lado -->
                        <div class="mb-3 row g-2 align-items-end flex-wrap">
                          <div class="col-auto">
                            <label for="costCenterSelect" class="form-label">Filtrar por centro de costo:</label>
                            <select id="costCenterSelect" v-model="selectedCostCenter" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="cc in props.costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="fruitSelect" class="form-label">Filtrar por especie:</label>
                            <select id="fruitSelect" v-model="selectedFruit" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="fruit in props.fruits" :key="fruit.id" :value="fruit.id">
                                {{ fruit.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="varietySelect" class="form-label">Filtrar por variedad:</label>
                            <select id="varietySelect" v-model="selectedVariety" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;" :disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarieties" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="filteredData.flatMap(cc => cc.subfamilies.flatMap(subfamily => subfamily.products.map(product => {
                                const parseSpanishNumber = val => {
                                  if (typeof val === 'number') return val;
                                  if (typeof val !== 'string') return undefined;
                                  const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                  if (cleaned.trim() === '') return undefined;
                                  const num = Number(cleaned);
                                  return isNaN(num) ? undefined : num;
                                };
                                const row = {
                                  cc: cc.name,
                                  subfamily: subfamily.name,
                                  producto: product.name,
                                  cantidad: parseSpanishNumber(product.totalQuantity),
                                  unidad: product.unit,
                                  monto: parseSpanishNumber(product.totalAmount)
                                };
                                ($page.props.months || []).forEach((month, idx) => {
                                  row[month.label] = parseSpanishNumber(product.months && product.months[idx]);
                                });
                                return row;
                              })))"
                              :headers="[
                                { label: 'CC', key: 'cc' },
                                { label: 'Subfamilia', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos-Detalles.xlsx"
                            />
                            <ExportPdfButton
                              :data="filteredData.flatMap(cc => cc.subfamilies.flatMap(subfamily => subfamily.products.map(product => {
                                const parseSpanishNumber = val => {
                                  if (typeof val === 'number') return val;
                                  if (typeof val !== 'string') return 0;
                                  const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                  const num = Number(cleaned);
                                  return isNaN(num) ? 0 : num;
                                };
                                return {
                                  cc: cc.name,
                                  subfamily: subfamily.name,
                                  producto: product.name,
                                  cantidad: parseSpanishNumber(product.totalQuantity),
                                  unidad: product.unit,
                                  monto: parseSpanishNumber(product.totalAmount),
                                  ...Object.fromEntries(($page.props.months || []).map((month, idx) => [month.label, product.months && product.months[idx] !== undefined && product.months[idx] !== '' ? parseSpanishNumber(product.months[idx]) : '']))
                                }
                              })))"
                              :headers="[
                                { label: 'CC', key: 'cc' },
                                { label: 'Subfamilia', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos-Detalles.pdf"
                            />
                          </div>
                        </div>
                       
                        <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <!--begin::Table head-->
                                <thead>
                                    <tr>
                                        <th class="min-w-150px">CC</th>
                                        <th>Subfamilia</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="text-dark text-end">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="text-primary">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                  <template v-for="cc in filteredData">
                                    <template v-for="(subfamily, index2) in cc.subfamilies">
                                      <tr>
                                        <td v-if="index2 == 0" :rowspan="cc.total" style="vertical-align:top">{{ cc.name }}</td>
                                        <td style="vertical-align:top;" :rowspan="subfamily.products.length">{{ subfamily.name }}</td>
                                        <td>{{ subfamily.products[0].name }}</td>
                                        <td>{{ subfamily.products[0].totalQuantity }}</td>
                                        <td>{{ subfamily.products[0].unit }}</td>
                                        <td>{{ subfamily.products[0].totalAmount }}</td>
                                        <td class="bg-opacity-5 table-primary" v-for="value in subfamily.products[0].months">{{ value }}</td>
                                      </tr>
                                      <template v-for="(product, index3) in subfamily.products">
                                        <tr v-if="index3 > 0">
                                          <!-- Aquí NO repetimos las columnas de centro de costo ni subfamilia -->
                                          <td>{{ product.name }}</td>
                                          <td>{{ product.totalQuantity }}</td>
                                          <td>{{ product.unit }}</td>
                                          <td>{{ product.totalAmount }}</td>
                                          <td class="bg-opacity-5 table-primary" v-for="value in product.months">{{ value }}</td>
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
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalFilteredDataGastos }}</p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{props.percentageAgrochemical}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>

                        <!-- Select de especie (fruta) y variedades para Gastos por Hectarea, lado a lado, y botones de exportar -->
                        <div class="mb-3 row g-2 align-items-end flex-wrap">
                           <div class="col-auto">
                            <label for="costCenterSelect" class="form-label">Filtrar por Cc:</label>
                            <select id="costCenterSelect" v-model="selectedCostCenter" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="cc in props.costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="fruitSelectGastos" class="form-label">Filtrar por especie:</label>
                            <select id="fruitSelectGastos" v-model="selectedFruit" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="fruit in props.fruits" :key="fruit.id" :value="fruit.id">
                                {{ fruit.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="varietySelectGastos" class="form-label">Filtrar por variedad:</label>
                            <select id="varietySelectGastos" v-model="selectedVarietyGastos" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;" :disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarietiesGastos" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="filteredDataGastos.flatMap(cc => cc.subfamilies.flatMap(subfamily => subfamily.products.map(product => {
                                const parseSpanishNumber = val => {
                                  if (typeof val === 'number') return val;
                                  if (typeof val !== 'string') return undefined;
                                  const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                  if (cleaned.trim() === '') return undefined;
                                  const num = Number(cleaned);
                                  return isNaN(num) ? undefined : num;
                                };
                                const row = {
                                  cc: cc.name,
                                  subfamily: subfamily.name,
                                  producto: product.name,
                                  cantidad: parseSpanishNumber(product.totalQuantity),
                                  unidad: product.unit,
                                  monto: parseSpanishNumber(product.totalAmount)
                                };
                                ($page.props.months || []).forEach((month, idx) => {
                                  row[month.label] = parseSpanishNumber(product.months && product.months[idx]);
                                });
                                return row;
                              })))"
                              :headers="[
                                { label: 'CC', key: 'cc' },
                                { label: 'Subfamilia', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos-GastosPorHectarea.xlsx"
                            />
                            <ExportPdfButton
                              :data="filteredDataGastos.flatMap(cc => cc.subfamilies.flatMap(subfamily => subfamily.products.map(product => {
                                const parseSpanishNumber = val => {
                                  if (typeof val === 'number') return val;
                                  if (typeof val !== 'string') return 0;
                                  const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                  const num = Number(cleaned);
                                  return isNaN(num) ? 0 : num;
                                };
                                return {
                                  cc: cc.name,
                                  subfamily: subfamily.name,
                                  producto: product.name,
                                  cantidad: parseSpanishNumber(product.totalQuantity),
                                  unidad: product.unit,
                                  monto: parseSpanishNumber(product.totalAmount),
                                  ...Object.fromEntries(($page.props.months || []).map((month, idx) => [month.label, product.months && product.months[idx] !== undefined && product.months[idx] !== '' ? parseSpanishNumber(product.months[idx]) : '']))
                                }
                              })))"
                              :headers="[
                                { label: 'CC', key: 'cc' },
                                { label: 'Subfamilia', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Agroquimicos-GastosPorHectarea.pdf"
                            />
                          </div>
                        </div>
                        <!--begin::Table-->
                        <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <!--begin::Table head-->
                                <thead>
                                    <tr>
                                        <th class="min-w-150px">CC</th>
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
                                  <template v-for="cc in filteredDataGastos">
                                    <template v-for="(subfamily, index2) in cc.subfamilies">
                                      <tr>
                                        <td v-if="index2 == 0" :rowspan="cc.total" style="vertical-align:top">{{ cc.name }}</td>
                                        <td style="vertical-align:top;" :rowspan="subfamily.products.length">{{ subfamily.name }}</td>
                                        <td>{{ subfamily.products[0].name }}</td>
                                        <td>{{ subfamily.products[0].totalQuantity }}</td>
                                        <td>{{ subfamily.products[0].unit }}</td>
                                        <td>{{ subfamily.products[0].totalAmount }}</td>
                                        <td class="bg-opacity-5 table-primary" v-for="value in subfamily.products[0].months">{{ value }}</td>
                                      </tr>
                                      <template v-for="(product, index3) in subfamily.products">
                                        <tr v-if="index3 > 0">
                                          <!-- Aquí NO repetimos las columnas de centro de costo ni subfamilia -->
                                          <td>{{ product.name }}</td>
                                          <td>{{ product.totalQuantity }}</td>
                                          <td>{{ product.unit }}</td>
                                          <td>{{ product.totalAmount }}</td>
                                          <td class="bg-opacity-5 table-primary" v-for="value in product.months">{{ value }}</td>
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
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{totalData2}}</p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{props.percentageAgrochemical}}%</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col d-flex justify-content-end align-items-end gap-1">
                              <ExportExcelButton
                                :data="data2.flatMap(subfamily => subfamily.products.map(product => {
                                  const parseSpanishNumber = val => {
                                    if (typeof val === 'number') return val;
                                    if (typeof val !== 'string') return undefined;
                                    const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                    if (cleaned.trim() === '') return undefined;
                                    const num = Number(cleaned);
                                    return isNaN(num) ? undefined : num;
                                  };
                                  return {
                                    subfamily: subfamily.name,
                                    producto: product.name,
                                    cantidad: parseSpanishNumber(product.totalQuantity),
                                    unidad: product.unit,
                                    monto: parseSpanishNumber(product.totalAmount)
                                  };
                                }))"
                                :headers="[
                                  { label: 'Subfamilia', key: 'subfamily' },
                                  { label: 'Producto', key: 'producto' },
                                  { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                  { label: 'Un', key: 'unidad' },
                                  { label: 'Monto Total', key: 'monto', type: 'number' }
                                ]"
                                class="btn btn-success btn-md d-flex align-items-center p-0"
                                filename="Agroquimicos-DetalleCompra.xlsx"
                              />
                              <ExportPdfButton
                                :data="data2.flatMap(subfamily => subfamily.products.map(product => {
                                  const parseSpanishNumber = val => {
                                    if (typeof val === 'number') return val;
                                    if (typeof val !== 'string') return 0;
                                    const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
                                    const num = Number(cleaned);
                                    return isNaN(num) ? 0 : num;
                                  };
                                  return {
                                    subfamily: subfamily.name,
                                    producto: product.name,
                                    cantidad: parseSpanishNumber(product.totalQuantity),
                                    unidad: product.unit,
                                    monto: parseSpanishNumber(product.totalAmount)
                                  };
                                }))"
                                :headers="[
                                  { label: 'Subfamilia', key: 'subfamily' },
                                  { label: 'Producto', key: 'producto' },
                                  { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                  { label: 'Un', key: 'unidad' },
                                  { label: 'Monto Total', key: 'monto', type: 'number' }
                                ]"
                                class="btn btn-danger btn-md d-flex align-items-center p-0"
                                filename="Agroquimicos-DetalleCompra.pdf"
                              />
                            </div>
                        </div>

                        <!--begin::Table-->
                        <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0 agrochem-details">
                                <!--begin::Table head-->
                                <thead>
                                    <tr>
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
                                            <td  style="vertical-align:top;" :rowspan="subfamily.products.length + 1">{{subfamily.name}}</td>
                                            <td>{{subfamily.products[0].name}}</td>
                                            <td>{{subfamily.products[0].totalQuantity}}</td>
                                            <td>{{subfamily.products[0].unit}}</td>
                                            <td class="text-dark text-end">{{subfamily.products[0].totalAmount}}</td>
                                        </tr>

                                        <template v-for="(product, index3) in subfamily.products">
                                            <tr v-if="index3 > 0">
                                                <td>{{product.name}}</td>
                                                <td>{{product.totalQuantity}}</td>
                                                <td>{{product.unit}}</td>
                                                <td class="text-dark text-end">{{product.totalAmount}}</td>
                                            </tr>
                                        </template>
                                        <!-- Subtotal row -->
                                        <tr class="table-secondary">
                                            <td colspan="3" class="text-end fw-bold">Subtotal</td>
                                            <td colspan="2" class="fw-bold text-dark text-end">
                                              {{ subfamily.products.reduce((acc, p) => {
                                                let amount = typeof p.totalAmount === 'string' ? Number(p.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(p.totalAmount);
                                                return !isNaN(amount) ? acc + amount : acc;
                                              }, 0).toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                  
        <CreateAgrochemicalModal @store="storeAgrochemical" :form="formMultiple" />
        <EditAgrochemicalModal @update="updateAgrochemical" :form="form" />
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

.small-card {
  max-width: 170px;
  min-width: 110px;
  font-size: 0.85rem;
}
.small-card-title {
  font-size: 0.85rem !important;
  line-height: 1.1;
}
.small-card-number {
  font-size: 0.95rem !important;
  line-height: 1.1;
}