<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import {computed} from 'vue'
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import { ref } from 'vue';



const divisor = ref(1000);
const divisorMin = 800;
const divisorMax = 1100;
const dividir = ref(false); // Por defecto, no dividir

const formatNumber = (value) => {
  if (typeof value !== 'number' || isNaN(value)) return '';
  return value.toLocaleString('es-CL', { maximumFractionDigits: 0 });
};



const title = 'Panel Tecnico';
const links = [{ title: 'Panel Tecnico', link: 'technicalpanel', active: true }];




const props = defineProps({
  fruitsMap: Object,
    totalSeason: String,
    pieLabels: Array,
    pieDatasets: Array,
    months: Array,
    monthsAgrochemical: Object,
    monthsFertilizer: Object,
    monthsManPower: Object,
    monthsServices: Object,
    monthsHarvests: Object,
    monthsSupplies: Object,
    monthsAdministration: Object,
  monthsFields: Object,
  totalAgrochemical: Number,
  totalFertilizer: Number,
  totalManPower: Number,
  totalSupplies: Number,
  totalServices: Number,
  totalHarvests: Number,
  agrochemicalByDevState: Object,
  fertilizerByDevState: Object,
  manPowerByDevState: Object,
  servicesByDevState: Object, // <-- agregar
  harvestsByDevState: Object, // <-- agregar
  suppliesByDevState: Object, // <-- agregar
  agrochemicalExpensePerHectare: Object,
  fertilizerExpensePerHectare: Object,
  manPowerExpensePerHectare: Object, // <-- agregar
  servicesExpensePerHectare: Object, // <-- agregar
  suppliesExpensePerHectare: Object, // <-- agregar
  harvestsExpensePerHectare: Object, // <-- agregar
  devStates: Object, // <-- nombres de estados de desarrollo
  administrationTotalsByLevel12: Array, // <-- agregar prop para la tabla de administración
  fieldTotalsByLevel12: Array, // <-- agregar prop para la tabla de fields
  totalsByLevel12: Array, // <-- nuevo prop para la tabla de totales generales

  totalSurface: Number, // <-- AGREGADO para mostrar superficie total
  entityCounts: Object, // <-- para la tabla de conteos
  totalAdministration: Number, // <-- AGREGADO para mostrar administración total
  mainTotalsAndPercents: Array // <-- nuevo prop para los gauges
});



// Calcular el total de administración sumando los montos de administrationTotalsByLevel12
const totalAdministrationCalc = computed(() => {
  return (props.administrationTotalsByLevel12 || []).reduce((sum, r) => sum + Number(r.total_amount || 0), 0)
})

// Calcular el total de campo sumando los montos de fieldTotalsByLevel12
const totalFieldsCalc = computed(() => {
  return (props.fieldTotalsByLevel12 || []).reduce((sum, r) => sum + Number(r.total_amount || 0), 0)
})





// Agrupar por Level1 y Level2, y mapear subtotales por fruta, sumando administración/campo repartido

function groupTotalsByLevelAndFruit() {
  // Obtener especies (fruits) presentes
  const fruits = props.totalsByLevel12?.reduce((acc, r) => {
    if (r.fruit_id && r.fruit_name) acc[r.fruit_id] = r.fruit_name;
    return acc;
  }, {}) || {};
  const fruitIds = Object.keys(fruits);
  // Calcular superficies totales y por fruta
  let totalSurface = 0;
  const surfaceByFruit = {};
  (props.totalsByLevel12 || []).forEach(row => {
    if (row.fruit_id && row.surface) {
      surfaceByFruit[row.fruit_id] = (surfaceByFruit[row.fruit_id] || 0) + Number(row.surface);
      totalSurface += Number(row.surface);
    }
  });
  // Agrupar totales generales por level1/level2/fruta
  const groups = {};
  (props.totalsByLevel12 || []).forEach(row => {
    const key = row.level1_id + '-' + row.level2_id;
    if (!groups[key]) {
      groups[key] = {
        level1_id: row.level1_id,
        level1_name: row.level1_name,
        level2_id: row.level2_id,
        level2_name: row.level2_name,
        fruits: {}
      };
    }
    if (row.fruit_id) {
      groups[key].fruits[row.fruit_id] = row.total_amount;
    }
  });
  // Prorratear administración y campo repartido según superficie
  (props.administrationTotalsByLevel12 || []).forEach(row => {
    const key = row.level1_id + '-' + row.level2_id;
    fruitIds.forEach(fruitId => {
      const prop = totalSurface > 0 ? (surfaceByFruit[fruitId] || 0) / totalSurface : 0;
      const prorrateo = Number(row.total_amount) * prop;
      if (!groups[key]) {
        groups[key] = {
          level1_id: row.level1_id,
          level1_name: row.level1_name,
          level2_id: row.level2_id,
          level2_name: row.level2_name,
          fruits: {}
        };
      }
      groups[key].fruits[fruitId] = (groups[key].fruits[fruitId] || 0) + prorrateo;
    });
  });
  (props.fieldTotalsByLevel12 || []).forEach(row => {
    const key = row.level1_id + '-' + row.level2_id;
    fruitIds.forEach(fruitId => {
      const prop = totalSurface > 0 ? (surfaceByFruit[fruitId] || 0) / totalSurface : 0;
      const prorrateo = Number(row.total_amount) * prop;
      if (!groups[key]) {
        groups[key] = {
          level1_id: row.level1_id,
          level1_name: row.level1_name,
          level2_id: row.level2_id,
          level2_name: row.level2_name,
          fruits: {}
        };
      }
      groups[key].fruits[fruitId] = (groups[key].fruits[fruitId] || 0) + prorrateo;
    });
  });
  // Ordenar los grupos por level1_id y luego por level2_id
  const sortedGroups = Object.values(groups).sort((a, b) => {
    if (a.level1_id !== b.level1_id) {
      return a.level1_id - b.level1_id;
    }
    return a.level2_id - b.level2_id;
  });
  return { groups: sortedGroups, fruits };
}



 
</script>

<template>
   <Head :title="title" />
  <AppLayout>
    <div class="container-fluid px-2 py-0" style="max-width: 100vw;">
     
      <!-- Switch para activar/desactivar la división y divisor slider/input -->
<div class="row mb-2">
  <div class="col-12">
    <div class="d-flex flex-wrap align-items-center gap-3">
      <div class="form-check form-switch d-flex align-items-center mb-0 me-4">
        <input class="form-check-input" type="checkbox" id="dividir-switch" v-model="dividir">
        <label class="form-check-label ms-2 mb-0" for="dividir-switch">ver en Usd</label>
      </div>
      <template v-if="dividir">
        <div class="d-flex align-items-center flex-grow-1 ms-4" style="min-width:220px;">
          <label for="divisor-slider" class="form-label mb-0 me-2">Divisor:</label>
          <input id="divisor-slider" type="range" class="form-range flex-grow-1" v-model.number="divisor" :min="divisorMin" :max="divisorMax" :step="1" style="max-width:250px;" />
          <span class="text-muted small ms-2"><b>{{ divisor }} CLP</b> ({{divisorMin}}-{{divisorMax}}) </span>
        </div>
      </template>
    </div>
  </div>
</div>
     

      <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-1 pb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="card-body pt-2 pb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="mb-0">Estado de Desarrollo</h5>
                  <ExportExcelButton
                    class="ms-2"
                    :file-name="'estado_desarrollo.xlsx'"
                    table-id="tabla-estado-desarrollo"
                  />
                </div>
              
                <table id="tabla-estado-desarrollo" class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-start text-uppercase text-secondary small fw-bold small">Especie</th>
                      <th class="text-start text-uppercase text-secondary small fw-bold small">Estado de desarrollo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Agroquímicos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Fertilizantes</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Mano de Obra</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Servicios</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Insumos</th>
                       <th class="text-center text-uppercase text-secondary small fw-bold small">Cosecha</th>
                      <!-- Puedes agregar más columnas aquí si lo deseas -->
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(devStatesObj, fruitId) in agrochemicalByDevState" :key="fruitId">
                      <tr v-for="(amount, devStateId, idx) in devStatesObj" :key="fruitId + '-' + devStateId">
                        <td v-if="idx === 0" :rowspan="Object.keys(devStatesObj).length" class="align-top small">
                          {{ $props.fruitsMap?.[String(fruitId)] || 'Sin especie' }}
                        </td>
                        <td class="small">{{ devStates[devStateId]?.name || 'Sin estado' }}</td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(amount || 0) / divisor) : Number(amount || 0)) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(fertilizerByDevState?.[fruitId]?.[devStateId] ?? 0) / divisor) : Number(fertilizerByDevState?.[fruitId]?.[devStateId] ?? 0)) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(manPowerByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(manPowerByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(servicesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(servicesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(suppliesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(suppliesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Number(harvestsByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(harvestsByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}
                        </td>
                        <!-- Puedes agregar más columnas aquí si lo deseas -->
                      </tr>
                      <!-- Subtotal por especie -->
                      <tr class="table-secondary small" style="font-size:0.8em;">
                        <td colspan="2" class="text-end">Subtotal {{ $props.fruitsMap?.[String(fruitId)] || 'Sin especie' }}</td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(devStatesObj).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(devStatesObj).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(fertilizerByDevState?.[fruitId] || {}).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(fertilizerByDevState?.[fruitId] || {}).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(manPowerByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(manPowerByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(servicesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(servicesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(suppliesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(suppliesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                        <td class="text-center text-end">
                          {{ formatNumber(dividir && divisor ? (Object.values(harvestsByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0) / divisor) : Object.values(harvestsByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0)) }}
                        </td>
                      </tr>
                    </template>
                  </tbody>
                  <tfoot>
                    <tr style="background-color: #555858; font-weight: bold;">
                      <td colspan="2" class="fw-bold text-end small text-white">Total General</td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(agrochemicalByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(agrochemicalByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(fertilizerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(fertilizerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(manPowerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(manPowerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(servicesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(servicesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(suppliesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(suppliesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ formatNumber(dividir && divisor ? (Object.values(harvestsByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0) / divisor) : Object.values(harvestsByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0)) }}
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

      <div class="row mt-4">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-1 pb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="card-body pt-2 pb-2">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Gastos por Hectáreas</h5>
                    <ExportExcelButton
                      class="ms-2"
                      :file-name="'gastos_por_hectarea.xlsx'"
                      table-id="tabla-gastos-hectarea"
                    />
                  </div>
                  <table id="tabla-gastos-hectarea" class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-start text-uppercase text-secondary small fw-bold small">Especie</th>
                      <th class="text-start text-uppercase text-secondary small fw-bold small">Estado de desarrollo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Agroquímicos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Fertilizantes</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Mano de Obra</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Servicios</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Insumos</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Cosecha</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Gral campo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Administración</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(devStatesObj, fruitId) in agrochemicalExpensePerHectare" :key="'hect-fruit-' + fruitId">
                      <tr v-for="(amount, devStateId, idx) in devStatesObj" :key="'hect-' + fruitId + '-' + devStateId" class="border-bottom">
                        <td v-if="idx === 0" :rowspan="Object.keys(devStatesObj).length" class="align-top small">
                          {{ $props.fruitsMap?.[String(fruitId)] || 'Sin especie' }}
                        </td>
                        <td class="text-start fw-semibold small">{{ devStates[devStateId]?.name || 'Sin estado' }}</td>
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(amount || 0) / divisor) : Number(amount || 0)) }}</td>
                       
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(fertilizerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(fertilizerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}</td>
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}</td>
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}</td>
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}</td>
                        <td class="text-center text-small text-warning fw-bold small">{{ formatNumber(dividir && divisor ? (Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) / divisor) : Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0)) }}</td>
                        <td class="text-center text-warning fw-bold small">
                          {{
                            formatNumber(dividir && divisor ? ((totalFieldsCalc / (totalSurface || 1)) / divisor) : (totalFieldsCalc / (totalSurface || 1)))
                          }}
                        </td>
                        <td class="text-center text-warning fw-bold small">
                          {{
                            formatNumber(dividir && divisor ? ((totalAdministrationCalc / (totalSurface || 1)) / divisor) : (totalAdministrationCalc / (totalSurface || 1)))
                          }}
                        </td>
                        <td class="text-center text-black fw-bold small">
                          {{
                            formatNumber(dividir && divisor
                              ? (
                                  (Number(amount || 0) +
                                  Number(fertilizerExpensePerHectare?.[fruitId]?.[devStateId] ?? 0) +
                                  Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  (totalFieldsCalc / (totalSurface || 1)) +
                                  (totalAdministrationCalc / (totalSurface || 1))
                                  ) / divisor
                                )
                              : (
                                  Number(amount || 0) +
                                  Number(fertilizerExpensePerHectare?.[fruitId]?.[devStateId] ?? 0) +
                                  Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                                  (totalFieldsCalc / (totalSurface || 1)) +
                                  (totalAdministrationCalc / (totalSurface || 1))
                                )
                            )
                          }}</td>
                      </tr>
                    </template>
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
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Totales unificados por nivel 1 y nivel 2</h5>
                <ExportExcelButton
                  class="ms-2"
                  :file-name="'totales_nivel1_nivel2.xlsx'"
                  table-id="tabla-totales-nivel1-nivel2"
                />
              </div>
              <div class="table-responsive scrollbar">
                <table id="tabla-totales-nivel1-nivel2" class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
                  <thead class="table-light border-bottom">
                    <tr>
                      <th class="text-uppercase text-secondary small fw-bold small">Level 1</th>
                      <th class="text-uppercase text-secondary small fw-bold small">Level 2</th>
                      <th v-for="(fruitName, fruitId) in groupTotalsByLevelAndFruit().fruits" :key="'fruit-col-' + fruitId" class="text-end text-uppercase text-secondary small fw-bold small">{{ fruitName }}</th>
                      <th class="text-end text-uppercase text-secondary small fw-bold small text-primary">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(group, idx) in groupTotalsByLevelAndFruit().groups" :key="'row-' + group.level1_id + '-' + group.level2_id">
                      <tr>
                        <td v-if="idx === 0 || group.level1_id !== groupTotalsByLevelAndFruit().groups[idx - 1].level1_id"
                            class="align-top small"
                            :rowspan="groupTotalsByLevelAndFruit().groups.filter(g => g.level1_id === group.level1_id).length">
                          {{ group.level1_name }}
                        </td>
                        <td class="small">{{ group.level2_name }}</td>
                        <td v-for="(fruitName, fruitId) in groupTotalsByLevelAndFruit().fruits" :key="'cell-' + group.level1_id + '-' + group.level2_id + '-' + fruitId" class="text-end small">
                          {{ formatNumber(dividir && divisor ? (Number(group.fruits[fruitId] || 0) / divisor) : Number(group.fruits[fruitId] || 0)) }}
                        </td>
                        <td class="text-end text-primary fw-bold small">
                          {{ formatNumber(dividir && divisor ? (Object.keys(group.fruits).reduce((sum, fruitId) => sum + Number(group.fruits[fruitId] || 0), 0) / divisor) : Object.keys(group.fruits).reduce((sum, fruitId) => sum + Number(group.fruits[fruitId] || 0), 0)) }}
                        </td>
                      </tr>
                      <!-- Subtotal por Level 1 -->
                      <tr v-if="idx === groupTotalsByLevelAndFruit().groups.length - 1 || group.level1_id !== groupTotalsByLevelAndFruit().groups[idx + 1].level1_id" class="table-secondary fw-bold small">
                        <td class="text-end" colspan="2">Subtotal {{ group.level1_name }}</td>
                        <td v-for="(fruitName, fruitId) in groupTotalsByLevelAndFruit().fruits" :key="'subtotal-' + group.level1_id + '-' + fruitId" class="text-end">
                          {{ formatNumber(dividir && divisor ? (groupTotalsByLevelAndFruit().groups.filter(g => g.level1_id === group.level1_id).reduce((sum, g) => sum + Number(g.fruits[fruitId] || 0), 0) / divisor) : groupTotalsByLevelAndFruit().groups.filter(g => g.level1_id === group.level1_id).reduce((sum, g) => sum + Number(g.fruits[fruitId] || 0), 0)) }}
                        </td>
                        <td class="text-end text-primary">
                          {{ formatNumber(dividir && divisor ? (groupTotalsByLevelAndFruit().groups.filter(g => g.level1_id === group.level1_id).reduce((sum, g) => sum + Object.keys(g.fruits).reduce((s, fruitId) => s + Number(g.fruits[fruitId] || 0), 0), 0) / divisor) : groupTotalsByLevelAndFruit().groups.filter(g => g.level1_id === group.level1_id).reduce((sum, g) => sum + Object.keys(g.fruits).reduce((s, fruitId) => s + Number(g.fruits[fruitId] || 0), 0), 0)) }}
                        </td>
                      </tr>
                    </template>
                  </tbody>
                  <tfoot>
                    <tr style="background-color: #555858; font-weight: bold;">
                      <td class="fw-bold text-end small text-white" colspan="2">Total General</td>
                      <td v-for="(fruitName, fruitId) in groupTotalsByLevelAndFruit().fruits" :key="'total-' + fruitId" class="fw-bold text-end small text-white">
                        {{ formatNumber(dividir && divisor ? (groupTotalsByLevelAndFruit().groups.reduce((sum, group) => sum + Number(group.fruits[fruitId] || 0), 0) / divisor) : groupTotalsByLevelAndFruit().groups.reduce((sum, group) => sum + Number(group.fruits[fruitId] || 0), 0)) }}
                      </td>
                      <td class="fw-bold text-end small text-white">
                        {{ formatNumber(dividir && divisor ? (groupTotalsByLevelAndFruit().groups.reduce((sum, group) => sum + Object.keys(group.fruits).reduce((s, fruitId) => s + Number(group.fruits[fruitId] || 0), 0), 0) / divisor) : groupTotalsByLevelAndFruit().groups.reduce((sum, group) => sum + Object.keys(group.fruits).reduce((s, fruitId) => s + Number(group.fruits[fruitId] || 0), 0), 0)) }}
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
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Totales Mensuales</h5>
                <ExportExcelButton
                  class="ms-2"
                  :file-name="'totales_mensuales.xlsx'"
                  table-id="tabla-totales-mensuales"
                />
              </div>
              <div class="table-responsive scrollbar">
                <table id="tabla-totales-mensuales" class="table table-sm table-hover align-middle border rounded shadow-sm bg-white">
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
                        {{ formatNumber(dividir && divisor ? Object.values(monthsAdministration || {}).reduce((sum, v) => sum + (v || 0), 0) / divisor : Object.values(monthsAdministration || {}).reduce((sum, v) => sum + (v || 0), 0)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsAdministration && monthsAdministration[value.value] ? monthsAdministration[value.value] / divisor : 0) : (monthsAdministration && monthsAdministration[value.value] ? monthsAdministration[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Gral Campo</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? Object.values(monthsFields || {}).reduce((sum, v) => sum + (v || 0), 0) / divisor : Object.values(monthsFields || {}).reduce((sum, v) => sum + (v || 0), 0)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsFields && monthsFields[value.value] ? monthsFields[value.value] / divisor : 0) : (monthsFields && monthsFields[value.value] ? monthsFields[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Agroquimicos</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalAgrochemical) / divisor) : Number(totalAgrochemical)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsAgrochemical && monthsAgrochemical[value.value] ? monthsAgrochemical[value.value] / divisor : 0) : (monthsAgrochemical && monthsAgrochemical[value.value] ? monthsAgrochemical[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Fertilizantes</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalFertilizer) / divisor) : Number(totalFertilizer)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsFertilizer && monthsFertilizer[value.value] ? monthsFertilizer[value.value] / divisor : 0) : (monthsFertilizer && monthsFertilizer[value.value] ? monthsFertilizer[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Mano de obra</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalManPower) / divisor) : Number(totalManPower)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsManPower && monthsManPower[value.value] ? monthsManPower[value.value] / divisor : 0) : (monthsManPower && monthsManPower[value.value] ? monthsManPower[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Insumos</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalSupplies) / divisor) : Number(totalSupplies)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsSupplies && monthsSupplies[value.value] ? monthsSupplies[value.value] / divisor : 0) : (monthsSupplies && monthsSupplies[value.value] ? monthsSupplies[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Servicios</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalServices) / divisor) : Number(totalServices)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsServices && monthsServices[value.value] ? monthsServices[value.value] / divisor : 0) : (monthsServices && monthsServices[value.value] ? monthsServices[value.value] : 0)) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold small">Cosecha</td>
                      <td class="text-end text-primary fw-bold small">
                        {{ formatNumber(dividir && divisor ? (Number(totalHarvests) / divisor) : Number(totalHarvests)) }}
                      </td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">
                        {{ formatNumber(dividir && divisor ? (monthsHarvests && monthsHarvests[value.value] ? monthsHarvests[value.value] / divisor : 0) : (monthsHarvests && monthsHarvests[value.value] ? monthsHarvests[value.value] : 0)) }}
                      </td>
                    </tr>

                    <!-- ...existing code... -->
                  </tbody>
                  <tfoot>
                    <tr class="table-secondary">
                      <td class="fw-bold text-end small">Total mes</td>
                      <td class="fw-bold text-end small text-primary">
                        {{ formatNumber(
                          dividir && divisor
                            ? [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
                                .reduce((sum, obj) => sum + Object.values(obj || {}).reduce((s, v) => s + (v || 0), 0), 0) / divisor
                            : [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
                                .reduce((sum, obj) => sum + Object.values(obj || {}).reduce((s, v) => s + (v || 0), 0), 0)
                        ) }}
                      </td>
                      <td class="fw-bold text-end small" v-for="value in months">
                        {{ formatNumber(
                          dividir && divisor
                            ? ([monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
                                .reduce((sum, obj) => sum + (obj && obj[value.value] ? obj[value.value] : 0), 0) / divisor)
                            : ([monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
                                .reduce((sum, obj) => sum + (obj && obj[value.value] ? obj[value.value] : 0), 0))
                        ) }}
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
