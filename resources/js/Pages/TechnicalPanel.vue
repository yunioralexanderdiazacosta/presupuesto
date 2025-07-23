<script setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import {computed} from 'vue'
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

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
    totalharvests: Number,
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



import { onMounted } from 'vue';
onMounted(() => {
  // Debug: log fruitsMap and agrochemicalByDevState
  // eslint-disable-next-line no-console
  console.log('fruitsMap:', props.fruitsMap);
  // eslint-disable-next-line no-console
  console.log('agrochemicalByDevState:', props.agrochemicalByDevState);
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
                          {{ Number(amount || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ Number(fertilizerByDevState?.[fruitId]?.[devStateId] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ Number(manPowerByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ Number(servicesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ Number(suppliesByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end text-primary fw-bold small">
                          {{ Number(harvestsByDevState?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <!-- Puedes agregar más columnas aquí si lo deseas -->
                      </tr>
                      <!-- Subtotal por especie -->
                      <tr class="table-secondary small" style="font-size:0.8em;">
                        <td colspan="2" class="text-end">Subtotal {{ $props.fruitsMap?.[String(fruitId)] || 'Sin especie' }}</td>
                        <td class="text-center text-end">
                          {{ Object.values(devStatesObj).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end">
                          {{ Object.values(fertilizerByDevState?.[fruitId] || {}).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end">
                          {{ Object.values(manPowerByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end">
                          {{ Object.values(servicesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end">
                          {{ Object.values(suppliesByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                        <td class="text-center text-end">
                          {{ Object.values(harvestsByDevState?.[String(fruitId)] || {}).reduce((sum, val) => sum + Number(val || 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                        </td>
                      </tr>
                    </template>
                  </tbody>
                  <tfoot>
                    <tr style="background-color: #555858; font-weight: bold;">
                      <td colspan="2" class="fw-bold text-end small text-white">Total General</td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(agrochemicalByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(fertilizerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(manPowerByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(servicesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(suppliesByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
                      </td>
                      <td class="text-center text-end fw-bold small text-white">
                        {{ Object.values(harvestsByDevState).reduce((sum, devStatesObj) => sum + Object.values(devStatesObj).reduce((s, v) => s + Number(v || 0), 0), 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}
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
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Gral campo</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Administración</th>
                      <th class="text-center text-uppercase text-secondary small fw-bold small">Cosecha</th>
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
                        <td class="text-center text-small text-warning fw-bold small">{{ Number(amount || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(fertilizerExpensePerHectare?.[fruitId]?.[devStateId] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
                        <td class="text-center text-warning fw-bold small">{{ Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 0 }) }}</td>
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
                        <td class="text-center text-black fw-bold small">
                          {{
                            (
                              Number(amount || 0) +
                              Number(fertilizerExpensePerHectare?.[fruitId]?.[devStateId] ?? 0) +
                              Number(manPowerExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                              Number(servicesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                              Number(suppliesExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                              Number(harvestsExpensePerHectare?.[String(fruitId)]?.[String(devStateId)] ?? 0) +
                              (totalFieldsCalc / (totalSurface || 1)) +
                              (totalAdministrationCalc / (totalSurface || 1))
                            ).toLocaleString('es-CL', { maximumFractionDigits: 0 })
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
                      <th class="text-uppercase text-secondary small text-end fw-bold small">Monto Total</th>
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
                    <tr style="background-color: #555858; font-weight: bold;">
                      <td colspan="2" class="fw-bold text-end small text-white">Total General</td>
                      <td class="fw-bold text-end small text-white">
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
                    <tr>
                      <td class="fw-semibold small">Cosecha</td>
                      <td class="text-end text-primary fw-bold small">{{totalHarvests}}</td>
                      <td class="bg-opacity-5 table-primary text-end small" v-for="value in months">{{monthsHarvests[value.value]}}</td>
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
                                return sum + [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
                                  .reduce((s, obj) => s + Number((obj && obj[value.value]) ? (obj[value.value]+"").replace(/\./g, "") : 0), 0)
                              }, 0).toLocaleString('es-CL', { maximumFractionDigits: 0 })
                            : 0
                        }}
                      </td>
                      <td class="fw-bold text-end small" v-for="value in months">
                        {{
                          [monthsAdministration, monthsFields, monthsAgrochemical, monthsFertilizer, monthsManPower, monthsSupplies, monthsServices, monthsHarvests]
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
