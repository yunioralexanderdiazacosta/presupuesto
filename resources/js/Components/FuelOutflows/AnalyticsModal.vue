<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    fuelStockByProduct: Array,
    totalStock: Number,
    fuelOutflows: Object,
});

const emit = defineEmits(['close']);

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

                            <div class="row">
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
                        </div>

                        <!-- Tab 2: Gráficos (placeholder) -->
                        <div class="tab-pane fade" id="graficos-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>En desarrollo:</strong> Aquí se mostrarán gráficos de consumo por maquinaria, tendencias temporales, etc.
                            </div>
                            <!-- Aquí irán los gráficos con Chart.js o ApexCharts -->
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
