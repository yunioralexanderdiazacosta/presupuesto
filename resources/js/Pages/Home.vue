<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    info: Object
});

const title = 'Inicio';

const links = [
    { title: 'Inicio', active: true }
];
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="fs-9 mb-0 py-2 py-xl-0">
                            <i class="fas fa-home text-primary me-2"></i>
                            Bienvenido al Sistema de Gestión Agrícola
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="row g-3">
                    <!-- Card 1 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Ultimas Actualizaciones</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                    <strong>Marzo 2026:</strong><br>
                                    • Copia de presupuesto entre temporadas: desde Temporadas puedes copiar agroquímicos, fertilizantes, insumos, servicios, cosecha, mano de obra, administración y campos a otra temporada<br>
                                    • Copia de centros de costo entre temporadas: desde Centros de Costo puedes copiar todos los cuarteles y sus variedades a la temporada activa con el botón "Copiar CC"<br>
                                    • Nueva página de inicio de sesión con diseño renovado<br>
                                    • Despachos de producción ahora con clasificación por tipo de fruta<br>
                                    • Se puede ver el detalle de cada despacho procesado<br>
                                    • Se reciben notificaciones por email al aprobar o rechazar rendiciones<br>
                                    • Pagos de facturas ahora muestran saldo pendiente, estado y vencimiento<br>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-alt fa-2x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Temporada Actual</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                    <strong>Temporada:</strong> {{ info.season_name }}<br>
                                    <strong>Equipo:</strong> {{ info.team_name }}<br>
                                    <strong>Usuario:</strong> {{ info.user_name }}
                                </p>
                                <p class="card-text text-muted">
                                    La temporada actual permite gestionar los presupuestos y operaciones correspondientes según el inicio de temporada que se seleccione al momento de crearla.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-chart-line fa-2x text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Módulos Principales</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                   El sistema está organizado en módulos especializados:<br><br>
                                   <strong>Presupuesto:</strong> Estructura jerárquica de 4 niveles, inversiones, centros de costo y agrupaciones para planificación detallada.<br><br>
                                   <strong>Gestión:</strong> Órdenes de compra, facturas, pagos, salidas de combustible, servicios, agroquímicos y aplicaciones de campo.<br><br>
                                   <strong>Inventario:</strong> Control de stock en tiempo real con alertas de mínimos y tracking de movimientos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-cog fa-2x text-danger"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Configuración</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                   El módulo <strong>Parámetros</strong> centraliza toda la configuración:<br>
                                   • Gestión de usuarios, equipos y roles con permisos granulares<br>
                                   • Catálogos de proveedores, productos y agroquímicos<br>
                                   • Configuración de niveles jerárquicos (rubros, items, cuentas)<br>
                                   • Centros de costo y agrupaciones presupuestarias<br>
                                   • Temporadas agrícolas con fechas de inicio personalizables<br>
                                   • Importación masiva de datos vía Excel
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users fa-2x text-info"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Gestión de Equipo</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                    El sistema está diseñado para trabajar en equipo de forma colaborativa. Cada usuario pertenece a un equipo específico y solo puede ver y gestionar la información correspondiente a su equipo.<br><br>
                                    <strong>Roles disponibles:</strong> Administrador, Aprobador de Compras, Usuario Estándar, entre otros, cada uno con permisos específicos según sus responsabilidades.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-file-alt fa-2x text-secondary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0">Reportes y Análisis</h5>
                                    </div>
                                </div>
                                <p class="card-text">
                                    El sistema ofrece reportes detallados y exportación a Excel de toda la información:<br>
                                    • Reportes de presupuestos por nivel y centro de costo<br>
                                    • Análisis de salidas de combustible y servicios<br>
                                    • Dashboard de órdenes de compra con estados<br>
                                    • Exportación de facturas y pagos<br>
                                    • Gráficos de ejecución presupuestaria
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
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
