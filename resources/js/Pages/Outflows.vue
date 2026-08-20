<script setup>
// Estado para ordenamiento con flechas CSS
const sortBy = ref('id');
const sortDesc = ref(true);
function setSort(field) {
  if (sortBy.value === field) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = field;
    sortDesc.value = false;
  }
}
const sortedOutflowDetails = computed(() => {
  const arr = [...filteredOutflowDetails.value];
  arr.sort((a, b) => {
    let aVal = a[sortBy.value];
    let bVal = b[sortBy.value];
    // Ordenar correctamente por producto
    if (sortBy.value === 'product_name') {
      aVal = (a.product_name || a.product || '').toLowerCase();
      bVal = (b.product_name || b.product || '').toLowerCase();
    }
    if (sortBy.value === 'unit_price' || sortBy.value === 'quantity') {
      aVal = Number(aVal);
      bVal = Number(bVal);
    }
    if (sortBy.value === 'total') {
      aVal = Number(a.unit_price) * Number(a.quantity);
      bVal = Number(b.unit_price) * Number(b.quantity);
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
// Suma el total de la columna 'Total' en la tabla de edición
const totalEdicion = computed(() => {
  if (!filteredOutflowDetails.value.length) return 0;
  return filteredOutflowDetails.value.reduce((sum, outflow) => {
    let val = outflow.unit_price * outflow.quantity;
    return sum + (isNaN(val) ? 0 : val);
  }, 0);
});

const totalEdicionFormatted = computed(() => {
  return new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(totalEdicion.value);
});

// Monto total por sucursal DEL CENTRO DE COSTO (prorrateado), respeta filtros
const totalsByBranch = computed(() => {
  const map = {};
  filteredOutflowDetails.value.forEach(outflow => {
    outflowBranchAmounts(outflow).forEach(({ branch_name, amount }) => {
      if (isNaN(amount)) return;
      map[branch_name] = (map[branch_name] || 0) + amount;
    });
  });
  return Object.entries(map)
    .map(([name, total]) => ({
      name,
      total,
      formatted: new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(total),
    }))
    .sort((a, b) => b.total - a.total);
});
const termEdicion = ref("");          // texto que el usuario escribe
const appliedTermEdicion = ref("");   // término aplicado al presionar Buscar / Enter

// Búsqueda por botón / Enter (no en vivo) en la pestaña Edición
const applySearchEdicion = () => { appliedTermEdicion.value = termEdicion.value; };
const clearSearchEdicion = () => { termEdicion.value = ''; appliedTermEdicion.value = ''; };

// Límite de filas visibles + "Ver más" en la tabla de Edición
const EDICION_PAGE_SIZE = 200;
const visibleCountEdicion = ref(EDICION_PAGE_SIZE);
const pagedOutflowDetails = computed(() => sortedOutflowDetails.value.slice(0, visibleCountEdicion.value));
const hasMoreEdicion = computed(() => sortedOutflowDetails.value.length > visibleCountEdicion.value);

// Filtros avanzados para la tabla de edición
const filterMes = ref(null);
const filterOperation = ref(null);
const filterSupplier = ref(null);
const filterLevel1 = ref(null);
const filterLevel2 = ref(null);
const filterLevel3 = ref(null);
const filterProject = ref(null);

// Filas seleccionadas en Matriz de Consumo (selección múltiple)
const selectedMatrizRows = ref({});
const toggleMatrizRow = (id) => {
  const s = { ...selectedMatrizRows.value };
  s[id] ? delete s[id] : (s[id] = true);
  selectedMatrizRows.value = s;
};

// Filas seleccionadas en Matriz de Consumo por Hectárea (selección múltiple)
const selectedMatrizHaRows = ref({});
const toggleMatrizHaRow = (id) => {
  const s = { ...selectedMatrizHaRows.value };
  s[id] ? delete s[id] : (s[id] = true);
  selectedMatrizHaRows.value = s;
};
const filterBranchEdicion = ref(null);

// Opciones únicas extraídas de los datos ya cargados
const mesOptions = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const unique = [...new Set(props.outflowDetails.map(i => i.mes_contable).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const operationOptions = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const unique = [...new Set(props.outflowDetails.map(i => i.operation).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const supplierOptions = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const unique = [...new Set(props.outflowDetails.map(i => i.supplier).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const level1Options = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const unique = [...new Set(props.outflowDetails.map(i => i.level1_name).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const level2Options = computed(() => {
  if (!props.outflowDetails?.length) return [];
  // Filtrar level2 según level1 seleccionado para cascada
  const source = filterLevel1.value
    ? props.outflowDetails.filter(i => i.level1_name === filterLevel1.value)
    : props.outflowDetails;
  const unique = [...new Set(source.map(i => i.level2_name).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const level3Options = computed(() => {
  if (!props.outflowDetails?.length) return [];
  let source = props.outflowDetails;
  if (filterLevel1.value) source = source.filter(i => i.level1_name === filterLevel1.value);
  if (filterLevel2.value) source = source.filter(i => i.level2_name === filterLevel2.value);
  const unique = [...new Set(source.map(i => i.level3_name).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const projectOptions = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const unique = [...new Set(props.outflowDetails.map(i => i.project).filter(Boolean))];
  return unique.sort().map(v => ({ value: v, label: v }));
});
const branchEdicionOptions = computed(() => {
  if (!props.outflowDetails?.length) return [];
  const set = new Set();
  props.outflowDetails.forEach(o => outflowBranchAmounts(o).forEach(b => set.add(b.branch_name)));
  return [...set].sort().map(v => ({ value: v, label: v }));
});

// Limpiar niveles hijos al cambiar padre
watch(filterLevel1, () => { filterLevel2.value = null; filterLevel3.value = null; });
watch(filterLevel2, () => { filterLevel3.value = null; });

const hasActiveFilters = computed(() => {
  return filterMes.value || filterOperation.value || filterSupplier.value 
      || filterLevel1.value || filterLevel2.value || filterLevel3.value || filterProject.value || filterBranchEdicion.value;
});

const isReloading = ref(false);
const clearAllFilters = () => {
  isReloading.value = true;
  router.visit(route('outflows.index'), { preserveScroll: true });
};

const filteredOutflowDetails = computed(() => {
  if (!props.outflowDetails || !props.outflowDetails.length) return [];
  
  let result = props.outflowDetails;

  // Filtros de select
  if (filterMes.value) result = result.filter(i => i.mes_contable === filterMes.value);
  if (filterOperation.value) result = result.filter(i => i.operation === filterOperation.value);
  if (filterSupplier.value) result = result.filter(i => i.supplier === filterSupplier.value);
  if (filterLevel1.value) result = result.filter(i => i.level1_name === filterLevel1.value);
  if (filterLevel2.value) result = result.filter(i => i.level2_name === filterLevel2.value);
  if (filterLevel3.value) result = result.filter(i => i.level3_name === filterLevel3.value);
  if (filterProject.value) result = result.filter(i => i.project === filterProject.value);
  // Sucursal ahora se filtra por la del centro de costo, no la de la factura (una fila puede tocar varias)
  if (filterBranchEdicion.value) result = result.filter(i => outflowBranchAmounts(i).some(b => b.branch_name === filterBranchEdicion.value));

  // Filtro de texto (aplicado por botón / Enter, no en vivo)
  if (appliedTermEdicion.value) {
    const search = appliedTermEdicion.value.toLowerCase();
    result = result.filter((item) => {
      return (
        (item.supplier || '').toLowerCase().includes(search) ||
        String(item.number_document || '').toLowerCase().includes(search) ||
        (item.product_name || item.product || '').toLowerCase().includes(search) ||
        (item.project || '').toLowerCase().includes(search) ||
        (item.operation || '').toLowerCase().includes(search) ||
        (item.machinery || '').toLowerCase().includes(search) ||
        (item.level1_name || '').toLowerCase().includes(search) ||
        (item.level2_name || '').toLowerCase().includes(search) ||
        (item.level3_name || '').toLowerCase().includes(search)
      );
    });
  }

  return result;
});
// Convierte los centros de costo en string para exportar a Excel
const outflowsExcelData = computed(() => {
  return sortedOutflowDetails.value.map(outflow => {
    const precioNum = Number(outflow.unit_price);
    const cantidad = Number(outflow.quantity);
    const totalNum = (!isNaN(precioNum) && !isNaN(cantidad)) ? precioNum * cantidad : '';
    
    return {
      ...outflow,
      centros_costo: Array.isArray(outflow.cost_centers)
        ? outflow.cost_centers.map(cc => cc.name + (cc.observations ? ' (' + cc.observations + ')' : '')).join(', ')
        : '',
      nivel_1: outflow.level1_name || '-',
      nivel_2: outflow.level2_name || '-',
      nivel_3: outflow.level3_name || '-',
      precio_unitario: isNaN(precioNum) ? '' : precioNum,
      total: totalNum
    };
  });
});
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import { ref, watch, getCurrentInstance, computed, nextTick } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Table from '@/Components/Table.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Empty from '@/Components/Empty.vue';
import OutflowEditModal from '@/Components/Outflows/OutflowEditModal.vue';
import Multiselect from '@vueform/multiselect';

const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page || { props: {} };

const props = defineProps({
  outflows: Object,
  term: String,
  projects: Array,
  operations: Array,
  machineries: Array,
  cost_centers: Array,
  outflowDetails: { type: Array, default: () => [] },
  levels2: { type: Array, default: () => [] },
  levels3: { type: Array, default: () => [] },
  investments: { type: Array, default: () => [] },
  branches: { type: Array, default: () => [] },
});

const title = 'Salidas de productos';
const inputTerm   = ref("");   // texto que el usuario escribe
const appliedTerm = ref("");   // término aplicado al presionar Buscar / Enter
const filterBranch = ref('');
const filteredOutflows = computed(() => {
  // Primero filtrar solo los outflows con stock numérico > 0
  let stockFiltered = props.outflows.data.filter(outflow => {
    const stockNum = Number(outflow.stock);
    return isFinite(stockNum) && stockNum > 0;
  });
  // Filtro por sucursal
  if (filterBranch.value) {
    stockFiltered = stockFiltered.filter(outflow => String(outflow.branch_id) === String(filterBranch.value));
  }
  // Si no hay término de búsqueda aplicado, retornar solo stockFiltered
  if (!appliedTerm.value) return stockFiltered;
  // Si hay búsqueda, aplicar filtro de texto sobre stockFiltered
  const search = appliedTerm.value.toLowerCase();
  return stockFiltered.filter(outflow => {
    return (
      (outflow.product && outflow.product.toLowerCase().includes(search)) ||
      (outflow.supplier && outflow.supplier.toLowerCase().includes(search)) ||
      (outflow.number_document && outflow.number_document.toLowerCase().includes(search)) ||
      (outflow.origen && outflow.origen.toLowerCase().includes(search)) ||
      (outflow.mes_contable && outflow.mes_contable.toLowerCase().includes(search))
    );
  });
});

// ─── Búsqueda por botón / Enter (no en vivo) ──────────────────────────────
const applySearch = () => { appliedTerm.value = inputTerm.value; };
const clearSearch = () => { inputTerm.value = ''; appliedTerm.value = ''; };

// ─── Límite de filas visibles + "Ver más" ─────────────────────────────────
const SALIDAS_PAGE_SIZE = 200;
const visibleCountSalidas = ref(SALIDAS_PAGE_SIZE);
const pagedOutflows = computed(() => filteredOutflows.value.slice(0, visibleCountSalidas.value));
const hasMoreOutflows = computed(() => filteredOutflows.value.length > visibleCountSalidas.value);
// Resetear el límite al cambiar búsqueda o sucursal
watch(filteredOutflows, () => { visibleCountSalidas.value = SALIDAS_PAGE_SIZE; });
// Resetear el límite de Edición/Matriz al cambiar la lista filtrada/ordenada
watch(sortedOutflowDetails, () => { visibleCountEdicion.value = EDICION_PAGE_SIZE; });
// Breadcrumb links
const links = [
  { title: 'Inicio', link: 'dashboard' },
  { title: title, active: true }
];

const form = ref({ outflows: [] });
const showCards = ref([]);
const selectedOutflows = ref([]);
const showEditModal = ref(false);
const editForm = ref({
  id: '',
  project_id: '',
  operation_id: '',
  investment_id: null,
  machinery_id: '',
  cost_center_ids: [],
  notes: '',
  quantity: '',
  date: '',
  invoice_product_id: null,
  credit_debit_note_item_id: null,
  product_name: '',
  unit_name: ''
});
const editStockAvailable = ref(0);

const editProjects = ref([]);
const editOperations = ref([]);
const editInvestments = ref([]);
const editMachineries = ref([]);
const editCostCenters = ref([]);
const editStockLineData = ref(null);

// Detecta si una operation_id corresponde a "Inversiones" (por nombre, case-insensitive)
const isInversionOp = (operationId, operationsList) => {
  if (!operationId) return false;
  const op = (operationsList || props.operations).find(o => String(o.value) === String(operationId));
  return op ? /invers/i.test(op.label) : false;
};

// Función para calcular el precio total de una salida
const calculateTotal = (selected) => {
  const precio = Number(selected.unit_price) || 0;
  const cantidad = Number(selected.quantity) || 0;
  const total = precio * cantidad;
  
  if (isNaN(total)) return '-';
  
  const sinDecimales = total % 1 === 0;
  return sinDecimales
    ? total.toLocaleString('es-ES')
    : total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Función para formatear precio unitario
const formatPrice = (price) => {
  const num = Number(price) || 0;
  if (isNaN(num)) return '-';
  
  const sinDecimales = num % 1 === 0;
  return sinDecimales
    ? num.toLocaleString('es-ES')
    : num.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Monto de la línea disponible (precio unitario x stock disponible), para la lista "Disponible para Salida"
const formatLineAmount = (outflow) => {
  const precio = Number(outflow.unit_price) || 0;
  const stock = Number(outflow.stock) || 0;
  const total = precio * stock;
  if (isNaN(total)) return '-';

  const sinDecimales = total % 1 === 0;
  return sinDecimales
    ? total.toLocaleString('es-ES')
    : total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const onFilter = () => {
  router.get(route('outflows.index', {term: appliedTerm.value}), { preserveState: true });  
}


function add() {
  form.value.outflows.push({
    project_id: '',
    operation_id: '',
    machinery_id: '',
    product_name: '',
    unit_name: '',
    quantity: '',
    cost_center_ids: [],
    observations: ''
  });
}

function onDeleted(index) {
  form.value.outflows.splice(index, 1);
}

async function openCard(outflow) {
  // Permitir registrar salida para cualquier tipo de documento
  let id = null;
  let tipo = null;
  if (outflow.credit_debit_note_item_id) {
    id = 'nota_debito-' + outflow.credit_debit_note_item_id;
    tipo = 'nota_debito';
  } else if (outflow.invoice_product_id) {
    id = 'factura-' + outflow.invoice_product_id;
    tipo = 'factura';
  } else {
    // Si no tiene ninguno, no se puede registrar salida
    return;
  }
  
  // Si ya está abierto, cerrarlo (toggle)
  if (showCards.value.includes(id)) {
    console.log('Cerrando card:', id);
    closeCard(id);
    return;
  }
  
  // Verificar también en selectedOutflows por si acaso hay desincronización
  const existsInSelected = selectedOutflows.value.some(sel => sel.id === id);
  if (existsInSelected) {
    console.warn('Desincronización detectada: existe en selectedOutflows pero no en showCards');
    // Sincronizar agregándolo a showCards
    showCards.value.push(id);
    return;
  }
  
  // Si llegamos aquí, es seguro agregar el card
  showCards.value.push(id);
  
  // Crear el objeto de salida
  const newOutflow = {
    id,
    tipo,
    invoice_product_id: outflow.invoice_product_id || null,
    credit_debit_note_item_id: outflow.credit_debit_note_item_id || null,
    project_id: '',
    operation_id: '',
    investment_id: null,
    machinery_id: '',
    product_name: outflow.product,
    unit_name: outflow.unit,
    branch_name: outflow.branch_name || '-', // Sucursal de la línea (solo lectura)
    quantity: outflow.stock, // Inicializa cantidad con el stock
    stock_original: outflow.stock, // Guardar stock original para validaciones
    unit_price: outflow.unit_price || 0, // Precio unitario de la factura
    cost_center_ids: [],
    observations: '',
    date: outflow.invoice_date || '', // Fecha de la factura por defecto
    level2_id: null, // Filtro helper (no se guarda)
    level3_id: null,
    product_id: outflow.product_id || null,
    suggested_level3: false, // Flag para saber si fue sugerido
  };
  
  selectedOutflows.value.push(newOutflow);
  
  // Buscar sugerencia inteligente de level3_id basado en el producto
  if (outflow.product_id) {
    try {
      const { data } = await axios.get(route('outflows.level3-suggestions'), {
        params: { product_id: outflow.product_id }
      });
      
      if (data && data.length > 0) {
        // Verificar que el card aún existe antes de modificarlo (por si fue cerrado durante la llamada async)
        const cardStillExists = selectedOutflows.value.find(sel => sel.id === id);
        if (!cardStillExists) {
          console.log('Card fue cerrado durante la llamada async, ignorando sugerencia');
          return;
        }
        
        // Auto-seleccionar el nivel 3 más usado
        newOutflow.level3_id = data[0].level3_id;
        newOutflow.suggested_level3 = true;
        
        // Auto-seleccionar también el nivel 2 padre para el filtro
        const selectedLevel3 = props.levels3.find(l => l.value === data[0].level3_id);
        if (selectedLevel3) {
          newOutflow.level2_id = selectedLevel3.level2_id;
        }
        
        // Mostrar notificación sutil
        console.log(`✨ Sugerencia: "${data[0].level3_name}" (usado ${data[0].usage_count} veces)`);
      }
    } catch (error) {
      console.error('Error al obtener sugerencias de nivel 3:', error);
      // No mostrar error al usuario, simplemente no auto-completar
    }
  }
}
function closeCard(id) {
  // Encontrar el índice en showCards
  const showCardsIdx = showCards.value.indexOf(id);
  
  if (showCardsIdx !== -1) {
    // Eliminar de showCards
    showCards.value.splice(showCardsIdx, 1);
    
    // Buscar el índice correcto en selectedOutflows usando el mismo id
    const selectedIdx = selectedOutflows.value.findIndex(sel => sel.id === id);
    
    if (selectedIdx !== -1) {
      selectedOutflows.value.splice(selectedIdx, 1);
    }
  }
}

// Determina si el card está abierto para el outflow
function isCardOpen(outflow) {
  if (outflow.credit_debit_note_item_id) {
    return showCards.value.includes('nota_debito-' + outflow.credit_debit_note_item_id);
  } else if (outflow.invoice_product_id) {
    return showCards.value.includes('factura-' + outflow.invoice_product_id);
  }
  return false;
}

function handleSave() {
  // Filtrar solo las cards con datos obligatorios (ejemplo: cantidad y al menos un centro de costo)
  const registros = selectedOutflows.value.filter(sel => {
    return sel.quantity && sel.cost_center_ids && sel.cost_center_ids.length > 0 && (sel.invoice_product_id || sel.credit_debit_note_item_id);
  }).map(sel => {
    // Enviar ambos campos, el backend decidirá cuál usar
    return {
      id: sel.id,
      tipo: sel.tipo,
      invoice_product_id: sel.invoice_product_id,
      credit_debit_note_item_id: sel.credit_debit_note_item_id,
      project_id: sel.project_id,
      operation_id: sel.operation_id,
      investment_id: isInversionOp(sel.operation_id) ? (sel.investment_id || null) : null,
      machinery_id: sel.machinery_id,
      product_name: sel.product_name,
      unit_name: sel.unit_name,
      quantity: sel.quantity,
      date: sel.date,
      cost_center_ids: sel.cost_center_ids,
      notes: sel.observations, // Usar notes para el backend
      level3_id: sel.level3_id,
    };
  });
  console.log('Registros enviados:', registros);
  if (registros.length === 0) {
    Swal.fire({ icon: 'warning', title: 'Atención', text: 'No hay registros completos para guardar.' });
    return;
  }
  router.post(route('outflows.store'), { outflows: registros }, {
    onSuccess: () => {
      selectedOutflows.value = [];
      showCards.value = [];
      Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Las salidas fueron registradas correctamente.' });
    },
    onError: (error) => {
      console.log(error?.response?.data || error);
      Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al guardar las salidas.' });
    }
  });
}


// --- POPUP DETALLADO PARA BADGE +NC EN TABLA ---
function formatCreditNotePopover(creditNoteInfo) {
  if (!creditNoteInfo || !Array.isArray(creditNoteInfo)) return '';
  let html = '';
  creditNoteInfo.forEach(note => {
    html += `<div><b>N°:</b> ${note.number} <b>Proveedor:</b> ${note.supplier} <b>Fecha:</b> ${note.date}</div>`;
    (note.items || []).forEach(item => {
      html += `<div style='margin-left:1em;'>• <b>${item.product}</b>: ${item.quantity}</div>`;
    });
  });
  return html.trim();
}

import { onMounted, onUpdated } from 'vue';
onMounted(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});
onUpdated(() => {
  nextTick(() => {
    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._popover) {
          el._popover = new window.bootstrap.Popover(el);
        }
      });
    }
  });
});

// Función para mostrar detalles de centros de costo adicionales
// ── Consumo Matriz: columnas dinámicas de CC ──────────────────────────────
const ccColumns = computed(() => {
  const all = props.cost_centers || [];
  // Sin filtro activo: mostrar todos
  if (!filterBranchEdicion.value) return all;
  // Con filtro: mostrar solo los CCs que aparecen en los outflows filtrados
  const usedCcIds = new Set();
  sortedOutflowDetails.value.forEach(outflow => {
    (outflow.cost_centers || []).forEach(cc => {
      if (cc.id) usedCcIds.add(String(cc.id));
    });
  });
  return all.filter(cc => usedCcIds.has(String(cc.value)));
});

/**
 * Calcula el monto prorrateado (en pesos) para un outflow en un CC dado.
 * Devuelve null si el CC no está asignado al outflow.
 */
function getProrationAmount(outflow, ccId) {
  const ccs = outflow.cost_centers || [];
  const ccMatch = ccs.find(cc => String(cc.id) === String(ccId));
  if (!ccMatch) return null;

  const totalSuperficie = ccs.reduce((sum, cc) => sum + (Number(cc.surface) || 0), 0);
  const superficie = Number(ccMatch.surface) || 0;
  const quantity = Number(outflow.quantity) || 0;
  const unitPrice = Number(outflow.unit_price) || 0;

  let cantidadAsignada;
  if (totalSuperficie > 0 && superficie > 0) {
    cantidadAsignada = (superficie / totalSuperficie) * quantity;
  } else {
    cantidadAsignada = quantity;
  }
  return cantidadAsignada * unitPrice;
}

// id de centro de costo -> branch_id (catálogo, ya trae branch_id por CC)
const ccBranchLookup = computed(() => Object.fromEntries((props.cost_centers || []).map(c => [String(c.value), c.branch_id])));
// id de sucursal -> nombre
const branchNameLookup = computed(() => Object.fromEntries((props.branches || []).map(b => [String(b.value), b.label])));

/**
 * Reparte el monto de un outflow entre las sucursales DE SUS CENTROS DE COSTO
 * (mismo prorrateo por superficie que getProrationAmount). Sin CC asignado,
 * el monto completo va a 'Sin sucursal'.
 */
function outflowBranchAmounts(outflow) {
  const ccs = outflow.cost_centers || [];
  const totalAmount = Number(outflow.unit_price || 0) * Number(outflow.quantity || 0);
  if (!ccs.length) {
    return [{ branch_name: 'Sin sucursal', amount: totalAmount }];
  }
  return ccs.map(cc => {
    const branchId = ccBranchLookup.value[String(cc.id)];
    const branch_name = branchId ? (branchNameLookup.value[String(branchId)] || 'Sin sucursal') : 'Sin sucursal';
    return { branch_name, amount: getProrationAmount(outflow, cc.id) || 0 };
  });
}

/** Suma total de la columna de un CC sobre todos los outflows filtrados */
function ccColumnTotal(ccId) {
  return sortedOutflowDetails.value.reduce((sum, outflow) => {
    const val = getProrationAmount(outflow, ccId);
    return sum + (val !== null ? val : 0);
  }, 0);
}

/**
 * Costo por hectárea para un outflow en un CC dado.
 * = monto prorrateado / superficie_CC
 * Devuelve null si el CC no está asignado o si superficie = 0.
 */
function getProrationPerHa(outflow, ccId) {
  const ccs = outflow.cost_centers || [];
  const ccMatch = ccs.find(cc => String(cc.id) === String(ccId));
  if (!ccMatch) return null;
  const superficie = Number(ccMatch.surface) || 0;
  if (superficie === 0) return null;
  const montoProrrateado = getProrationAmount(outflow, ccId);
  if (montoProrrateado === null) return null;
  return montoProrrateado / superficie;
}

function ccColumnTotalPerHa(ccId) {
  return sortedOutflowDetails.value.reduce((sum, outflow) => {
    const val = getProrationPerHa(outflow, ccId);
    return sum + (val !== null ? val : 0);
  }, 0);
}

/** Datos para exportar la tabla Consumo Matriz Hectárea a Excel */
const matrizHaExcelData = computed(() => {
  return sortedOutflowDetails.value.map(outflow => {
    const row = {
      id: outflow.id,
      fecha_digitacion: outflow.date,
      numero_documento: outflow.number_document,
      proveedor: outflow.supplier,
      fecha_factura: outflow.fecha_factura,
      mes_contable: outflow.mes_contable || '',
      producto: outflow.product,
      nivel_1: outflow.level1_name || '',
      nivel_2: outflow.level2_name || '',
      nivel_3: outflow.level3_name || '',
      precio_unitario: Number(outflow.unit_price || 0),
      proyecto: outflow.project || '',
      operacion: outflow.operation || '',
      inversion: outflow.investment || '',
      maquinaria: outflow.machinery || '',
      cantidad: Number(outflow.quantity || 0),
      total: Math.round(Number(outflow.unit_price || 0) * Number(outflow.quantity || 0)),
      sucursal: outflow.branch_name || '',
    };
    (props.cost_centers || []).forEach(cc => {
      const val = getProrationPerHa(outflow, cc.value);
      row['cc_' + cc.value] = val !== null ? Math.round(val) : '';
    });
    return row;
  });
});

const matrizHaExcelHeaders = computed(() => {
  const fixed = [
    { label: 'ID', key: 'id' },
    { label: 'Fecha dig.', key: 'fecha_digitacion' },
    { label: 'N° Doc', key: 'numero_documento' },
    { label: 'Proveedor', key: 'proveedor' },
    { label: 'Fecha factura', key: 'fecha_factura' },
    { label: 'Mes contable', key: 'mes_contable' },
    { label: 'Producto', key: 'producto' },
    { label: 'Nivel 1', key: 'nivel_1' },
    { label: 'Nivel 2', key: 'nivel_2' },
    { label: 'Nivel 3', key: 'nivel_3' },
    { label: 'Precio Unit.', key: 'precio_unitario', type: 'number' },
    { label: 'Proyecto', key: 'proyecto' },
    { label: 'Operación', key: 'operacion' },
    { label: 'Inversión', key: 'inversion' },
    { label: 'Maquinaria', key: 'maquinaria' },
    { label: 'Cantidad', key: 'cantidad', type: 'number' },
    { label: 'Total', key: 'total', type: 'number' },
    { label: 'Sucursal', key: 'sucursal' },
  ];
  const ccHeaders = (props.cost_centers || []).map(cc => ({
    label: cc.label + ' ($/ha)',
    key: 'cc_' + cc.value,
    type: 'number',
  }));
  return [...fixed, ...ccHeaders];
});

/** Datos para exportar la tabla Consumo Matriz a Excel */
const matrizExcelData = computed(() => {
  return sortedOutflowDetails.value.map(outflow => {
    const row = {
      id: outflow.id,
      fecha_digitacion: outflow.date,
      numero_documento: outflow.number_document,
      proveedor: outflow.supplier,
      fecha_factura: outflow.fecha_factura,
      mes_contable: outflow.mes_contable || '',
      producto: outflow.product,
      nivel_1: outflow.level1_name || '',
      nivel_2: outflow.level2_name || '',
      nivel_3: outflow.level3_name || '',
      precio_unitario: Number(outflow.unit_price || 0),
      proyecto: outflow.project || '',
      operacion: outflow.operation || '',
      inversion: outflow.investment || '',
      maquinaria: outflow.machinery || '',
      cantidad: Number(outflow.quantity || 0),
      total: Math.round(Number(outflow.unit_price || 0) * Number(outflow.quantity || 0)),
      sucursal: outflow.branch_name || '',
      observaciones: outflow.notes || '',
    };
    // columnas dinámicas por CC
    (props.cost_centers || []).forEach(cc => {
      const val = getProrationAmount(outflow, cc.value);
      row['cc_' + cc.value] = val !== null ? Math.round(val) : '';
    });
    return row;
  });
});

/** Headers dinámicos para el Excel de la Consumo Matriz */
const matrizExcelHeaders = computed(() => {
  const fixed = [
    { label: 'ID', key: 'id' },
    { label: 'Fecha dig.', key: 'fecha_digitacion' },
    { label: 'N° Doc', key: 'numero_documento' },
    { label: 'Proveedor', key: 'proveedor' },
    { label: 'Fecha factura', key: 'fecha_factura' },
    { label: 'Mes contable', key: 'mes_contable' },
    { label: 'Producto', key: 'producto' },
    { label: 'Nivel 1', key: 'nivel_1' },
    { label: 'Nivel 2', key: 'nivel_2' },
    { label: 'Nivel 3', key: 'nivel_3' },
    { label: 'Precio Unit.', key: 'precio_unitario', type: 'number' },
    { label: 'Proyecto', key: 'proyecto' },
    { label: 'Operación', key: 'operacion' },
    { label: 'Inversión', key: 'inversion' },
    { label: 'Maquinaria', key: 'maquinaria' },
    { label: 'Cantidad', key: 'cantidad', type: 'number' },
    { label: 'Total', key: 'total', type: 'number' },
    { label: 'Sucursal', key: 'sucursal' },
    { label: 'Observaciones', key: 'observaciones' },
  ];
  const ccHeaders = (props.cost_centers || []).map(cc => ({
    label: cc.label,
    key: 'cc_' + cc.value,
    type: 'number',
  }));
  return [...fixed, ...ccHeaders];
});
// ──────────────────────────────────────────────────────────────────────────

const showMoreCenters = (centers) => {
  const items = centers.slice(2).map(cc => {
    return `<li><strong>${cc.name}</strong>${cc.observations ? ' - ' + cc.observations : ''}</li>`;
  }).join('');
  Swal.fire({
    title: 'Centros de Costo adicionales',
    html: `<ul style="text-align:left;margin:0;padding:0 1rem;list-style:none;">${items}</ul>`,
    width: 400,
    confirmButtonText: 'Cerrar'
  });
};
// Eliminar una salida existente con confirmación
function deleteOutflow(outflow) {
  Swal.fire({
    title: '¿Estás seguro de eliminar esta salida?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: 'rgb(220,53,69)'  
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('outflows.delete', outflow.id), {
        onSuccess: () => {
          Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'Salida eliminada',
            showConfirmButton: false,
            timer: 1000
          });
          // Recargar datos con Inertia sin refrescar toda la página
          router.reload({ preserveScroll: true });
        }
      });
    }
  });
}
// Abrir modal de edición cargando datos vía AJAX
function editOutflow(outflow) {
  axios.get(route('outflows.edit', outflow.id))
    .then(({ data }) => {
      console.log('Datos recibidos en edición de salida:', data);
      // Asignar campo por campo para mantener la reactividad
      editForm.value.id = data.outflow.id;
      editForm.value.project_id = data.outflow.project_id ? Number(data.outflow.project_id) : '';
      editForm.value.operation_id = data.outflow.operation_id ? Number(data.outflow.operation_id) : '';
      editForm.value.investment_id = data.outflow.investment_id ? Number(data.outflow.investment_id) : null;
      editForm.value.machinery_id = data.outflow.machinery_id ? Number(data.outflow.machinery_id) : '';
      editForm.value.cost_center_ids = Array.isArray(data.outflow.cost_centers)
        ? data.outflow.cost_centers.map(cc => Number(cc.id)).filter(id => !!id)
        : [];
      editForm.value.notes = data.outflow.notes ?? '';
      editForm.value.quantity = data.outflow.quantity ?? '';
      editForm.value.date = data.outflow.date ?? '';
      editForm.value.invoice_product_id = data.outflow.invoice_product_id ?? null;
      editForm.value.credit_debit_note_item_id = data.outflow.credit_debit_note_item_id ?? null;
      editForm.value.product_name = data.outflow.invoice_product?.product?.name || data.outflow.credit_debit_note_item?.product?.name || '';
      editForm.value.unit_name = data.outflow.invoice_product?.product?.unit?.name || data.outflow.credit_debit_note_item?.product?.unit?.name || '';
      editForm.value.product_id = data.outflow.invoice_product?.product_id || data.outflow.credit_debit_note_item?.product_id || null;
      editForm.value.level3_id = data.outflow.level3_id ? Number(data.outflow.level3_id) : null;
  // Pasar la propiedad has_credit_note y credit_note_info si existen
  editForm.value.has_credit_note = data.outflow.has_credit_note ?? false;
  editForm.value.credit_note_info = data.outflow.credit_note_info ?? null;
      editStockAvailable.value = data.stockAvailable;
      editProjects.value = (data.projects || []).map(p => ({
        value: Number(p.id),
        label: p.name
      }));
      editOperations.value = (data.operations || []).map(o => ({
        value: Number(o.id),
        label: o.name
      }));
      editInvestments.value = (data.investments || []).map(i => ({
        value: Number(i.value ?? i.id),
        label: i.label ?? i.name
      }));
      editMachineries.value = (data.machineries || []).map(m => ({
        value: Number(m.id),
        label: m.brand ? m.brand + ' (' + m.cod_machinery + ')' : m.cod_machinery
      }));
      editCostCenters.value = (data.costCenters || []).map(c => ({
        value: Number(c.id),
        label: c.name
      }));

      // Construir el objeto de línea de stock asociada para el modal
      let stockLine = null;
      if (data.outflow.invoice_product) {
        stockLine = {
          tipo: 'factura',
          documento: data.outflow.invoice_product.invoice?.number || data.outflow.invoice_product.invoice_id || '',
          proveedor: data.outflow.invoice_product.invoice?.supplier?.name || '',
          cantidad_original: data.outflow.invoice_product.quantity || data.outflow.invoice_product.amount || 0,
          stock_disponible: data.stockAvailable
        };
      } else if (data.outflow.credit_debit_note_item) {
        stockLine = {
          tipo: 'nota',
          documento: data.outflow.credit_debit_note_item.credit_debit_note?.number || data.outflow.credit_debit_note_item.credit_debit_note_id || '',
          proveedor: data.outflow.credit_debit_note_item.credit_debit_note?.supplier?.name || '',
          cantidad_original: data.outflow.credit_debit_note_item.quantity || 0,
          stock_disponible: data.stockAvailable
        };
      }
      editStockLineData.value = stockLine;
      showEditModal.value = true;
    })
    .catch((error) => {
      console.error('Error al cargar datos de edición:', error);
      Swal.fire('Error', 'No se pudo cargar los datos de edición', 'error');
    });
}

function handleEditModalUpdated() {
  showEditModal.value = false;
  router.reload({ preserveScroll: true });
}

// Estado para agrupación seleccionada por cada card
const selectedGroupings = ref({});

// Estado para expandir/colapsar tags de CC por card
const expandedCCCards = ref({});

// Computed para filtrar levels3 según el level2 seleccionado en cada card
const getFilteredLevels3 = (cardId) => {
  const card = selectedOutflows.value.find(sel => sel.id === cardId);
  if (!card || !card.level2_id) {
    return props.levels3; // Sin filtro, mostrar todos
  }
  return props.levels3.filter(l3 => l3.level2_id == card.level2_id);
};

watch(selectedGroupings, (newVals) => {
  Object.entries(newVals).forEach(([cardId, groupingId]) => {
    if (!groupingId) return;
    const grouping = page.props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
      const groupCCs = grouping.cost_centers.map(cc => cc.id);
      const card = selectedOutflows.value.find(sel => sel.id == cardId);
      if (card) card.cost_center_ids = groupCCs;
    }
  });
}, { deep: true });

// Función para copiar el CC de un card a todos los demás
function copyCCToAllCards(sourceCardId) {
  const sourceCard = selectedOutflows.value.find(sel => sel.id === sourceCardId);

  if (!sourceCard) return;

  if (!sourceCard.cost_center_ids || sourceCard.cost_center_ids.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Sin centros de costo',
      text: 'Selecciona al menos un centro de costo antes de copiar.',
      timer: 2500,
      showConfirmButton: false
    });
    return;
  }

  const targetCards = selectedOutflows.value.filter(sel => sel.id !== sourceCardId);

  if (targetCards.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Información',
      text: 'No hay otros cards abiertos para copiar el CC.',
      timer: 2000,
      showConfirmButton: false
    });
    return;
  }

  targetCards.forEach(card => {
    card.cost_center_ids = [...sourceCard.cost_center_ids];
  });

  Swal.fire({
    icon: 'success',
    title: '¡CC copiado!',
    html: `Centro(s) de costo replicados a <strong>${targetCards.length}</strong> card${targetCards.length > 1 ? 's' : ''}`,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
  });
}

// Función para copiar datos de un card a todos los demás
function copyToAllCards(sourceCardId) {
  const sourceCard = selectedOutflows.value.find(sel => sel.id === sourceCardId);
  
  if (!sourceCard) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se encontró el card de origen',
      timer: 2000,
      showConfirmButton: false
    });
    return;
  }
  
  // Verificar que al menos uno de los campos tenga valor (diferente de null, undefined y '')
  const hasData = sourceCard.operation_id || sourceCard.machinery_id || sourceCard.project_id || 
      sourceCard.level2_id || sourceCard.level3_id || sourceCard.date;
  
  if (!hasData) {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'No hay datos para copiar. Selecciona al menos un campo.',
      timer: 2500,
      showConfirmButton: false
    });
    return;
  }
  
  // Contar cuántos cards se van a actualizar
  const targetCards = selectedOutflows.value.filter(sel => sel.id !== sourceCardId);
  
  if (targetCards.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Información',
      text: 'No hay otros cards abiertos para copiar los datos.',
      timer: 2000,
      showConfirmButton: false
    });
    return;
  }
  
  // Copiar los campos a todos los demás cards
  targetCards.forEach(card => {
    card.operation_id = sourceCard.operation_id;
    card.investment_id = sourceCard.investment_id;
    card.machinery_id = sourceCard.machinery_id;
    card.project_id = sourceCard.project_id;
    card.level2_id = sourceCard.level2_id;
    card.level3_id = sourceCard.level3_id;
    card.date = sourceCard.date;
    // Resetear el flag de sugerencia automática ya que ahora es manual
    card.suggested_level3 = false;
  });
  
  // Feedback visual de éxito
  Swal.fire({
    icon: 'success',
    title: '¡Copiado!',
    html: `Datos replicados a <strong>${targetCards.length}</strong> card${targetCards.length > 1 ? 's' : ''}`,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
  });
}
</script>



<template>
  
   
    <Head :title="title" />
    <AppLayout>
         <Breadcrumb :links="links" />
        <div class="card my-3">
         <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-dolly text-primary me-2"></i>Informacion de Salidas</h5>
                </div>
               
            </div>
         </div>
            <div class="card-body bg-body-tertiary">
                  <div class="row align-items-center mb-2">
                    <div class="col">
                      <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                        <li class="nav-item"><a class="nav-link" id="pill-salidas" data-bs-toggle="tab" href="#pill-tab-salidas" role="tab" aria-controls="pill-tab-salidas" aria-selected="false">Disponible para Salida</a></li>
                        <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">Matriz de Consumo</a></li>
                        <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">Matriz de Consumo por Hectárea</a></li>
                      </ul>
                    </div>
                    <div class="col-auto text-end">
                      <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <div v-for="b in totalsByBranch" :key="b.name" class="card h-100 p-1 small-card">
                          <div class="card-header pb-0 pt-1 px-2">
                            <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">{{ b.name }}</h6>
                          </div>
                          <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                            <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                              {{ b.formatted }}
                            </p>
                          </div>
                        </div>
                        <div class="card h-100 p-1 small-card">
                          <div class="card-header pb-0 pt-1 px-2">
                            <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto Salidas</h6>
                          </div>
                          <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                            <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                              {{ totalEdicionFormatted }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
             
            <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
               
                  <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="pill-edicion">
                    <!-- Filtros compactos en una sola fila -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                      <div class="d-flex gap-1 align-items-center" style="min-width: 220px; flex: 1 1 260px; max-width: 340px;">
                        <SearchInput v-model="termEdicion" placeholder="Buscar..."
                          style="max-width:unset; flex:1; margin-bottom:0;"
                          @keyup.enter="applySearchEdicion" />
                        <button type="button" class="btn btn-falcon-primary btn-sm px-2 flex-shrink-0" @click="applySearchEdicion" title="Buscar" style="height:31px;">
                          <i class="fas fa-search"></i>
                        </button>
                        <button v-if="appliedTermEdicion || termEdicion" type="button" class="btn btn-falcon-default btn-sm px-2 flex-shrink-0" @click="clearSearchEdicion" title="Limpiar búsqueda" style="height:31px;">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                      <div style="min-width: 110px; flex: 0 1 130px;">
                        <select v-model="filterMes" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los meses</option>
                          <option v-for="o in mesOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 130px; flex: 0 1 160px;">
                        <select v-model="filterOperation" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las operaciones</option>
                          <option v-for="o in operationOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 160px; flex: 0 1 210px;">
                        <select v-model="filterSupplier" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proveedores</option>
                          <option v-for="o in supplierOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 120px; flex: 0 1 130px;">
                        <select v-model="filterLevel1" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Nivel 1</option>
                          <option v-for="o in level1Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 120px; flex: 0 1 130px;">
                        <select v-model="filterLevel2" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel1">
                          <option :value="null">Nivel 2</option>
                          <option v-for="o in level2Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 120px; flex: 0 1 130px;">
                        <select v-model="filterLevel3" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel2">
                          <option :value="null">Nivel 3</option>
                          <option v-for="o in level3Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 130px; flex: 0 1 160px;">
                        <select v-model="filterProject" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proyectos</option>
                          <option v-for="o in projectOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width: 120px; flex: 0 1 150px;">
                        <select v-model="filterBranchEdicion" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las sucursales</option>
                          <option v-for="o in branchEdicionOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div class="d-flex align-items-center gap-1" style="flex-shrink: 0;">
                        <button 
                          v-if="hasActiveFilters || isReloading"
                          type="button" 
                          class="btn btn-falcon-default btn-sm py-1"
                          @click="clearAllFilters"
                          :disabled="isReloading"
                          title="Limpiar filtros"
                        >
                          <i :class="isReloading ? 'fas fa-spinner fa-spin' : 'fas fa-times'"></i>
                        </button>
                        <ExportExcelButton
                          :data="outflowsExcelData"
                          :headers="[
                            { label: 'ID', key: 'id' },
                            { label: 'Fecha digitación', key: 'date' },
                            { label: 'N° Doc', key: 'number_document' },
                            { label: 'Proveedor', key: 'supplier' },
                            { label: 'RUT Proveedor', key: 'supplier_rut' },
                            { label: 'Fecha factura', key: 'fecha_factura' },
                            { label: 'Mes contable', key: 'mes_contable' },
                            { label: 'Producto', key: 'product' },
                            { label: 'Nivel 1', key: 'nivel_1' },
                            { label: 'Nivel 2', key: 'nivel_2' },
                            { label: 'Nivel 3', key: 'nivel_3' },
                            { label: 'Precio Unitario', key: 'precio_unitario' },
                            { label: 'Proyecto', key: 'project' },
                            { label: 'Operación', key: 'operation' },
                            { label: 'Inversión', key: 'investment' },
                            { label: 'Maquinaria', key: 'machinery' },
                            { label: 'Cantidad', key: 'quantity', type: 'number' },
                            { label: 'Total', key: 'total' },
                            { label: 'Observaciones', key: 'notes' },
                            { label: 'Centros de Costo', key: 'centros_costo' },
                            { label: 'Sucursal', key: 'branch_name' },
                            { label: 'Usuario', key: 'user' }
                          ]"
                          filename="salidas.xlsx"
                          class="btn btn-falcon-default btn-sm py-1"
                        >
                          <i class="fas fa-file-excel"></i>
                        </ExportExcelButton>
                      </div>
                      <small class="text-muted ms-auto align-self-end" style="flex-shrink: 0;">
                        {{ sortedOutflowDetails.length }}/{{ props.outflowDetails?.length || 0 }}
                      </small>
                    </div>
           
                    <div style="position: relative;">
                      <div v-if="isReloading" class="table-loading-overlay">
                        <div class="text-center">
                          <div class="table-spinner"></div>
                          <div class="mt-2 text-muted small">Recargando datos...</div>
                        </div>
                      </div>
                    <div class="table-responsive mb-4" style="max-height:450px; overflow-y:auto; overflow-x:auto; width:100%;">
                      <table class="table table-bordered table-striped table-hover table-sm mb-0 tabla-edicion-small" style="min-width:2000px;">

                        <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                          <tr>
                            <th @click="setSort('id')" :class="sortClass('id')">ID</th>
                            <th @click="setSort('date')" :class="sortClass('date')">Fecha dig.</th>
                            <th @click="setSort('number_document')" :class="sortClass('number_document')">N° Doc</th>
                            <th @click="setSort('supplier')" :class="sortClass('supplier')">Proveedor</th>
                            <th @click="setSort('fecha_factura')" :class="sortClass('fecha_factura')">Fecha factura</th>
                            <th @click="setSort('mes_contable')" :class="sortClass('mes_contable')">Mes contable</th>
                            <th @click="setSort('product_name')" :class="sortClass('product_name')">Producto</th>
                            <th @click="setSort('level1_name')" :class="sortClass('level1_name')">Nivel 1</th>
                            <th @click="setSort('level2_name')" :class="sortClass('level2_name')">Nivel 2</th>
                            <th @click="setSort('level3_name')" :class="sortClass('level3_name')">Nivel 3</th>
                            <th @click="setSort('unit_price')" :class="sortClass('unit_price')">Precio Unitario</th>
                            <th @click="setSort('project')" :class="sortClass('project')">Proyecto</th>
                            <th @click="setSort('operation')" :class="sortClass('operation')">Operación</th>
                            <th @click="setSort('investment')" :class="sortClass('investment')">Inversión</th>
                            <th @click="setSort('machinery')" :class="sortClass('machinery')">Maquinaria</th>
                            <th @click="setSort('quantity')" :class="sortClass('quantity')">Cantidad</th>
                            <th @click="setSort('total')" :class="sortClass('total')">Total</th>
                            <th @click="setSort('notes')" :class="sortClass('notes')">Observaciones</th>
                            <th @click="setSort('centros_costo')" :class="sortClass('centros_costo')">Centros de Costo</th>
                            <th @click="setSort('user')" :class="sortClass('user')">Usuario</th>
                            <th class="text-center">Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr 
                            v-for="outflow in pagedOutflowDetails" 
                            :key="outflow.id"
                            @click="!outflow.fuel_outflow_id && editOutflow(outflow)"
                            :style="outflow.fuel_outflow_id ? 'cursor: default;' : 'cursor: pointer;'"
                          >
                            <td>{{ outflow.id }}</td>
                            <td>{{ outflow.date || '-' }}</td>
                            <td>{{ outflow.number_document || '-' }}</td>
                            <td class="td-supplier">{{ outflow.supplier || '-' }}</td>
                            <td>{{ outflow.fecha_factura || '-' }}</td>
                            <td>{{ outflow.mes_contable || '-' }}</td>
                            <td>{{ outflow.product_name || outflow.product || '-' }}</td>
                            <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis;">{{ outflow.level1_name || '-' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ outflow.level2_name || '-' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ outflow.level3_name || '-' }}</td>
                            <td>
                              <span v-if="outflow.unit_price !== undefined && outflow.unit_price !== null">
                                {{
                                  (() => {
                                    const num = Number(outflow.unit_price);
                                    if (isNaN(num)) return '-';
                                    const sinDecimales = num % 1 === 0;
                                    return sinDecimales
                                      ? num.toLocaleString('es-ES')
                                      : num.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                  })()
                                }}
                              </span>
                              <span v-else>-</span>
                            </td>
                            <td>{{ outflow.project || '-' }}</td>
                            <td>{{ outflow.operation }}</td>
                            <td>{{ outflow.investment || '-' }}</td>
                            <td>{{ outflow.machinery || '-' }}</td>
                            <td>{{ (+outflow.quantity).toFixed(2) }}</td>
                            <td>
                              <span v-if="outflow.unit_price !== undefined && outflow.unit_price !== null && outflow.quantity !== undefined && outflow.quantity !== null">
                                {{
                                  (() => {
                                    const precio = Number(outflow.unit_price);
                                    const cantidad = Number(outflow.quantity);
                                    if (isNaN(precio) || isNaN(cantidad)) return '-';
                                    const total = precio * cantidad;
                                    const sinDecimales = total % 1 === 0;
                                    return sinDecimales
                                      ? total.toLocaleString('es-ES')
                                      : total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                  })()
                                }}
                              </span>
                              <span v-else>-</span>
                            </td>
                            <td :title="outflow.notes || ''">{{ outflow.notes || '-' }}</td>
                            <td>
                              <ul class="mb-0 ps-3">
                                <!-- Mostrar hasta 2 centros por defecto -->
                                <li v-for="cc in (outflow.cost_centers || []).slice(0,2)" :key="cc.name">
                                  <span class="fw-bold">{{ cc.name }}</span>
                                  <span v-if="cc.observations"> - {{ cc.observations }}</span>
                                </li>
                                <!-- Si no hay centros, mostrar guión -->
                                <li v-if="(outflow.cost_centers || []).length === 0">
                                  <span class="text-muted">-</span>
                                </li>
                                <!-- Indicador de más centros -->
                                <li v-if="(outflow.cost_centers || []).length > 2">
                                  <a
                                    href="#"
                                    class="text-primary small text-decoration-underline"
                                    @click.prevent="showMoreCenters(outflow.cost_centers)">
                                    +{{ outflow.cost_centers.length - 2 }} más
                                  </a>
                                </li>
                              </ul>
                            </td>
                            <td style="white-space:nowrap;">{{ outflow.user }}</td>
                            <td class="text-center" style="white-space:nowrap;">
                              <template v-if="outflow.fuel_outflow_id">
                                <span
                                  v-tooltip="'Esta salida proviene de Combustibles. Editarla o eliminarla desde ese módulo.'"
                                  style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:20px; cursor:help;"
                                >
                                  <span class="fas fa-lock text-muted" style="font-size: 0.7rem;"></span>
                                </span>
                              </template>
                              <template v-else>
                                <button type="button" class="btn btn-icon btn-active-light-primary w-20px h-20px me-1" @click.stop="editOutflow(outflow)">
                                  <span class="fas fa-pen" style="font-size: 0.65rem;"></span>
                                </button>
                                <button type="button" class="btn btn-icon btn-active-light-danger w-20px h-20px" @click.stop="deleteOutflow(outflow)">
                                  <span class="fas fa-trash" style="font-size: 0.65rem;"></span>
                                </button>
                              </template>
                            </td>
                          </tr>
                          <tr v-if="!props.outflowDetails || !props.outflowDetails.length">
                            <td colspan="20" class="text-center text-muted">No hay salidas registradas.</td>
                          </tr>
                          <tr v-if="hasMoreEdicion">
                            <td colspan="21" class="text-center py-2">
                              <button type="button" class="btn btn-sm btn-falcon-default" @click="visibleCountEdicion += EDICION_PAGE_SIZE">
                                <i class="fas fa-chevron-down me-1"></i>
                                Ver más ({{ sortedOutflowDetails.length - visibleCountEdicion }} restantes)
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="pill-tab-salidas" role="tabpanel" aria-labelledby="salidas-tab">
                    <div style="max-height: 450px; overflow-y: auto; overflow-x: auto;">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex gap-1 align-items-center" style="flex:1;">
                          <SearchInput v-model="inputTerm" placeholder="Buscar por producto, proveedor, documento..."
                            style="max-width:unset; flex:1; margin-bottom:0;"
                            @keyup.enter="applySearch" />
                          <button type="button" class="btn btn-falcon-primary btn-sm px-2 flex-shrink-0" @click="applySearch" title="Buscar" style="height:31px;">
                            <i class="fas fa-search"></i>
                          </button>
                          <button v-if="appliedTerm || inputTerm" type="button" class="btn btn-falcon-default btn-sm px-2 flex-shrink-0" @click="clearSearch" title="Limpiar búsqueda" style="height:31px;">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                        <div style="width:200px; flex-shrink:0;">
                          <select v-model="filterBranch" class="form-select form-select-sm">
                            <option value="">Todas las sucursales</option>
                            <option v-for="b in props.branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                          </select>
                        </div>
                      </div>
                      <Table :id="'outflows'" :total="filteredOutflows.length" :links="outflows.links">
                          <template #header>
                            <th>Origen</th>
                            <th>Factura / N° Nota</th>
                            <th>Mes contable</th>
                            <th>Proveedor</th>
                            <th>Sucursal</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Stock</th>
                            <th>Unidad</th>
                            <th>Monto</th>
                            <th class="text-center">Acciones</th>
                          </template>
                          <template #body>
                            <tr v-for="outflow in pagedOutflows" :key="(outflow.credit_debit_note_item_id ? 'nota-' + outflow.credit_debit_note_item_id : 'factura-' + outflow.invoice_product_id)">
                                <td>
                                  <span v-if="outflow.origen && outflow.origen.toLowerCase().includes('factura')" class="badge bg-success">{{ outflow.origen }}</span>
                                  <span v-else class="badge bg-info text-dark">{{ outflow.origen }}</span>
                                </td>
                                <td>
                                  {{ outflow.number_document }}
                                  <span
                                    v-if="outflow.has_credit_note"
                                    class="badge bg-warning text-dark ms-1"
                                    tabindex="0"
                                    data-bs-toggle="popover"
                                    data-bs-html="true"
                                    :data-bs-content="formatCreditNotePopover(outflow.credit_note_info)"
                                    data-bs-trigger="focus hover"
                                    style="cursor:pointer;"
                                  >+NC</span>
                                </td>
                                <td>{{ outflow.mes_contable || '-' }}</td>
                                <td>{{ outflow.supplier }}</td>
                                <td>{{ outflow.branch_name || '—' }}</td>
                                <td>{{ outflow.product }}</td>
                                <td>{{ (+outflow.quantity).toFixed(2) }}</td>
                                <td>{{ outflow.stock }}</td>
                                <td>{{ outflow.unit }}</td>
                                <td>{{ formatLineAmount(outflow) }}</td>
                                <td class="text-center">
                                  <button 
                                    @click.stop="openCard(outflow)"
                                    type="button"
                                    class="btn btn-sm me-1"
                                    :class="isCardOpen(outflow) ? 'btn-success btn-active' : 'btn-white'"
                                    :title="isCardOpen(outflow) ? 'Cerrar card' : 'Registrar salida'">
                                    <span 
                                      class="fas fa-paper-plane"
                                      :class="isCardOpen(outflow) ? 'text-success' : 'text-secondary'"
                                    ></span>
                                  </button>
                                </td>

                            </tr>
                            <tr v-if="filteredOutflows.length === 0">
                                <td colspan="11"><Empty /></td>
                            </tr>
                            <!-- Ver más -->
                            <tr v-if="hasMoreOutflows">
                                <td colspan="11" class="text-center py-2">
                                    <button type="button" class="btn btn-sm btn-falcon-default" @click="visibleCountSalidas += SALIDAS_PAGE_SIZE">
                                        <i class="fas fa-chevron-down me-1"></i>
                                        Ver más ({{ filteredOutflows.length - visibleCountSalidas }} restantes)
                                    </button>
                                </td>
                            </tr>
                          </template>
                      </Table>
                    </div>
                    <!-- Cards para agregar nuevas salidas SOLO visibles en la pestaña Salidas -->
                    <div v-for="(selected, idx) in selectedOutflows" :key="selected.id" class="outflow-cards mt-4">
                      <h6 class="mb-2">Registrar salida</h6>
                      <div class="card mb-2 p-3 shadow-sm">
                        <div class="row g-2 align-items-center">
                          <!-- FILA 1: Producto, Unidad, Sucursal, Cantidad, Fecha, Precio Unit., Total -->
                          <div class="col-12 col-md-2">
                            <label class="form-label">Producto</label>
                            <input v-model="selected.product_name" class="form-control form-control-sm w-100" disabled />
                          </div>
                          <div class="col-6 col-md-1">
                            <label class="form-label">Unidad</label>
                            <input v-model="selected.unit_name" class="form-control form-control-sm w-100" disabled />
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label">Sucursal</label>
                            <input v-model="selected.branch_name" class="form-control form-control-sm w-100" disabled />
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label">Cantidad</label>
                            <input
                              v-model="selected.quantity"
                              class="form-control form-control-sm w-100"
                              type="number"
                              min="0.01"
                              :max="selected.stock_original"
                              step="0.01"
                              @input="
                                if (Number(selected.quantity) > Number(selected.stock_original)) {
                                  selected.quantity = selected.stock_original;
                                }
                              "
                            />
                            <small v-if="Number(selected.quantity) > Number(selected.stock_original)" class="text-danger">No puede exceder el stock disponible ({{ selected.stock_original }})</small>
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input
                              v-model="selected.date"
                              class="form-control form-control-sm w-100"
                              type="date"
                              required
                            />
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label text-muted">Precio Unit.</label>
                            <input
                              :value="formatPrice(selected.unit_price)"
                              class="form-control form-control-sm w-100 bg-light"
                              disabled
                              style="cursor: not-allowed;"
                            />
                          </div>
                          <div class="col-6 col-md-2">
                            <label class="form-label fw-bold text-primary">Total</label>
                            <input
                              :value="calculateTotal(selected)"
                              class="form-control form-control-sm w-100 bg-light-primary fw-bold"
                              disabled
                              style="cursor: not-allowed;"
                            />
                          </div>

                          <!-- FILA 2: Operación, Inversión, Proyecto, Maquinaria -->
                          <div class="col-12 col-md-2">
                            <label class="form-label">Operación</label>
                            <select 
                              v-model="selected.operation_id" 
                              class="form-select form-select-sm"
                              @change="selected.investment_id = null"
                            >
                              <option :value="null" disabled selected hidden>Seleccione operación</option>
                              <option v-for="operation in props.operations" :key="operation.value" :value="operation.value">
                                {{ operation.label }}
                              </option>
                            </select>
                          </div>
                          <!-- Select inversión: solo aparece si la operación seleccionada es "Inversión" -->
                          <div v-if="isInversionOp(selected.operation_id)" class="col-12 col-md-2">
                            <label class="form-label">Inversión</label>
                            <select
                              v-model="selected.investment_id"
                              class="form-select form-select-sm"
                            >
                              <option :value="null">— Sin inversión —</option>
                              <option v-for="inv in props.investments" :key="inv.value" :value="inv.value">
                                {{ inv.label }}
                              </option>
                            </select>
                          </div>
                          <div class="col-12 col-md-2">
                            <label class="form-label">Proyecto</label>
                            <select 
                              v-model="selected.project_id" 
                              class="form-select form-select-sm"
                            >
                              <option :value="null" disabled selected hidden>Seleccione proyecto</option>
                              <option v-for="project in props.projects" :key="project.value" :value="project.value">
                                {{ project.label }}
                              </option>
                            </select>
                          </div>
                          <div class="col-12 col-md-2">
                            <label class="form-label">Maquinaria</label>
                            <select 
                              v-model="selected.machinery_id" 
                              class="form-select form-select-sm"
                            >
                              <option :value="null" disabled selected hidden>Seleccione maquinaria</option>
                              <option v-for="machinery in props.machineries" :key="machinery.value" :value="machinery.value">
                                {{ machinery.label }}
                              </option>
                            </select>
                          </div>

                          <!-- FILA 3: Nivel 2 (Filtro), Nivel 3 (Clasificación) -->
                          <div class="col-12 col-md-4">
                            <label class="form-label">
                              Nivel 2 (Filtro)
                              <i class="fas fa-filter text-muted" style="font-size: 0.65rem;"></i>
                            </label>
                            <select 
                              v-model="selected.level2_id" 
                              class="form-select form-select-sm"
                              @change="!selected.suggested_level3 && (selected.level3_id = null)"
                            >
                              
                              <option v-for="level2 in props.levels2" :key="level2.value" :value="level2.value">
                                {{ level2.label }}
                              </option>
                            </select>
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">
                              Clasificación (Nivel 3) <span class="text-danger">*</span>
                              <span v-if="selected.suggested_level3" class="badge bg-info ms-1" style="font-size: 0.65rem;">
                                <i class="fas fa-lightbulb"></i> Sugerido
                              </span>
                            </label>
                            <select 
                              v-model="selected.level3_id" 
                              class="form-select form-select-sm"
                              :class="{ 'border-info': selected.suggested_level3 }"
                              @change="selected.suggested_level3 = false"
                              required
                            >
                             
                              <option v-for="level in getFilteredLevels3(selected.id)" :key="level.value" :value="level.value">
                                {{ level.label }}
                              </option>
                            </select>
                            <small v-if="selected.level2_id && getFilteredLevels3(selected.id).length === 0" class="text-muted">
                              No hay opciones para este nivel 2
                            </small>
                          </div>

                          <!-- FILA 4: Agrupación, Centro de Costo, Observaciones -->
                          <div class="col-12 col-md-3">
                            <label class="col-form-label mb-0">Agrupación</label>
                            <select 
                              v-model="selectedGroupings[selected.id]" 
                              class="form-select form-select-sm"
                            >
                              <option :value="null" disabled selected hidden>Seleccione agrupación</option>
                              <option v-for="g in (page.props.groupings || [])" :key="g.id" :value="g.id">
                                {{ g.name }}
                              </option>
                            </select>
                          </div>
                          <div class="col-12 col-md-5">
                            <div class="d-flex align-items-center justify-content-between mb-0">
                              <label class="form-label mb-0">Centro de Costo
                                <span v-if="selected.cost_center_ids.length > 0" class="badge bg-primary ms-1" style="font-size: 0.6rem; vertical-align: middle;">
                                  {{ selected.cost_center_ids.length }}
                                </span>
                              </label>
                              <button
                                v-if="selected.cost_center_ids.length > 5"
                                type="button"
                                @click="expandedCCCards[selected.id] = !expandedCCCards[selected.id]"
                                class="btn btn-link btn-sm p-0 text-muted"
                                style="font-size: 0.65rem; text-decoration: none;"
                              >
                                <i class="fas" :class="expandedCCCards[selected.id] ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size: 0.6rem;"></i>
                                {{ expandedCCCards[selected.id] ? 'Colapsar' : 'Ver todos' }}
                              </button>
                            </div>
                            <Multiselect
                              mode="tags"
                              placeholder="Centro de Costo"
                              v-model="selected.cost_center_ids"
                              :close-on-select="false"
                              :options="props.cost_centers"
                              option-label="label"
                              option-value="value"
                              :searchable="true"
                              :hide-selected="false"
                              :class="['multiselect-blue form-control-sm multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCCCards[selected.id] }]"
                            />
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Observaciones</label>
                            <input v-model="selected.observations" class="form-control form-control-sm w-100" />
                          </div>

                          <!-- Botones: Copiar y Cerrar -->
                          <div class="col-12 col-md-12 mt-2 text-end">
                            <div 
                              class="copy-icon-wrapper d-inline-flex me-2"
                              @click="copyCCToAllCards(selected.id)"
                              title="Copiar centros de costo a todos los cards abiertos"
                            >
                              <i class="fas fa-sitemap" style="color: #ffffff;"></i>
                            </div>
                            <div 
                              class="copy-icon-wrapper d-inline-flex me-2"
                              @click="copyToAllCards(selected.id)"
                              title="Copiar fecha, operación, maquinaria, proyecto y clasificación a todos los cards"
                            >
                              <i class="fas fa-clone" style="color: #ffffff;"></i>
                            </div>
                            <button type="button" @click="closeCard(selected.id)" class="btn btn-sm btn-secondary">Cerrar</button>
                          </div>
                        </div>
                      </div>
                    </div>


                      <!-- Botón Guardar global -->
                <div class="row mt-4" v-if="selectedOutflows.length">
                  <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary btn-sm px-3 py-1" @click="handleSave">
                      <i class="fas fa-save me-2"></i> Guardar todas las salidas
                    </button>
                  </div>
                </div>
                    <!-- Fin cards -->
                  </div>
                  <!-- ── Tab Consumo Matriz ───────────────────────────────────────── -->
                  <div class="tab-pane fade" id="pill-tab-gastos" role="tabpanel" aria-labelledby="pill-gastos">
                    <!-- Filtros -->
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                      <div class="d-flex gap-1 align-items-center" style="min-width:180px; flex:1 1 220px; max-width:300px;">
                        <SearchInput v-model="termEdicion" placeholder="Buscar..."
                          style="max-width:unset; flex:1; margin-bottom:0;"
                          @keyup.enter="applySearchEdicion" />
                        <button type="button" class="btn btn-falcon-primary btn-sm px-2 flex-shrink-0" @click="applySearchEdicion" title="Buscar" style="height:31px;">
                          <i class="fas fa-search"></i>
                        </button>
                        <button v-if="appliedTermEdicion || termEdicion" type="button" class="btn btn-falcon-default btn-sm px-2 flex-shrink-0" @click="clearSearchEdicion" title="Limpiar búsqueda" style="height:31px;">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                      <div style="min-width:110px; flex:0 1 130px;">
                        <select v-model="filterMes" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los meses</option>
                          <option v-for="o in mesOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:130px; flex:0 1 160px;">
                        <select v-model="filterOperation" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las operaciones</option>
                          <option v-for="o in operationOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:130px; flex:0 1 180px;">
                        <select v-model="filterSupplier" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proveedores</option>
                          <option v-for="o in supplierOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel1" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Nivel 1</option>
                          <option v-for="o in level1Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel2" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel1">
                          <option :value="null">Nivel 2</option>
                          <option v-for="o in level2Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel3" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel2">
                          <option :value="null">Nivel 3</option>
                          <option v-for="o in level3Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterProject" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proyectos</option>
                          <option v-for="o in projectOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterBranchEdicion" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las sucursales</option>
                          <option v-for="o in branchEdicionOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="btn btn-falcon-default btn-sm"
                        @click="filterMes=null; filterOperation=null; filterSupplier=null; filterLevel1=null; filterLevel2=null; filterLevel3=null; filterProject=null; filterBranchEdicion=null; termEdicion='';"
                        title="Limpiar filtros"
                      ><i class="fas fa-times"></i></button>
                      <ExportExcelButton
                        :data="matrizExcelData"
                        :headers="matrizExcelHeaders"
                        filename="consumo_matriz.xlsx"
                        class="btn btn-falcon-default btn-sm py-1"
                      >
                        <i class="fas fa-file-excel me-1"></i> Excel
                      </ExportExcelButton>
                      <small class="text-muted ms-auto">{{ sortedOutflowDetails.length }} registros</small>
                      <button
                        v-if="Object.keys(selectedMatrizRows).length"
                        type="button"
                        class="btn btn-sm btn-warning py-0 px-2"
                        @click="selectedMatrizRows = {}"
                        style="font-size:0.75rem;"
                        v-tooltip="'Limpiar filas seleccionadas'"
                      ><i class="fas fa-times me-1"></i>{{ Object.keys(selectedMatrizRows).length }} selecc.</button>
                    </div>
                    <div style="max-height: 520px; overflow: auto;">
                      <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.7rem; min-width: max-content;">
                        <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                          <tr>
                            <!-- Columnas fijas -->
                            <th style="white-space:nowrap;">ID</th>
                            <th style="white-space:nowrap;">Fecha dig.</th>
                            <th style="white-space:nowrap;">N° Doc</th>
                            <th style="white-space:nowrap;">Proveedor</th>
                            <th style="white-space:nowrap;">Fecha factura</th>
                            <th style="white-space:nowrap;">Mes contable</th>
                            <th style="white-space:nowrap;">Producto</th>
                            <th style="white-space:nowrap;">Nivel 1</th>
                            <th style="white-space:nowrap;">Nivel 2</th>
                            <th style="white-space:nowrap;">Nivel 3</th>
                            <th class="text-end" style="white-space:nowrap;">Precio Unit.</th>
                            <th style="white-space:nowrap;">Proyecto</th>
                            <th style="white-space:nowrap;">Operación</th>
                            <th style="white-space:nowrap;">Inversión</th>
                            <th style="white-space:nowrap;">Maquinaria</th>
                            <th class="text-end" style="white-space:nowrap;">Cantidad</th>
                            <th class="text-end" style="white-space:nowrap;">Total</th>
                            <th style="white-space:nowrap;">Sucursal</th>
                            <th style="white-space:nowrap;">Observaciones</th>
                            <!-- Columnas dinámicas por CC -->
                            <th
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap; background:#d4e6f1;"
                            >{{ cc.label }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-if="!sortedOutflowDetails.length">
                            <td :colspan="19 + ccColumns.length" class="text-center py-3 text-muted">No hay salidas registradas.</td>
                          </tr>
                          <tr v-for="outflow in pagedOutflowDetails" :key="outflow.id"
                            @click="toggleMatrizRow(outflow.id)"
                            :class="{ 'matriz-row-selected': selectedMatrizRows[outflow.id] }"
                            style="cursor:pointer;"
                          >
                            <td>{{ outflow.id }}</td>
                            <td style="white-space:nowrap;">{{ outflow.date }}</td>
                            <td style="white-space:nowrap;">{{ outflow.number_document }}</td>
                            <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="outflow.supplier">{{ outflow.supplier }}</td>
                            <td style="white-space:nowrap;">{{ outflow.fecha_factura }}</td>
                            <td style="white-space:nowrap;">{{ outflow.mes_contable || '—' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="outflow.product">{{ outflow.product }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level1_name || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level2_name || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level3_name || '—' }}</td>
                            <td class="text-end" style="white-space:nowrap;">${{ Number(outflow.unit_price || 0).toLocaleString('es-ES') }}</td>
                            <td style="white-space:nowrap;">{{ outflow.project || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.operation || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.investment || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.machinery || '—' }}</td>
                            <td class="text-end" style="white-space:nowrap;">{{ Number(outflow.quantity || 0).toLocaleString('es-ES') }}</td>
                            <td class="text-end" style="white-space:nowrap;">${{ (Number(outflow.unit_price || 0) * Number(outflow.quantity || 0)).toLocaleString('es-ES', {maximumFractionDigits:0}) }}</td>
                            <td style="white-space:nowrap;">{{ outflow.branch_name || '—' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="outflow.notes">{{ outflow.notes || '—' }}</td>
                            <!-- Celdas dinámicas por CC -->
                            <td
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap;"
                            >
                              <template v-if="getProrationAmount(outflow, cc.value) !== null">
                                ${{ Math.round(getProrationAmount(outflow, cc.value)).toLocaleString('es-ES') }}
                              </template>
                              <template v-else>
                                <span class="text-muted">—</span>
                              </template>
                            </td>
                          </tr>
                          <tr v-if="hasMoreEdicion">
                            <td :colspan="19 + ccColumns.length" class="text-center py-2">
                              <button type="button" class="btn btn-sm btn-falcon-default" @click="visibleCountEdicion += EDICION_PAGE_SIZE">
                                <i class="fas fa-chevron-down me-1"></i>
                                Ver más ({{ sortedOutflowDetails.length - visibleCountEdicion }} restantes)
                              </button>
                            </td>
                          </tr>
                        </tbody>
                        <!-- Fila de totales -->
                        <tfoot v-if="sortedOutflowDetails.length" style="position:sticky; bottom:0; z-index:5;">
                          <tr class="table-secondary fw-bold">
                            <td :colspan="16" class="text-end" style="white-space:nowrap;">Total</td>
                            <td class="text-end" style="white-space:nowrap;">
                              ${{ sortedOutflowDetails.reduce((s, o) => s + Number(o.unit_price||0)*Number(o.quantity||0), 0).toLocaleString('es-ES', {maximumFractionDigits:0}) }}
                            </td>
                            <td></td>
                            <td></td>
                            <td
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap;"
                            >
                              ${{ Math.round(ccColumnTotal(cc.value)).toLocaleString('es-ES') }}
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                  <!-- ──────────────────────────────────────────────────────────────── -->
                  <!-- ── Tab Consumo Matriz Hectárea ──────────────────────────────── -->
                  <div class="tab-pane fade" id="pill-tab-detalles-compra" role="tabpanel" aria-labelledby="pill-detalles-compra">
                    <!-- Filtros -->
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                      <div class="d-flex gap-1 align-items-center" style="min-width:180px; flex:1 1 220px; max-width:300px;">
                        <SearchInput v-model="termEdicion" placeholder="Buscar..."
                          style="max-width:unset; flex:1; margin-bottom:0;"
                          @keyup.enter="applySearchEdicion" />
                        <button type="button" class="btn btn-falcon-primary btn-sm px-2 flex-shrink-0" @click="applySearchEdicion" title="Buscar" style="height:31px;">
                          <i class="fas fa-search"></i>
                        </button>
                        <button v-if="appliedTermEdicion || termEdicion" type="button" class="btn btn-falcon-default btn-sm px-2 flex-shrink-0" @click="clearSearchEdicion" title="Limpiar búsqueda" style="height:31px;">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                      <div style="min-width:110px; flex:0 1 130px;">
                        <select v-model="filterMes" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los meses</option>
                          <option v-for="o in mesOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:130px; flex:0 1 160px;">
                        <select v-model="filterOperation" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las operaciones</option>
                          <option v-for="o in operationOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:130px; flex:0 1 180px;">
                        <select v-model="filterSupplier" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proveedores</option>
                          <option v-for="o in supplierOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel1" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Nivel 1</option>
                          <option v-for="o in level1Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel2" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel1">
                          <option :value="null">Nivel 2</option>
                          <option v-for="o in level2Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterLevel3" class="form-select form-select-sm" style="font-size:0.72rem;" :disabled="!filterLevel2">
                          <option :value="null">Nivel 3</option>
                          <option v-for="o in level3Options" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterProject" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todos los proyectos</option>
                          <option v-for="o in projectOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <div style="min-width:120px; flex:0 1 150px;">
                        <select v-model="filterBranchEdicion" class="form-select form-select-sm" style="font-size:0.72rem;">
                          <option :value="null">Todas las sucursales</option>
                          <option v-for="o in branchEdicionOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                      </div>
                      <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="btn btn-falcon-default btn-sm"
                        @click="filterMes=null; filterOperation=null; filterSupplier=null; filterLevel1=null; filterLevel2=null; filterLevel3=null; filterProject=null; filterBranchEdicion=null; termEdicion='';"
                        title="Limpiar filtros"
                      ><i class="fas fa-times"></i></button>
                      <ExportExcelButton
                        :data="matrizHaExcelData"
                        :headers="matrizHaExcelHeaders"
                        filename="consumo_matriz_ha.xlsx"
                        class="btn btn-falcon-default btn-sm py-1"
                      >
                        <i class="fas fa-file-excel me-1"></i> Excel
                      </ExportExcelButton>
                      <small class="text-muted ms-auto">{{ sortedOutflowDetails.length }} registros</small>
                      <button
                        v-if="Object.keys(selectedMatrizHaRows).length"
                        type="button"
                        class="btn btn-sm btn-warning py-0 px-2"
                        @click="selectedMatrizHaRows = {}"
                        style="font-size:0.75rem;"
                        v-tooltip="'Limpiar filas seleccionadas'"
                      ><i class="fas fa-times me-1"></i>{{ Object.keys(selectedMatrizHaRows).length }} selecc.</button>
                    </div>
                    <div style="max-height: 520px; overflow: auto;">
                      <table class="table table-bordered table-hover table-sm mb-0" style="font-size:0.7rem; min-width: max-content;">
                        <thead class="table-success" style="position: sticky; top: 0; z-index: 10;">
                          <tr>
                            <th style="white-space:nowrap;">ID</th>
                            <th style="white-space:nowrap;">Fecha dig.</th>
                            <th style="white-space:nowrap;">N° Doc</th>
                            <th style="white-space:nowrap;">Proveedor</th>
                            <th style="white-space:nowrap;">Fecha factura</th>
                            <th style="white-space:nowrap;">Mes contable</th>
                            <th style="white-space:nowrap;">Producto</th>
                            <th style="white-space:nowrap;">Nivel 1</th>
                            <th style="white-space:nowrap;">Nivel 2</th>
                            <th style="white-space:nowrap;">Nivel 3</th>
                            <th class="text-end" style="white-space:nowrap;">Precio Unit.</th>
                            <th style="white-space:nowrap;">Proyecto</th>
                            <th style="white-space:nowrap;">Operación</th>
                            <th style="white-space:nowrap;">Inversión</th>
                            <th style="white-space:nowrap;">Maquinaria</th>
                            <th class="text-end" style="white-space:nowrap;">Cantidad</th>
                            <th class="text-end" style="white-space:nowrap;">Total</th>
                            <th style="white-space:nowrap;">Sucursal</th>
                            <th
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap; background:#d5f5e3;"
                            >{{ cc.label }} <small class="text-muted">($/ha)</small></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-if="!sortedOutflowDetails.length">
                            <td :colspan="18 + ccColumns.length" class="text-center py-3 text-muted">No hay salidas registradas.</td>
                          </tr>
                          <tr v-for="outflow in pagedOutflowDetails" :key="outflow.id"
                            @click="toggleMatrizHaRow(outflow.id)"
                            :class="{ 'matriz-row-selected': selectedMatrizHaRows[outflow.id] }"
                            style="cursor:pointer;"
                          >
                            <td>{{ outflow.id }}</td>
                            <td style="white-space:nowrap;">{{ outflow.date }}</td>
                            <td style="white-space:nowrap;">{{ outflow.number_document }}</td>
                            <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="outflow.supplier">{{ outflow.supplier }}</td>
                            <td style="white-space:nowrap;">{{ outflow.fecha_factura }}</td>
                            <td style="white-space:nowrap;">{{ outflow.mes_contable || '—' }}</td>
                            <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="outflow.product">{{ outflow.product }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level1_name || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level2_name || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.level3_name || '—' }}</td>
                            <td class="text-end" style="white-space:nowrap;">${{ Number(outflow.unit_price || 0).toLocaleString('es-ES') }}</td>
                            <td style="white-space:nowrap;">{{ outflow.project || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.operation || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.investment || '—' }}</td>
                            <td style="white-space:nowrap;">{{ outflow.machinery || '—' }}</td>
                            <td class="text-end" style="white-space:nowrap;">{{ Number(outflow.quantity || 0).toLocaleString('es-ES') }}</td>
                            <td class="text-end" style="white-space:nowrap;">${{ (Number(outflow.unit_price || 0) * Number(outflow.quantity || 0)).toLocaleString('es-ES', {maximumFractionDigits:0}) }}</td>
                            <td style="white-space:nowrap;">{{ outflow.branch_name || '—' }}</td>
                            <td
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap;"
                            >
                              <template v-if="getProrationPerHa(outflow, cc.value) !== null">
                                ${{ Math.round(getProrationPerHa(outflow, cc.value)).toLocaleString('es-ES') }}
                              </template>
                              <template v-else>
                                <span class="text-muted">—</span>
                              </template>
                            </td>
                          </tr>
                          <tr v-if="hasMoreEdicion">
                            <td :colspan="18 + ccColumns.length" class="text-center py-2">
                              <button type="button" class="btn btn-sm btn-falcon-default" @click="visibleCountEdicion += EDICION_PAGE_SIZE">
                                <i class="fas fa-chevron-down me-1"></i>
                                Ver más ({{ sortedOutflowDetails.length - visibleCountEdicion }} restantes)
                              </button>
                            </td>
                          </tr>
                        </tbody>
                        <tfoot v-if="sortedOutflowDetails.length" style="position:sticky; bottom:0; z-index:5;">
                          <tr class="table-secondary fw-bold">
                            <td :colspan="16" class="text-end" style="white-space:nowrap;">Total</td>
                            <td class="text-end" style="white-space:nowrap;">
                              ${{ sortedOutflowDetails.reduce((s, o) => s + Number(o.unit_price||0)*Number(o.quantity||0), 0).toLocaleString('es-ES', {maximumFractionDigits:0}) }}
                            </td>
                            <td></td>
                            <td
                              v-for="cc in ccColumns"
                              :key="cc.value"
                              class="text-end"
                              style="white-space:nowrap;"
                            >
                              ${{ Math.round(ccColumnTotalPerHa(cc.value)).toLocaleString('es-ES') }}
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                  <!-- ──────────────────────────────────────────────────────────────── -->
               </div>
              </div>
            </div>
       
       </AppLayout>

       <OutflowEditModal
        :show="showEditModal"
        :form="{ ...editForm }"
        :projects="editProjects"
        :operations="editOperations"
        :investments="editInvestments"
        :machineries="editMachineries"
        :costCenters="editCostCenters"
        :groupings="page.props.groupings || []"
        :stockAvailable="editStockAvailable"
        :stockLineData="editStockLineData"
        :levels2="props.levels2"
        :levels3="props.levels3"
        @close="showEditModal = false"
        @updated="handleEditModalUpdated"
      />
  

</template>



<style>
/* Fila seleccionada en Matriz de Consumo */
.matriz-row-selected td {
    background-color: #fff3cd !important;
    border-top: 1px solid #ffc107 !important;
    border-bottom: 1px solid #ffc107 !important;
}

.multiselect-blue {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.75rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajuste de placeholder dentro de multiselect-blue */
.multiselect-blue .multiselect__placeholder {
    font-size: 0.85rem !important;
    opacity: 0.7 !important;
	 white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ajustes para inputs nativos */
input.form-control:not([role="combobox"]),
select.form-control {
    height: 26px;
    min-height: 26px;
    font-size: 0.75rem;
    padding-top: 2px;
    padding-bottom: 2px;
}

/* Ajuste de tamaño de placeholder en inputs nativos */
input.form-control::placeholder {
    font-size: 0.75rem !important;
    opacity: 0.7 !important;
}

/* Checkboxes */
.form-check-input[type="checkbox"] {
    width: 0.8em;
    height: 0.8em;
    vertical-align: middle;
}
/* Group icon alignment */
.input-group-text {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}
/* Labels */
.col-form-label,
label {
    font-size: 0.8rem;
}
/* Opciones del multiselect */
.multiselect__option {
    font-size: 0.7rem;
}
/* Asegura z-index adecuado para dropdown */
.multiselect__content {
    z-index: 2050;
}


input::placeholder,
textarea::placeholder {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
textarea::placeholder {
  text-transform: none !important;
}


.elegant-divider {
	width: 100%;
	height: 3px;
	border: none;
	border-radius: 2px;
	background: linear-gradient(90deg, rgba(44,123,229,0.18) 0%, rgba(44,123,229,0.45) 50%, rgba(44,123,229,0.18) 100%);
	box-shadow: 0 2px 8px 0 rgba(44,123,229,0.10);
}

.table-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 20;
  border-radius: 4px;
}

.table-spinner {
  width: 36px;
  height: 36px;
  border: 4px solid #e3e6ed;
  border-top: 4px solid #2c7be5;
  border-radius: 50%;
  animation: table-spin 0.8s linear infinite;
  margin: 0 auto;
}

@keyframes table-spin {
  to { transform: rotate(360deg); }
}


/* Header fijo para todas las tablas de Outflows */
.table-responsive, .outflows-table-scroll {
  max-height: 450px;
  overflow-y: auto;
  overflow-x: auto;
  width: 100%;
}
th {
  position: sticky;
  top: 0;
  background: #f8f9fa;
  z-index: 2;
  white-space: nowrap;
}

/* Celdas de tabla: no apilar, ocultar con ellipsis, alto de fila compacto */
#pill-tab-edicion .table td {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
  line-height: 1.2;
}

/* Multiselect compacto para filtros */
.multiselect-sm {
  --ms-font-size: 0.8rem;
  --ms-line-height: 1.3;
  --ms-py: 0.25rem;
  --ms-px: 0.5rem;
  --ms-tag-font-size: 0.75rem;
  --ms-option-font-size: 0.8rem;
  --ms-ring-width: 0px;
  min-height: 32px;
}

.td-supplier {
  max-width:140px;
  min-width:100px;
  width:140px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
/* Header fijo para todas las tablas de Outflows */
.table-responsive, .outflows-table-scroll {
  max-height: 450px;
  overflow-y: auto;
  overflow-x: auto;
  width: 100%;
}
th {
  position: sticky;
  top: 0;
  background: #f8f9fa;
  z-index: 2;
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

/* Ajustes para Multiselect - más opciones visibles - Override Bootstrap */
.multiselect :deep(.multiselect-dropdown),
.multiselect-blue :deep(.multiselect-dropdown) {
  max-height: 400px !important;
  height: auto !important;
}

.multiselect :deep(.multiselect-options),
.multiselect-blue :deep(.multiselect-options) {
  max-height: 400px !important;
  overflow-y: auto !important;
}

/* Variable CSS de Multiselect */
.multiselect-blue {
  --ms-max-height: 400px !important;
  --ms-dropdown-max-height: 400px !important;
}

/* Reducir tamaño de fuente en los cards de registro de salidas */
.outflow-cards .form-control-sm,
.outflow-cards .form-select-sm {
  font-size: 0.75rem !important;
  padding: 0.2rem 0.5rem !important;
  height: calc(1.3em + 0.4rem + 2px) !important;
}

.outflow-cards .form-label {
  font-size: 0.75rem !important;
  margin-bottom: 0.2rem !important;
}

/* Ajuste para el multiselect dentro de las cards */
.outflow-cards .multiselect-blue {
  font-size: 0.75rem !important;
  min-height: 29px !important;
  height: 29px !important;
}

/* Limitar tags visibles en el multiselect de centros de costo */
.multiselect-tags-limited .multiselect-tags {
    max-height: 32px !important;
    overflow: hidden !important;
    flex-wrap: wrap;
    transition: max-height 0.3s ease;
}

/* Estado expandido */
.multiselect-tags-expanded .multiselect-tags {
    max-height: 200px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

/* Scrollbar discreto para los tags expandidos */
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar {
    width: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-track {
    background: transparent;
}

/* Ajuste para el multiselect limitado: permitir auto-height */
.outflow-cards .multiselect-blue.multiselect-tags-limited {
    height: auto !important;
    max-height: 38px !important;
    min-height: 29px !important;
    transition: max-height 0.3s ease;
}

/* Estado expandido del contenedor */
.outflow-cards .multiselect-blue.multiselect-tags-expanded {
    max-height: 210px !important;
}

/* Ícono de copiar/clonar con efecto hover */
.copy-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #667eea 0%, #44a2cd 100%);
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
  vertical-align: middle;
  position: relative;
  top: 1px;
}

.copy-icon-wrapper:hover {
  transform: translateY(-1px) scale(1.05);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
  background: linear-gradient(135deg, #7c8ef5 0%, #5ab3d8 100%);
}

.copy-icon-wrapper:active {
  transform: translateY(1px) scale(0.95);
}

.copy-icon-wrapper i,
.copy-icon-wrapper i.fas,
.copy-icon-wrapper i.fa-clone {
  color: #ffffff !important;
  font-size: 0.8rem !important;
  opacity: 1 !important;
}


</style>