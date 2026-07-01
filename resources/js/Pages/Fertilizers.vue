<script setup>
import { computed, ref } from 'vue';
import { useSeasonLock } from '@/Composables/useSeasonLock';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import BudgetCCNavBar from '@/Components/Budgets/BudgetCCNavBar.vue';
import CreateFertilizerModal from '@/Components/Fertilizers/CreateFertilizerModal.vue';
import EditFertilizerModal from '@/Components/Fertilizers/EditFertilizerModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';

const isLocked = useSeasonLock();

const props = defineProps({
    fertilizers: Object,
    data: Array,
    data2: Array,
    data3: Array,
    totalData1: String,
    totalData2:String,
    percentage: String,
     costCenters: { type: Array, default: () => [] }, // <-- AGREGAR ESTA LÍNEA
    companyReasons: { type: Array, default: () => [] },
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
const selectedCostCenter = ref('');
const selectedCompanyReason = ref('');
const selectedProduct = ref('');
const hideCc = ref(false);

const filteredCostCenters = computed(() => {
  if (!selectedCompanyReason.value) return props.costCenters;
  return props.costCenters.filter(cc => String(cc.company_reason_id) === String(selectedCompanyReason.value));
});

const onCompanyReasonChange = () => {
  if (selectedCostCenter.value) {
    const stillValid = filteredCostCenters.value.some(cc => String(cc.value) === String(selectedCostCenter.value));
    if (!stillValid) selectedCostCenter.value = '';
  }
};

// Opciones únicas de productos extraídas de data y data3
const productOptions = computed(() => {
  const names = new Set();
  const addProducts = (list) => {
    (list || []).forEach(cc => {
      (cc.subfamilies || []).forEach(sf => {
        (sf.products || []).forEach(p => { if (p.name) names.add(p.name); });
      });
    });
  };
  addProducts(props.data);
  addProducts(props.data3);
  return Array.from(names).sort((a, b) => a.localeCompare(b, 'es'));
});

// Buscador global para la tabla de fertilizantes
const search = ref('');

// Toggle global por columna (Edición tab)
const expandAllMonths = ref(false);
const expandAllCc = ref(false);
const MONTH_PREVIEW = 3;
const CC_PREVIEW = 2;

// Variedades filtradas por fruta
const filteredVarieties = computed(() => {
  if (!selectedFruit.value) {
    // Si está seleccionado "Todas" en especie, también forzar "Todas" en variedad
    if (selectedVariety.value) selectedVariety.value = '';
    return props.varieties;
  }
  return props.varieties.filter(v => v.fruit_id == selectedFruit.value);
});

// Computed para filtrar los fertilizantes según el texto de búsqueda
const filteredFertilizers = computed(() => {
  if (!props.fertilizers || !props.fertilizers.data) return [];
  if (!search.value) return props.fertilizers.data;
  const term = search.value.toLowerCase();
  return props.fertilizers.data.filter(item => {
    const name = item.product_name ? item.product_name.toLowerCase() : '';
    const subfamily = item.subfamily && item.subfamily.name ? item.subfamily.name.toLowerCase() : '';
    const unit = item.unit && item.unit.name ? item.unit.name.toLowerCase() : '';
    const unit2 = item.unit2 && item.unit2.name ? item.unit2.name.toLowerCase() : '';
    return (
      name.includes(term) ||
      subfamily.includes(term) ||
      unit.includes(term) ||
      unit2.includes(term)
    );
  });
});

const edicionTotals = computed(() => {
  const items = filteredFertilizers.value;
  return {
    count: items.length,
    totalPrice: items.reduce((sum, item) => sum + (Number(item.price) || 0), 0),
  };
});

// Filtra los cost centers por fruit_id y variedad_id para la pestaña Detalles
// Además, asegura que cc.total esté correctamente calculado para el rowspan
const filteredData = computed(() => {
  let data = props.data;
  if (selectedCompanyReason.value) {
    data = data.filter(cc => String(cc.company_reason_id) === String(selectedCompanyReason.value));
  }
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
  // Filtro por producto
  if (selectedProduct.value) {
    data = data.map(cc => ({
      ...cc,
      subfamilies: (cc.subfamilies || []).map(sf => ({
        ...sf,
        products: (sf.products || []).filter(p => p.name === selectedProduct.value)
      })).filter(sf => sf.products.length > 0)
    })).filter(cc => (cc.subfamilies || []).length > 0);
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
        let amount = typeof product.totalAmount === 'string' ? Number(product.totalAmount.replace(/\./g, '').replace(/,/g, '.')) : Number(product.totalAmount);
        if (!isNaN(amount)) total += amount;
      });
    });
  });
  return total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});

// Sumatoria mensual para la pestaña Detalles
const monthlyTotalsDetails = computed(() => {
  const totals = new Array(12).fill(0);
  filteredData.value.forEach(cc => {
    cc.subfamilies.forEach(subfamily => {
      subfamily.products.forEach(product => {
        if (product.months) {
          product.months.forEach((val, i) => {
            let num = typeof val === 'string' ? Number(val.replace(/\./g, '').replace(/,/g, '.')) : Number(val);
            if (!isNaN(num)) totals[i] += num;
          });
        }
      });
    });
  });
  return totals.map(t => t.toLocaleString('es-ES', { maximumFractionDigits: 0 }));
});

// Helper para parsear números formateados en español
const parseNum = (val) => {
  if (typeof val === 'number') return val;
  if (typeof val !== 'string') return 0;
  const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
  const num = Number(cleaned);
  return isNaN(num) ? 0 : num;
};

// Vista consolidada: agrupa por subfamilia y suma productos con mismo nombre
const consolidatedData = computed(() => {
  const sfMap = {};
  filteredData.value.forEach(cc => {
    cc.subfamilies.forEach(sf => {
      if (!sfMap[sf.id]) {
        sfMap[sf.id] = { id: sf.id, name: sf.name, productsMap: {} };
      }
      sf.products.forEach(p => {
        const key = p.name + '|' + (p.unit || '');
        if (!sfMap[sf.id].productsMap[key]) {
          sfMap[sf.id].productsMap[key] = {
            name: p.name, unit: p.unit, totalQuantity: 0, totalAmount: 0, months: new Array(12).fill(0)
          };
        }
        const target = sfMap[sf.id].productsMap[key];
        target.totalQuantity += parseNum(p.totalQuantity);
        target.totalAmount += parseNum(p.totalAmount);
        if (p.months) { p.months.forEach((val, i) => { target.months[i] += parseNum(val); }); }
      });
    });
  });
  return Object.values(sfMap).map(sf => {
    const products = Object.values(sf.productsMap).map(p => ({
      name: p.name, unit: p.unit,
      totalQuantity: p.totalQuantity.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
      totalAmount: p.totalAmount.toLocaleString('es-ES', { maximumFractionDigits: 0 }),
      months: p.months.map(m => m.toLocaleString('es-ES', { maximumFractionDigits: 0 }))
    }));
    return { id: sf.id, name: sf.name, products, total: products.length };
  }).filter(sf => sf.total > 0);
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
  if (selectedCompanyReason.value) {
    data = data.filter(cc => String(cc.company_reason_id) === String(selectedCompanyReason.value));
  }
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
  // Filtro por producto
  if (selectedProduct.value) {
    data = data.map(cc => ({
      ...cc,
      subfamilies: (cc.subfamilies || []).map(sf => ({
        ...sf,
        products: (sf.products || []).filter(p => p.name === selectedProduct.value)
      })).filter(sf => sf.products.length > 0)
    })).filter(cc => (cc.subfamilies || []).length > 0);
  }
  // Aseguramos que cada cc tenga la propiedad total
  return data.map(cc => {
    const total = cc.subfamilies.reduce((acc, sf) => acc + (sf.products ? sf.products.length : 0), 0);
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
  return total.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});

const hideCcGastos = ref(false);
const consolidatedDataGastos = computed(() => {
  const sfMap = {};
  filteredDataGastos.value.forEach(cc => {
    cc.subfamilies.forEach(sf => {
      if (!sfMap[sf.id]) {
        sfMap[sf.id] = { id: sf.id, name: sf.name, productsMap: {} };
      }
      sf.products.forEach(p => {
        const key = p.name + '|' + (p.unit || '');
        if (!sfMap[sf.id].productsMap[key]) {
          sfMap[sf.id].productsMap[key] = { name: p.name, unit: p.unit, totalQuantity: 0, totalAmount: 0, months: new Array(12).fill(0) };
        }
        const target = sfMap[sf.id].productsMap[key];
        target.totalQuantity += parseNum(p.totalQuantity);
        target.totalAmount += parseNum(p.totalAmount);
        if (p.months) { p.months.forEach((val, i) => { target.months[i] += parseNum(val); }); }
      });
    });
  });
  return Object.values(sfMap).map(sf => {
    const products = Object.values(sf.productsMap).map(p => ({
      name: p.name, unit: p.unit,
      totalQuantity: p.totalQuantity.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
      totalAmount: p.totalAmount.toLocaleString('es-ES', { maximumFractionDigits: 0 }),
      months: p.months.map(m => m.toLocaleString('es-ES', { maximumFractionDigits: 0 }))
    }));
    return { id: sf.id, name: sf.name, products, total: products.length };
  }).filter(sf => sf.total > 0);
});


// Filtro para Detalle de compra (independiente de los selectores globales)
const filteredDataCompra = computed(() => props.data2);

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
    form.months = (fertilizer.months || []).map(m => parseInt(m));
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

        <BudgetCCNavBar />
       
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-seedling text-primary me-2"></i>Fertilizantes</h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                      <div id="table-purchases-replace-element">
                        <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()" :disabled="isLocked"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
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
                         <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                          <SearchInput
                            v-model="search"
                            placeholder="Buscar por nombre, nivel 2, unidad..."
                          />
                          <div class="d-flex align-items-center gap-1">
                            <ExportExcelButton
                              :data="fertilizers.data"
                              :headers="[
                                { label: 'Nombre', key: 'product_name' },
                                { label: 'Nivel 3', key: 'subfamily.name' },
                                { label: 'Dosis', key: 'dose', type: 'number' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Precio', key: 'price', type: 'number' },
                                { label: 'Unidad de $', key: 'unit2.name' }
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Fertilizantes.xlsx"
                            />
                            <ExportPdfButton
                              :data="fertilizers.data"
                              :headers="[
                                { label: 'Nombre', key: 'product_name' },
                                { label: 'Nivel 3', key: 'subfamily.name' },
                                { label: 'Dosis', key: 'dose' },
                                { label: 'Unidad', key: 'unit.name' },
                                { label: 'Precio', key: 'price' },
                                { label: 'Unidad de $', key: 'unit2.name' }
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Fertilizantes.pdf"
                            />
                            
                          </div>
                        </div>
                        <div class="table-responsive budget-table-wrapper mt-1">
                        <Table :id="'fertilizers'" :total="filteredFertilizers.length" :links="fertilizers.links">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th width="min-w-50px">#</th>
                                <th width="min-w-100px">Nombre</th>
                                <th width="min-w-100px">Nivel 3</th>
                                <th width="min-w-100px">Dosis</th>
                                <th width="min-w-100px">Unidad dosis</th>
                                <th width="min-w-100px">Precio</th>
                                <th width="min-w-100px">Unidad de $</th>
                                <th width="min-w-150px" style="white-space:nowrap">
                                    Meses
                                    <span @click="expandAllMonths = !expandAllMonths" class="badge ms-1" :class="expandAllMonths ? 'bg-primary' : 'bg-secondary'" style="cursor:pointer;font-size:0.65rem;" v-tooltip="expandAllMonths ? 'Colapsar meses' : 'Expandir meses'">
                                        {{ expandAllMonths ? '−' : '+' }}
                                    </span>
                                </th>
                                <th width="min-w-150px" style="white-space:nowrap">
                                    Centros de Costo
                                    <span @click="expandAllCc = !expandAllCc" class="badge ms-1" :class="expandAllCc ? 'bg-primary' : 'bg-secondary'" style="cursor:pointer;font-size:0.65rem;" v-tooltip="expandAllCc ? 'Colapsar CC' : 'Expandir CC'">
                                        {{ expandAllCc ? '−' : '+' }}
                                    </span>
                                </th>
                                <th width="min-w-100px">Digitado por</th>
                                <th width="min-w-150px" class="text-end text-center">Acciones</th>
                                <!--end::Table row-->
                            </template>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <template #body>
                                <template v-if="filteredFertilizers.length === 0">
                                    <Empty colspan="11" />
                                </template>
                                <template v-else>
                                    <tr v-for="(fertilizer, index) in filteredFertilizers" :key="index">
                                        <td class="text-muted">{{fertilizer.id}}</td>
                                        <td>
                                            <span class="text-dark  fw-bold mb-1">{{fertilizer.product_name}}</span>
                                        </td>
                                        <td>{{fertilizer.subfamily.name}}</td>
                                        <td>{{fertilizer.dose}}</td>
                                        <td>{{fertilizer.unit.name}}</td>
                                        <td class="text-center">{{ Number(fertilizer.price).toLocaleString('es-CL') }}</td>
                                        <td>{{fertilizer.unit2.name}}</td>
                                        <td>
                                            <template v-if="fertilizer.months && fertilizer.months.length">
                                                {{ (expandAllMonths ? fertilizer.months : fertilizer.months.slice(0, MONTH_PREVIEW))
                                                    .map(mId => ($page.props.months || []).find(x => String(x.value) === String(mId))?.label || mId)
                                                    .join(', ') }}<span v-if="!expandAllMonths && fertilizer.months.length > MONTH_PREVIEW" class="text-muted"> …</span>
                                            </template>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>
                                            <template v-if="fertilizer.cc && fertilizer.cc.length">
                                                {{ (expandAllCc ? fertilizer.cc : fertilizer.cc.slice(0, CC_PREVIEW))
                                                    .map(ccId => (props.costCenters.find(c => String(c.value) === String(ccId)) || {}).label || ccId)
                                                    .join(', ') }}<span v-if="!expandAllCc && fertilizer.cc.length > CC_PREVIEW" class="text-muted"> …</span>
                                            </template>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td>{{ fertilizer.user ? fertilizer.user.name : '—' }}</td>
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
                            <template #footer>
                                <tr class="fw-bold table-light">
                                    <td colspan="2" class="text-end text-muted" style="font-size:0.8rem;">{{ edicionTotals.count }} registro{{ edicionTotals.count !== 1 ? 's' : '' }}</td>
                                    <td colspan="3"></td>
                                    <td style="text-align:center !important;">{{ edicionTotals.totalPrice.toLocaleString('es-CL') }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </template>
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
                                    <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalFilteredData }}</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="card h-100 p-1 small-card">
                              <div class="card-body d-flex align-items-center justify-content-center py-2 px-2">
                                <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{percentage}}%</strong> del presupuesto</p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Select de especie (fruta) y variedades, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                          <div class="col-auto" v-if="props.companyReasons && props.companyReasons.length > 0">
                            <label for="companyReasonSelect" class="form-label">Filtrar por razón social:</label>
                            <select id="companyReasonSelect" v-model="selectedCompanyReason" @change="onCompanyReasonChange" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="cr in props.companyReasons" :key="cr.value" :value="cr.value">{{ cr.label }}</option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="costCenterSelect" class="form-label">Filtrar por centro de costo:</label>
                            <select id="costCenterSelect" v-model="selectedCostCenter" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="cc in filteredCostCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
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
                            <select id="varietySelect" v-model="selectedVariety" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;":disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarieties" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="productSelect" class="form-label">Filtrar por producto:</label>
                            <select id="productSelect" v-model="selectedProduct" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="product in productOptions" :key="product" :value="product">
                                {{ product }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto d-flex align-items-end">
                            <button type="button" class="btn btn-sm d-flex align-items-center gap-1" :class="hideCc ? 'btn-falcon-primary' : 'btn-falcon-default'" @click="hideCc = !hideCc" v-tooltip="'Agrupar productos por Nivel 3, omitiendo centros de costo'">
                              <i class="fas fa-layer-group fa-sm"></i>
                              <span class="d-none d-md-inline">Agrupar</span>
                            </button>
                          </div>
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="(hideCc ? consolidatedData : filteredData.flatMap(cc => cc.subfamilies.map(sf => ({ ccName: cc.name, ...sf })))).flatMap(item => {
                                const isConsolidated = hideCc;
                                const sfName = item.name;
                                const ccName = isConsolidated ? '' : item.ccName;
                                return (item.products || []).map(product => {
                                  const pn = val => { if (typeof val === 'number') return val; if (typeof val !== 'string') return undefined; const c = val.replace(/\./g, '').replace(/,/g, '.'); return c.trim() === '' ? undefined : (isNaN(Number(c)) ? undefined : Number(c)); };
                                  const row = {};
                                  if (!isConsolidated) row['CC'] = ccName;
                                  row['Nivel 3'] = sfName;
                                  row['Producto'] = product.name;
                                  row['Cantidad Total'] = pn(product.totalQuantity);
                                  row['Un'] = product.unit;
                                  row['Monto Total'] = pn(product.totalAmount);
                                  ($page.props.months || []).forEach((month, idx) => { row[month.label] = pn(product.months && product.months[idx]); });
                                  return row;
                                });
                              })"
                              :headers="[
                                ...(hideCc ? [] : [{ label: 'CC', key: 'CC' }]),
                                { label: 'Nivel 3', key: 'Nivel 3' },
                                { label: 'Producto', key: 'Producto' },
                                { label: 'Cantidad Total', key: 'Cantidad Total', type: 'number' },
                                { label: 'Un', key: 'Un' },
                                { label: 'Monto Total', key: 'Monto Total', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Fertilizantes-Detalles.xlsx"
                            />
                          </div>
                        </div>
                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <!--begin::Table head-->
                                <thead>
                                    <tr>
                                        <th v-if="!hideCc" class="min-w-150px">Centro de costo</th>
                                        <th>Nivel 3</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="col-amount">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="col-month">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <tbody v-if="!hideCc">
                                    <template v-for="cc in filteredData">
                                        <template v-for="(subfamily, index2) in cc.subfamilies">
                                            <tr>
                                                <td v-if="index2 == 0" :rowspan="cc.total" class="cell-group">{{cc.name}}</td>
                                                <td :rowspan="subfamily.products.length" class="cell-group">{{subfamily.name}}</td>
                                                <td>{{subfamily.products[0].name}}</td>
                                                <td>{{subfamily.products[0].totalQuantity}}</td>
                                                <td>{{subfamily.products[0].unit}}</td>
                                                <td>{{subfamily.products[0].totalAmount}}</td>
                                                <td class="col-month col-amount" v-for="value in subfamily.products[0].months">{{value}}</td>
                                            </tr>
                                            <template v-for="(product, index3) in subfamily.products">
                                                <tr v-if="index3 > 0">
                                                    <td>{{product.name}}</td>
                                                    <td>{{product.totalQuantity}}</td>
                                                    <td>{{product.unit}}</td>
                                                    <td>{{product.totalAmount}}</td>
                                                    <td class="col-month col-amount" v-for="value in product.months">{{value}}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </template>
                                </tbody>
                                <tbody v-else>
                                  <template v-for="sf in consolidatedData" :key="sf.id">
                                    <tr>
                                      <td :rowspan="sf.total" class="cell-group">{{ sf.name }}</td>
                                      <td>{{ sf.products[0].name }}</td>
                                      <td>{{ sf.products[0].totalQuantity }}</td>
                                      <td>{{ sf.products[0].unit }}</td>
                                      <td>{{ sf.products[0].totalAmount }}</td>
                                      <td class="col-month col-amount" v-for="value in sf.products[0].months">{{ value }}</td>
                                    </tr>
                                    <template v-for="(product, idx) in sf.products" :key="idx">
                                      <tr v-if="idx > 0">
                                        <td>{{ product.name }}</td>
                                        <td>{{ product.totalQuantity }}</td>
                                        <td>{{ product.unit }}</td>
                                        <td>{{ product.totalAmount }}</td>
                                        <td class="col-month col-amount" v-for="value in product.months">{{ value }}</td>
                                      </tr>
                                    </template>
                                  </template>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td :colspan="hideCc ? 4 : 5" class="text-end">Total:</td>
                                        <td>{{ totalFilteredData }}</td>
                                        <td class="col-month col-amount" v-for="(val, idx) in monthlyTotalsDetails" :key="idx">{{ val }}</td>
                                    </tr>
                                </tfoot>
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
                          <div class="col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="card h-100 p-1 small-card">
                              <div class="card-body d-flex align-items-center justify-content-center py-2 px-2">
                                <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{percentage}}%</strong> del presupuesto</p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Select de especie (fruta) y variedades para Gastos por Hectarea, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                           <div class="col-auto" v-if="props.companyReasons && props.companyReasons.length > 0">
                            <label for="companyReasonSelectGastos" class="form-label">Filtrar por razón social:</label>
                            <select id="companyReasonSelectGastos" v-model="selectedCompanyReason" @change="onCompanyReasonChange" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="cr in props.companyReasons" :key="cr.value" :value="cr.value">{{ cr.label }}</option>
                            </select>
                          </div>
                           <div class="col-auto">
                            <label for="costCenterSelect" class="form-label">Filtrar por Cc:</label>
                            <select id="costCenterSelect" v-model="selectedCostCenter" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="cc in filteredCostCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
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
                            <select id="varietySelectGastos" v-model="selectedVarietyGastos" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;":disabled="!selectedFruit">
                              <option value="">Todas</option>
                              <option v-for="variety in filteredVarietiesGastos" :key="variety.id" :value="variety.id">
                                {{ variety.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto">
                            <label for="productSelectGastos" class="form-label">Filtrar por producto:</label>
                            <select id="productSelectGastos" v-model="selectedProduct" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todos</option>
                              <option v-for="product in productOptions" :key="product" :value="product">
                                {{ product }}
                              </option>
                            </select>
                          </div>
                          <div class="col-auto d-flex align-items-end">
                            <button type="button" class="btn btn-sm d-flex align-items-center gap-1"
                              :class="hideCcGastos ? 'btn-falcon-primary' : 'btn-falcon-default'"
                              @click="hideCcGastos = !hideCcGastos"
                              v-tooltip="'Agrupar productos por Nivel 3, omitiendo centros de costo'">
                              <i class="fas fa-layer-group fa-sm"></i>
                              <span class="d-none d-md-inline">Agrupar</span>
                            </button>
                          </div>
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="(() => {
                                const pn = val => { if (typeof val === 'number') return val; if (typeof val !== 'string') return undefined; const c = val.replace(/\./g, '').replace(/,/g, '.'); return c.trim() === '' ? undefined : (isNaN(Number(c)) ? undefined : Number(c)); };
                                if (hideCcGastos) {
                                  return consolidatedDataGastos.flatMap(sf => sf.products.map(p => {
                                    const row = { subfamily: sf.name, producto: p.name, cantidad: pn(p.totalQuantity), unidad: p.unit, monto: pn(p.totalAmount) };
                                    ($page.props.months || []).forEach((m, i) => { row[m.label] = pn(p.months && p.months[i]); });
                                    return row;
                                  }));
                                }
                                return filteredDataGastos.flatMap(cc => cc.subfamilies.flatMap(sf => sf.products.map(p => {
                                  const row = { cc: cc.name, subfamily: sf.name, producto: p.name, cantidad: pn(p.totalQuantity), unidad: p.unit, monto: pn(p.totalAmount) };
                                  ($page.props.months || []).forEach((m, i) => { row[m.label] = pn(p.months && p.months[i]); });
                                  return row;
                                })));
                              })()"
                              :headers="[
                                ...(!hideCcGastos ? [{ label: 'Centro de costo', key: 'cc' }] : []),
                                { label: 'Nivel 3', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Fertilizantes-GastosPorHectarea.xlsx"
                            />
                          </div>
                        </div>
                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th v-if="!hideCcGastos" class="min-w-150px">Centro de costo</th>
                                        <th>Nivel 3</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="col-amount">Monto Total</th>
                                        <th v-for="month in $page.props.months" class="col-month">{{month.label}}</th> 
                                    </tr>
                                </thead>
                                <tbody v-if="!hideCcGastos">
                                    <template v-for="cc in filteredDataGastos">
                                        <template v-for="(subfamily, index2) in cc.subfamilies">
                                            <tr>
                                                <td v-if="index2 == 0" :rowspan="cc.total" class="cell-group">{{cc.name}}</td>
                                                <td :rowspan="subfamily.products.length" class="cell-group">{{subfamily.name}}</td>
                                                <td>{{subfamily.products[0].name}}</td>
                                                <td>{{subfamily.products[0].totalQuantity}}</td>
                                                <td>{{subfamily.products[0].unit}}</td>
                                                <td>{{subfamily.products[0].totalAmount}}</td>
                                                <td class="col-month col-amount" v-for="value in subfamily.products[0].months">{{value}}</td>
                                            </tr>
                                            <template v-for="(product, index3) in subfamily.products">
                                                <tr v-if="index3 > 0">
                                                    <td>{{product.name}}</td>
                                                    <td>{{product.totalQuantity}}</td>
                                                    <td>{{product.unit}}</td>
                                                    <td>{{product.totalAmount}}</td>
                                                    <td class="col-month col-amount" v-for="value in product.months">{{value}}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </template>
                                </tbody>
                                <tbody v-else>
                                  <template v-for="sf in consolidatedDataGastos" :key="sf.id">
                                    <tr>
                                      <td :rowspan="sf.total" class="cell-group">{{ sf.name }}</td>
                                      <td>{{ sf.products[0].name }}</td>
                                      <td>{{ sf.products[0].totalQuantity }}</td>
                                      <td>{{ sf.products[0].unit }}</td>
                                      <td>{{ sf.products[0].totalAmount }}</td>
                                      <td class="col-month col-amount" v-for="value in sf.products[0].months">{{ value }}</td>
                                    </tr>
                                    <template v-for="(product, idx) in sf.products" :key="idx">
                                      <tr v-if="idx > 0">
                                        <td>{{ product.name }}</td>
                                        <td>{{ product.totalQuantity }}</td>
                                        <td>{{ product.unit }}</td>
                                        <td>{{ product.totalAmount }}</td>
                                        <td class="col-month col-amount" v-for="value in product.months">{{ value }}</td>
                                      </tr>
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
                                    <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalFilteredDataCompra }}</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                            <div class="card h-100 p-1 small-card">
                              <div class="card-body d-flex align-items-center justify-content-center py-2 px-2">
                                <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{percentage}}%</strong> del presupuesto</p>
                              </div>
                            </div>
                          </div>
                        </div>

                       <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <!--begin::Table head-->
                                <thead>
                                    <tr>
                                        <th>Nivel 3</th>
                                        <th class="min-w-100px">Producto</th>
                                        <th>Cantidad Total</th>
                                        <th>Un</th>
                                        <th class="col-amount">Monto Total</th>
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                    <template v-for="(subfamily, index2) in filteredDataCompra">
                                        <tr>
                                            <td  style="vertical-align:top;" :rowspan="subfamily.products.length + 1">{{subfamily.name}}</td>
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
                                        <!-- Subtotal row -->
                                        <tr class="row-subtotal">
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

<style>
/* Estilos de tablas centralizados en budget-tables.css */
</style>