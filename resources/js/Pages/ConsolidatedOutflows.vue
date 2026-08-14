<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardHeader from '@/Components/CardHeader.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    outflows: Object,
    filters: Object,
    filterOptions: Object,
    totals: Object,
});

const title = 'Consolidado de Salidas';

const links = [
    { title: 'Gestión', url: null },
    { title: 'Consolidado de Salidas', active: true }
];

// Estado reactivo inicializado desde filtros del servidor
const term = ref(props.filters?.term || '');
const monthFilter = ref(props.filters?.month || '');
const supplierFilter = ref(props.filters?.supplier_id || '');
const level2Filter = ref(props.filters?.level2_id || '');
const level3Filter = ref(props.filters?.level3_id || '');
const tipoGastoFilter = ref(props.filters?.tipo_gasto || 'gestion');
const sortBy = ref(props.filters?.sort_by || 'outflow_id');
const sortDesc = ref(props.filters?.sort_desc ?? true);
const perPage = ref(props.filters?.per_page || 50);
const isLoading = ref(false);

const monthOptions = computed(() => props.filterOptions?.months || []);
const supplierOptions = computed(() => props.filterOptions?.suppliers || []);
const level2Options = computed(() => props.filterOptions?.levels2 || []);
const level3Options = computed(() => props.filterOptions?.levels3 || []);

watch(level2Filter, () => {
    if (level2Filter.value !== '') {
        level3Filter.value = '';
    }
});

function clearFilters() {
    monthFilter.value = '';
    supplierFilter.value = '';
    level2Filter.value = '';
    level3Filter.value = '';
    tipoGastoFilter.value = 'gestion';
    fetchData({ page: 1 });
}

// Debounce timer
let searchTimeout = null;

// Función para hacer request al servidor
function fetchData(extraParams = {}) {
    isLoading.value = true;
    router.get(route('consolidated-outflows.index'), {
        term: term.value,
        month: monthFilter.value,
        supplier_id: supplierFilter.value,
        level2_id: level2Filter.value,
        level3_id: level3Filter.value,
        tipo_gasto: tipoGastoFilter.value,
        sort_by: sortBy.value,
        sort_desc: sortDesc.value ? 'true' : 'false',
        per_page: perPage.value,
        ...extraParams,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Búsqueda con debounce (300ms) - busca en TODOS los registros
function onSearch(value) {
    term.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchData({ page: 1 }); // Siempre volver a página 1 al buscar
    }, 400);
}

// Ordenamiento server-side
function setSort(field) {
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = field;
        sortDesc.value = false;
    }
    fetchData({ page: 1 });
}

const sortClass = (field) => ({
    sortable: true,
    'sorted-asc': sortBy.value === field && !sortDesc.value,
    'sorted-desc': sortBy.value === field && sortDesc.value,
});

// Formateo de números
function formatNumber(value, decimals = 2) {
    return new Intl.NumberFormat('es-ES', {
        style: 'decimal',
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(value ?? 0);
}

// Datos para exportar a Excel (exporta la página actual visible)
const excelData = computed(() => {
    if (!props.outflows || !props.outflows.data) return [];
    return props.outflows.data.map(item => ({
        id_salida: item.outflow_id,
        tipo_gasto: item.tipo_gasto,
        fecha: item.date,
        mes: item.month,
        proveedor: item.supplier,
        sucursal_factura: item.branch_factura,
        razon_social_factura: item.company_reason_factura,
        numero_documento: item.number_document,
        tipo_documento: item.tipo_documento,
        producto: item.product_name,
        unidad: item.unit_name,
        nivel_1: item.level1_name || '-',
        nivel_2: item.level2_name || '-',
        nivel_3: item.level3_name || '-',
        cantidad_total: item.quantity_total,
        precio_unitario: item.unit_price,
        proyecto: item.project || '-',
        operacion: item.operation || '-',
        maquinaria: item.machinery || '-',
        centro_costo: item.cost_center_name,
        sucursal_cc: item.branch_cc,
        razon_social_cc: item.company_reason_cc,
        superficie_cc: item.surface,
        cantidad_asignada: item.cantidad_asignada,
        estado_desarrollo: item.development_state || '-',
        cantidad_por_ha: item.cantidad_por_ha,
        total: item.total,
        notas: item.notes || '',
    }));
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        
        <div class="card my-3">
            <CardHeader :title="title" />
            <div class="card-body bg-body-tertiary">
                <!-- Filtros y búsqueda -->
                <div class="row mb-3 g-2 align-items-end">
                    <div class="col-md-3 col-12">
                        <label class="form-label small mb-1">Búsqueda</label>
                        <input
                            :value="term"
                            @input="onSearch($event.target.value)"
                            placeholder="Producto, proveedor, proyecto..."
                            class="form-control form-control-sm"
                        />
                        <small class="text-muted" v-if="isLoading">
                            <i class="fas fa-spinner fa-spin me-1"></i>Buscando...
                        </small>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label small mb-1">Tipo de Gasto</label>
                        <select v-model="tipoGastoFilter" @change="fetchData({ page: 1 })" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="gestion">Gestión</option>
                            <option value="remuneraciones">Remuneraciones</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label small mb-1">Mes</label>
                        <select v-model="monthFilter" @change="fetchData({ page: 1 })" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="option in monthOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <label class="form-label small mb-1">Proveedor</label>
                        <select v-model="supplierFilter" @change="fetchData({ page: 1 })" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="option in supplierOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label small mb-1">Nivel 2</label>
                        <select v-model="level2Filter" @change="fetchData({ page: 1 })" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="option in level2Options" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label small mb-1">Nivel 3</label>
                        <select v-model="level3Filter" @change="fetchData({ page: 1 })" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="option in level3Options" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-12 col-md d-flex flex-wrap justify-content-end gap-2 align-self-end">
                        <button v-if="monthFilter || supplierFilter || level2Filter || level3Filter || tipoGastoFilter !== 'gestion'" type="button" class="btn btn-falcon-default btn-sm" @click="clearFilters">
                            <i class="fas fa-times me-1"></i>Limpiar filtros
                        </button>
                        <ExportExcelButton 
                            :data="excelData" 
                            :headers="[
                                { label: 'ID Salida', key: 'id_salida' },
                                { label: 'Tipo de Gasto', key: 'tipo_gasto' },
                                { label: 'Fecha', key: 'fecha' },
                                { label: 'Mes', key: 'mes' },
                                { label: 'Proveedor', key: 'proveedor' },
                                { label: 'Sucursal Factura', key: 'sucursal_factura' },
                                { label: 'Razón Social Factura', key: 'razon_social_factura' },
                                { label: 'N° Documento', key: 'numero_documento' },
                                { label: 'Tipo Documento', key: 'tipo_documento' },
                                { label: 'Producto', key: 'producto' },
                                { label: 'Unidad', key: 'unidad' },
                                { label: 'Nivel 1', key: 'nivel_1' },
                                { label: 'Nivel 2', key: 'nivel_2' },
                                { label: 'Nivel 3', key: 'nivel_3' },
                                { label: 'Cantidad Total', key: 'cantidad_total', type: 'number' },
                                { label: 'Precio Unitario', key: 'precio_unitario', type: 'number' },
                                { label: 'Proyecto', key: 'proyecto' },
                                { label: 'Operación', key: 'operacion' },
                                { label: 'Maquinaria', key: 'maquinaria' },
                                { label: 'Centro de Costo', key: 'centro_costo' },
                                { label: 'Sucursal CC', key: 'sucursal_cc' },
                                { label: 'Razón Social CC', key: 'razon_social_cc' },
                                { label: 'Superficie CC (ha)', key: 'superficie_cc', type: 'number' },
                                { label: 'Cantidad Asignada', key: 'cantidad_asignada', type: 'number' },
                                { label: 'Estado Desarrollo', key: 'estado_desarrollo' },
                                { label: 'Cantidad/Ha', key: 'cantidad_por_ha', type: 'number' },
                                { label: 'Total', key: 'total', type: 'number' },
                                { label: 'Notas', key: 'notas' },
                            ]" 
                            filename="consolidado_salidas.xlsx"
                            class="btn btn-falcon-default btn-sm export-btn-fix"
                        >
                            <span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Exportar Página</span>
                        </ExportExcelButton>
                        <a
                            :href="`/consolidated-outflows/export?term=${encodeURIComponent(term)}&month=${encodeURIComponent(monthFilter)}&supplier_id=${encodeURIComponent(supplierFilter)}&level2_id=${encodeURIComponent(level2Filter)}&level3_id=${encodeURIComponent(level3Filter)}&tipo_gasto=${encodeURIComponent(tipoGastoFilter)}`"
                            class="btn btn-falcon-default btn-sm"
                            target="_blank"
                        >
                            <span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Exportar Todo ({{ totals.total_count }})</span>
                        </a>
                    </div>
                </div>

                <!-- Info de paginación -->
                <div class="d-flex justify-content-between align-items-center mb-2" v-if="outflows?.total">
                    <small class="text-muted">
                        Mostrando {{ outflows.from ?? 0 }}-{{ outflows.to ?? 0 }} de {{ outflows.total }} registros
                        <span v-if="filters?.term || filters?.month || filters?.supplier_id || filters?.level2_id || filters?.level3_id || (filters?.tipo_gasto && filters.tipo_gasto !== 'gestion')"> (filtrado activo)</span>
                    </small>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Por página:</small>
                        <select class="form-select form-select-sm" style="width: 75px;" :value="perPage" @change="perPage = Number($event.target.value); fetchData({ page: 1 })">
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="200">200</option>
                        </select>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive mb-2" style="max-height:600px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th @click="setSort('outflow_id')" :class="sortClass('outflow_id')">ID ({{ totals?.total_count ?? 0 }})</th>
                                <th @click="setSort('tipo_gasto')" :class="sortClass('tipo_gasto')">Tipo de Gasto</th>
                                <th @click="setSort('date')" :class="sortClass('date')">Fecha</th>
                                <th @click="setSort('month')" :class="sortClass('month')">Mes</th>
                                <th @click="setSort('supplier')" :class="sortClass('supplier')">Proveedor</th>
                                <th @click="setSort('branch_factura')" :class="sortClass('branch_factura')">Sucursal Fact.</th>
                                <th @click="setSort('company_reason_factura')" :class="sortClass('company_reason_factura')">RS Fact.</th>
                                <th @click="setSort('number_document')" :class="sortClass('number_document')">N° Doc</th>
                                <th @click="setSort('product_name')" :class="sortClass('product_name')">Producto</th>
                                <th @click="setSort('level1_name')" :class="sortClass('level1_name')">Nivel 1</th>
                                <th @click="setSort('level2_name')" :class="sortClass('level2_name')">Nivel 2</th>
                                <th @click="setSort('level3_name')" :class="sortClass('level3_name')">Nivel 3</th>
                                <th @click="setSort('quantity_total')" :class="sortClass('quantity_total')" class="text-end">Cant. Total</th>
                                <th @click="setSort('unit_price')" :class="sortClass('unit_price')" class="text-end">Precio Unit.</th>
                                <th @click="setSort('project')" :class="sortClass('project')">Proyecto</th>
                                <th @click="setSort('cost_center_name')" :class="sortClass('cost_center_name')">Centro de Costo</th>
                                <th @click="setSort('branch_cc')" :class="sortClass('branch_cc')">Sucursal CC</th>
                                <th @click="setSort('company_reason_cc')" :class="sortClass('company_reason_cc')">RS CC</th>
                                <th @click="setSort('surface')" :class="sortClass('surface')" class="text-end">Superficie</th>
                                <th @click="setSort('cantidad_asignada')" :class="sortClass('cantidad_asignada')" class="text-end">Cant. Asignada</th>
                                <th @click="setSort('development_state')" :class="sortClass('development_state')">Estado Desarrollo</th>
                                <th @click="setSort('total')" :class="sortClass('total')" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!outflows?.data?.length">
                                <td colspan="22" class="text-center py-4">
                                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin me-2"></i>Cargando...</span>
                                    <span v-else>No hay registros para mostrar</span>
                                </td>
                            </tr>
                            <tr v-for="(item, idx) in outflows.data" :key="idx">
                                <td>{{ item.outflow_id }}</td>
                                <td>{{ item.tipo_gasto }}</td>
                                <td style="white-space:nowrap;">{{ item.date }}</td>
                                <td style="white-space:nowrap;">{{ item.month || '-' }}</td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.supplier">{{ item.supplier }}</td>
                                <td style="max-width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.branch_factura">{{ item.branch_factura || '-' }}</td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.company_reason_factura">{{ item.company_reason_factura || '-' }}</td>
                                <td>{{ item.number_document }}</td>
                                <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis;">{{ item.product_name }}</td>
                                <td style="max-width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.level1_name">{{ item.level1_name || '-' }}</td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.level2_name">{{ item.level2_name || '-' }}</td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.level3_name">{{ item.level3_name || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.quantity_total, 2) }}</td>
                                <td class="text-end">{{ formatNumber(item.unit_price, 2) }}</td>
                                <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis;">{{ item.project || '-' }}</td>
                                <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ item.cost_center_name }}</td>
                                <td style="max-width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.branch_cc">{{ item.branch_cc || '-' }}</td>
                                <td style="max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" :title="item.company_reason_cc">{{ item.company_reason_cc || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.surface, 2) }}</td>
                                <td class="text-end">{{ formatNumber(item.cantidad_asignada, 2) }}</td>
                                <td>{{ item.development_state || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.total, 0) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="outflows?.data?.length">
                            <tr class="table-primary fw-bold">
                                <td colspan="21" class="text-end">Total General</td>
                                <td class="text-end">{{ formatNumber(totals?.total_general ?? 0, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="outflows?.links && outflows.links.length > 3" class="d-flex justify-content-center mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li
                                v-for="(link, index) in outflows.links"
                                :key="index"
                                class="page-item"
                                :class="{ 'active': link.active, 'disabled': !link.url }"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="page-link"
                                    v-html="link.label"
                                    preserve-state
                                />
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.table, .table th, .table td {
    font-size: 0.68rem !important;
}

:deep(.export-btn-fix) {
    padding: 0.25rem 0.5rem !important;
    margin-bottom: 0 !important;
}

.sortable {
    position: relative;
    cursor: pointer;
    user-select: none;
}

.sortable:after {
    content: '\25B2';
    position: absolute;
    right: 8px;
    font-size: 0.6rem;
    opacity: 0.3;
}

.sorted-asc:after {
    content: '\25B2';
    opacity: 1;
}

.sorted-desc:after {
    content: '\25BC';
    opacity: 1;
}

.small-card {
    min-height: 60px;
}

.small-card-title {
    font-size: 0.75rem;
    font-weight: 600;
}

.small-card-number {
    font-size: 1.25rem;
    font-weight: 700;
}
</style>
