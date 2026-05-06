<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import FalconBarChart from '@/Components/FalconBarChart.vue';

const props = defineProps({
    show: Boolean,
    fuelStockByProduct: Array,
    totalStock: Number,
    fuelOutflows: Object,
});

const emit = defineEmits(['close']);

// Estado para datos de analytics
const analyticsData = ref(null);
const loadingAnalytics = ref(false);
const chartKey = ref(0); // Key para forzar re-render del gráfico

// Cargar datos de analytics cuando se abre el modal
watch(() => props.show, async (isOpen) => {
    if (isOpen && !analyticsData.value) {
        await loadAnalytics();
        // Forzar re-render del gráfico después de cargar datos
        await nextTick();
        chartKey.value++;
    }
});

async function loadAnalytics() {
    loadingAnalytics.value = true;
    try {
        const response = await fetch(route('fuel-outflows.analytics'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error('Error al cargar analytics');
        const data = await response.json();
        analyticsData.value = data;
    } catch (error) {
        console.error('Error loading analytics:', error);
        analyticsData.value = { consumo_por_maquinaria: [] };
    } finally {
        loadingAnalytics.value = false;
    }
}

// Preparar datos para el gráfico de barras
const chartLabels = computed(() => {
    if (!analyticsData.value?.consumo_por_maquinaria) return [];
    const labels = analyticsData.value.consumo_por_maquinaria.map(m => m.machinery_name);
    console.log('Chart Labels:', labels);
    return labels;
});

const chartData = computed(() => {
    if (!analyticsData.value?.consumo_por_maquinaria) return [];
    const data = analyticsData.value.consumo_por_maquinaria.map(m => parseFloat(m.total_litros));
    console.log('Chart Data:', data);
    return data;
});

function closeModal() {
    emit('close');
}
</script>

<template>
    <div 
        v-if="show" 
        class="modal fade show" 
        style="display: block; background-color: rgba(0,0,0,0.5);" 
        tabindex="-1"
        @click.self="closeModal"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Análisis Detallado de Consumo de Combustible
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Tabs para organizar el contenido -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button 
                                class="nav-link active" 
                                data-bs-toggle="tab" 
                                data-bs-target="#stock-tab"
                                type="button"
                            >
                                <i class="fas fa-boxes me-1"></i> Stock Disponible
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button 
                                class="nav-link" 
                                data-bs-toggle="tab" 
                                data-bs-target="#graficos-tab"
                                type="button"
                            >
                                <i class="fas fa-chart-bar me-1"></i> Gráficos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button 
                                class="nav-link" 
                                data-bs-toggle="tab" 
                                data-bs-target="#promedios-tab"
                                type="button"
                            >
                                <i class="fas fa-calculator me-1"></i> Promedios
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab 1: Stock Disponible -->
                        <div class="tab-pane fade show active" id="stock-tab">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card bg-light-info border-info">
                                        <div class="card-body text-center py-4">
                                            <h6 class="text-muted mb-2">Stock Total Disponible</h6>
                                            <h2 class="mb-0 fw-bold text-info">
                                                {{ totalStock.toFixed(2) }} Litros
                                            </h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="mb-3">Detalle por Producto</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Unidad</th>
                                                    <th class="text-end">Stock Disponible</th>
                                                    <th class="text-end">% del Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="fuel in fuelStockByProduct" :key="fuel.product_id">
                                                    <td>{{ fuel.product_name }}</td>
                                                    <td>{{ fuel.unit }}</td>
                                                    <td class="text-end fw-bold">
                                                        {{ fuel.stock_disponible.toFixed(2) }}
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-secondary">
                                                            {{ ((fuel.stock_disponible / totalStock) * 100).toFixed(1) }}%
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="fuelStockByProduct.length === 0">
                                                    <td colspan="4" class="text-center text-muted">
                                                        No hay stock disponible
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock por Estanque -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-3"><i class="fas fa-drum me-2 text-warning"></i>Stock por Estanque</h6>
                                    <div v-if="loadingAnalytics" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2 text-muted">Cargando...</span>
                                    </div>
                                    <div v-else-if="analyticsData?.stock_por_estanque?.length" class="table-responsive">
                                        <table class="table table-bordered table-hover table-sm">
                                            <thead class="table-warning">
                                                <tr>
                                                    <th>Estanque</th>
                                                    <th>Sucursal</th>
                                                    <th>Combustible</th>
                                                    <th class="text-end">Ingresado (L)</th>
                                                    <th class="text-end">Consumido (L)</th>
                                                    <th class="text-end">Stock (L)</th>
                                                    <th class="text-end">Capacidad (L)</th>
                                                    <th class="text-center">% Lleno</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="t in analyticsData.stock_por_estanque" :key="t.tank_id"
                                                    :class="{ 'table-danger': t.stock < 0, 'table-success': t.stock > 0 && t.porcentaje >= 50, 'table-warning': t.stock >= 0 && t.porcentaje !== null && t.porcentaje < 50 }">
                                                    <td><strong>{{ t.tank_name }}</strong></td>
                                                    <td>{{ t.branch_name ?? '—' }}</td>
                                                    <td>{{ t.product_name ?? '—' }}</td>
                                                    <td class="text-end">{{ t.ingresado.toFixed(2) }}</td>
                                                    <td class="text-end">{{ t.consumido.toFixed(2) }}</td>
                                                    <td class="text-end fw-bold">{{ t.stock.toFixed(2) }}</td>
                                                    <td class="text-end">{{ t.capacity ? t.capacity.toFixed(2) : '—' }}</td>
                                                    <td class="text-center">
                                                        <template v-if="t.porcentaje !== null">
                                                            <div class="progress" style="height:14px; min-width:60px;">
                                                                <div class="progress-bar"
                                                                    :class="t.porcentaje >= 50 ? 'bg-success' : t.porcentaje >= 20 ? 'bg-warning' : 'bg-danger'"
                                                                    :style="{ width: Math.min(t.porcentaje, 100) + '%' }"
                                                                    style="font-size:0.7rem; line-height:14px;">
                                                                    {{ t.porcentaje }}%
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <span v-else class="text-muted">—</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="text-muted small">
                                        No hay estanques registrados.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Gráficos -->
                        <div class="tab-pane fade" id="graficos-tab">
                            <div v-if="loadingAnalytics" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando datos de análisis...</p>
                            </div>

                            <div v-else-if="analyticsData && chartData.length > 0">
                                <h5 class="mb-3">Consumo Total por Maquinaria</h5>
                                
                                <!-- Gráfico de Barras -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div style="height: 400px;">
                                            <FalconBarChart
                                                :key="chartKey"
                                                :barLabels="chartLabels"
                                                :barData="chartData"
                                                :height="400"
                                                containerStyle="height: 400px; width: 100%;"
                                                color="#2c7be5"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla de Datos -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Detalle de Consumo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover table-sm">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th>Maquinaria</th>
                                                        <th class="text-end">Total Litros</th>
                                                        <th class="text-end">N° Registros</th>
                                                        <th class="text-end">Promedio L/Consumo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="m in analyticsData.consumo_por_maquinaria" :key="m.machinery_id">
                                                        <td><strong>{{ m.machinery_name }}</strong></td>
                                                        <td class="text-end">{{ parseFloat(m.total_litros).toFixed(2) }} L</td>
                                                        <td class="text-end">{{ m.cantidad_registros }}</td>
                                                        <td class="text-end">{{ parseFloat(m.promedio_litros).toFixed(2) }} L</td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th>TOTAL</th>
                                                        <th class="text-end">
                                                            {{ analyticsData.consumo_por_maquinaria.reduce((sum, m) => sum + parseFloat(m.total_litros), 0).toFixed(2) }} L
                                                        </th>
                                                        <th class="text-end">
                                                            {{ analyticsData.consumo_por_maquinaria.reduce((sum, m) => sum + parseInt(m.cantidad_registros), 0) }}
                                                        </th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay datos de consumo disponibles para mostrar.
                            </div>
                        </div>

                        <!-- Tab 3: Promedios (placeholder) -->
                        <div class="tab-pane fade" id="promedios-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>En desarrollo:</strong> Aquí se mostrarán promedios de consumo por maquinaria, operario, etc.
                            </div>
                            <!-- Aquí irán las tablas de promedios -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    overflow-y: auto;
}
</style>
