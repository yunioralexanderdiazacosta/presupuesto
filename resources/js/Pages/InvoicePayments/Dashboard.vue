<script setup>
import { computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    stats: Object,
    pagos_por_metodo: Array,
    pagos_por_banco: Array,
    pagos_por_mes: Array,
    top_proveedores: Array,
    pagos_recientes: Array,
});

const title = 'Dashboard de Pagos';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Pagos de Facturas', link: 'invoice-payments.index' },
    { title, active: true },
];

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
}

onMounted(() => {
    // Gráfico de Pagos por Método
    if (props.pagos_por_metodo && props.pagos_por_metodo.length > 0) {
        const ctxMetodo = document.getElementById('chartMetodo');
        if (ctxMetodo) {
            new Chart(ctxMetodo, {
                type: 'doughnut',
                data: {
                    labels: props.pagos_por_metodo.map(p => p.method),
                    datasets: [{
                        data: props.pagos_por_metodo.map(p => p.total),
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    // Gráfico de Pagos por Mes
    if (props.pagos_por_mes && props.pagos_por_mes.length > 0) {
        const ctxMes = document.getElementById('chartMes');
        if (ctxMes) {
            new Chart(ctxMes, {
                type: 'bar',
                data: {
                    labels: props.pagos_por_mes.map(p => p.month),
                    datasets: [{
                        label: 'Monto Pagado',
                        data: props.pagos_por_mes.map(p => p.total),
                        backgroundColor: '#3b82f6',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // Gráfico de Top Bancos
    if (props.pagos_por_banco && props.pagos_por_banco.length > 0) {
        const ctxBanco = document.getElementById('chartBanco');
        if (ctxBanco) {
            new Chart(ctxBanco, {
                type: 'horizontalBar',
                data: {
                    labels: props.pagos_por_banco.map(p => p.bank),
                    datasets: [{
                        label: 'Monto',
                        data: props.pagos_por_banco.map(p => p.total),
                        backgroundColor: '#10b981',
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-chart-line me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <a 
                            :href="route('invoice-payments.index')" 
                            class="btn btn-falcon-default btn-sm"
                        >
                            <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Volver a Pagos</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Resumen de Estadísticas -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary h-100">
                            <div class="card-body">
                                <div class="text-primary text-uppercase mb-1 small fw-bold">Total Pagado</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    $ {{ formatCurrency(stats.total_pagado) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-danger h-100">
                            <div class="card-body">
                                <div class="text-danger text-uppercase mb-1 small fw-bold">Saldo Pendiente</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    $ {{ formatCurrency(stats.saldo_pendiente) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success h-100">
                            <div class="card-body">
                                <div class="text-success text-uppercase mb-1 small fw-bold">Facturas Pagadas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ stats.facturas_pagadas }} / {{ stats.total_facturas_count }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning h-100">
                            <div class="card-body">
                                <div class="text-warning text-uppercase mb-1 small fw-bold">Facturas Parciales</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ stats.facturas_parciales }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Pagos por Método</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px;">
                                    <canvas id="chartMetodo"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Evolución Mensual de Pagos</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px;">
                                    <canvas id="chartMes"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Top 10 Bancos (Transferencias)</h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="chartBanco"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Top 10 Proveedores</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Proveedor</th>
                                                <th class="text-end">Total Pagado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, idx) in top_proveedores" :key="idx">
                                                <td>{{ item.supplier }}</td>
                                                <td class="text-end">$ {{ formatCurrency(item.total) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagos Recientes -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Últimos Pagos Registrados</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Factura</th>
                                        <th>Proveedor</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="pago in pagos_recientes" :key="pago.id">
                                        <td>{{ pago.date }}</td>
                                        <td>{{ pago.invoice }}</td>
                                        <td>{{ pago.supplier }}</td>
                                        <td class="text-end">$ {{ formatCurrency(pago.amount) }}</td>
                                        <td>{{ pago.method }}</td>
                                        <td>{{ pago.user }}</td>
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
.border-left-primary {
    border-left: 4px solid #3b82f6;
}
.border-left-danger {
    border-left: 4px solid #ef4444;
}
.border-left-success {
    border-left: 4px solid #10b981;
}
.border-left-warning {
    border-left: 4px solid #f59e0b;
}
</style>
