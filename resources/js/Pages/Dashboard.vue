<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';
import { onMounted, ref, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'


const title = 'Tablero';
import { reactive } from 'vue'
// Estado reactivo para switches de prorrateo por especie
const showProrrateo = reactive({});


// Función utilitaria para formatear números en el template
const formatNumber = (value) => {
  if (typeof value !== 'number' || isNaN(value)) return '';
  return value.toLocaleString('es-CL', { maximumFractionDigits: 0 });
}


const divisor = ref(970)
const divisorMin = 800
const divisorMax = 1100
const dividir = ref(true) // Por defecto, mostrar en dólares



const links = [{ title: 'Tablero', link: 'dashboard', active: true }];


/* 
const userCity = ref(null)

onMounted(async () => {
  if (!props.weather) { // Solo si no hay clima cargado
    try {
      const res = await fetch('https://ipapi.co/json/');
      const data = await res.json();
      if (data && data.city) {
        userCity.value = data.city + (data.country_name ? ', ' + data.country_name : '');
        // Redirige al dashboard with la ciudad detectada
        router.get('/dashboard', { city: userCity.value }, { preserveState: true, replace: true });
      }
    } catch (e) {
      // Si falla, puedes dejar la ciudad por defecto
    }
  }
});*/


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
  //weather: Object,
  //weatherCity: String,
  agrochemicalByDevState: Object,
  fertilizerByDevState: Object,
  manPowerByDevState: Object,
  servicesByDevState: Object,
  suppliesByDevState: Object,
  agrochemicalExpensePerHectare: Object,
  fertilizerExpensePerHectare: Object,
  manPowerExpensePerHectare: Object,
  servicesExpensePerHectare: Object,
  suppliesExpensePerHectare: Object,
  devStates: Object,
  administrationTotalsByLevel12: Array,
  fieldTotalsByLevel12: Array,
  totalsByLevel12: Array,
  totalSurface: Number,
  entityCounts: Object,
  totalAdministration: Number,
  mainTotalsAndPercents: Array,
  kilosByFruit: Object,
  fruitNames: Object,
  kilosByEstimate: Object,
  kilosByEstimateFruitDevState: Object,
  estimateOptions: Array,
  defaultEstimateStatusId: Number,
  adminFieldsByFruit: Object, // <-- nuevo prop para admin+fields prorrateado por especie
  totalHarvestByFruit: Object, // <-- nuevo prop para total de cosecha por especie
  fruitDevStateSummary: Array,
  totalInvestments: Number
});

// Select de estimación
const selectedEstimateStatusId = ref(props.defaultEstimateStatusId);
const activeKilosByFruit = computed(() => {
  if (props.kilosByEstimate && selectedEstimateStatusId.value) {
    return props.kilosByEstimate[selectedEstimateStatusId.value] || {};
  }
  return props.kilosByFruit || {};
});

// Calcular el total por fruta usando totalsByLevel12 (sin dividir aquí, solo suma cruda)
const totalByFruit = computed(() => {
  const map = {};
  (props.totalsByLevel12 || []).forEach(row => {
    if (row.fruit_id) {
      map[row.fruit_id] = (map[row.fruit_id] || 0) + Number(row.total_amount || 0);
    }
  });
  return map;
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
// Sumar los montos de todos los arrays para cada level1_id y dividir solo si dividir.value está activo
  allRows.forEach(row => {
    if (groups[row.level1_id]) {
      groups[row.level1_id].total_amount += Number(row.total_amount || 0);
    }
  });
  return Object.values(groups).map(g => ({
    ...g,
    total_amount: dividir.value && divisor.value ? g.total_amount / divisor.value : g.total_amount
  }));
});

const selectedLevel1Id = ref(null);

const handleLevel1BarClick = ({ index }) => {
  const clicked = barChartFromTable.value[index];
  if (!clicked) return;

  selectedLevel1Id.value = String(selectedLevel1Id.value) === String(clicked.level1_id)
    ? null
    : clicked.level1_id;
};

const isSelectedLevel1 = (level1Id) => String(selectedLevel1Id.value) === String(level1Id);

const categoryContainsSelectedLevel1 = (group) => {
  if (!selectedLevel1Id.value) return false;
  return (group.items || []).some(item => isSelectedLevel1(item.level1_id));
};

const barChartColors = computed(() => {
  if (!selectedLevel1Id.value) {
    return barChartFromTable.value.map(() => '#2c7be5');
  }

  return barChartFromTable.value.map(item => (
    isSelectedLevel1(item.level1_id) ? '#2c7be5' : 'rgba(44, 123, 229, 0.25)'
  ));
});

// Computed: Tabla resumen agrupada por Level1 → Level2 (todas las fuentes combinadas)
const summaryByLevel12 = computed(() => {
  const allRows = [
    ...(props.administrationTotalsByLevel12 || []).map(r => ({ ...r })),
    ...(props.fieldTotalsByLevel12 || []).map(r => ({ ...r })),
    ...(props.totalsByLevel12 || []).map(r => ({ ...r })),
  ];
  const groups = {};
  allRows.forEach(row => {
    const l1Key = row.level1_id;
    if (!groups[l1Key]) {
      groups[l1Key] = {
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        total: 0,
        level2s: {}
      };
    }
    const l2Key = row.level2_id;
    if (!groups[l1Key].level2s[l2Key]) {
      groups[l1Key].level2s[l2Key] = {
        level2_id: row.level2_id,
        level2_name: row.level2_name,
        total: 0
      };
    }
    const amount = Number(row.total_amount || 0);
    groups[l1Key].total += amount;
    groups[l1Key].level2s[l2Key].total += amount;
  });
  const d = (dividir.value && divisor.value) ? divisor.value : 1;
  return Object.values(groups).map(g => ({
    ...g,
    total: g.total / d,
    level2s: Object.values(g.level2s).map(l2 => ({
      ...l2,
      total: l2.total / d
    })).sort((a, b) => b.total - a.total)
  })).sort((a, b) => b.total - a.total);
});

const summaryGrandTotal = computed(() => {
  return summaryByLevel12.value.reduce((sum, g) => sum + g.total, 0);
});

// Control expand/collapse para tabla resumen
const expandedBudgetGroups = ref(new Set(summaryByLevel12.value.map((_, i) => 'bg-' + i)));

const toggleBudgetGroup = (key) => {
  if (expandedBudgetGroups.value.has(key)) {
    expandedBudgetGroups.value.delete(key);
  } else {
    expandedBudgetGroups.value.add(key);
  }
  expandedBudgetGroups.value = new Set(expandedBudgetGroups.value);
};

const expandAllBudget = () => {
  expandedBudgetGroups.value = new Set(summaryByLevel12.value.map((_, i) => 'bg-' + i));
};

const collapseAllBudget = () => {
  expandedBudgetGroups.value = new Set();
};

// Computed: Tabla resumen agrupada por categoría (nombre base de Level2) → Level1 (área)
// Lógica igual a groupedByCategory de OutflowsDashboard: quita prefijos cos./adm./admin. y agrupa
const summaryByCategory = computed(() => {
  const allRows = [
    ...(props.administrationTotalsByLevel12 || []).map(r => ({ ...r })),
    ...(props.fieldTotalsByLevel12 || []).map(r => ({ ...r })),
    ...(props.totalsByLevel12 || []).map(r => ({ ...r })),
  ];
  const groups = {};
  allRows.forEach(row => {
    // Extraer nombre base: quitar prefijos como "cos. ", "adm. ", "admin. ", "gral. ", etc.
    // Normalizar espacios múltiples y trim
    const baseName = (row.level2_name || '').replace(/^(cos\.?|adm\.?|admin\.?|gral\.?)\s*/i, '').replace(/\s+/g, ' ').trim().toLowerCase();
    const displayName = baseName.charAt(0).toUpperCase() + baseName.slice(1);
    if (!groups[baseName]) {
      groups[baseName] = {
        category_name: displayName,
        total: 0,
        items: []
      };
    }
    // Buscar si ya existe un item para este level1 + level2 original en este grupo
    const itemKey = String(row.level1_id) + '|' + String(row.level2_id);
    let existingItem = groups[baseName].items.find(it => it._key === itemKey);
    if (!existingItem) {
      existingItem = {
        _key: itemKey,
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        originalLabel: row.level2_name,
        total: 0
      };
      groups[baseName].items.push(existingItem);
    }
    const amount = Number(row.total_amount || 0);
    groups[baseName].total += amount;
    existingItem.total += amount;
  });
  const d = (dividir.value && divisor.value) ? divisor.value : 1;
  return Object.values(groups)
    .sort((a, b) => b.total - a.total)
    .map(g => ({
      ...g,
      total: g.total / d,
      items: g.items.map(it => ({
        ...it,
        total: it.total / d
      })).sort((a, b) => b.total - a.total)
    }));
});

// Control expand/collapse para tabla por categoría
const expandedCategoryGroups = ref(new Set(summaryByCategory.value.map((_, i) => 'cat-' + i)));

const toggleCategoryGroup = (key) => {
  if (expandedCategoryGroups.value.has(key)) {
    expandedCategoryGroups.value.delete(key);
  } else {
    expandedCategoryGroups.value.add(key);
  }
  expandedCategoryGroups.value = new Set(expandedCategoryGroups.value);
};

const expandAllCategory = () => {
  expandedCategoryGroups.value = new Set(summaryByCategory.value.map((_, i) => 'cat-' + i));
};

const collapseAllCategory = () => {
  expandedCategoryGroups.value = new Set();
};

const normalizeLabel = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim();

const summaryByFruitDevState = computed(() => {
  const activeKilosMatrix = selectedEstimateStatusId.value && props.kilosByEstimateFruitDevState?.[selectedEstimateStatusId.value]
    ? props.kilosByEstimateFruitDevState[selectedEstimateStatusId.value]
    : {};
  const d = (dividir.value && divisor.value) ? divisor.value : 1;

  return (props.fruitDevStateSummary || []).map(row => {
    const fruitId = String(row.fruit_id);
    const devStateId = String(row.development_state_id);
    const kilos = Number(activeKilosMatrix?.[fruitId]?.[devStateId] || 0);
    const directCostTotal = Number(row.direct_cost_total || 0) / d;
    const adminFieldsTotal = Number(row.admin_fields_total || 0) / d;
    const totalCost = Number(row.total_cost || 0) / d;
    const surface = Number(row.surface || 0);

    return {
      ...row,
      kilos,
      surface,
      directCostTotal,
      adminFieldsTotal,
      totalCost,
      costPerHa: surface > 0 ? totalCost / surface : null,
      costPerKg: kilos > 0 ? totalCost / kilos : null,
    };
  }).filter(row => row.surface > 0 || row.kilos > 0 || row.totalCost > 0)
    .sort((left, right) => {
      const fruitCompare = String(left.fruit_name || '').localeCompare(String(right.fruit_name || ''), 'es');
      if (fruitCompare !== 0) return fruitCompare;

      const leftIsProduction = normalizeLabel(left.development_state_name) === 'produccion';
      const rightIsProduction = normalizeLabel(right.development_state_name) === 'produccion';

      if (leftIsProduction !== rightIsProduction) {
        return leftIsProduction ? -1 : 1;
      }

      return String(left.development_state_name || '').localeCompare(String(right.development_state_name || ''), 'es');
    });
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

// Mover 'Cosecha' a la posición 2 en orderedMainTotalsAndPercents
// Además, dividir el total por el divisor (no el porcentaje) solo si dividir.value está activo
const orderedMainTotalsAndPercents = computed(() => {
  const arr = [...props.mainTotalsAndPercents];
  const idx = arr.findIndex(i => i.label === 'Cosecha');
  if (idx !== -1) {
    const [harvest] = arr.splice(idx, 1);
    arr.splice(2, 0, harvest);
  }
  return arr.map(item => ({
    ...item,
    total: dividir.value && divisor.value ? (Number(item.total) / divisor.value) : Number(item.total),
    percent: item.percent
  }));
});

onMounted(() => {
  nextTick(() => {
    if (orderedMainTotalsAndPercents.value && window.echarts) {
      orderedMainTotalsAndPercents.value.forEach((item, idx) => {
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
                  width: 18, // ancho reducido
                  itemStyle: {
                    color: color // color frontal de la barra
                  }
                },
                axisLine: {
                  lineStyle: {
                    width: 18, // ancho reducido
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

<style scoped>
.dashboard-row-highlight > td {
  background-color: rgba(149, 213, 178, 0.24) !important;
}

.dashboard-row-highlight-soft > td {
  background-color: rgba(149, 213, 178, 0.12) !important;
}
</style>

<template>

  <Head :title="title" />
  <AppLayout>
    <div class="container-fluid px-0 py-2" style="max-width: 100vw;">
      <!-- Switch para activar/desactivar la división y divisor slider/input -->
      <div class="row mb-2">
        <div class="col-12">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="form-check form-switch d-flex align-items-center mb-2 me-4">
              <input class="form-check-input" type="checkbox" id="dividir-switch" v-model="dividir">
              <label class="form-check-label ms-2 mb-0" for="dividir-switch">ver en Usd</label>
            </div>
            <template v-if="dividir">
              <div class="d-flex align-items-center flex-grow-1 ms-4" style="min-width:220px;">
                <label for="divisor-slider" class="form-label mb-2 me-2">Divisor:</label>
                <input id="divisor-slider" type="range" class="form-range flex-grow-1" v-model.number="divisor" :min="divisorMin" :max="divisorMax" :step="1" style="max-width:250px;" />
                <span class="text-muted small ms-2"><b>{{ divisor }} CLP</b> ({{divisorMin}}-{{divisorMax}})</span>
              </div>
            </template>
          </div>
        </div>
      </div>

     <!-- Total Presupuestos y Totales por Especie -->
      <div class="row">
        <div class="col-md-3">
          <div class="card ecommerce-card-min-width mb-2">
            <div class="card-header pb-2 bg-info bg-opacity-10 d-flex align-items-center justify-content-between">
              <h6 class="mb-0 mt-1 d-flex align-items-center fs-10">
                Total Presupuestos
                <span class="ms-1 text-300" data-bs-toggle="tooltip" data-bs-placement="top" title="Calculated according to last week's sales">
                  <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                </span>
              </h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-2">
              <div class="row">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-6">
                    {{
                      (props.totalSeason === undefined || props.totalSeason === null || props.totalSeason === '' || isNaN(Number(String(props.totalSeason).replace(/\./g, ''))))
                        ? 'Nodata'
                        : Number(
                            dividir && divisor
                              ? (Number(String(props.totalSeason).replace(/\./g, '')) / divisor)
                              : Number(String(props.totalSeason).replace(/\./g, ''))
                          ).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                    }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>



  <div class="col-md-3">
          <div class="card ecommerce-card-min-width mb-2">
            <div class="card-header pb-2 bg-info bg-opacity-10 d-flex align-items-center justify-content-between">
              <h6 class="mb-0 mt-1 d-flex align-items-center fs-10">
                Total Inversiones
                <span class="ms-1 text-300" data-bs-toggle="tooltip" data-bs-placement="top" title="Calculated according to last week's sales">
                  <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                </span>
              </h6>
            </div>
 <div class="card-body d-flex flex-column justify-content-end py-2">
              <div class="row">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-6">
        {{
          (props.totalInvestments === undefined || props.totalInvestments === null || props.totalInvestments === '' || isNaN(Number(props.totalInvestments)))
            ? 'Nodata'
            : Number(
                dividir && divisor
                  ? (Number(props.totalInvestments) / divisor)
                  : Number(props.totalInvestments)
              ).toLocaleString('es-CL', { maximumFractionDigits: 0 })
        }}
        </p>
      </div>
     
    </div>
  </div>
</div>
  </div>



        <!-- Card por cada fruta -->
        <div class="col-md-3" v-for="(total, fruitId) in totalByFruit" :key="'fruit-card-' + fruitId">
          <div class="card ecommerce-card-min-width mb-2">
            <div class="card-header pb-2 bg-success bg-opacity-10 d-flex align-items-center justify-content-between">
              <h6 class="mb-0 mt-1 d-flex align-items-center fs-10">
                Total {{ fruitNames && fruitNames[fruitId] ? fruitNames[fruitId] : ('Fruta ' + fruitId) }}
              </h6>
              <div class="d-flex align-items-center ms-2" style="margin-bottom:0;">
                <div class="form-check form-switch me-1" style="margin-bottom:0;">
                  <input class="form-check-input" type="checkbox" :id="'switch-prorrateo-' + fruitId" v-model="showProrrateo[fruitId]" :title="'Mostrar prorrateo de Admin+GralCampo para esta especie'">
                </div>
                <label :for="'switch-prorrateo-' + fruitId" class="small text-secondary mb-0" style="cursor:pointer;user-select:none;">+generales</label>
              </div>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-2">
              <div class="row">
                <div class="col">
                  <p class="font-sans-serif lh-1 mb-1 fs-6 text-success">
                    {{
                      (() => {
                        let value;
                        if (showProrrateo[fruitId] && adminFieldsByFruit && adminFieldsByFruit[fruitId]) {
                          value = Number(total) + Number(adminFieldsByFruit[fruitId].admin_fields_total);
                        } else {
                          value = Number(total);
                        }
                        if (dividir && divisor && !isNaN(Number(divisor))) {
                          value = value / Number(divisor);
                        }
                        return value.toLocaleString('es-CL', { maximumFractionDigits: 0 });
                      })()
                    }}
                  </p>
                  <template v-if="showProrrateo[fruitId] && adminFieldsByFruit && adminFieldsByFruit[fruitId]">
                    <p class="lh-1 mb-0 fs-10 text-secondary">
                      <p>Incluye Admin+GralCampo:</p> {{
                        dividir && divisor
                          ? (Number(adminFieldsByFruit[fruitId].admin_fields_total) / divisor).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                          : Number(adminFieldsByFruit[fruitId].admin_fields_total).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                      }}
                    </p>
                  </template>
                </div>
              </div>
            </div>
          </div>

        </div>


         <!-- Superficie -->
          <div class="col-md-3">
            <div class="card ecommerce-card-min-width mb-2">
              <div class="card-header pb-2 bg-info bg-opacity-10 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 mt-1 d-flex align-items-center fs-10">
                  Total Superficie
                </h6>
              </div>
              <div class="card-body d-flex flex-column justify-content-end py-2">
                <div class="row">
                  <div class="col">
                    <p class="font-sans-serif lh-1 mb-1 fs-6">
                    <span class="fs-6">{{ parseFloat(totalSurface).toFixed(1) }}</span>
                    <span class="fs-8"> Hectáreas</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>


      <!-- Card de 7 gauge ring charts con porcentajes de cada rubro -->
      <div class="row mt-2 mb-2">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-header pb-1 pt-1">
              <h6 class="mb-0">Indicadores por rubro (porcentaje del total)</h6>
            </div>
            <div class="card-body pt-1 pb-1">
              <div class="d-flex flex-nowrap overflow-auto justify-content-center align-items-stretch w-100">
                <div
                  v-for="(item, idx) in orderedMainTotalsAndPercents"
                  :key="'gauge-' + idx"
                  class="falcon-gauge-card d-flex flex-column align-items-center justify-content-center mb-1 rounded"
                  :class="{
                    'bg-secondary bg-opacity-10': item.label === 'Generales Campo' || item.label === 'Administración',
                    'bg-success bg-opacity-10': item.label === 'Cosecha'
                  }"
                  style="flex: 1 1 0; min-width: 120px;"
                >
                  <div
                    class="echart-gauge-ring-chart-example mx-auto"
                    :id="'gauge-ring-' + idx"
                    style="min-height: 120px; width: 100%; max-width: 160px;"
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

      <div class="row mt-4 mb-2" v-if="summaryByFruitDevState.length">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0"><i class="fas fa-chart-line me-2 text-secondary"></i>KPI por Frutal / Estado de Desarrollo</h6>
            <span class="badge bg-soft-primary text-primary">{{ dividir ? 'USD' : 'CLP' }}</span>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3" v-if="summaryByFruitDevState.length">
        <div v-for="row in summaryByFruitDevState" :key="'fruit-dev-kpi-' + row.fruit_id + '-' + row.development_state_id" class="col-12 col-md-6 col-xl-4">
          <div class="card border border-primary h-100">
            <div class="card-header py-2 bg-primary bg-opacity-10">
              <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-apple-alt me-1" :class="normalizeLabel(row.fruit_name).includes('cerez') ? 'text-danger' : 'text-success'"></i>{{ row.fruit_name }}</h6>
              <small class="text-muted">{{ row.development_state_name }}</small>
            </div>
            <div class="card-body py-2">
              <div class="row g-1">
                <div class="col-6">
                  <small class="text-muted d-block">Superficie</small>
                  <strong>{{ row.surface > 0 ? row.surface.toLocaleString('es-CL', { maximumFractionDigits: 1 }) + ' ha' : '-' }}</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Kilos Est.</small>
                  <strong>{{ row.kilos > 0 ? formatNumber(Math.round(row.kilos)) : '-' }}</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Costo/Ha</small>
                  <strong class="text-primary">{{ row.costPerHa !== null ? formatNumber(Math.round(row.costPerHa)) : '-' }}</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Costo/Kg</small>
                  <strong class="text-success">{{ row.costPerKg !== null ? row.costPerKg.toLocaleString('es-CL', { maximumFractionDigits: 2 }) : '-' }}</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Costo Directo</small>
                  <strong>{{ formatNumber(Math.round(row.directCostTotal)) }}</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Adm + Campo</small>
                  <strong>{{ formatNumber(Math.round(row.adminFieldsTotal)) }}</strong>
                </div>
                <div class="col-12 mt-1 border-top pt-1">
                  <small class="text-muted d-block">Costo Total</small>
                  <strong class="fs-6">
                    {{ formatNumber(Math.round(row.totalCost)) }}
                    <span style="font-size: 0.8em;">{{ dividir ? 'USD' : 'CLP' }}</span>
                  </strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-xl-6 d-flex">
          <div class="card shadow-sm mt-2 border-0 bg-white w-100">
            <div class="card-header bg-white border-0 pb-2 pt-3 d-flex align-items-center">
              <span class="me-2"><span class="fas fa-chart-bar text-primary"></span></span>
              <h6 class="mb-0">Gráfico: Totales agrupados por Nivel 1</h6>
            </div>
            <div class="card-body pt-3 pb-3 px-4">
              <div class="falcon-bar-chart-container" style="height:320px;">
                <FalconBarChart
                  :barLabels="barChartFromTable.map(item => item.level1_name)"
                  :barData="barChartFromTable.map(item => item.total_amount)"
                  :color="barChartColors"
                  :height="320"
                  @bar-click="handleLevel1BarClick"
                />
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-6 d-flex">
          <div class="card mt-2 w-100">
            <div class="card-body bg-body-tertiary py-2 d-flex align-items-center justify-content-center">
              <FalconPieChart :pieLabels="pieLabels" :pieDatasets="pieDatasets" />
            </div>
          </div>
        </div>
      </div>

      <!-- Tablas resumen presupuesto lado a lado -->
      <div class="row mt-3 mb-2">
        <!-- Tabla por Nivel 1 / Nivel 2 -->
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-header py-2 position-relative">
              <h5 class="mb-0 text-center fs-8" style="font-weight: 600;">
                <i class="fas fa-table text-info me-2"></i>
                Resumen por Área (Nivel 1)
              </h5>
              <div class="position-absolute top-50 end-0 translate-middle-y me-3 d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllBudget" v-tooltip="'Expandir todo'">
                    <i class="fas fa-expand-alt"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllBudget" v-tooltip="'Colapsar todo'">
                    <i class="fas fa-compress-alt"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                  <thead class="bg-light">
                    <tr>
                      <th class="border-0 py-2">
                        <span class="text-uppercase fw-bold">Nivel 1 / Nivel 2</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">Monto</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">% Total</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">% Área</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(group, gi) in summaryByLevel12" :key="'bg-'+gi">
                      <tr class="table-light" :class="{ 'dashboard-row-highlight': isSelectedLevel1(group.level1_id) }" style="cursor: pointer;" @click="toggleBudgetGroup('bg-'+gi)">
                        <td class="py-2 fw-bold text-primary">
                          <i class="fas me-2" :class="expandedBudgetGroups.has('bg-'+gi) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                          {{ group.level1_name }}
                          <small class="text-muted ms-1">({{ group.level2s.length }})</small>
                        </td>
                        <td class="py-2 text-end fw-bold text-primary">
                          {{ formatNumber(Math.round(group.total)) }}
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-primary">{{ summaryGrandTotal > 0 ? ((group.total / summaryGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-info">100%</span>
                        </td>
                      </tr>
                      <tr v-if="expandedBudgetGroups.has('bg-'+gi)" v-for="l2 in group.level2s" :key="'bl2-'+group.level1_id+'-'+l2.level2_id" :class="{ 'dashboard-row-highlight-soft': isSelectedLevel1(group.level1_id) }">
                        <td class="py-2 ps-5">{{ l2.level2_name }}</td>
                        <td class="py-2 text-end">
                          {{ formatNumber(Math.round(l2.total)) }}
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-secondary">{{ summaryGrandTotal > 0 ? ((l2.total / summaryGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-light text-dark">{{ group.total > 0 ? ((l2.total / group.total) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                      </tr>
                    </template>
                    <tr class="table-primary fw-bold">
                      <td class="py-2">Total</td>
                      <td class="py-2 text-end">
                        {{ formatNumber(Math.round(summaryGrandTotal)) }}
                      </td>
                      <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                      <td class="py-2 text-end"><span class="text-muted">-</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- Tabla por Categoría (Nivel 2) -->
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-header py-2 position-relative">
              <h5 class="mb-0 text-center fs-8" style="font-weight: 600;">
                <i class="fas fa-tags text-info me-2"></i>
                Resumen por Categoría (Nivel 2)
              </h5>
              <div class="position-absolute top-50 end-0 translate-middle-y me-3 d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="expandAllCategory" v-tooltip="'Expandir todo'">
                    <i class="fas fa-expand-alt"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="collapseAllCategory" v-tooltip="'Colapsar todo'">
                    <i class="fas fa-compress-alt"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                  <thead class="bg-light">
                    <tr>
                      <th class="border-0 py-2">
                        <span class="text-uppercase fw-bold">Categoría / Área</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">Monto</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">% Total</span>
                      </th>
                      <th class="border-0 py-2 text-end">
                        <span class="text-uppercase fw-bold">% Cat.</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(group, gi) in summaryByCategory" :key="'cat-'+gi">
                      <tr class="table-light" :class="{ 'dashboard-row-highlight': categoryContainsSelectedLevel1(group) }" style="cursor: pointer;" @click="toggleCategoryGroup('cat-'+gi)">
                        <td class="py-2 fw-bold text-primary">
                          <i class="fas me-2" :class="expandedCategoryGroups.has('cat-'+gi) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                          {{ group.category_name }}
                          <small class="text-muted ms-1">({{ group.items.length }})</small>
                        </td>
                        <td class="py-2 text-end fw-bold text-primary">
                          {{ formatNumber(Math.round(group.total)) }}
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-primary">{{ summaryGrandTotal > 0 ? ((group.total / summaryGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-info">100%</span>
                        </td>
                      </tr>
                      <tr v-if="expandedCategoryGroups.has('cat-'+gi)" v-for="item in group.items" :key="'cl1-'+gi+'-'+item._key" :class="{ 'dashboard-row-highlight-soft': isSelectedLevel1(item.level1_id) }">
                        <td class="py-2 ps-5">{{ item.level1_name }} <span class="text-muted">({{ item.originalLabel }})</span></td>
                        <td class="py-2 text-end">
                          {{ formatNumber(Math.round(item.total)) }}
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-secondary">{{ summaryGrandTotal > 0 ? ((item.total / summaryGrandTotal) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                        <td class="py-2 text-end">
                          <span class="badge bg-light text-dark">{{ group.total > 0 ? ((item.total / group.total) * 100).toFixed(1) : '0.0' }}%</span>
                        </td>
                      </tr>
                    </template>
                    <tr class="table-primary fw-bold">
                      <td class="py-2">Total</td>
                      <td class="py-2 text-end">
                        {{ formatNumber(Math.round(summaryGrandTotal)) }}
                      </td>
                      <td class="py-2 text-end"><span class="badge bg-primary">100%</span></td>
                      <td class="py-2 text-end"><span class="text-muted">-</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-2 g-xl-3">
        <!-- Columna izquierda: Weather, superficie, total presupuestos -->
        <div class="col-12 d-flex flex-column">
          <!-- Weather card (comentado) -->
          <!--
          <div class="card mb-3" v-if="weather">
            <div class="card-body py-2 d-flex align-items-center">
              <img :src="weather.current ? weather.current.condition.icon : ''" alt="icon" style="width:32px;height:32px;" class="me-2" />
              <div>
                <div class="fw-bold">Clima en {{ weatherCity || userCity || weather.location ? weather.location.name : '' }}</div>
                <div class="">{{ weather.current ? weather.current.temp_c : '' }} °C, {{ weather.current ? weather.current.condition.text : '' }}</div>
              </div>
            </div>
          </div>
          -->


          <div class="card ecommerce-card-min-width mb-3 mt-2">
            <div class="card-header pb-2 bg-info bg-opacity-10">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 mt-1 d-flex align-items-center fs-10">Estimación de kilos por Especie
                  <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Kilos estimados agrupados por fruta">
                    <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                  </span>
                </h5>
                <select v-if="estimateOptions && estimateOptions.length" v-model="selectedEstimateStatusId" class="form-select form-select-sm" style="width:auto;max-width:200px;font-size:0.75rem;">
                  <option v-for="opt in estimateOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                </select>
              </div>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-1 align-items-center">
              <div v-if="Object.keys(activeKilosByFruit).length" class="d-flex flex-wrap gap-1 align-items-center">
                <div v-for="(kilos, fruitId, idx) in activeKilosByFruit" :key="fruitId" class="d-flex align-items-center">
                  <span class="fs-9 text-secondary me-1">{{ fruitNames && fruitNames[fruitId] ? fruitNames[fruitId] : ('Fruta ' + fruitId) }}:</span>
                  <span class="lh-1 fs-9 text-primary">{{ Number(kilos).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }} <span class="text-secondary">Kg</span></span>
                  <span v-if="idx < Object.keys(activeKilosByFruit).length - 1" class="mx-4" style="border-left:1px solid #bbb;height:60px;"></span>
                </div>
              </div>
              <div v-else>
                <span class="text-muted">No hay datos de kilos estimados.</span>
              </div>
            </div>
          </div>

          <!-- Nuevo card: Costo kilo cosecha por especie -->
          <div class="card ecommerce-card-min-width mb-3">
            <div class="card-header pb-2 bg-info bg-opacity-10">
              <h5 class="mb-0 mt-1 d-flex align-items-center fs-10">Costo kilo cosecha (sin admin ni gral campo)
                <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Costo de cosecha por kilo, por especie (total cosecha / kilos estimados)">
                  <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                </span>
              </h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-1 align-items-center">
              <div v-if="Object.keys(activeKilosByFruit).length && totalByFruit && Object.keys(totalByFruit).length" class="d-flex flex-wrap gap-1 align-items-center">
                <div v-for="(kilos, fruitId, idx) in activeKilosByFruit" :key="'costo-kilo-' + fruitId" class="d-flex align-items-center">
                  <span class="fs-9 text-secondary me-1">{{ fruitNames && fruitNames[fruitId] ? fruitNames[fruitId] : ('Fruta ' + fruitId) }}:</span>
                  <span class="lh-1 fs-9 text-primary">{{ (() => {
                    // Usar el totalHarvest por especie si existe
                    let totalHarvestByFruit = (props.totalHarvestByFruit && props.totalHarvestByFruit[fruitId]) ? Number(props.totalHarvestByFruit[fruitId]) : null;
                    if (totalHarvestByFruit === null) return 'No data';
                    let total = totalHarvestByFruit;
                    const kilosNum = Number(kilos);
                    if (dividir && divisor && !isNaN(Number(divisor))) {
                      total = total / Number(divisor);
                    }
                    if (!kilosNum || isNaN(total) || kilosNum === 0) return 'No data';
                    const costoKilo = total / kilosNum;
                    return costoKilo.toLocaleString('es-CL', { maximumFractionDigits: 2 });
                  })() }} <span class="text-secondary">{{ dividir ? 'USD/Kg' : 'CLP/Kg' }}</span></span>
                  <span v-if="idx < Object.keys(activeKilosByFruit).length - 1" class="mx-4" style="border-left:1px solid #bbb;height:60px;"></span>
                </div>
              </div>
              <div v-else>
                <span class="text-muted">No hay datos de costo kilo cosecha.</span>
              </div>
            </div>
          </div>

          <!-- Nuevo card: Costo kilo total (incluye admin+gral campo) -->
          <div class="card ecommerce-card-min-width mb-3">
            <div class="card-header pb-2 bg-info bg-opacity-10">
              <h5 class="mb-0 mt-1 d-flex align-items-center fs-10">Costo kilo total (incluye admin+gral campo)
                <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Costo total por kilo, por especie (total especie + admin+gral campo / kilos estimados)">
                  <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                </span>
              </h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-end py-1 align-items-center">
              <div v-if="Object.keys(activeKilosByFruit).length && totalByFruit && Object.keys(totalByFruit).length && adminFieldsByFruit && Object.keys(adminFieldsByFruit).length" class="d-flex flex-wrap gap-1 align-items-center">
                <div v-for="(kilos, fruitId, idx) in activeKilosByFruit" :key="'costo-kilo-total-' + fruitId" class="d-flex align-items-center">
                  <span class="fs-9 text-secondary me-1">{{ fruitNames && fruitNames[fruitId] ? fruitNames[fruitId] : ('Fruta ' + fruitId) }}:</span>
                  <span class="lh-1 fs-9 text-primary">{{ (() => {
                    let total = 0;
                    if (totalByFruit[fruitId] !== undefined) {
                      total += Number(totalByFruit[fruitId]);
                    }
                    if (adminFieldsByFruit[fruitId] && adminFieldsByFruit[fruitId].admin_fields_total !== undefined) {
                      total += Number(adminFieldsByFruit[fruitId].admin_fields_total);
                    }
                    const kilosNum = Number(kilos);
                    if (dividir && divisor && !isNaN(Number(divisor))) {
                      total = total / Number(divisor);
                    }
                    if (!kilosNum || isNaN(total) || kilosNum === 0) return 'No data';
                    const costoKilo = total / kilosNum;
                    return costoKilo.toLocaleString('es-CL', { maximumFractionDigits: 2 });
                  })() }} <span class="text-secondary">{{ dividir ? 'USD/Kg' : 'CLP/Kg' }}</span></span>
                  <span v-if="idx < Object.keys(activeKilosByFruit).length - 1" class="mx-4" style="border-left:1px solid #bbb;height:60px;"></span>
                </div>
              </div>
              <div v-else>
                <span class="text-muted">No hay datos de costo kilo total.</span>
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
      </div>
    </div>
  </AppLayout>
</template>
