<script setup>
import { ref, computed, watch } from 'vue';
import CreateEstimateModal from '@/Components/Estimates/CreateEstimateModal.vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const page = usePage();
const costcenters = computed(() => page.props.costcenters);
const estimates = computed(() => page.props.estimates);
const estimate_statuses = computed(() => page.props.estimate_statuses);
const fruits = computed(() => page.props.fruits);
const season_id = computed(() => page.props.season_id);

const fruitOptions = computed(() => {
  const list = fruits.value;
  if (!list) return [];
  return list.map(f => ({ id: f.id, name: f.name }));
});

const selectedFruit = ref('');

// Reiniciar centro de costo y variedad al cambiar fruta
watch(selectedFruit, () => {
  selectedCostCenter.value = '';
  selectedVariety.value = '';
});

const estimateStatusOptions = computed(() => {
  const statuses = estimate_statuses.value;
  if (!statuses) return [];
  // Mostrar solo estados relacionados al fruit_id seleccionado
  return statuses
    .filter(s => s.fruit_id == selectedFruit.value)
    .map(s => ({ id: s.id, name: s.name }));
});

const selectedEstimateStatus = ref(estimateStatusOptions.value[0]?.id || '');

const selectedCostCenter = ref('');
const selectedVariety = ref('');

const filteredCostCenters = computed(() => {
  const list = costcenters.value;
  if (!selectedFruit.value) return list;
  // Mostrar todos los centros de costo de la temporada que tengan el fruit_id seleccionado
  return list.filter(cc => cc.fruit_id == selectedFruit.value);
});

const filteredVarieties = computed(() => {
  // Si no hay centro de costo seleccionado, mostrar todas las variedades únicas de los costcenters filtrados por fruta
  const list = filteredCostCenters.value;
  if (!selectedCostCenter.value) {
    const seen = new Set();
    return list
      .map(cc => cc.variety)
      .filter(v => {
        if (!v || seen.has(v.id)) return false;
        seen.add(v.id);
        return true;
      });
  }
  // Si hay centro de costo seleccionado, mostrar solo la variedad de ese costcenter
  const cc = costcenters.value.find(c => c.id == selectedCostCenter.value);
  return cc && cc.variety ? [cc.variety] : [];
});

const form = ref({ season_id });

function openAdd() {
  // Usar Bootstrap modal
  const modal = document.getElementById('createEstimateModal');
  if (modal) {
    const modalInstance = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modal) : null;
    if (modalInstance) modalInstance.show();
    else modal.classList.add('show');
  }
}

const filteredEstimates = computed(() => {
  return estimates.value.filter(e =>
    (selectedEstimateStatus.value ? e.estimate_status_id === selectedEstimateStatus.value : true) &&
    (selectedFruit.value ? e.fruit_id == selectedFruit.value : true)
  );
});

// Nueva lógica: la tabla se llena con combinaciones de fruta, estado de estimación, centro de costo y variedad
const rows = computed(() => {
  // La tabla solo se llena si el usuario ha seleccionado fruta y estado de estimación
  if (!selectedFruit.value || !selectedEstimateStatus.value) return [];
  let result = [];
  fruits.value.forEach(fruit => {
    if (fruit.id != selectedFruit.value) return;
    estimate_statuses.value.forEach(status => {
      if (status.fruit_id == fruit.id && status.id == selectedEstimateStatus.value) {
        costcenters.value.forEach(cc => {
          if (cc.fruit_id == fruit.id && cc.variety) {
            // Buscar estimate para esta combinación (solo por costcenter y estado)
            const estimate = estimates.value.find(e =>
              e.estimate_status_id == status.id &&
              e.cost_center_id == cc.id
            );
            // Parse surface and kilos as numbers for calculation
            const surfaceNum = cc.surface ? Number(cc.surface) : 0;
            const kilosNum = estimate ? Number(estimate.kilos_ha) : 0;
            result.push({
              fruit: fruit.name,
              fruitId: fruit.id,
              estimateStatus: status.name,
              estimateStatusId: status.id,
              costcenter: cc.name,
              costcenterId: cc.id,
              variety: cc.variety.name,
              varietyId: cc.variety.id,
              surface: surfaceNum ? surfaceNum.toLocaleString('es-ES') : '',
              kilos: kilosNum ? kilosNum.toLocaleString('es-ES') : '',
              kilosTotal: surfaceNum && kilosNum ? (surfaceNum * kilosNum).toLocaleString('es-ES') : '',
            });
          }
        });
      }
    });
  });
  return result;
});

const filteredRows = computed(() => {
  return rows.value.filter(row => {
    const fruitFilter = selectedFruit.value ? row.fruitId == selectedFruit.value : true;
    const statusFilter = selectedEstimateStatus.value ? row.estimateStatusId == selectedEstimateStatus.value : true;
    const ccFilter = selectedCostCenter.value ? row.costcenterId == selectedCostCenter.value : true;
    const varietyFilter = selectedVariety.value ? row.varietyId == selectedVariety.value : true;
    return fruitFilter && statusFilter && ccFilter && varietyFilter;
  });
});
</script>
<template>
  <Head :title="title" />
    <AppLayout>
    <!--begin::Breadcrumb-->
    <Breadcrumb :links="links" />
    <!--end::Breadcrumb-->

 <div class="card my-1">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-people-carry text-primary me-2"></i>Estimaciones</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                  <div id="table-purchases-replace-element">
                    <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()">
                      <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                      <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                    </button>
                  </div>
                </div>
            </div>
        </div>
           <div class="card-body bg-body-tertiary">
            <ul class="nav nav-pills" id="pill-myTab" role="tablist">
               
            </ul>


        <div class="tab-content border p-3 mt-1" id="pill-myTabContent">
      <!-- Card de Total Kilos -->
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="card h-100 p-1 small-card">
            <div class="card-header pb-0 pt-1 px-2">
              <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Total Kilos</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
              <div class="row">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">
                    {{
                      filteredRows.reduce((sum, row) => {
                        // Parse kilosTotal from string to number
                        const val = row.kilosTotal ? Number(row.kilosTotal.replace(/\./g,'').replace(',','.')) : 0;
                        return sum + val;
                      }, 0).toLocaleString('es-ES')
                    }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
          <label class="form-label mb-1">Especie</label>
          <select v-model="selectedFruit" class="form-select form-select-sm">
            <option v-for="fruit in fruitOptions" :key="fruit.id" :value="fruit.id">{{ fruit.name }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Estado de la estimación</label>
          <select v-model="selectedEstimateStatus" class="form-select form-select-sm">
            <option v-for="status in estimateStatusOptions" :key="status.id" :value="status.id">{{ status.name }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Centro de Costo</label>
          <select v-model="selectedCostCenter" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option v-for="cc in filteredCostCenters" :key="cc.id" :value="cc.id">{{ cc.name }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Variedad</label>
          <select v-model="selectedVariety" class="form-select form-select-sm">
            <option value="">Todas</option>
            <option v-for="v in filteredVarieties" :key="v.id" :value="v.id">{{ v.name }}</option>
          </select>
        </div>
      </div>
      <div class="mt-0">
        <div class="card my-3 border shadow-sm">
        <div class="card-body p-0 px-4">
            <Table sticky-header :id="'estimates'" :total="filteredRows.length">
              <template #header>
                <th scope="col">Fruta</th>
                <th scope="col">Estado de estimación</th>
                <th scope="col">Centro de Costo</th>
                <th scope="col">Variedad</th>
                <th scope="col">Superficie</th>
                <th scope="col">Kilos/ha</th>
                <th scope="col">Kilos total</th>
              </template>
              <template #body>
                <template v-if="filteredRows.length === 0">
                  <tr>
                    <td colspan="7" class="text-center">Sin datos</td>
                  </tr>
                </template>
                <template v-else>
                  <tr v-for="(row, idx) in filteredRows" :key="idx">
                    <td>{{ row.fruit }}</td>
                    <td>{{ row.estimateStatus }}</td>
                    <td>{{ row.costcenter }}</td>
                    <td>{{ row.variety }}</td>
                    <td>{{ row.surface }}</td>
                    <td>{{ row.kilos }}</td>
                    <td>{{ row.kilosTotal }}</td>
                  </tr>
                </template>
              </template>
            </Table>
          </div>
        </div>
      </div>
 </div>
  <CreateEstimateModal
    :form="form"
    :costcenters="costcenters"
    :estimates="estimates"
    :estimate_statuses="estimate_statuses"
    :fruits="fruits"
    :season_id="season_id"
    @store="() => {}"
  />
 </div>
 </div>
</AppLayout>
</template>
