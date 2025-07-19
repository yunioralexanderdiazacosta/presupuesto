<script setup>
import { ref, computed } from 'vue';
import CreateEstimateModal from '@/Components/Estimates/CreateEstimateModal.vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const { costcenters, estimates } = usePage().props;

const estimateNames = computed(() => {
  // Extrae nombres únicos de las estimaciones
  const names = estimates.map(e => e.estimate_name);
  return [...new Set(names)];
});

const selectedEstimate = ref(estimateNames.value[0] || '');
const selectedCostCenter = ref('');
const selectedVariety = ref('');

const form = ref({});

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
  return estimates.filter(e => e.estimate_name === selectedEstimate.value);
});

const rows = computed(() => {
  let result = [];
  costcenters.forEach(cc => {
    if (cc.variety) {
      const estimate = filteredEstimates.value.find(e => e.cost_center_id === cc.id);
      const kilos = estimate ? Number(estimate.kilos_ha) : 0;
      const surface = cc.surface ? Number(cc.surface) : 0;
      const totalKilos = kilos && surface ? Math.round(kilos * surface) : '';
      result.push({
        costcenter: cc.name,
        costcenterId: cc.id,
        variety: cc.variety.name,
        varietyId: cc.variety.id,
        surface: cc.surface,
        kilos: kilos !== 0 ? Math.round(kilos).toLocaleString('es-ES') : '',
        totalKilos: totalKilos !== '' ? totalKilos.toLocaleString('es-ES') : '',
      });
    }
  });
  if (result.length === 0) {
    result.push({ costcenter: '', costcenterId: '', variety: '', varietyId: '', kilos: '' });
  }
  return result;
});

const filteredRows = computed(() => {
  return rows.value.filter(row => {
    const ccFilter = selectedCostCenter.value ? row.costcenterId == selectedCostCenter.value : true;
    const varietyFilter = selectedVariety.value ? row.varietyId == selectedVariety.value : true;
    return ccFilter && varietyFilter;
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
                        const val = row.totalKilos ? Number(row.totalKilos.replace(/\./g,'').replace(',','.')) : 0;
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
          <label class="form-label mb-1">Nombre de la estimación</label>
          <select v-model="selectedEstimate" class="form-select form-select-sm">
            <option v-for="name in estimateNames" :key="name" :value="name">{{ name }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Centro de Costo</label>
          <select v-model="selectedCostCenter" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option v-for="cc in costcenters" :key="cc.id" :value="cc.id">{{ cc.name }}</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label mb-1">Variedad</label>
          <select v-model="selectedVariety" class="form-select form-select-sm">
            <option value="">Todas</option>
            <option v-for="cc in costcenters" :key="cc.variety.id" :value="cc.variety.id">{{ cc.variety.name }}</option>
          </select>
        </div>
      </div>
      <div class="mt-0">
        <div class="card my-3 border shadow-sm">
        <div class="card-body p-0 px-4">
            <Table sticky-header :id="'estimates'" :total="filteredRows.length">
              <template #header>
                <th scope="col">Centro de Costo</th>
                <th scope="col">Variedad</th>
                <th scope="col">Superficie</th>
                <th scope="col">Kilos</th>
                <th scope="col">Total Kilos</th>
              </template>
              <template #body>
                <template v-if="filteredRows.length === 0">
                  <tr>
                    <td colspan="5" class="text-center">Sin datos</td>
                  </tr>
                </template>
                <template v-else>
                  <tr v-for="(row, idx) in filteredRows" :key="idx">
                    <td>{{ row.costcenter }}</td>
                    <td>{{ row.variety }}</td>
                    <td>{{ row.surface }}</td>
                    <td>{{ row.kilos }}</td>
                    <td>{{ row.totalKilos }}</td>
                  </tr>
                </template>
              </template>
            </Table>
          </div>
        </div>
      </div>
 </div>
  <CreateEstimateModal :form="form" @store="() => {}" />
 </div>
 </div>
</AppLayout>
</template>
