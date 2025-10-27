<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardHeader from '@/Components/CardHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    outflows: Object,
    term: String,
});

const title = 'Consolidado de Salidas';
const term = ref('');

const links = [
    { title: 'Gestión', url: null },
    { title: 'Consolidado de Salidas', active: true }
];

// Estado para ordenamiento
const sortBy = ref('outflow_id');
const sortDesc = ref(true);

function setSort(field) {
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = field;
        sortDesc.value = false;
    }
}

// Filtrado en frontend
const filteredOutflows = computed(() => {
    if (!props.outflows || !props.outflows.data) return [];
    if (!term.value) return props.outflows.data;
    
    const search = term.value.toLowerCase();
    return props.outflows.data.filter(item => {
        return (
            (item.product_name && item.product_name.toLowerCase().includes(search)) ||
            (item.supplier && item.supplier.toLowerCase().includes(search)) ||
            (item.number_document && String(item.number_document).toLowerCase().includes(search)) ||
            (item.project && item.project.toLowerCase().includes(search)) ||
            (item.cost_center_name && item.cost_center_name.toLowerCase().includes(search)) ||
            (item.level1_name && item.level1_name.toLowerCase().includes(search)) ||
            (item.level2_name && item.level2_name.toLowerCase().includes(search)) ||
            (item.level3_name && item.level3_name.toLowerCase().includes(search)) ||
            (item.operation && item.operation.toLowerCase().includes(search)) ||
            (item.machinery && item.machinery.toLowerCase().includes(search))
        );
    });
});

const sortedOutflows = computed(() => {
    const arr = [...filteredOutflows.value];
    arr.sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];
        
        // Manejar campos numéricos
        if (['outflow_id', 'quantity_total', 'surface', 'cantidad_asignada', 'unit_price', 'total', 'cantidad_por_ha'].includes(sortBy.value)) {
            aVal = Number(aVal) || 0;
            bVal = Number(bVal) || 0;
        }
        
        // Ordenar strings
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = (bVal || '').toLowerCase();
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

// Totales basados en datos filtrados
const totalGeneral = computed(() => {
    return filteredOutflows.value.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
});

const totalCantidad = computed(() => {
    return filteredOutflows.value.reduce((sum, item) => sum + (Number(item.cantidad_asignada) || 0), 0);
});

const totalSuperficie = computed(() => {
    // Sumar superficies únicas (evitar duplicados por outflow_id)
    const superficies = {};
    filteredOutflows.value.forEach(item => {
        if (item.cost_center_id) {
            superficies[item.cost_center_id] = item.surface;
        }
    });
    return Object.values(superficies).reduce((sum, s) => sum + s, 0);
});

// Formateo de números
function formatNumber(value, decimals = 2) {
    return new Intl.NumberFormat('es-ES', { 
        style: 'decimal', 
        minimumFractionDigits: decimals, 
        maximumFractionDigits: decimals 
    }).format(value ?? 0);
}

// Datos para exportar a Excel
const excelData = computed(() => {
    if (!props.outflows || !props.outflows.data) return [];
    return props.outflows.data.map(item => ({
        id_salida: item.outflow_id,
        fecha: item.date,
        proveedor: item.supplier,
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
                <div class="row mb-3">
                    <div class="col-md-4 col-12">
                        <SearchInput 
                            v-model="term" 
                            placeholder="Buscar por producto, proveedor, proyecto, centro de costo..." 
                        />
                    </div>
                    <div class="col-md-8 col-12 text-end d-flex flex-wrap justify-content-end gap-2">
                        <ExportExcelButton 
                            :data="excelData" 
                            :headers="[
                                { label: 'ID Salida', key: 'id_salida' },
                                { label: 'Fecha', key: 'fecha' },
                                { label: 'Proveedor', key: 'proveedor' },
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
                                { label: 'Superficie CC (ha)', key: 'superficie_cc', type: 'number' },
                                { label: 'Cantidad Asignada', key: 'cantidad_asignada', type: 'number' },
                                { label: 'Estado Desarrollo', key: 'estado_desarrollo' },
                                { label: 'Cantidad/Ha', key: 'cantidad_por_ha', type: 'number' },
                                { label: 'Total', key: 'total', type: 'number' },
                                { label: 'Notas', key: 'notas' },
                            ]" 
                            filename="consolidado_salidas.xlsx"
                            class="btn btn-light-primary"
                        >
                            <span class="svg-icon svg-icon-2"></span>
                            Exportar Excel
                        </ExportExcelButton>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-3">
                    <div class="col-md-6 col-12 mb-2">
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Salidas</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    {{ filteredOutflows.length }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-12 mb-2">
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total General</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    {{ formatNumber(totalGeneral, 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive mb-4" style="max-height:600px;overflow-y:auto;">
                    <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                        <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th @click="setSort('outflow_id')" :class="sortClass('outflow_id')">ID</th>
                                <th @click="setSort('date')" :class="sortClass('date')">Fecha</th>
                                <th @click="setSort('supplier')" :class="sortClass('supplier')">Proveedor</th>
                                <th @click="setSort('number_document')" :class="sortClass('number_document')">N° Doc</th>
                                <th @click="setSort('product_name')" :class="sortClass('product_name')">Producto</th>
                                <th @click="setSort('level1_name')" :class="sortClass('level1_name')">Nivel 1</th>
                                <th @click="setSort('level2_name')" :class="sortClass('level2_name')">Nivel 2</th>
                                <th @click="setSort('level3_name')" :class="sortClass('level3_name')">Nivel 3</th>
                                <th @click="setSort('quantity_total')" :class="sortClass('quantity_total')" class="text-end">Cant. Total</th>
                                <th @click="setSort('unit_price')" :class="sortClass('unit_price')" class="text-end">Precio Unit.</th>
                                <th @click="setSort('project')" :class="sortClass('project')">Proyecto</th>
                                <th @click="setSort('cost_center_name')" :class="sortClass('cost_center_name')">Centro de Costo</th>
                                <th @click="setSort('surface')" :class="sortClass('surface')" class="text-end">Superficie</th>
                                <th @click="setSort('cantidad_asignada')" :class="sortClass('cantidad_asignada')" class="text-end">Cant. Asignada</th>
                                <th @click="setSort('development_state')" :class="sortClass('development_state')">Estado Desarrollo</th>
                                <th @click="setSort('total')" :class="sortClass('total')" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sortedOutflows.length === 0">
                                <td colspan="16" class="text-center py-4">No hay registros para mostrar</td>
                            </tr>
                            <tr v-for="(item, idx) in sortedOutflows" :key="idx">
                                <td>{{ item.outflow_id }}</td>
                                <td style="white-space:nowrap;">{{ item.date }}</td>
                                <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ item.supplier }}</td>
                                <td>{{ item.number_document }}</td>
                                <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis;">{{ item.product_name }}</td>
                                <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis;">{{ item.level1_name || '-' }}</td>
                                <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ item.level2_name || '-' }}</td>
                                <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ item.level3_name || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.quantity_total, 2) }}</td>
                                <td class="text-end">{{ formatNumber(item.unit_price, 2) }}</td>
                                <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis;">{{ item.project || '-' }}</td>
                                <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis;">{{ item.cost_center_name }}</td>
                                <td class="text-end">{{ formatNumber(item.surface, 2) }}</td>
                                <td class="text-end">{{ formatNumber(item.cantidad_asignada, 2) }}</td>
                                <td>{{ item.development_state || '-' }}</td>
                                <td class="text-end">{{ formatNumber(item.total, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.table, .table th, .table td {
    font-size: 0.68rem !important;
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
