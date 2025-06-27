<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pie from '@/Components/Pie.vue';
import { onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
const title = 'Tablero';

const links = [{ title: 'Tablero', link: 'dashboard', active: true }];

const userCity = ref(null)

onMounted(async () => {
  if (!props.weather) { // Solo si no hay clima cargado
    try {
      const res = await fetch('https://ipapi.co/json/');
      const data = await res.json();
      if (data && data.city) {
        userCity.value = data.city + (data.country_name ? ', ' + data.country_name : '');
        // Redirige al dashboard con la ciudad detectada
        router.get('/dashboard', { city: userCity.value }, { preserveState: true, replace: true });
      }
    } catch (e) {
      // Si falla, puedes dejar la ciudad por defecto
    }
  }
});


const props = defineProps({
    totalSeason: String,
    pieLabels: Array,
    pieDatasets: Array,
    months: Array,
    monthsAgrochemical: Object,
    monthsFertilizer: Object,
    monthsManPower: Object,
    monthsServices: Object,
    monthsSupplies: Object,
    totalAgrochemical: Number,
    totalFertilizer: Number,
    totalManPower: Number,
    totalSupplies: Number,
    totalServices: Number,
    weather: Object,
  weatherCity: String,
  agrochemicalByDevState: Object,
  fertilizerByDevState: Object,
  manPowerByDevState: Object,
  servicesByDevState: Object, // <-- agregar
  suppliesByDevState: Object, // <-- agregar
  agrochemicalExpensePerHectare: Object,
  fertilizerExpensePerHectare: Object,
  manPowerExpensePerHectare: Object, // <-- agregar
  servicesExpensePerHectare: Object, // <-- agregar
  suppliesExpensePerHectare: Object, // <-- agregar
  devStates: Object, // <-- nombres de estados de desarrollo
})

// Estados para mostrar/ocultar tablas
const showDevStateTable = ref(false)
const showExpensePerHectareTable = ref(false)
</script>

<template>
    <Head :title="title" />
    <AppLayout>
      <div class="container-fluid px-2 px-md-4 py-2">
        <div class="row g-3 g-xl-4">
          <div class="col-xl-5">
            <!-- Weather card arriba de Total Presupuestos, mismo ancho -->
            <div class="card mb-3" v-if="weather">
              <div class="card-body py-2 d-flex align-items-center">
                <img :src="weather.current.condition.icon" alt="icon" style="width:32px;height:32px;" class="me-2" />
                <div>
                  <div class="fw-bold">Clima en {{ weatherCity || userCity || weather.location.name }}</div>
                  <div class="">{{ weather.current.temp_c }} °C, {{ weather.current.condition.text }}</div>
                </div>
              </div>
            </div>
            <!-- fin weather card -->
            <div class="card ecommerce-card-min-width">
              <div class="card-header pb-1">
                <h4 class="mb-0 mt-1 d-flex align-items-center fs-6">Total Presupuestos
                  <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Calculated according to last week's sales">
                    <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                  </span>
                </h4>
              </div>
              <div class="card-body d-flex flex-column justify-content-end py-2">
                <div class="row">
                  <div class="col">
                    <p class="font-sans-serif lh-1 mb-1 fs-6">{{totalSeason}}</p>
                  </div>
                </div>
              </div>
            </div>
           
          </div>
          <div class="col-xl-7">
            <div class="card">
              <div class="card-body bg-body-tertiary py-2">
                <Pie :pieLabels="pieLabels" :pieDatasets="pieDatasets"></Pie>
              </div>
            </div>
          </div>
        </div>


        <div class="row mt-4">
          <div class="col-xl-12">
            <div class="card">
              <div class="card-body pt-2 pb-2">
                <div class="table-responsive scrollbar">
                  <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                    <thead class="table-light border-bottom">
                      <tr>
                        <th class="text-uppercase text-secondary small fw-bold"></th>
                        <th class="text-uppercase text-secondary small fw-bold">TOTAL</th>
                        <th class="text-uppercase text-primary small fw-bold" v-for="month in $page.props.months">{{month.label}}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="fw-semibold small">Agroquimicos</td>
                        <td class="text-end text-primary fw-bold small">{{totalAgrochemical}}</td>
                        <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsAgrochemical[value.value]}}</td>
                      </tr>
                      <tr>
                        <td class="fw-semibold small">Fertilizantes</td>
                        <td class="text-end text-primary fw-bold small">{{totalFertilizer}}</td>
                        <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsFertilizer[value.value]}}</td>
                      </tr>
                      <tr>
                        <td class="fw-semibold small">Mano de obra</td>
                        <td class="text-end text-primary fw-bold small">{{totalManPower}}</td>
                        <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsManPower[value.value]}}</td>
                      </tr>
                      <tr>
                        <td class="fw-semibold small">Insumos</td>
                        <td class="text-end text-primary fw-bold small">{{totalSupplies}}</td>
                        <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsSupplies[value.value]}}</td>
                      </tr>
                      <tr>
                        <td class="fw-semibold small">Servicios</td>
                        <td class="text-end text-primary fw-bold small">{{totalServices}}</td>
                        <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsServices[value.value]}}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>





        
        <div class="row mt-4">
          <div class="col-xl-12">
            <div class="card">
              <div class="card-body pt-1 pb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="mb-0">Estado de Desarrollo</h6>
                  <button class="btn btn-sm btn-outline-primary" @click="showDevStateTable = !showDevStateTable">
                    <span v-if="showDevStateTable">Ocultar</span>
                    <span v-else>Mostrar</span>
                  </button>
                </div>
                <div class="table-responsive scrollbar" v-if="showDevStateTable">
                  <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                    <thead class="table-light border-bottom">
                      <tr>
                        <th class="text-start text-uppercase text-secondary small fw-bold small">Estado de desarrollo</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold small">Total Agroquímicos</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold small">Total Fertilizantes</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold small">Total Mano de Obra</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold small">Total Servicios</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold small">Total Insumos</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="devStateId in Object.keys(devStates)" :key="devStateId" class="border-bottom">
                        <td class="fw-semibold">{{ devStates[devStateId]?.name || 'Sin estado' }}</td>
                        <td class="text-center text-end text-success fw-bold small">{{ Number(agrochemicalByDevState[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-end text-success fw-bold small">{{ Number(fertilizerByDevState[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-end text-success fw-bold small">{{ Number(manPowerByDevState[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-end text-success fw-bold small">{{ Number(servicesByDevState[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-end text-success fw-bold small">{{ Number(suppliesByDevState[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-4">
          <div class="col-xl-12">
            <div class="card">
              <div class="card-body pt-1 pb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <h6 class="mb-0">Gastos por Hectáreas</h6>
                  <button class="btn btn-sm btn-outline-primary" @click="showExpensePerHectareTable = !showExpensePerHectareTable">
                    <span v-if="showExpensePerHectareTable">Ocultar</span>
                    <span v-else>Mostrar</span>
                  </button>
                </div>
                <div class="table-responsive scrollbar" v-if="showExpensePerHectareTable">
                  <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                    <thead class="table-light border-bottom">
                      <tr>
                        <th class="text-start text-uppercase text-secondary small fw-bold">Estado de desarrollo</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold">Agroquímicos</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold">Fertilizantes</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold">Mano de Obra</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold">Servicios</th>
                        <th class="text-center text-uppercase text-secondary small fw-bold">Insumos</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="devStateId in Object.keys(devStates)" :key="devStateId" class="border-bottom">
                        <td class="text-start fw-semibold">{{ devStates[devStateId]?.name || 'Sin estado' }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(agrochemicalExpensePerHectare[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(fertilizerExpensePerHectare[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(manPowerExpensePerHectare[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(servicesExpensePerHectare[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(suppliesExpensePerHectare[devStateId] || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>







    </AppLayout>
</template>
