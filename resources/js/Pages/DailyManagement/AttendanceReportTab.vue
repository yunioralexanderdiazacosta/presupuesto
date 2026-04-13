<script setup>
import { computed, ref, watch, nextTick, onMounted } from 'vue';
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(DoughnutController, ArcElement, Tooltip, Legend);

const props = defineProps({
    summary: Object,
    employees: Array,
    selectedDate: String,
});

// Empleados presentes con CC y labor asignados
const presentWithData = computed(() => {
    if (!props.summary?.parcelSummary) return [];
    return props.summary.parcelSummary;
});

// Totales generales
const totalWorkers = computed(() => presentWithData.value.reduce((sum, p) => sum + p.total_workers, 0));
const totalParcels = computed(() => presentWithData.value.length);

function fmt(n) {
    return n != null ? Number(n).toLocaleString('es-CL') : '—';
}

// === Gráfico Doughnut ===
const chartCanvas = ref(null);
let chartInstance = null;

const parcelColors = [
    '#00d27a', '#2c7be5', '#e63757', '#f5803e', '#27bcfd',
    '#748194', '#d8e2ef', '#b6d4fe', '#ffc107', '#6f42c1',
];

function renderChart() {
    if (!chartCanvas.value || presentWithData.value.length === 0) return;
    if (chartInstance) chartInstance.destroy();

    const labels = presentWithData.value.map(p => p.parcel_name);
    const data = presentWithData.value.map(p => p.total_workers);
    const colors = labels.map((_, i) => parcelColors[i % parcelColors.length]);

    chartInstance = new Chart(chartCanvas.value, {
        type: 'doughnut',
        plugins: [ChartDataLabels],
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11 }, padding: 12, usePointStyle: true, pointStyle: 'circle' },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.label}: ${ctx.parsed} jh (${Math.round(ctx.parsed / data.reduce((a, b) => a + b, 0) * 100)}%)`,
                    },
                },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 13 },
                    formatter: (value) => value,
                    display: (ctx) => ctx.dataset.data[ctx.dataIndex] > 0,
                },
            },
        },
    });
}

onMounted(() => nextTick(renderChart));
watch(presentWithData, () => nextTick(renderChart), { deep: true });
</script>

<template>
    <div>
        <!-- Resumen general -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card bg-soft-primary text-center p-2">
                    <small class="text-muted">Presentes</small>
                    <strong>{{ summary.present }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-danger text-center p-2">
                    <small class="text-muted">Ausentes</small>
                    <strong>{{ summary.absent }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-success text-center p-2">
                    <small class="text-muted">Parcelas activas</small>
                    <strong>{{ totalParcels }}</strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-soft-warning text-center p-2">
                    <small class="text-muted">Jornadas asignadas</small>
                    <strong>{{ totalWorkers }}</strong>
                </div>
            </div>
        </div>

        <!-- Sin datos -->
        <div v-if="presentWithData.length === 0" class="text-center py-5">
            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay distribución por parcela para este día.</p>
            <small class="text-muted">Registra la asistencia con CC y labor asignados para ver este reporte.</small>
        </div>

        <template v-else>
            <!-- Gráfico + Cards -->
            <div class="row g-3">
                <!-- Gráfico Doughnut -->
                <div class="col-12 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header py-2">
                            <h6 class="mb-0 small fw-bold"><i class="fas fa-chart-pie me-1 text-primary"></i>Distribución por Parcela</h6>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 280px;">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Cards por parcela -->
                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div v-for="(parcel, idx) in presentWithData" :key="idx" class="col-12 col-md-6">
                            <div class="card h-100" style="border-left: 4px solid #00d27a;">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #eaf4ed;">
                                    <h6 class="mb-0 text-success fw-bold small">
                                        <i class="fas fa-seedling me-1"></i>{{ parcel.parcel_name }}
                                    </h6>
                                    <span class="badge bg-success">{{ parcel.total_workers }} jh</span>
                                </div>
                                <div class="card-body py-2 px-3">
                                    <div v-for="(labor, li) in parcel.labors" :key="li"
                                        class="d-flex justify-content-between align-items-center py-1"
                                        :class="{ 'border-bottom': li < parcel.labors.length - 1 }"
                                        style="border-color: #edf2f9 !important;">
                                        <span class="small">{{ labor.labor_name }}</span>
                                        <span class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">{{ labor.workers }} jh</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
