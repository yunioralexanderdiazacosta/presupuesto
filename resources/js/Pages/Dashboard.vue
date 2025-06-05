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
  weatherCity: String
})
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
                  <div class="fw-bold">Clima en {{ weatherCity || city || weather.location.name }}</div>
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
              <div class="card-body pt-0 pb-2">
                <div class="table-responsive scrollbar">
                  <table class="table table-sm fs-10">
                    <thead>
                      <tr>
                        <th scope="col"></th>
                        <th scope="col">TOTAL</th>
                        <th scope="col" v-for="month in $page.props.months" class="text-primary">{{month.label}}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Agroquimicos</td>
                        <td>{{totalAgrochemical}}</td>
                        <td class="bg-opacity-5 table-primary" v-for="value in months">{{monthsAgrochemical[value.value]}}</td>
                      </tr>
                      <tr>
                        <td>Fertilizantes</td>
                        <td>{{totalFertilizer}}</td>
                        <td class="bg-opacity-5 table-primary" v-for="value in months">{{monthsFertilizer[value.value]}}</td>
                      </tr>
                      <tr>
                        <td>Mano de obra</td>
                        <td>{{totalManPower}}</td>
                        <td class="bg-opacity-5 table-primary" v-for="value in months">{{monthsManPower[value.value]}}</td>
                      </tr>
                      <tr>
                        <td>Insumos</td>
                        <td>{{totalSupplies}}</td>
                        <td class="bg-opacity-5 table-primary" v-for="value in months">{{monthsSupplies[value.value]}}</td>
                      </tr>
                      <tr>
                        <td>Servicios</td>
                        <td>{{totalServices}}</td>
                        <td class="bg-opacity-5 table-primary" v-for="value in months">{{monthsServices[value.value]}}</td>
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
