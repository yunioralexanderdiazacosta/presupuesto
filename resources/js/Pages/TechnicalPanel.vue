<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import { onMounted, ref, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
const title = 'Panel Tecnico';

const links = [{ title: 'Panel Tecnico', link: 'technicalpanel', active: true }];




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
  administrations: 'Administración'
};

// Renderizar los gauge charts usando ECharts
// Colores personalizados para los gauges (excepto fields y administration)
const gaugeColors = [
  '#1a922e ', // Agroquímicos
  '#1a922e ', // Fertilizantes
  '#1a922e ', // Mano de Obra
  '#1a922e ', // Insumos
  '#1a922e ', // Servicios
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
    <div class="container-fluid px-2 py-0" style="max-width: 100vw;">

     

      <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-1 pb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="card-body pt-2 pb-2">
                <h6 class="mb-2">Estado de Desarrollo</h6>
              
                <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-start text-uppercase text-secondary small fw-bold small">Estado de desarrollo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Agroquímicos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Fertilizantes</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Mano de Obra</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Servicios</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Insumos</th>
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
      </div>

      <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-1 pb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="card-body pt-2 pb-2">
                <h6 class="mb-2">Gastos por Hectáreas</h6>
                        
                <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-start text-uppercase text-secondary small fw-bold">Estado de desarrollo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Agroquímicos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Fertilizantes</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Mano de Obra</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Servicios</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Insumos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Gral campo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Administración</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold">Total</th>
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
                      <td class="text-center text-warning fw-bold small">
                        {{
                          (totalFieldsCalc / (totalSurface || 1)).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                        }}
                      </td>
                      <td class="text-center text-warning fw-bold small">
                        {{
                          (totalAdministrationCalc / (totalSurface || 1)).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                        }}
                      </td>
                      <td class="text-center text-warning fw-bold small">
                        {{
                          (
                            Number(agrochemicalExpensePerHectare[devStateId] || 0) +
                            Number(fertilizerExpensePerHectare[devStateId] || 0) +
                            Number(manPowerExpensePerHectare[devStateId] || 0) +
                            Number(servicesExpensePerHectare[devStateId] || 0) +
                            Number(suppliesExpensePerHectare[devStateId] || 0) +
                            (totalFieldsCalc / (totalSurface || 1)) +
                            (totalAdministrationCalc / (totalSurface || 1))
                          ).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                        }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>

     

      <!-- Tabla unificada de totales por Level 1 y Level 2 -->
      <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-2 pb-2">
              <h6 class="mb-2">Totales unificados por nivel 1 y nivel 2</h6>
              <div class="table-responsive scrollbar">
                <table class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-uppercase text-secondary small fw-bold small">Level 1</th>
                      <th class="text-uppercase text-secondary small fw-bold small">Level 2</th>
                      <th class="text-uppercase text-secondary small fw-bold small">Monto Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(group, l1idx) in groupAllTotalsByLevel1()" :key="'all-l1-' + group.level1_id">
                      <template v-for="(row, idx) in group.rows" :key="row.key">
                        <tr>
                          <td v-if="idx === 0" :rowspan="group.rows.length + 1" style="vertical-align:top" class="small">{{ group.level1_name }}</td>
                          <td class="small">{{ row.level2_name }}</td>
                          <td class="text-end small">{{ Number(row.total_amount).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        </tr>
                      </template>
                      <tr class="table-secondary">
                        <td class="small">Subtotal {{ group.level1_name }}</td>
                        <td class="text-end small" colspan="2">
                          {{ group.rows.reduce((sum, r) => sum + Number(r.total_amount), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                      </tr>
                    </template>
                  </tbody>
                  <tfoot>
                    <tr class="table-dark">
                      <td colspan="2" class="fw-bold text-end small">Total General</td>
                      <td class="fw-bold text-end small">
                        {{ groupAllTotalsByLevel1().reduce((grand, group) => grand + group.rows.reduce((sum, r) => sum + Number(r.total_amount), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

     

     <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-2 pb-2">
                <h6 class="mb-2">Totales Mensuales</h6>
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
                      <td class="fw-semibold small">Administración</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ Object.values(monthsAdministration || {}).reduce((sum, v) => sum + Number((v+'').replace(/\./g, '')), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{ (monthsAdministration && monthsAdministration[value.value]) ? monthsAdministration[value.value] : 0 }}</td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Gral Campo</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ Object.values(monthsFields || {}).reduce((sum, v) => sum + Number((v+'').replace(/\./g, '')), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{ (monthsFields && monthsFields[value.value]) ? monthsFields[value.value] : 0 }}</td>
                    </tr>
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
                    <!-- ...existing code... -->
                  </tbody>
                  <tfoot>
                    <tr class="table-secondary">
                      <td class="fw-bold text-end small">Total mes</td>
                      <td class="fw-bold text-end small text-primary">
                        {{
                          months && months.length
                            ? months.reduce((sum, value) => {
                                return sum + [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices]
                                  .reduce((s, obj) => s + Number((obj && obj[value.value]) ? (obj[value.value]+"").replace(/\./g, "") : 0), 0)
                              }, 0).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                            : 0
                        }}
                      </td>
                      <td class="fw-bold text-end small" v-for="value in months">
                        {{
                          [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices]
                            .reduce((sum, obj) => sum + Number((obj && obj[value.value]) ? (obj[value.value]+"").replace(/\./g, "") : 0), 0)
                            .toLocaleString('es-CL', { maximumFractionDigits: 0 })
                        }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
