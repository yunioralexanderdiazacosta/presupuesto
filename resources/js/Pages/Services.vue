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
import CreateServiceModal from '@/Components/Services/CreateServiceModal.vue';
import EditServiceModal from '@/Components/Services/EditServiceModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
// Buscador global para la tabla de servicios
const search = ref('');

// Toggle global por columna (Edición tab)
const expandAllMonths = ref(false);
const expandAllCc = ref(false);
const MONTH_PREVIEW = 3;
const CC_PREVIEW = 2;

// Computed para filtrar los servicios según el texto de búsqueda
const filteredServices = computed(() => {
  if (!props.services || !props.services.data) return [];
  if (!search.value) return props.services.data;
  const term = search.value.toLowerCase();
  return props.services.data.filter(item => {
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
  const items = filteredServices.value;
  return {
    count: items.length,
    totalPrice: items.reduce((sum, item) => sum + (Number(item.price) || 0), 0),
  };
});

const isLocked = useSeasonLock();

const props = defineProps({
    services: Object,
    data: Array,
    data2: Array,
    data3: Array,
    data4: Object,
    totalData1: String,
    totalData2: String,
    percentage: String,
     costCenters: { type: Array, default: () => [] }, // <-- AGREGAR ESTA LÍNEA
     companyReasons: { type: Array, default: () => [] },
     branches: { type: Array, default: () => [] },
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
const selectedBranch = ref('');
const hideCc = ref(false);

const filteredCostCenters = computed(() => {
  let ccs = props.costCenters;
  if (selectedCompanyReason.value) {
    ccs = ccs.filter(cc => String(cc.company_reason_id) === String(selectedCompanyReason.value));
  }
  if (selectedBranch.value) {
    ccs = ccs.filter(cc => String(cc.branch_id) === String(selectedBranch.value));
  }
  return ccs;
});

const onCompanyReasonChange = () => {
  if (selectedCostCenter.value) {
    const stillValid = filteredCostCenters.value.some(cc => String(cc.value) === String(selectedCostCenter.value));
    if (!stillValid) selectedCostCenter.value = '';
  }
};

const onBranchChange = () => {
  if (selectedCostCenter.value) {
    const stillValid = filteredCostCenters.value.some(cc => String(cc.value) === String(selectedCostCenter.value));
    if (!stillValid) selectedCostCenter.value = '';
  }
};


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
  if (selectedBranch.value) {
    data = data.filter(cc => String(cc.branch_id) === String(selectedBranch.value));
  }
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

// Helpers para subtotales de Nivel 3 en tabla Detalles
function parseAmt(val) {
  if (typeof val === 'number') return val;
  if (!val) return 0;
  return Number(String(val).replace(/\./g, '').replace(/,/g, '.')) || 0;
}
function fmtAmt(val) {
  return Math.round(parseAmt(val)).toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

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

/// Filtro por variedad para Gastos por Hectarea
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
  if (selectedBranch.value) {
    data = data.filter(cc => String(cc.branch_id) === String(selectedBranch.value));
  }
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
            router.delete(route('services.delete', id), {
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

const resumenData = computed(() => props.data4 || { rows: [], subfamilyList: [], totalSurface: 0, totalCCs: 0, globalSubfamilyCosts: {}, globalTotalCostPerHa: 0 });

const excelDataResumen = computed(() => {
  const d = resumenData.value;
  return (d.rows || []).map(row => {
    const obj = {
      estado_desarrollo: row.development_state_name,
      superficie: row.total_surface,
      centros_costo: row.cost_centers_count,
    };
    (d.subfamilyList || []).forEach(sf => {
      obj[sf.name] = row.subfamilyCosts[sf.id] || 0;
    });
    obj['total_por_ha'] = row.total_cost_per_ha;
    return obj;
  });
});
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
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-people-carry text-primary me-2"></i>Servicios</h5>
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
                 <li class="nav-item"><a class="nav-link" id="pill-resumen-estado" data-bs-toggle="tab" href="#pill-tab-resumen-estado" role="tab" aria-controls="pill-tab-resumen-estado" aria-selected="false">Resumen por Estado</a></li>
            </ul>
            <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="edicion-tab">
                    <!-- Buscador global y botones de exportación -->
                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.8rem;white-space:nowrap;" v-tooltip="'Total calculado: cantidad × precio unitario × superficie de los centros de costo seleccionados'">
                          <span class="text-muted me-1">Total:</span><strong>{{ totalFilteredData }}</strong>
                        </span>
                        <SearchInput
                          v-model="search"
                          placeholder="Buscar por nombre, subfamilia, unidad..."
                        />
                      </div>
                      <div class="d-flex align-items-center gap-1">
                        <ExportExcelButton
                          :data="services.data"
                          :headers="[
                            { label: 'Nombre', key: 'product_name' },
                            { label: 'SubFamilia', key: 'subfamily.name' },
                            { label: 'Cantidad', key: 'quantity' },
                            { label: 'Unidad', key: 'unit.name' },
                            { label: 'Precio', key: 'price' },
                            { label: 'Unidad de $', key: 'unit2.name' }
                          ]"
                          class="btn btn-success btn-md d-flex align-items-center p-0"
                          filename="Servicios.xlsx"
                        />
                        <ExportPdfButton
                          :data="services.data"
                          :headers="[
                            { label: 'Nombre', key: 'product_name' },
                            { label: 'SubFamilia', key: 'subfamily.name' },
                            { label: 'Cantidad', key: 'quantity' },
                            { label: 'Unidad', key: 'unit.name' },
                            { label: 'Precio', key: 'price' },
                            { label: 'Unidad de $', key: 'unit2.name' }
                          ]"
                          class="btn btn-danger btn-md d-flex align-items-center p-0"
                          filename="Servicios.pdf"
                        />
                       
                      </div>
                    </div>
                    <div class="table-responsive budget-table-wrapper mt-1">
                    <Table sticky-header :id="'services'" :total="filteredServices.length" :links="services.links">
                        <!--begin::Table head-->
                        <template #header>
                            <!--begin::Table row-->
                            <th width="min-w-50px">#</th>
                            <th width="min-w-100px">Nombre</th>
                            <th width="min-w-100px">SubFamilia</th>
                            <th width="min-w-100px">Cantidad</th>
                            <th width="min-w-100px">Unidad</th>
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
                            <template v-if="filteredServices.length === 0">
                                <Empty colspan="11" />
                            </template>
                            <template v-else>
                                <tr v-for="(service, index) in filteredServices" :key="index">
                                    <td class="text-muted">{{service.id}}</td>
                                    <td>
                                        <span class="text-dark  fw-bold mb-1">{{service.product_name}}</span>
                                    </td>
                                    <td>{{service.subfamily.name}}</td>
                                    <td>{{service.quantity}}</td>
                                    <td>{{service.unit.name}}</td>
                                    <td class="text-center">{{ Number(service.price).toLocaleString('es-CL') }}</td>
                                    <td>{{service.unit2.name}}</td>
                                    <td>
                                        <template v-if="service.months && service.months.length">
                                            {{ (expandAllMonths ? service.months : service.months.slice(0, MONTH_PREVIEW))
                                                .map(mId => ($page.props.months || []).find(x => String(x.value) === String(mId))?.label || mId)
                                                .join(', ') }}<span v-if="!expandAllMonths && service.months.length > MONTH_PREVIEW" class="text-muted"> …</span>
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>
                                        <template v-if="service.cc && service.cc.length">
                                            {{ (expandAllCc ? service.cc : service.cc.slice(0, CC_PREVIEW))
                                                .map(ccId => (props.costCenters.find(c => String(c.value) === String(ccId)) || {}).label || ccId)
                                                .join(', ') }}<span v-if="!expandAllCc && service.cc.length > CC_PREVIEW" class="text-muted"> …</span>
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>{{ service.user ? service.user.name : '—' }}</td>
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
                    <div class="row  mb-3">
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
                              <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{ percentage }}%</strong> del presupuesto</p>
                            </div>
                          </div>
                        </div>
                    </div>

                     <!-- Select de especie (fruta) y variedades, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                          <div class="col-auto" v-if="props.branches && props.branches.length > 0">
                            <label for="branchSelect" class="form-label">Filtrar por sucursal:</label>
                            <select id="branchSelect" v-model="selectedBranch" @change="onBranchChange" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="branch in props.branches" :key="branch.value" :value="branch.value">{{ branch.label }}</option>
                            </select>
                          </div>
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
                              <option v-for="fruit in fruits" :key="fruit.id" :value="fruit.id">
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
                          <div class="col-auto d-flex align-items-end">
                            <button type="button" class="btn btn-sm d-flex align-items-center gap-1" :class="hideCc ? 'btn-falcon-primary' : 'btn-falcon-default'" @click="hideCc = !hideCc" v-tooltip="'Agrupar productos por subfamilia, omitiendo centros de costo'">
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
                                  row['Subfamilia'] = sfName;
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
                                { label: 'Subfamilia', key: 'Subfamilia' },
                                { label: 'Producto', key: 'Producto' },
                                { label: 'Cantidad Total', key: 'Cantidad Total', type: 'number' },
                                { label: 'Un', key: 'Un' },
                                { label: 'Monto Total', key: 'Monto Total', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Servicios-Detalles.xlsx"
                            />
                          </div>
                        </div>

                    <div class="table-responsive budget-table-wrapper mt-1">
                        <table class="table budget-tbl">
                            <!--begin::Table head-->
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th v-if="!hideCc" class="min-w-150px">Centro de costo</th>
                                    <th>Subfamilia</th>
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
                                            <td v-if="index2 == 0" :rowspan="cc.total + cc.subfamilies.length" class="cell-group">{{cc.name}}</td>
                                            <td :rowspan="subfamily.products.length" class="cell-group">{{subfamily.name}}</td>
                                            <td>{{subfamily.products[0].name}}</td>
                                            <td>{{subfamily.products[0].totalQuantity}}</td>
                                            <td>{{subfamily.products[0].unit}}</td>
                                            <td>{{ fmtAmt(subfamily.products[0].totalAmount) }}</td>
                                            <td class="col-month col-amount" v-for="value in subfamily.products[0].months">{{value}}</td>
                                        </tr>
                                        <template v-for="(product, index3) in subfamily.products">
                                            <tr v-if="index3 > 0">
                                                <td>{{product.name}}</td>
                                                <td>{{product.totalQuantity}}</td>
                                                <td>{{product.unit}}</td>
                                                <td>{{ fmtAmt(product.totalAmount) }}</td>
                                                <td class="col-month col-amount" v-for="value in product.months">{{value}}</td>
                                            </tr>
                                        </template>
                                        <!-- Subtotal Nivel 3 -->
                                        <tr class="table-secondary" style="font-size:0.78rem;">
                                          <td colspan="4" class="text-end py-1 text-muted fst-italic">Subtotal {{ subfamily.name }}</td>
                                          <td class="py-1 fw-bold">{{ fmtAmt(subfamily.products.reduce((s,p) => s + parseAmt(p.totalAmount), 0)) }}</td>
                                          <td class="col-month col-amount py-1 fw-bold" v-for="(_, mi) in (subfamily.products[0]?.months || [])" :key="mi">
                                            {{ fmtAmt(subfamily.products.reduce((s,p) => s + parseAmt(p.months[mi]), 0)) }}
                                          </td>
                                        </tr>
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
                                <!-- Subtotal Nivel 3 -->
                                <tr class="table-secondary" style="font-size:0.78rem;">
                                  <td colspan="4" class="text-end py-1 text-muted fst-italic">Subtotal {{ sf.name }}</td>
                                  <td class="py-1 fw-bold">{{ fmtAmt(sf.products.reduce((s,p) => s + parseAmt(p.totalAmount), 0)) }}</td>
                                  <td class="col-month col-amount py-1 fw-bold" v-for="(_, mi) in (sf.products[0]?.months || [])" :key="mi">
                                    {{ fmtAmt(sf.products.reduce((s,p) => s + parseAmt(p.months[mi]), 0)) }}
                                  </td>
                                </tr>
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
                    <div class="row  mb-3">
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
                              <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{ percentage }}%</strong> del presupuesto</p>
                            </div>
                          </div>
                        </div>
                    </div>


  <!-- Select de especie (fruta) y variedades para Gastos por Hectarea, lado a lado -->
                        <div class="mb-3 d-flex align-items-end gap-2 flex-wrap">
                          <div class="col-auto" v-if="props.branches && props.branches.length > 0">
                            <label for="branchSelectGastos" class="form-label">Filtrar por sucursal:</label>
                            <select id="branchSelectGastos" v-model="selectedBranch" @change="onBranchChange" class="form-select form-select-sm" style="min-width: 180px; max-width: 220px;">
                              <option value="">Todas</option>
                              <option v-for="branch in props.branches" :key="branch.value" :value="branch.value">{{ branch.label }}</option>
                            </select>
                          </div>
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
                              <option v-for="fruit in fruits" :key="fruit.id" :value="fruit.id">
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
                          <div class="col-auto d-flex align-items-end">
                            <button type="button" class="btn btn-sm d-flex align-items-center gap-1"
                              :class="hideCcGastos ? 'btn-falcon-primary' : 'btn-falcon-default'"
                              @click="hideCcGastos = !hideCcGastos"
                              v-tooltip="'Agrupar productos por Subfamilia, omitiendo centros de costo'">
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
                                { label: 'Subfamilia', key: 'subfamily' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Cantidad Total', key: 'cantidad', type: 'number' },
                                { label: 'Un', key: 'unidad' },
                                { label: 'Monto Total', key: 'monto', type: 'number' },
                                ...($page.props.months || []).map(month => ({ label: month.label, key: month.label, type: 'number' }))
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Servicios-GastosPorHectarea.xlsx"
                            />
                          </div>
                        </div>

                 <div class="table-responsive budget-table-wrapper mt-1">
                        <table class="table budget-tbl">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th v-if="!hideCcGastos" class="yunior min-w-150px">Centro de costo</th>
                                    <th>Subfamilia</th>
                                    <th class="yunior hmin-w-100px">Producto</th>
                                    <th>Cantidad Total</th>
                                    <th>Un</th>
                                    <th class="yunior text-dark">Monto Total</th>
                                    <th v-for="month in $page.props.months" class="text-primary yunior">{{month.label}}</th> 
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
                    <div class="row  mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">
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
                            <div class="col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-body d-flex align-items-center justify-content-center py-2 px-2">
                                  <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{ percentage }}%</strong> del presupuesto</p>
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
                            </table>
                        </div>
                </div>

                    <!-- Resumen por Estado -->
                    <div class="tab-pane fade" id="pill-tab-resumen-estado" role="tabpanel" aria-labelledby="resumen-estado-tab">
                        <div class="row mb-3">
                            <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-header pb-0 pt-1 px-2">
                                  <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Prom. Ponderado $/Ha</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                  <div class="row">
                                    <div class="col">
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ resumenData.globalTotalCostPerHa?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) || 0 }}</p>
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
                                      <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ resumenData.totalSurface?.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || 0 }} ha</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4 col-lg-3 col-xl-3 col-xxl-3">
                              <div class="card h-100 p-1 small-card">
                                <div class="card-body d-flex align-items-center justify-content-center py-2 px-2">
                                  <p class="mb-0 fs-9 text-muted">Corresponde al <strong class="text-dark">{{props.percentage}}%</strong> del presupuesto</p>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="mb-3 row g-2 align-items-end flex-wrap">
                          <div class="col d-flex justify-content-end align-items-end gap-1">
                            <ExportExcelButton
                              :data="excelDataResumen"
                              :headers="[
                                { label: 'Estado Desarrollo', key: 'estado_desarrollo' },
                                { label: 'Superficie (ha)', key: 'superficie', type: 'number' },
                                { label: 'N° CC', key: 'centros_costo', type: 'number' },
                                ...resumenData.subfamilyList.map(sf => ({ label: sf.name, key: sf.name, type: 'number' })),
                                { label: 'Total $/Ha', key: 'total_por_ha', type: 'number' }
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="Servicios-ResumenEstado.xlsx"
                            />
                            <ExportPdfButton
                              :data="excelDataResumen"
                              :headers="[
                                { label: 'Estado Desarrollo', key: 'estado_desarrollo' },
                                { label: 'Superficie (ha)', key: 'superficie', type: 'number' },
                                { label: 'N° CC', key: 'centros_costo', type: 'number' },
                                ...resumenData.subfamilyList.map(sf => ({ label: sf.name, key: sf.name, type: 'number' })),
                                { label: 'Total $/Ha', key: 'total_por_ha', type: 'number' }
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="Servicios-ResumenEstado.pdf"
                            />
                          </div>
                        </div>

                        <div class="table-responsive budget-table-wrapper mt-1">
                            <table class="table budget-tbl">
                                <thead>
                                    <tr>
                                        <th>Estado Desarrollo</th>
                                        <th class="text-end">Superficie (ha)</th>
                                        <th class="text-center">N° CC</th>
                                        <th v-for="sf in resumenData.subfamilyList" :key="sf.id" class="text-end">{{ sf.name }}</th>
                                        <th class="text-end fw-bold">Total $/Ha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in resumenData.rows" :key="row.development_state_id">
                                        <td class="cell-group">{{ row.development_state_name }}</td>
                                        <td class="text-end">{{ row.total_surface.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                        <td class="text-center">{{ row.cost_centers_count }}</td>
                                        <td v-for="sf in resumenData.subfamilyList" :key="sf.id" class="text-end">
                                          {{ row.subfamilyCosts[sf.id]?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) || '-' }}
                                        </td>
                                        <td class="text-end fw-bold">{{ row.total_cost_per_ha.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="text-end">{{ resumenData.totalSurface?.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                        <td class="text-center">{{ resumenData.totalCCs }}</td>
                                        <td v-for="sf in resumenData.subfamilyList" :key="sf.id" class="text-end">
                                          {{ resumenData.globalSubfamilyCosts[sf.id]?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) || '-' }}
                                        </td>
                                        <td class="text-end">{{ resumenData.globalTotalCostPerHa?.toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</td>
                                    </tr>
                                </tfoot>
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
/* Estilos de tablas centralizados en budget-tables.css */
</style>