<script setup>
const totalConsolidado = computed(() => {
  return totalNetoFacturas.value + totalNetoND.value - totalNetoNC.value;
});
import AppLayout from '@/Layouts/AppLayout.vue';
import CardHeader from '@/Components/CardHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, ref, computed } from 'vue';



const props = defineProps({
  documents: Array
});

const term = ref("");

const filteredDocuments = computed(() => {
  if (!term.value) return props.documents;
  const search = term.value.toLowerCase();
  return props.documents.filter(doc => {
    return (
      (doc.proveedor && doc.proveedor.toLowerCase().includes(search)) ||
      (doc.n_doc && doc.n_doc.toLowerCase().includes(search)) ||
      (doc.razon_social && doc.razon_social.toLowerCase().includes(search)) ||
      (doc.mes_contable && doc.mes_contable.toLowerCase().includes(search)) ||
      (doc.tipo && doc.tipo.toLowerCase().includes(search))
    );
  });
});

const totalGeneral = computed(() => {
  let total = 0;
  filteredDocuments.value.forEach(doc => {
    if (doc.tipo === 'debito' || doc.tipo === 'Débito') {
      total += Number(doc.monto_total);
    } else if (doc.tipo === 'credito' || doc.tipo === 'Crédito') {
      total -= Number(doc.monto_total);
    } else {
      total += Number(doc.monto_total);
    }
  });
  return total;
});

const totalNetoFacturas = computed(() => {
  return filteredDocuments.value
    .filter(doc => doc.tipo !== 'debito' && doc.tipo !== 'credito' && doc.tipo !== 'Débito' && doc.tipo !== 'Crédito')
    .reduce((sum, doc) => sum + Number(doc.monto_total), 0);
});
const totalNetoND = computed(() => {
  return filteredDocuments.value
    .filter(doc => doc.tipo === 'debito' || doc.tipo === 'Débito')
    .reduce((sum, doc) => sum + Number(doc.monto_total), 0);
});
const totalNetoNC = computed(() => {
  return filteredDocuments.value
    .filter(doc => doc.tipo === 'credito' || doc.tipo === 'Crédito')
    .reduce((sum, doc) => sum + Number(doc.monto_total), 0);
});
const consolidatedExcelData = computed(() => {
  return filteredDocuments.value.map(doc => ({
    tipo: doc.tipo,
    razon_social: doc.razon_social,
    mes_contable: doc.mes_contable,
    fecha: doc.fecha,
    proveedor: doc.proveedor,
    n_doc: doc.n_doc,
    monto_total: doc.monto_total
  }));
});

function formatNumber(value, decimals = 2) {
  return new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value ?? 0);
}
const formatSimple = val => new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0 }).format(val ?? 0);

// Meses en español
const mesesPivot = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
function getMes(mesContable) {
  // mesContable ya es el nombre del mes
  return mesContable || '';
}

// Calcular total por mes para el pivot
const totalPorMes = computed(() => {
  const totales = {};
  mesesPivot.forEach(mes => { totales[mes] = 0; });
  filteredDocuments.value.forEach(doc => {
    const mes = getMes(doc.mes_contable);
    if (mes && totales.hasOwnProperty(mes)) {
      if (doc.tipo === 'credito' || doc.tipo === 'Crédito') {
        totales[mes] -= Number(doc.monto_total);
      } else {
        totales[mes] += Number(doc.monto_total);
      }
    }
  });
  return totales;
});
function formatFecha(fecha) {
  // Mostrar la fecha tal como viene del backend
  return fecha || '';
}
</script>


<template>
  <Head :title="'Documentos Consolidados'" />
  <AppLayout>
    <div class="card my-3">
      <CardHeader title="Consolidado" />
      <div class="card-body bg-body-tertiary">
        <ul class="nav nav-pills mb-3" id="pill-consolidado" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="pill-lista" data-bs-toggle="tab" href="#pill-tab-lista" role="tab" aria-controls="pill-tab-lista" aria-selected="true">Lista</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="pill-pivot" data-bs-toggle="tab" href="#pill-tab-pivot" role="tab" aria-controls="pill-tab-pivot" aria-selected="false">Mensual</a>
          </li>
        </ul>
        <div class="tab-content" id="pill-consolidado-content">
          <div class="tab-pane fade show active" id="pill-tab-lista" role="tabpanel" aria-labelledby="pill-lista">
            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <SearchInput v-model="term" placeholder="Buscar por proveedor, número, razón social..." />
              </div>
              <div class="col-md-8 col-12 text-end d-flex flex-wrap justify-content-end gap-2">
                <ExportExcelButton :data="consolidatedExcelData" :headers=" [
                  { label: 'Tipo', key: 'tipo' },
                  { label: 'Razón Social', key: 'razon_social' },
                  { label: 'Mes Contable', key: 'mes_contable' },
                  { label: 'Fecha', key: 'fecha' },
                  { label: 'Proveedor', key: 'proveedor' },
                  { label: 'N° Doc', key: 'n_doc' },
                  { label: 'Monto Total', key: 'monto_total', type: 'number' }
                ]" filename="consolidado.xlsx" class="btn btn-light-primary me-3">
                  <span class="svg-icon svg-icon-2"></span>
                  Exportar Excel
                </ExportExcelButton>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-3 col-12 mb-2">
                <div class="card h-100 p-1 small-card">
                  <div class="card-header pb-0 pt-1 px-2">
                    <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto Facturas</h6>
                  </div>
                  <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                    <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">{{ formatNumber(totalNetoFacturas, 0) }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-12 mb-2">
                <div class="card h-100 p-1 small-card">
                  <div class="card-header pb-0 pt-1 px-2">
                    <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto ND</h6>
                  </div>
                  <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                    <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">{{ formatNumber(totalNetoND, 0) }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-12 mb-2">
                <div class="card h-100 p-1 small-card">
                  <div class="card-header pb-0 pt-1 px-2">
                    <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto NC</h6>
                  </div>
                  <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                    <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">{{ formatNumber(totalNetoNC, 0) }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-12 mb-2">
                <div class="card h-100 p-1 small-card">
                  <div class="card-header pb-0 pt-1 px-2">
                    <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Consolidado</h6>
                  </div>
                  <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                    <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">{{ formatNumber(totalConsolidado, 0) }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="table-responsive mb-4" style="max-height:440px;overflow-y:auto;">
              <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-primary">
                  <tr>
                    <th style="max-width:100px; min-width:100px; width:0px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Tipo</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Razón Social</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Mes Contable</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Fecha</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">N° Doc</th>
                    <th style="max-width:200px; min-width:200px; width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Proveedor</th>
                    <th class="text-end">Monto Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(doc, idx) in filteredDocuments" :key="idx">
                    <td style="max-width:70px; min-width:50px; width:60px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                      <span v-if="doc.tipo === 'debito' || doc.tipo === 'Débito'" class="badge bg-secondary">Débito</span>
                      <span v-else-if="doc.tipo === 'credito' || doc.tipo === 'Crédito'" class="badge bg-success">Crédito</span>
                      <span v-else class="badge bg-primary">{{ doc.tipo }}</span>
                    </td>
                    <td class="text-lowercase" style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.razon_social }}</td>
                    <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.mes_contable }}</td>
                    <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.fecha }}</td>
                    <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.n_doc }}</td>
                    <td style="max-width:200px; min-width:200px; width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.proveedor }}</td>
                    <td class="text-end" :class="(doc.tipo === 'credito' || doc.tipo === 'Crédito') ? 'text-danger' : ''">
                      {{ (doc.tipo === 'credito' || doc.tipo === 'Crédito') ? '-' + formatNumber(doc.monto_total, 0) : formatNumber(doc.monto_total, 0) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="6" class="text-end fw-bold">Total general</td>
                    <td class="text-end fw-bold">{{ formatNumber(totalGeneral, 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="pill-tab-pivot" role="tabpanel" aria-labelledby="pill-pivot">
            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <SearchInput v-model="term" placeholder="Buscar por proveedor, número, razón social..." />
              </div>
            </div>
            <div class="table-responsive mb-4" style="max-height:700px;overflow-y:auto;">
              <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-info">
                  <tr>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>N° Doc</th>
                    <th style="max-width:180px; min-width:120px; width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Proveedor</th>
                    <th v-for="mes in mesesPivot" :key="mes" class="text-end">{{ mes }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(doc, idx) in filteredDocuments" :key="'pivot-' + idx">
                    <td>{{ doc.tipo }}</td>
                    <td>{{ formatFecha(doc.fecha) }}</td>
                    <td>{{ doc.n_doc }}</td>
                    <td style="max-width:180px; min-width:120px; width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.proveedor }}</td>
                    <td v-for="mes in mesesPivot" :key="'pivot-cell-' + mes + '-' + idx" :class="(doc.tipo === 'credito' || doc.tipo === 'Crédito') ? 'text-danger text-end' : 'text-end'">
                      <span v-if="getMes(doc.mes_contable) === mes">
                        {{ (doc.tipo === 'credito' || doc.tipo === 'Crédito') ? '-' + formatNumber(doc.monto_total, 0) : formatNumber(doc.monto_total, 0) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-end fw-bold">Total por mes</td>
                    <td v-for="mes in mesesPivot" :key="'pivot-total-' + mes" class="text-end fw-bold">{{ formatNumber(totalPorMes[mes], 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    </AppLayout>
</template>

<style scoped>

.text-lowercase {
  text-transform: lowercase;
}
.table, .table th, .table td {
  font-size: 0.68rem !important;
}
</style>




