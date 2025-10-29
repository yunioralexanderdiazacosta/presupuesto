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
        
        <div class="card mb-3 mt-2">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-home text-primary me-2"></i>
                            Bienvenido al Sistema de Gestión Presupuestaria
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
                                    Se mejora niveles, ahora se relacionan a cada producto.<br>
                                    En los formularios de salidas ahora al hacer click en fila ya se puede editar..<br>
                                La pestaña parametros estara por defecto cerrada para ocupar menos espacio en pantalla.<br>
                                En facturas, la tabla se carga con icono warning cuando el producto no esta resgistrado.
                                
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
                                   El sistema está organizado en dos módulos principales:
<br><strong>Presupuesto:</strong> Permite planificar, asignar y controlar los recursos económicos de cada temporada y proyecto.
<br><strong>Gestión:</strong> Facilita el registro, seguimiento y análisis de todas las operaciones, gastos y movimientos realizados.
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
                                   Toda la configuración del sistema, incluyendo la gestión de usuarios, permisos, categorías, proveedores y otros parámetros esenciales, se encuentra en el módulo de Parametros.
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
                                    At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti.
                                    Quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.
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
                                    Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.
                                    Et harum quidem rerum facilis est et expedita distinctio nam libero tempore.
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
