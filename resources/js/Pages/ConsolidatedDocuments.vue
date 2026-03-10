
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CardHeader from '@/Components/CardHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, ref, computed } from 'vue';


// Estado para ordenamiento con flechas CSS
const sortBy = ref('tipo');
const sortDesc = ref(false);
function setSort(field) {
  if (sortBy.value === field) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = field;
    sortDesc.value = false;
  }
}
const sortedDocuments = computed(() => {
  const arr = [...filteredDocuments.value];
  arr.sort((a, b) => {
    let aVal = a[sortBy.value];
    let bVal = b[sortBy.value];
    if (sortBy.value === 'monto_total') {
      aVal = Number(aVal);
      bVal = Number(bVal);
    }
    if (sortDesc.value) {
      return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
    }
    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
  });
  return arr;
});
const sortClass = (field) => ({
  sortable: true,
  'sorted-asc': sortBy.value === field && !sortDesc.value,
  'sorted-desc': sortBy.value === field && sortDesc.value,
});
const totalConsolidado = computed(() => {
  return totalNetoFacturas.value + totalNetoND.value - totalNetoNC.value;
});



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
    if (doc.is_financial) return; // NC financiera ya descontada del precio
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
    .filter(doc => (doc.tipo === 'credito' || doc.tipo === 'Crédito') && !doc.is_financial)
    .reduce((sum, doc) => sum + Number(doc.monto_total), 0);
});

// NCs financieras (ya aplicadas al precio unitario)
const financialNCs = computed(() => {
  return filteredDocuments.value.filter(doc => doc.is_financial);
});

// Agrupar documentos por tipo
const documentsByType = computed(() => {
  const groups = {};
  sortedDocuments.value.forEach(doc => {
    const tipo = doc.tipo || 'Sin tipo';
    if (!groups[tipo]) {
      groups[tipo] = [];
    }
    groups[tipo].push(doc);
  });
  return groups;
});

// Calcular subtotales por tipo (excluye NC financieras)
const getSubtotalByType = (docs, field) => {
  return docs.reduce((sum, doc) => {
    if (doc.is_financial) return sum; // NC financiera ya descontada del precio
    const value = Number(doc[field] || 0);
    // Para notas de crédito, todos los valores van en negativo (incluido IVA)
    const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
    return sum + (isCredito ? -value : value);
  }, 0);
};

const consolidatedExcelData = computed(() => {
  return filteredDocuments.value.map(doc => ({
    tipo: doc.tipo,
    razon_social: doc.razon_social,
    mes_contable: doc.mes_contable,
    fecha: doc.fecha,
    proveedor: doc.proveedor,
    n_doc: doc.n_doc,
    monto_total: doc.monto_total,
    iva: doc.iva,
    total_general: Number(doc.monto_total || 0) + Number(doc.iva || 0)
  }));
});

// Datos para exportar en formato pivot (mensual)
const pivotExcelData = computed(() => {
  return filteredDocuments.value.map(doc => {
    const row = {
      tipo: doc.tipo,
      fecha: doc.fecha,
      n_doc: doc.n_doc,
      proveedor: doc.proveedor
    };
    
    // Agregar columnas para cada mes
    mesesPivot.forEach(mes => {
      const mesDoc = getMes(doc.mes_contable);
      if (mesDoc === mes) {
        const monto = Number(doc.monto_total || 0);
        const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
        row[mes] = isCredito ? -monto : monto;
      } else {
        row[mes] = null;
      }
    });
    
    return row;
  });
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
    if (doc.is_financial) return; // NC financiera ya descontada del precio
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

// Función para generar un color único basado en el tipo de documento
const getColorForType = (tipo) => {
  const colors = [
    { bg: 'bg-primary bg-opacity-25', badge: 'bg-primary' },         // Azul fuerte
    { bg: 'bg-success bg-opacity-25', badge: 'bg-success' },         // Verde
    { bg: 'bg-info bg-opacity-25', badge: 'bg-info' },               // Celeste
    { bg: 'bg-warning bg-opacity-25', badge: 'bg-warning' },         // Amarillo
    { bg: 'bg-danger bg-opacity-25', badge: 'bg-danger' },           // Rojo
    { bg: 'bg-secondary bg-opacity-25', badge: 'bg-secondary' },     // Gris
    { bg: 'bg-dark bg-opacity-10', badge: 'bg-dark' },               // Negro/Gris oscuro
    { bg: 'bg-success bg-opacity-50', badge: 'bg-success' },         // Verde intenso
    { bg: 'bg-info bg-opacity-50', badge: 'bg-info' },               // Celeste intenso
    { bg: 'bg-warning bg-opacity-50', badge: 'bg-warning' },         // Amarillo intenso
    { bg: 'bg-danger bg-opacity-50', badge: 'bg-danger' },           // Rojo intenso
    { bg: 'bg-primary bg-opacity-50', badge: 'bg-primary' },         // Azul intenso
  ];
  
  // Generar hash simple del tipo
  let hash = 0;
  const tipoStr = (tipo || '').toLowerCase();
  for (let i = 0; i < tipoStr.length; i++) {
    hash = ((hash << 5) - hash) + tipoStr.charCodeAt(i);
    hash = hash & hash; // Convertir a entero de 32bit
  }
  
  // Usar el hash para seleccionar un color
  const index = Math.abs(hash) % colors.length;
  return colors[index];
};

// Función para obtener la clase de color según el tipo de documento
const getDocTypeClass = (tipo) => {
  return getColorForType(tipo).bg;
};

// Función para obtener el badge según el tipo
const getDocTypeBadge = (tipo) => {
  const tipoLower = (tipo || '').toLowerCase();
  const color = getColorForType(tipo);
  
  // Normalizar el texto del badge
  let text = tipo || 'Documento';
  if (tipoLower.includes('debito') || tipoLower.includes('débito') || tipoLower === 'nd') {
    text = 'N. Débito';
  } else if (tipoLower.includes('credito') || tipoLower.includes('crédito') || tipoLower === 'nc') {
    text = 'N. Crédito';
  } else if (tipoLower.includes('factura')) {
    text = tipo.includes('EXENTA') || tipo.includes('exenta') ? 'F. Exenta' : 'Factura';
  } else if (tipoLower.includes('boleta')) {
    text = tipoLower.includes('honorario') ? 'B. Honorarios' : 'Boleta';
  } else if (tipoLower.includes('remuneracion')) {
    text = 'Remuneración';
  }
  
  return { text, class: color.badge };
};
</script>


<template>
  <Head :title="'Documentos Consolidados'" />
  <AppLayout>
    <div class="card my-3">
      <CardHeader title="Consolidado" />
      <div class="card-body bg-body-tertiary">
        <ul class="nav nav-pills mb-3" id="pill-consolidado" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="pill-resumen" data-bs-toggle="tab" href="#pill-tab-resumen" role="tab" aria-controls="pill-tab-resumen" aria-selected="true">Resumen</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="pill-lista" data-bs-toggle="tab" href="#pill-tab-lista" role="tab" aria-controls="pill-tab-lista" aria-selected="false">Lista</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="pill-pivot" data-bs-toggle="tab" href="#pill-tab-pivot" role="tab" aria-controls="pill-tab-pivot" aria-selected="false">Mensual</a>
          </li>
        </ul>
        <div class="tab-content" id="pill-consolidado-content">
          <!-- PILL RESUMEN -->
          <div class="tab-pane fade show active" id="pill-tab-resumen" role="tabpanel" aria-labelledby="pill-resumen">
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
                  { label: 'Monto Total', key: 'monto_total', type: 'number' },
                  { label: 'IVA', key: 'iva', type: 'number' },
                  { label: 'Total General', key: 'total_general', type: 'number' }
                ]" filename="consolidado.xlsx" class="btn btn-light-primary me-3">
                  <span class="svg-icon svg-icon-2"></span>
                  Exportar Excel
                </ExportExcelButton>
              </div>
            </div>
            <div v-if="financialNCs.length > 0" class="alert alert-warning d-flex align-items-start py-2 px-3 mb-3" role="alert" style="font-size: 0.78rem;">
              <i class="fas fa-info-circle text-warning me-2 mt-1"></i>
              <div>
                <strong>{{ financialNCs.length }} NC financiera{{ financialNCs.length > 1 ? 's' : '' }}</strong>: 
                su descuento ya fue aplicado al precio unitario de la factura. 
                No se incluye{{ financialNCs.length > 1 ? 'n' : '' }} en los totales de esta tabla.
              </div>
            </div>
            <div class="table-responsive mb-4">
              <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-primary">
                  <tr>
                    <th>Tipo de Documento</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Monto Total</th>
                    <th class="text-end">IVA</th>
                    <th class="text-end">Total General</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(docs, tipo) in documentsByType" :key="tipo">
                    <td><span :class="'badge ' + getDocTypeBadge(tipo).class">{{ getDocTypeBadge(tipo).text }}</span></td>
                    <td class="text-end">{{ docs.length }}</td>
                    <td class="text-end" :class="(tipo === 'credito' || tipo === 'Crédito') ? 'text-danger' : ''">
                      {{ formatNumber(getSubtotalByType(docs, 'monto_total'), 0) }}
                    </td>
                    <td class="text-end" :class="(tipo === 'credito' || tipo === 'Crédito') ? 'text-danger' : ''">
                      {{ formatNumber(getSubtotalByType(docs, 'iva'), 0) }}
                    </td>
                    <td class="text-end fw-bold" :class="(tipo === 'credito' || tipo === 'Crédito') ? 'text-danger' : ''">
                      {{ formatNumber(getSubtotalByType(docs, 'monto_total') + getSubtotalByType(docs, 'iva'), 0) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="table-dark">
                  <tr>
                    <td class="fw-bold">TOTAL GENERAL</td>
                    <td class="text-end fw-bold">{{ sortedDocuments.length }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(totalGeneral, 0) }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(sortedDocuments.reduce((sum, doc) => {
                      if (doc.is_financial) return sum;
                      const iva = Number(doc.iva || 0);
                      const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
                      return sum + (isCredito ? -iva : iva);
                    }, 0), 0) }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(totalGeneral + sortedDocuments.reduce((sum, doc) => {
                      if (doc.is_financial) return sum;
                      const iva = Number(doc.iva || 0);
                      const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
                      return sum + (isCredito ? -iva : iva);
                    }, 0), 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <!-- PILL LISTA -->
          <div class="tab-pane fade" id="pill-tab-lista" role="tabpanel" aria-labelledby="pill-lista">
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
                  { label: 'Monto Total', key: 'monto_total', type: 'number' },
                  { label: 'IVA', key: 'iva', type: 'number' },
                  { label: 'Total General', key: 'total_general', type: 'number' }
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
            <div v-if="financialNCs.length > 0" class="alert alert-warning d-flex align-items-start py-2 px-3 mb-3" role="alert" style="font-size: 0.78rem;">
              <i class="fas fa-info-circle text-warning me-2 mt-1"></i>
              <div>
                <strong>{{ financialNCs.length }} Nota{{ financialNCs.length > 1 ? 's' : '' }} de Crédito financiera{{ financialNCs.length > 1 ? 's' : '' }}</strong> 
                (marcada{{ financialNCs.length > 1 ? 's' : '' }} como <span class="badge bg-soft-warning text-warning" style="font-size: 0.65rem;">Aplicada</span>) 
                ya {{ financialNCs.length > 1 ? 'fueron descontadas' : 'fue descontada' }} directamente del precio unitario de su factura original. 
                Por ello, {{ financialNCs.length > 1 ? 'aparecen' : 'aparece' }} atenuada{{ financialNCs.length > 1 ? 's' : '' }} y <strong>no se {{ financialNCs.length > 1 ? 'suman' : 'suma' }} en los totales</strong> para evitar doble descuento.
              </div>
            </div>
            <div class="table-responsive mb-4" style="max-height:440px;overflow-y:auto;">
              <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                  <tr>
                    <th style="max-width:100px; min-width:100px; width:0px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('tipo')" :class="sortClass('tipo')">Tipo</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('razon_social')" :class="sortClass('razon_social')">Razón Social</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('mes_contable')" :class="sortClass('mes_contable')">Mes Contable</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('fecha')" :class="sortClass('fecha')">Fecha</th>
                    <th style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('n_doc')" :class="sortClass('n_doc')">N° Doc</th>
                    <th style="max-width:200px; min-width:200px; width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" @click="setSort('proveedor')" :class="sortClass('proveedor')">Proveedor</th>
                    <th class="text-end" @click="setSort('monto_total')" :class="sortClass('monto_total')">Monto Total</th>
                    <th class="text-end" @click="setSort('iva')" :class="sortClass('iva')">IVA</th>
                    <th class="text-end">Total General</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(docs, tipo) in documentsByType" :key="tipo">
                    <!-- Documentos del tipo -->
                    <tr v-for="(doc, idx) in docs" :key="tipo + '-' + idx" :style="doc.is_financial ? 'opacity: 0.5;' : ''">
                      <td style="max-width:70px; min-width:50px; width:60px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <span :class="'badge ' + getDocTypeBadge(doc.tipo).class">{{ getDocTypeBadge(doc.tipo).text }}</span>
                        <span v-if="doc.is_financial" v-tooltip="'NC financiera: ya descontada del precio unitario de la factura. No suma en totales.'" class="badge bg-soft-warning text-warning ms-1" style="font-size: 0.55rem; cursor: help;"><i class="fas fa-info-circle fa-xs"></i> Aplicada</span>
                      </td>
                      <td class="text-lowercase" style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.razon_social }}</td>
                      <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.mes_contable }}</td>
                      <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.fecha }}</td>
                      <td style="max-width:100px; min-width:100px; width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.n_doc }}</td>
                      <td style="max-width:200px; min-width:200px; width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ doc.proveedor }}</td>
                      <td class="text-end" :class="(doc.tipo === 'credito' || doc.tipo === 'Crédito') ? 'text-danger' : ''">
                        {{ (doc.tipo === 'credito' || doc.tipo === 'Crédito') ? '-' + formatNumber(doc.monto_total, 0) : formatNumber(doc.monto_total, 0) }}
                      </td>
                      <td class="text-end" :class="(doc.tipo === 'credito' || doc.tipo === 'Crédito') ? 'text-danger' : ''">
                        {{ doc.iva !== null ? ((doc.tipo === 'credito' || doc.tipo === 'Crédito') ? '-' + formatNumber(Math.abs(doc.iva), 0) : formatNumber(doc.iva, 0)) : '' }}
                      </td>
                      <td class="text-end fw-bold" :class="(doc.tipo === 'credito' || doc.tipo === 'Crédito') ? 'text-danger' : ''">
                        {{ (doc.tipo === 'credito' || doc.tipo === 'Crédito') ? '-' + formatNumber(Number(doc.monto_total || 0) + Math.abs(Number(doc.iva || 0)), 0) : formatNumber(Number(doc.monto_total || 0) + Number(doc.iva || 0), 0) }}
                      </td>
                    </tr>
                    <!-- Subtotal por tipo -->
                    <tr class="table-secondary fw-bold" style="background-color: #e9ecef !important;">
                      <td colspan="6" class="text-end">Subtotal {{ tipo }}</td>
                      <td class="text-end">{{ formatNumber(getSubtotalByType(docs, 'monto_total'), 0) }}</td>
                      <td class="text-end">{{ formatNumber(getSubtotalByType(docs, 'iva'), 0) }}</td>
                      <td class="text-end">{{ formatNumber(getSubtotalByType(docs, 'monto_total') + getSubtotalByType(docs, 'iva'), 0) }}</td>
                    </tr>
                  </template>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="6" class="text-end fw-bold">Total general</td>
                    <td class="text-end fw-bold">{{ formatNumber(totalGeneral, 0) }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(sortedDocuments.reduce((sum, doc) => {
                      if (doc.is_financial) return sum;
                      const iva = Number(doc.iva || 0);
                      const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
                      return sum + (isCredito ? -iva : iva);
                    }, 0), 0) }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(totalGeneral + sortedDocuments.reduce((sum, doc) => {
                      if (doc.is_financial) return sum;
                      const iva = Number(doc.iva || 0);
                      const isCredito = doc.tipo === 'credito' || doc.tipo === 'Crédito';
                      return sum + (isCredito ? -iva : iva);
                    }, 0), 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <!-- PILL MENSUAL -->
          <div class="tab-pane fade" id="pill-tab-pivot" role="tabpanel" aria-labelledby="pill-pivot">
            <div class="row mb-3">
              <div class="col-md-4 col-12">
                <SearchInput v-model="term" placeholder="Buscar por proveedor, número, razón social..." />
              </div>
              <div class="col-md-8 col-12 text-end d-flex flex-wrap justify-content-end gap-2">
                <ExportExcelButton :data="pivotExcelData" :headers="[
                  { label: 'Tipo', key: 'tipo' },
                  { label: 'Fecha', key: 'fecha' },
                  { label: 'N° Doc', key: 'n_doc' },
                  { label: 'Proveedor', key: 'proveedor' },
                  { label: 'Enero', key: 'Enero', type: 'number' },
                  { label: 'Febrero', key: 'Febrero', type: 'number' },
                  { label: 'Marzo', key: 'Marzo', type: 'number' },
                  { label: 'Abril', key: 'Abril', type: 'number' },
                  { label: 'Mayo', key: 'Mayo', type: 'number' },
                  { label: 'Junio', key: 'Junio', type: 'number' },
                  { label: 'Julio', key: 'Julio', type: 'number' },
                  { label: 'Agosto', key: 'Agosto', type: 'number' },
                  { label: 'Septiembre', key: 'Septiembre', type: 'number' },
                  { label: 'Octubre', key: 'Octubre', type: 'number' },
                  { label: 'Noviembre', key: 'Noviembre', type: 'number' },
                  { label: 'Diciembre', key: 'Diciembre', type: 'number' }
                ]" filename="consolidado_mensual.xlsx" class="btn btn-light-primary me-3">
                  <span class="svg-icon svg-icon-2"></span>
                  Exportar Excel
                </ExportExcelButton>
              </div>
            </div>
            <div class="table-responsive mb-4" style="max-height:700px;overflow-y:auto;">
              <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                <thead class="table-info" style="position: sticky; top: 0; z-index: 10;">
                  <tr>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>N° Doc</th>
                    <th style="max-width:180px; min-width:120px; width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Proveedor</th>
                    <th v-for="mes in mesesPivot" :key="mes" class="text-end">{{ mes }}</th>
                  </tr>
                  <tr class="table-warning">
                    <td colspan="4" class="text-end fw-bold">Total por mes</td>
                    <td v-for="mes in mesesPivot" :key="'pivot-total-header-' + mes" class="text-end fw-bold">{{ formatNumber(totalPorMes[mes], 0) }}</td>
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
/* Estilos para columnas ordenables */
.sortable {
  position: relative;
  cursor: pointer;
}
.sortable:after {
  content: '\25B2'; /* triángulo hacia arriba por defecto */
  position: absolute;
  right: 8px;
  font-size: 0.6rem;
  opacity: 0.3;
}
.sorted-asc:after {
  content: '\25B2'; /* triángulo hacia arriba */
  opacity: 1;
}
.sorted-desc:after {
  content: '\25BC'; /* triángulo hacia abajo */
  opacity: 1;
}
</style>




