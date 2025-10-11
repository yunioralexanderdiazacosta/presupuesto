<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { computed } from 'vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_amount: 0,
            total_count: 0,
            avg_per_outflow: 0
        })
    }
});

const title = 'Dashboard de Outflows';

const links = [
    { title: 'Gestión' },
    { title: 'Dashboard Outflows', active: true }
];

// Formatear números con separador de miles
const formatNumber = (number) => {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat('es-CL').format(number);
};

// Formatear moneda
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '$0';
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        
        <div class="card mb-3 mt-2">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Dashboard de Análisis de Consumos
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- KPI Cards -->
                <div class="row g-3 mb-4">
                    <!-- Total Outflows Card -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-primary border-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-2">Total Consumido</h6>
                                        <h2 class="mb-0 text-primary fw-bold">
                                            {{ formatCurrency(summary?.total_amount || 0) }}
                                        </h2>
                                        <small class="text-muted">
                                            {{ formatNumber(summary?.total_count || 0) }} registros
                                        </small>
                                    </div>
                                    <div class="text-primary">
                                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Promedio por Outflow Card -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-success border-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-2">Promedio por Registro</h6>
                                        <h2 class="mb-0 text-success fw-bold">
                                            {{ formatCurrency(summary?.avg_per_outflow || 0) }}
                                        </h2>
                                        <small class="text-muted">
                                            Por consumo
                                        </small>
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-calculator fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder para futuras métricas -->
                    <div class="col-md-4">
                        <div class="card h-100 border-start border-info border-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-muted text-uppercase mb-2">Próximamente</h6>
                                        <h2 class="mb-0 text-info fw-bold">
                                            --
                                        </h2>
                                        <small class="text-muted">
                                            Más métricas próximamente
                                        </small>
                                    </div>
                                    <div class="text-info">
                                        <i class="fas fa-chart-bar fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área para futuros gráficos y tablas -->
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Gráficos y análisis detallados próximamente</h5>
                                <p class="text-muted mb-0">
                                    Aquí se mostrarán análisis por producto, niveles, operaciones, proyectos y maquinarias
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.opacity-50 {
    opacity: 0.5;
}
</style>
