<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import AgrochemicalNavBar from '@/Components/AgrochemicalOutflows/AgrochemicalNavBar.vue';

const props = defineProps({
    libroCampo: { type: Array, default: () => [] },
    costCenterOptions: { type: Array, default: () => [] },
    orderOptions: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
});

// Filtros
const filterCostCenter = ref(null);
const filterOrder = ref(null);
const filterProduct = ref(null);

const filteredLibroCampo = computed(() => {
    let data = props.libroCampo;

    // Filtro por cuartel
    if (filterCostCenter.value) {
        data = data.filter(cc => String(cc.cost_center_id) === String(filterCostCenter.value));
    }

    // Filtro por orden y/o producto (filtra las rows internas)
    if (filterOrder.value || filterProduct.value) {
        data = data.map(cc => {
            let rows = cc.rows;
            if (filterOrder.value) {
                rows = rows.filter(r => String(r.orden_id) === String(filterOrder.value));
            }
            if (filterProduct.value) {
                const prodLabel = props.productOptions.find(p => String(p.value) === String(filterProduct.value))?.label;
                if (prodLabel) {
                    rows = rows.filter(r => r.producto === prodLabel);
                }
            }
            return { ...cc, rows };
        }).filter(cc => cc.rows.length > 0);
    }

    return data;
});

const clearFilters = () => {
    filterCostCenter.value = null;
    filterOrder.value = null;
    filterProduct.value = null;
};

const hasFilters = computed(() => filterCostCenter.value || filterOrder.value || filterProduct.value);

const pdfUrl = computed(() => {
    const params = {};
    if (filterCostCenter.value) params.cost_center_id = filterCostCenter.value;
    if (filterOrder.value) params.order_id = filterOrder.value;
    if (filterProduct.value) params.product_id = filterProduct.value;
    return route('libro-campo.export-pdf', params);
});

const formatDate = (date) => {
    if (!date) return '-';
    const dateStr = String(date).substring(0, 10);
    const d = new Date(dateStr + 'T12:00:00');
    return d.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatNumber = (val, decimals = 2) => {
    if (val === null || val === undefined || val === '') return '-';
    return Number(val).toLocaleString('es-CL', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
};

// Datos para exportar a Excel (aplanados)
const excelHeaders = [
    { label: 'Cuartel', key: 'cuartel' },
    { label: 'Variedad', key: 'variedad' },
    { label: 'Superficie (Ha)', key: 'superficie', type: 'number' },
    { label: 'Fecha Aplicación', key: 'fecha_aplic' },
    { label: 'Límite Protección', key: 'limite_proteccion' },
    { label: 'Orden Nº', key: 'orden_id', type: 'number' },
    { label: 'Producto', key: 'producto' },
    { label: 'Ingrediente Activo', key: 'ingrediente_activo' },
    { label: 'Días Carencia', key: 'carencia', type: 'number' },
    { label: 'Días Reingreso', key: 'reingreso', type: 'number' },
    { label: 'Cosecha a partir de', key: 'cosecha_desde' },
    { label: 'Tractor', key: 'tractor' },
    { label: 'Equipo', key: 'equipo' },
    { label: 'Operario', key: 'operario' },
    { label: 'Dosis/100L', key: 'dosis_100', type: 'number' },
    { label: 'Dosis/Ha', key: 'dosis_ha', type: 'number' },
    { label: 'Unidad', key: 'unidad' },
    { label: 'Moj. Real', key: 'mojamiento', type: 'number' },
    { label: 'Maquinadas', key: 'maquinadas', type: 'number' },
    { label: 'Cantidad', key: 'cantidad', type: 'number' },
    { label: 'Etapa Fenológica', key: 'etapa_fenologica' },
];

const excelData = computed(() => {
    const rows = [];
    filteredLibroCampo.value.forEach(cc => {
        cc.rows.forEach(r => {
            rows.push({
                cuartel: cc.cuartel,
                variedad: cc.variedad,
                superficie: cc.superficie,
                fecha_aplic: r.fecha_aplic,
                limite_proteccion: r.limite_proteccion || '',
                orden_id: r.orden_id,
                producto: r.producto,
                ingrediente_activo: r.ingrediente_activo || '',
                carencia: r.carencia,
                reingreso: r.reingreso,
                cosecha_desde: r.cosecha_desde || '',
                tractor: r.tractor || '',
                equipo: r.equipo || '',
                operario: r.operario || '',
                dosis_100: r.dosis_100,
                dosis_ha: r.dosis_ha,
                unidad: r.unidad,
                mojamiento: r.mojamiento,
                maquinadas: r.maquinadas,
                cantidad: r.cantidad,
                etapa_fenologica: r.etapa_fenologica || '',
            });
        });
    });
    return rows;
});

const totalCuarteles = computed(() => filteredLibroCampo.value.length);
const totalAplicaciones = computed(() => filteredLibroCampo.value.reduce((sum, cc) => sum + cc.rows.length, 0));
</script>

<template>
    <AppLayout title="Libro de Campo">
        <AgrochemicalNavBar />

        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-book me-2"></i>Libro de Campo
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                filename="libro-de-campo.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <a :href="pdfUrl"
                               target="_blank"
                               class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-file-pdf" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">PDF</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row mb-3 g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cuartel</label>
                        <select v-model="filterCostCenter" class="form-select form-select-sm">
                            <option :value="null">Todos los cuarteles</option>
                            <option v-for="opt in costCenterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Orden de Aplicación</label>
                        <select v-model="filterOrder" class="form-select form-select-sm">
                            <option :value="null">Todas las órdenes</option>
                            <option v-for="opt in orderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Producto</label>
                        <select v-model="filterProduct" class="form-select form-select-sm">
                            <option :value="null">Todos los productos</option>
                            <option v-for="opt in productOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button
                            v-if="hasFilters"
                            @click="clearFilters"
                            class="btn btn-falcon-default btn-sm"
                        >
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                        <span class="badge bg-soft-primary text-primary">
                            <i class="fas fa-th-large me-1"></i>{{ totalCuarteles }} cuarteles
                        </span>
                        <span class="badge bg-soft-success text-success">
                            <i class="fas fa-spray-can me-1"></i>{{ totalAplicaciones }} aplicaciones
                        </span>
                    </div>
                </div>

                <!-- Sin datos -->
                <div v-if="filteredLibroCampo.length === 0" class="text-center py-5 text-muted">
                    <i class="fas fa-book-open fa-3x mb-3"></i>
                    <p>No hay registros de aplicaciones de agroquímicos para esta temporada.</p>
                </div>

                <!-- Tablas por cuartel -->
                <div v-for="cc in filteredLibroCampo" :key="cc.cost_center_id" class="mb-4">
                    <!-- Header del cuartel -->
                    <div class="d-flex align-items-center justify-content-between bg-light px-3 py-2 rounded-top border">
                        <div>
                            <strong class="text-primary">
                                <i class="fas fa-map-marker-alt me-1"></i>{{ cc.cuartel }}
                            </strong>
                            <span class="ms-3 text-muted small">
                                <i class="fas fa-seedling me-1"></i>{{ cc.fruta }} - {{ cc.variedad }}
                            </span>
                        </div>
                        <div>
                            <span class="badge bg-soft-info text-info">
                                {{ formatNumber(cc.superficie) }} Ha
                            </span>
                            <span class="badge bg-soft-secondary text-secondary ms-1">
                                {{ cc.rows.length }} aplicaciones
                            </span>
                        </div>
                    </div>

                    <!-- Tabla de aplicaciones -->
                    <div class="table-responsive border border-top-0 rounded-bottom">
                        <table class="table table-sm table-hover mb-0" style="font-size: 0.75rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha Aplic.</th>
                                    <th>Límite Protec.</th>
                                    <th>Orden</th>
                                    <th>Producto</th>
                                    <th>Ingrediente Activo</th>
                                    <th class="text-center">Carencia</th>
                                    <th class="text-center">Reingreso</th>
                                    <th>Cosecha desde</th>
                                    <th>Tractor</th>
                                    <th>Equipo</th>
                                    <th>Operario</th>
                                    <th class="text-end">Dosis/100L</th>
                                    <th class="text-end">Dosis/Ha</th>
                                    <th>Unidad</th>
                                    <th class="text-end">Moj. Real</th>
                                    <th class="text-end">Maquinadas</th>
                                    <th class="text-end">Cantidad</th>
                                    <th>Etapa Fen.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in cc.rows" :key="idx">
                                    <td>{{ formatDate(row.fecha_aplic) }}</td>
                                    <td>{{ formatDate(row.limite_proteccion) }}</td>
                                    <td>
                                        <a v-if="row.orden_id"
                                           :href="route('application-orders.show', row.orden_id)"
                                           class="text-primary"
                                           target="_blank">
                                            #{{ row.orden_id }}
                                        </a>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="fw-semibold">{{ row.producto }}</td>
                                    <td class="text-muted fst-italic">{{ row.ingrediente_activo }}</td>
                                    <td class="text-center">{{ row.carencia ?? '-' }}</td>
                                    <td class="text-center">{{ row.reingreso ?? '-' }}</td>
                                    <td>{{ formatDate(row.cosecha_desde) }}</td>
                                    <td>{{ row.tractor }}</td>
                                    <td>{{ row.equipo }}</td>
                                    <td>{{ row.operario }}</td>
                                    <td class="text-end">{{ row.dosis_100 ? formatNumber(row.dosis_100) : '-' }}</td>
                                    <td class="text-end">{{ row.dosis_ha ? formatNumber(row.dosis_ha) : '-' }}</td>
                                    <td>{{ row.unidad }}</td>
                                    <td class="text-end">{{ row.mojamiento ? formatNumber(row.mojamiento, 0) : '-' }}</td>
                                    <td class="text-end">{{ row.maquinadas ? formatNumber(row.maquinadas) : '-' }}</td>
                                    <td class="text-end">{{ row.cantidad ? formatNumber(row.cantidad) : '-' }}</td>
                                    <td>
                                        <span class="badge bg-soft-warning text-warning" style="font-size: 0.65rem;">
                                            {{ row.etapa_fenologica }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
