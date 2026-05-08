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
const showTankTable = ref(false);
const chartKey = ref(0); // Key para forzar re-render del gráfico

// Cargar datos de analytics cuando se abre el modal
watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        analyticsData.value = null;
        await loadAnalytics();
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
    return data;
});

// Paleta de colores para las barras
const BAR_COLORS = [
    '#2c7be5', '#00d97e', '#e63757', '#f6c343', '#39afd1',
    '#fd7e14', '#6b5eae', '#02a8b5', '#748194', '#c94c4c'
];

const chartColors = computed(() => {
    if (!analyticsData.value?.consumo_por_maquinaria) return [];
    return analyticsData.value.consumo_por_maquinaria.map((_, i) => BAR_COLORS[i % BAR_COLORS.length]);
});

// Total de litros para calcular % por maquinaria
const totalLitros = computed(() => {
    if (!analyticsData.value?.consumo_por_maquinaria) return 0;
    return analyticsData.value.consumo_por_maquinaria.reduce((sum, m) => sum + parseFloat(m.total_litros), 0);
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
                    <h5 class="modal-title text-white">
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

                            <!-- Stock Total + Cards por Producto en la misma fila -->
                            <div class="row g-3 mb-4 align-items-stretch">
                                <!-- Card Total Disponible -->
                                <div class="col-12 col-sm">
                                    <div class="card bg-light-info border-info h-100">
                                        <div class="card-body d-flex flex-column justify-content-center text-center py-3">
                                            <div class="text-muted mb-1" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Stock Total Disponible</div>
                                            <h3 class="mb-0 fw-bold text-info">{{ totalStock.toLocaleString('es-CL', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</h3>
                                            <div class="text-muted" style="font-size:0.8rem;">Litros</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cards por producto -->
                                <template v-if="fuelStockByProduct.length">
                                    <div
                                        v-for="fuel in fuelStockByProduct"
                                        :key="fuel.product_id"
                                        class="col-12 col-sm"
                                    >
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body py-3 px-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <div class="fw-bold" style="font-size:0.9rem;">{{ fuel.product_name }}</div>
                                                        <div class="text-muted" style="font-size:0.72rem;">{{ fuel.unit }}</div>
                                                    </div>
                                                    <span
                                                        class="badge rounded-pill"
                                                        :class="((fuel.stock_disponible / totalStock) * 100) >= 50 ? 'bg-success' : ((fuel.stock_disponible / totalStock) * 100) >= 20 ? 'bg-warning text-dark' : 'bg-danger'"
                                                        style="font-size:0.72rem;"
                                                    >
                                                        {{ ((fuel.stock_disponible / totalStock) * 100).toFixed(1) }}%
                                                    </span>
                                                </div>
                                                <div class="progress mb-2" style="height:8px;">
                                                    <div
                                                        class="progress-bar"
                                                        :class="((fuel.stock_disponible / totalStock) * 100) >= 50 ? 'bg-success' : ((fuel.stock_disponible / totalStock) * 100) >= 20 ? 'bg-warning' : 'bg-danger'"
                                                        :style="{ width: Math.min((fuel.stock_disponible / totalStock) * 100, 100) + '%' }"
                                                    ></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted" style="font-size:0.75rem;">Stock disponible</span>
                                                    <strong style="font-size:0.95rem;">{{ fuel.stock_disponible.toLocaleString('es-CL', {minimumFractionDigits:2, maximumFractionDigits:2}) }} L</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="col">
                                    <p class="text-muted small">No hay stock disponible por producto.</p>
                                </div>
                            </div>

                            <!-- Stock por Estanque — Cards visuales -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="mb-3"><i class="fas fa-drum me-2 text-warning"></i>Stock por Estanque</h6>
                                    <div v-if="loadingAnalytics" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="ms-2 text-muted">Cargando...</span>
                                    </div>

                                    <!-- Cards visuales tipo depósito -->
                                    <div v-else-if="analyticsData?.stock_por_estanque?.length">
                                        <div class="row g-3 mb-4">
                                            <div
                                                v-for="t in analyticsData.stock_por_estanque"
                                                :key="t.tank_id"
                                                class="col-12 col-sm"
                                            >
                                                <div class="card h-100 shadow-sm border-0">
                                                    <div class="card-body d-flex flex-column align-items-center p-3">
                                                        <div class="fw-bold text-center mb-1" style="font-size:0.85rem;">{{ t.tank_name }}</div>
                                                        <div class="text-muted text-center mb-2" style="font-size:0.72rem;">
                                                            {{ t.product_name ?? '—' }}<span v-if="t.branch_name"> · {{ t.branch_name }}</span>
                                                        </div>
                                                        <div class="tank-visual mb-2">
                                                            <div class="tank-cap"></div>
                                                            <div class="tank-body">
                                                                <div
                                                                    class="tank-fill"
                                                                    :class="{
                                                                        'tank-fill-green':  t.porcentaje !== null && t.porcentaje >= 50,
                                                                        'tank-fill-yellow': t.porcentaje !== null && t.porcentaje >= 20 && t.porcentaje < 50,
                                                                        'tank-fill-red':    t.porcentaje !== null && t.porcentaje < 20,
                                                                        'tank-fill-nodata': t.porcentaje === null,
                                                                    }"
                                                                    :style="{ height: t.porcentaje !== null ? Math.min(t.porcentaje, 100) + '%' : '100%' }"
                                                                >
                                                                    <span class="tank-pct-label">
                                                                        {{ t.porcentaje !== null ? t.porcentaje + '%' : '?' }}
                                                                    </span>
                                                                </div>
                                                                <div class="tank-scale">
                                                                    <div class="tank-scale-line" style="bottom:75%"></div>
                                                                    <div class="tank-scale-line" style="bottom:50%"></div>
                                                                    <div class="tank-scale-line" style="bottom:25%"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="w-100 mt-2" style="font-size:0.72rem;">
                                                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                                <span class="text-muted">Stock</span>
                                                                <strong :class="t.stock < 0 ? 'text-danger' : 'text-success'">{{ t.stock.toFixed(0) }} L</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                                <span class="text-muted">Consumido</span>
                                                                <span>{{ t.consumido.toFixed(0) }} L</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                                <span class="text-muted">Ingresado</span>
                                                                <span>{{ t.ingresado.toFixed(0) }} L</span>
                                                            </div>
                                                            <div v-if="t.capacity" class="d-flex justify-content-between">
                                                                <span class="text-muted">Capacidad</span>
                                                                <span>{{ t.capacity.toFixed(0) }} L</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tabla detalle colapsable -->
                                        <div class="card border mt-1">
                                            <div
                                                class="card-header py-2 px-3 d-flex justify-content-between align-items-center"
                                                style="cursor:pointer; background:#f9f9f9;"
                                                @click="showTankTable = !showTankTable"
                                            >
                                                <span class="small fw-semibold text-muted"><i class="fas fa-table me-1"></i>Ver tabla detallada</span>
                                                <i class="fas" :class="showTankTable ? 'fa-chevron-up' : 'fa-chevron-down'" style="font-size:0.75rem;"></i>
                                            </div>
                                            <div v-if="showTankTable" class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover table-sm mb-0">
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
                                            </div>
                                        </div>
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
                                <h6 class="mb-3">Consumo Total por Maquinaria</h6>

                                <!-- Gráfico de Barras -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div style="height: 380px;">
                                            <FalconBarChart
                                                :key="chartKey"
                                                :barLabels="chartLabels"
                                                :barData="chartData"
                                                :color="chartColors"
                                                :height="380"
                                                containerStyle="height: 380px; width: 100%;"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Ranking de Consumo -->
                                <div class="row g-3">
                                    <div
                                        v-for="(m, i) in analyticsData.consumo_por_maquinaria"
                                        :key="m.machinery_id"
                                        class="col-12 col-sm-6 col-md-4"
                                    >
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body py-3 px-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div
                                                            class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                            :style="{ background: BAR_COLORS[i % BAR_COLORS.length], color: '#fff', width: '28px', height: '28px', fontSize: '0.75rem', flexShrink: 0 }"
                                                        >#{{ i + 1 }}</div>
                                                        <div class="fw-semibold" style="font-size:0.85rem;">{{ m.machinery_name }}</div>
                                                    </div>
                                                    <span class="badge bg-secondary rounded-pill" style="font-size:0.7rem;">{{ m.cantidad_registros }} reg.</span>
                                                </div>
                                                <!-- Barra de progreso vs total -->
                                                <div class="progress mb-2" style="height:6px;">
                                                    <div
                                                        class="progress-bar"
                                                        :style="{ width: (parseFloat(m.total_litros) / totalLitros * 100).toFixed(1) + '%', background: BAR_COLORS[i % BAR_COLORS.length] }"
                                                    ></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted" style="font-size:0.72rem;">{{ (parseFloat(m.total_litros) / totalLitros * 100).toFixed(1) }}% del consumo</span>
                                                    <strong style="font-size:0.9rem;">{{ parseFloat(m.total_litros).toLocaleString('es-CL', {minimumFractionDigits:1, maximumFractionDigits:1}) }} L</strong>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1 border-top pt-1">
                                                    <span class="text-muted" style="font-size:0.7rem;">Prom. por consumo</span>
                                                    <span style="font-size:0.75rem;">{{ parseFloat(m.promedio_litros).toFixed(1) }} L</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay datos de consumo disponibles para mostrar.
                            </div>
                        </div>

                        <!-- Tab 3: Averages -->
                        <div class="tab-pane fade" id="promedios-tab">
                            <div v-if="loadingAnalytics" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Cargando datos...</p>
                            </div>

                            <div v-else-if="analyticsData?.consumption_averages?.length">
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Calculado en base al rango entre la primera y última lectura del contador por maquinaria en esta temporada.
                                </p>

                                <div class="row g-3">
                                    <div
                                        v-for="(m, i) in analyticsData.consumption_averages"
                                        :key="m.machinery_id"
                                        class="col-12 col-sm-6 col-md-4"
                                    >
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body py-3 px-3">
                                                <!-- Header -->
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <div
                                                        class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                                        :style="{ background: BAR_COLORS[i % BAR_COLORS.length], color: '#fff', width: '28px', height: '28px', fontSize: '0.75rem' }"
                                                    >#{{ i + 1 }}</div>
                                                    <div>
                                                        <div class="fw-semibold" style="font-size:0.88rem;">{{ m.machinery_name }}</div>
                                                        <div class="text-muted" style="font-size:0.72rem;">{{ m.counter_name }}</div>
                                                    </div>
                                                </div>

                                                <!-- Main KPI -->
                                                <div class="text-center py-2 mb-2 rounded" :style="{ background: BAR_COLORS[i % BAR_COLORS.length] + '15' }">
                                                    <div class="text-muted" style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em;">Consumo promedio</div>
                                                    <div v-if="m.avg_per_unit !== null" class="fw-bold" :style="{ fontSize: '1.4rem', color: BAR_COLORS[i % BAR_COLORS.length] }">
                                                        {{ m.avg_per_unit.toLocaleString('es-CL', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) }}
                                                        <span style="font-size:0.75rem;">{{ m.unit_label }}</span>
                                                    </div>
                                                    <div v-else class="text-muted" style="font-size:0.85rem;">Sin datos de contador</div>
                                                </div>

                                                <!-- Detail rows -->
                                                <div style="font-size:0.74rem;">
                                                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                        <span class="text-muted">Litros consumidos</span>
                                                        <strong>{{ m.effective_liters.toLocaleString('es-CL', { minimumFractionDigits: 1 }) }} L</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                        <span class="text-muted">Último llenado (excluido)</span>
                                                        <span class="text-muted">{{ m.last_liters.toLocaleString('es-CL', { minimumFractionDigits: 1 }) }} L</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                        <span class="text-muted">Rango contador</span>
                                                        <span>{{ m.min_counter.toLocaleString('es-CL') }} → {{ m.max_counter.toLocaleString('es-CL') }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                                        <span class="text-muted">Total {{ m.unit_label === 'L/km' ? 'km' : 'horas' }}</span>
                                                        <span>{{ m.counter_delta.toLocaleString('es-CL', { minimumFractionDigits: 1 }) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Registros</span>
                                                        <span>{{ m.record_count }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="analyticsData && !analyticsData.consumption_averages?.length" class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No se encontraron registros con lecturas de contador en esta temporada.
                            </div>
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

/* ── Depósito visual ── */
.tank-visual {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 64px;
}

.tank-cap {
    width: 24px;
    height: 8px;
    background: #adb5bd;
    border-radius: 3px 3px 0 0;
    border: 2px solid #6c757d;
    border-bottom: none;
}

.tank-body {
    width: 64px;
    height: 130px;
    border: 2px solid #6c757d;
    border-radius: 0 0 6px 6px;
    background: #e9ecef;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
}

.tank-fill {
    width: 100%;
    transition: height 0.6s ease;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    position: relative;
    z-index: 1;
}

.tank-fill-green  { background: rgba(25, 135, 84, 0.75); }
.tank-fill-yellow { background: rgba(255, 193, 7, 0.80); }
.tank-fill-red    { background: rgba(220, 53, 69, 0.80); }
.tank-fill-nodata { background: rgba(108, 117, 125, 0.4); }

.tank-pct-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 0 3px rgba(0,0,0,0.5);
    padding-top: 3px;
    line-height: 1;
    pointer-events: none;
}

/* Líneas de escala dentro del estanque */
.tank-scale {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.tank-scale-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 1px;
    background: rgba(0,0,0,0.12);
}
</style>
