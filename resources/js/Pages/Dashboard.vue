<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import { onMounted, ref, computed, nextTick } from 'vue'
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
    monthsAdministration: Object,
  monthsFields: Object,
    totalAgrochemical: Number,
    totalFertilizer: Number,
    totalManPower: Number,
    totalSupplies: Number,
    totalServices: Number,
    totalHarvest: Number,
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
  administrationTotalsByLevel12: Array, // <-- agregar prop para la tabla de administración
  fieldTotalsByLevel12: Array, // <-- agregar prop para la tabla de fields
  totalsByLevel12: Array, // <-- nuevo prop para la tabla de totales generales

  totalSurface: Number, // <-- AGREGADO para mostrar superficie total
  entityCounts: Object, // <-- para la tabla de conteos
  totalAdministration: Number, // <-- AGREGADO para mostrar administración total
  mainTotalsAndPercents: Array // <-- nuevo prop para los gauges
});

// Calcular el máximo para la barra de progreso
const maxCount = computed(() => {
  if (!props.entityCounts) return 0;
  return Math.max(...Object.values(props.entityCounts));
});

// Calcular el total de administración sumando los montos de administrationTotalsByLevel12
const totalAdministrationCalc = computed(() => {
  return (props.administrationTotalsByLevel12 || []).reduce((sum, r) => sum + Number(r.total_amount || 0), 0)
})

// Calcular el total de campo sumando los montos de fieldTotalsByLevel12
const totalFieldsCalc = computed(() => {
  return (props.fieldTotalsByLevel12 || []).reduce((sum, r) => sum + Number(r.total_amount || 0), 0)
})

// Estados para mostrar/ocultar tablas
const showDevStateTable = ref(false)
const showExpensePerHectareTable = ref(false)

// Inicializar el gráfico de pie Falcon ECharts al montar
import FalconPieChart from '@/Components/FalconPieChart.vue';

// Agrupación por Level1 para la tabla de administración y fields
function groupByLevel1() {
  // Junta administración y fields en un solo array, pero mantiene el orden original
  const allRows = [
    ...(props.administrationTotalsByLevel12?.map(r => ({...r, key: 'adm-' + r.level1_id + '-' + r.level2_id})) || []),
    ...(props.fieldTotalsByLevel12?.map(r => ({...r, key: 'field-' + r.level1_id + '-' + r.level2_id})) || [])
  ];
  // Agrupa por level1_id
  const groups = {};
  allRows.forEach(row => {
    if (!groups[row.level1_id]) {
      groups[row.level1_id] = {
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        rows: []
      };
    }
    groups[row.level1_id].rows.push(row);
  });
  // Devuelve como array
  return Object.values(groups);
}

// Nueva función para agrupar por Level1 y Level2 los totales generales
function groupTotalsByLevel1() {
  const allRows = props.totalsByLevel12?.map(r => ({...r, key: 'total-' + r.level1_id + '-' + r.level2_id})) || [];
  const groups = {};
  allRows.forEach(row => {
    if (!groups[row.level1_id]) {
      groups[row.level1_id] = {
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        rows: []
      };
    }
    groups[row.level1_id].rows.push(row);
  });
  return Object.values(groups);
}

// Unir y agrupar ambas tablas por Level1 y Level2
function groupAllTotalsByLevel1() {
  const allRows = [
    ...(props.administrationTotalsByLevel12?.map(r => ({...r, key: 'adm-' + r.level1_id + '-' + r.level2_id, source: 'Administración'})) || []),
    ...(props.fieldTotalsByLevel12?.map(r => ({...r, key: 'field-' + r.level1_id + '-' + r.level2_id, source: 'Campo'})) || []),
    ...(props.totalsByLevel12?.map(r => ({...r, key: 'total-' + r.level1_id + '-' + r.level2_id, source: 'General'})) || [])
  ];
  const groups = {};
  allRows.forEach(row => {
    if (!groups[row.level1_id]) {
      groups[row.level1_id] = {
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        rows: []
      };
    }
    groups[row.level1_id].rows.push(row);
  });
  return Object.values(groups);
}

// Unir y agrupar administración, fields y totales generales por Level 1 para el gráfico
const barChartFromTable = computed(() => {
  // Unificar todos los level1_id posibles de los tres arrays
  const allRows = [
    ...(props.administrationTotalsByLevel12 || []),
    ...(props.fieldTotalsByLevel12 || []),
    ...(props.totalsByLevel12 || [])
  ];
  // Crear un set de todos los level1_id únicos
  const level1Ids = new Set(allRows.map(row => row.level1_id));
  const groups = {};
  // Inicializar todos los level1_id con total 0 y nombre
  level1Ids.forEach(id => {
    // Buscar el primer nombre disponible para ese id
    const found = allRows.find(r => r.level1_id === id);
    groups[id] = {
      level1_id: id,
      level1_name: found ? found.level1_name : '',
      total_amount: 0
    };
  });
  // Sumar los montos de todos los arrays para cada level1_id
  allRows.forEach(row => {
    if (groups[row.level1_id]) {
      groups[row.level1_id].total_amount += Number(row.total_amount || 0);
    }
  });
  return Object.values(groups);
});


//(nombra los form en el grafico de barras)
const entityLabels = {
  agrochemicals: 'Agroquímicos',
  fertilizers: 'Fertilizantes',
  manpowers: 'Mano de Obra',
  supplies: 'Insumos',
  services: 'Servicios',
  fields: 'Generales Campo',
  administrations: 'Administración',
  harvests: 'Cosecha'
};

// Renderizar los gauge charts usando ECharts
// Colores personalizados para los gauges (excepto fields y administration)
const gaugeColors = [
  '#1a922e ', // Agroquímicos
  '#1a922e ', // Fertilizantes
  '#1a922e ', // Mano de Obra
  '#1a922e ', // Insumos
  '#1a922e ', // Servicios
  '#1a922e ', // Cosecha
];

onMounted(() => {
  nextTick(() => {
    if (props.mainTotalsAndPercents && window.echarts) {
      props.mainTotalsAndPercents.forEach((item, idx) => {
        const chartDom = document.getElementById('gauge-ring-' + idx);
        if (chartDom) {
          // Determina si es fields o administration
          const isSpecial = ['Generales Campo', 'Administración'].includes(item.label);
          // Si es especial, usa azul Bootstrap, si no, usa el color del arreglo
          const color = isSpecial ? '#0d6efd' : gaugeColors[idx % gaugeColors.length];
          const myChart = window.echarts.init(chartDom);
          myChart.setOption({
            series: [
              {
                type: 'gauge',
                startAngle: 225,
                endAngle: -45,
                min: 0,
                max: 100,
                progress: {
                  show: true,
                  width: 18,
                  itemStyle: {
                    color: color // color frontal de la barra
                  }
                },
                axisLine: {
                  lineStyle: {
                    width: 18,
                    color: [
                      [item.percent / 100, color], // color de la parte llena
                      [1, '#e3e1e1'] // color de la parte vacía (blanco)
                    ]
                  }
                },
                axisTick: {
                  show: false
                },
                splitLine: {
                  show: false
                },
                axisLabel: {
                  show: false
                },
                pointer: {
                  show: false
                },
                title: {
                  show: false
                },
                detail: {
                  valueAnimation: true,
                  fontSize: 18,
                  offsetCenter: [0, '90%'],
                  formatter: '{value}%'
                },
                data: [
                  {
                    value: item.percent
                  }
                ]
              }
            ]
          });
        }
      });
    }
  });
});
</script>

<template>
  <Head :title="title" />
  <AppLayout>
    <div class="container-fluid px-0 py-2" style="max-width: 100vw;">

      <!-- Card de 7 gauge ring charts con porcentajes de cada rubro -->
      <div class="row mt-2 mb-3">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-header pb-1 pt-1">
              <h6 class="mb-0">Indicadores clave por rubro (porcentaje del total)</h6>
            </div>
            <div class="card-body pt-1 pb-1">
              <div class="d-flex flex-nowrap overflow-auto justify-content-start align-items-center ">
                <div
                  v-for="(item, idx) in mainTotalsAndPercents"
                  :key="'gauge-' + idx"
                  class="falcon-gauge-card flex-grow-1 d-flex flex-column align-items-center justify-content-center mb-1 rounded"
                  :class="{
                    'bg-secondary bg-opacity-10': item.label === 'Generales Campo' || item.label === 'Administración'
                  }"
                  style="min-width: 150px; max-width: 100px;"
                >
                  <div
                    class="echart-gauge-ring-chart-example"
                    :id="'gauge-ring-' + idx"
                    style="min-height: 120px; width: 100%;"
                    data-echart-responsive="true"
                  ></div>
                  <div class="fw-semibold text-center mt-2 text-dark small">{{ item.label }}</div>
                  <div class="text-center text-primary fw-bold fs-10">{{ Number(item.total).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</div>
                 
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>



      <div class="row g-2 g-xl-3">
        <!-- Columna izquierda: Weather, superficie, total presupuestos -->
        <div class="col-xl-5 col-lg-6 d-flex flex-column">
          <!-- Weather card -->
          <div class="card mb-3" v-if="weather">
            <div class="card-body py-2 d-flex align-items-center">
              <img :src="weather.current ? weather.current.condition.icon : ''" alt="icon" style="width:32px;height:32px;" class="me-2" />
              <div>
                <div class="fw-bold">Clima en {{ weatherCity || userCity || weather.location ? weather.location.name : '' }}</div>
                <div class="">{{ weather.current ? weather.current.temp_c : '' }} °C, {{ weather.current ? weather.current.condition.text : '' }}</div>
              </div>
            </div>
          </div>
          <!-- Superficie -->
          <div class="alert alert-info mb-3">
            <strong>Total superficie:</strong> {{ totalSurface }} <strong> hectareas</strong>
          </div>
          <!-- Total Presupuestos -->
          <div class="card ecommerce-card-min-width mb-3">
            <div class="card-header pb-3">
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
          <!-- Tabla Falcon de conteos de entidades -->
          <div class="card shadow-sm h-100 mb-3">
            <div class="card-header pb-2 pt-2">
              <h6 class="mb-0">
                <span class="me-1"><span class="fas fa-list-alt text-primary"></span></span>
                Registros por Formulario
              </h6>
            </div>
            <div class="card-body pt-2 pb-4">
              <div class="table-responsive mb-3">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th class="text-uppercase text-secondary small fw-bold">Formulario</th>
                      <th class="text-uppercase text-secondary small fw-bold text-center">Cant. registros</th>
                      <th class="text-uppercase text-secondary small fw-bold text-end">Progreso</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(count, key) in entityCounts" :key="key">
                      <td class="small text-capitalize">{{ entityLabels[key] || key }}</td>
                      <td class="text-center fw-bold small">{{ count }}</td>
                      <td class="text-end" style="min-width:120px;">
                        <div class="progress" style="height: 12px;">
                          <div
                            class="progress-bar bg-primary"
                            role="progressbar"
                            :style="{ width: (maxCount > 0 ? (count / maxCount * 100) : 0) + '%' }"
                            :aria-valuenow="count"
                            aria-valuemin="0"
                            :aria-valuemax="maxCount"
                          ></div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
       
        </div>
        <!-- Columna derecha: Pie chart y gráfico de barras -->
        <div class="col-xl-7 col-lg-6 d-flex flex-column">
          <!-- Gráfico de barras Falcon modernizado -->
          <div class="card shadow-sm mb-3 border-0 bg-white">
            <div class="card-header bg-white border-0 pb-2 pt-3 d-flex align-items-center">
              <span class="me-2"><span class="fas fa-chart-bar text-primary"></span></span>
              <h6 class="mb-0">Gráfico de barras: Totales por Nivel 1</h6>
            </div>
            <div class="card-body pt-3 pb-3 px-4">
              <div class="falcon-bar-chart-container" style="height:320px;">
                <FalconBarChart
                  :barLabels="barChartFromTable.map(item => item.level1_name)"
                  :barData="barChartFromTable.map(item => item.total_amount)"
                  :height="320"
                />
              </div>
            </div>
          </div>
          <div class="card h-100">
            <div class="card-body bg-body-tertiary py-2">
              <FalconPieChart :pieLabels="pieLabels" :pieDatasets="pieDatasets" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
