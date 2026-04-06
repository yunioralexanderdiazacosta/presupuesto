<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import EditApplicationOrderModal from '@/Components/ApplicationOrders/EditApplicationOrderModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    applicationOrder: Object,
    products: Array,
    costCenters: Array,
    units: Array,
    groupings: Array,
    fruits: Array,
    phenologicalStages: Array,
    machineries: Array,
    operators: Array,
});

const title = 'Detalle de Orden de Aplicación';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Órdenes de Aplicación', link: 'application-orders.index' },
    { title, active: true },
];

const showEditModal = ref(false);

const totalHectareas = computed(() => {
    return props.applicationOrder.order_cost_centers?.reduce((sum, occ) => {
        return sum + Number(occ.cost_center?.surface || 0);
    }, 0) || 0;
});

const maquinadas = computed(() => {
    const mojamiento = Number(props.applicationOrder.mojamiento || 0);
    const hectareas = totalHectareas.value;
    const volumen = Number(props.applicationOrder.volume || 0);
    
    if (volumen === 0) return 0;
    
    return (mojamiento * hectareas) / volumen;
});

function openEditModal() {
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
}

function getStatusBadgeClass(status) {
    const classes = {
        'pendiente': 'bg-warning text-dark',
        'en_proceso': 'bg-info text-white',
        'completada': 'bg-success text-white',
        'cancelada': 'bg-danger text-white'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusLabel(status) {
    const labels = {
        'pendiente': 'Pendiente',
        'en_proceso': 'En Proceso',
        'completada': 'Completada',
        'cancelada': 'Cancelada'
    };
    return labels[status] || status;
}

function printPDF() {
    window.open(route('application-orders.pdf', props.applicationOrder.id), '_blank');
}

function confirmDelete() {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('application-orders.delete', props.applicationOrder.id), {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'La orden ha sido eliminada.', 'success');
                }
            });
        }
    });
}

// Función para convertir a unidades prácticas para aplicación en campo
function getSimplifiedQuantity(orderProduct) {
    const cantidad = Number(orderProduct.cantidad_total);
    const unitName = (orderProduct.unit?.name || orderProduct.product?.unit?.name || '').toLowerCase();
    
    // Convertir lt a cc si es < 1
    if (unitName === 'lt' && cantidad < 1) {
        return {
            value: (cantidad * 1000).toFixed(0),
            unit: 'cc'
        };
    }
    
    // Convertir kg a gr si es < 1
    if (unitName === 'kg' && cantidad < 1) {
        return {
            value: (cantidad * 1000).toFixed(0),
            unit: 'gr'
        };
    }
    
    // No convertir, devolver original
    return {
        value: cantidad.toFixed(2),
        unit: orderProduct.unit?.name || orderProduct.product?.unit?.name || ''
    };
}

// Función para convertir cantidades por hectárea
function getPracticalQuantityPerHa(value, unitName) {
    const cantidad = Number(value);
    const unit = unitName.toLowerCase();
    
    // Convertir lt a cc si es < 1
    if (unit === 'lt' && cantidad < 1) {
        return {
            value: (cantidad * 1000).toFixed(0),
            unit: 'cc'
        };
    }
    
    // Convertir kg a gr si es < 1
    if (unit === 'kg' && cantidad < 1) {
        return {
            value: (cantidad * 1000).toFixed(0),
            unit: 'gr'
        };
    }
    
    return {
        value: cantidad.toFixed(2),
        unit: unitName
    };
}
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        <Breadcrumb :title="title" :links="links" />

        <div class="container-fluid mt-3">
            <!-- Botones de acción -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <Link :href="route('application-orders.index')" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </Link>
                        <div class="btn-group">
                            <button @click="printPDF" class="btn btn-primary">
                                <i class="fas fa-print me-2"></i>Imprimir PDF
                            </button>
                            <button @click="openEditModal" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Editar
                            </button>
                            <button @click="confirmDelete" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información General -->
            <div class="card mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-info-circle me-2 text-muted"></i>Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="text-muted small">Fecha:</label>
                            <p class="fw-bold mb-0">{{ new Date(applicationOrder.date).toLocaleDateString('es-ES') }}</p>
                        </div>
                        <div class="col-md-2" v-if="applicationOrder.start_date">
                            <label class="text-muted small">Fecha Inicio:</label>
                            <p class="fw-bold mb-0">{{ new Date(applicationOrder.start_date).toLocaleDateString('es-ES') }}</p>
                        </div>
                        <div class="col-md-2" v-if="applicationOrder.volume">
                            <label class="text-muted small">Volumen:</label>
                            <p class="fw-bold mb-0">{{ Number(applicationOrder.volume).toLocaleString('es-ES') }} L</p>
                        </div>
                        <div class="col-md-2">
                            <label class="text-muted small">Mojamiento:</label>
                            <p class="fw-bold mb-0">{{ Math.round(Number(applicationOrder.mojamiento)).toLocaleString('es-ES') }} L</p>
                        </div>
                        <div class="col-md-2" v-if="applicationOrder.volume">
                            <label class="text-muted small">Maquinadas:</label>
                            <p class="fw-bold mb-0 text-primary">{{ maquinadas.toLocaleString('es-ES', {minimumFractionDigits: 1, maximumFractionDigits: 1}) }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="text-muted small">Total Hectáreas:</label>
                            <p class="fw-bold mb-0 text-success">{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</p>
                        </div>
                        <div class="col-md-2">
                            <label class="text-muted small">Estado:</label>
                            <p class="mb-0">
                                <span class="badge" :class="getStatusBadgeClass(applicationOrder.status)">
                                    {{ getStatusLabel(applicationOrder.status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-3">
                            <label class="text-muted small">Recomendado por:</label>
                            <p class="fw-bold mb-0">{{ applicationOrder.recomendado }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small">Responsable:</label>
                            <p class="fw-bold mb-0">{{ applicationOrder.responsable }}</p>
                        </div>
                        <div class="col-md-3" v-if="applicationOrder.phenological_stage">
                            <label class="text-muted small">Etapa Fenológica:</label>
                            <p class="fw-bold mb-0">
                                <i class="fas fa-seedling text-success me-1"></i>
                                {{ applicationOrder.phenological_stage.name }}
                            </p>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small">Temporada:</label>
                            <p class="fw-bold mb-0">{{ applicationOrder.season?.name || 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4" v-if="applicationOrder.tractors">
                            <label class="text-muted small">Tractores:</label>
                            <p class="fw-bold mb-0">
                                <i class="fas fa-tractor text-muted me-1"></i>{{ applicationOrder.tractors }}
                            </p>
                        </div>
                        <div class="col-md-4" v-if="applicationOrder.equipments">
                            <label class="text-muted small">Equipos:</label>
                            <p class="fw-bold mb-0">
                                <i class="fas fa-cogs text-muted me-1"></i>{{ applicationOrder.equipments }}
                            </p>
                        </div>
                        <div class="col-md-4" v-if="applicationOrder.operators">
                            <label class="text-muted small">Operarios:</label>
                            <p class="fw-bold mb-0">
                                <i class="fas fa-user-hard-hat text-muted me-1"></i>{{ applicationOrder.operators }}
                            </p>
                        </div>
                    </div>

                    <div v-if="applicationOrder.observations" class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="text-muted small">Observaciones:</label>
                            <p class="text-muted mb-0">{{ applicationOrder.observations }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Centros de Costo -->
            <div class="card mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-map-marker-alt me-2 text-success"></i>Centros de Costo Aplicados
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="applicationOrder.order_cost_centers?.length > 0" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Centro de Costo</th>
                                    <th class="text-end">Superficie (ha)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(occ, index) in applicationOrder.order_cost_centers" :key="occ.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        {{ occ.cost_center?.name || 'N/A' }}
                                    </td>
                                    <td class="text-end">
                                        {{ Number(occ.cost_center?.surface || 0).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light sticky-bottom">
                                <tr>
                                    <th colspan="2" class="text-end">Total:</th>
                                    <th class="text-end">{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-else class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No hay centros de costo asociados
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="card mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-flask me-2 text-info"></i>Productos a Aplicar
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="applicationOrder.order_products?.length > 0" class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Tipo Dosis</th>
                                    <th class="text-end">Dosis</th>
                                    <th class="text-end">Cantidad/ha</th>
                                    <th class="text-end">Cantidad Total</th>
                                    <th class="text-center">Carencia</th>
                                    <th class="text-center">Reingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(op, index) in applicationOrder.order_products" :key="op.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <i class="fas fa-flask text-muted me-2"></i>
                                        <strong>{{ op.product?.name || 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        <span v-if="op.tipo_dosis === 'por_hectarea'" class="badge bg-primary">
                                            Por Hectárea
                                        </span>
                                        <span v-else class="badge bg-info">
                                            Por 100L
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span v-if="op.tipo_dosis === 'por_hectarea'">
                                            {{ getPracticalQuantityPerHa(op.dosis_por_hectarea, op.unit?.name || op.product?.unit?.name || '').value }}
                                            {{ getPracticalQuantityPerHa(op.dosis_por_hectarea, op.unit?.name || op.product?.unit?.name || '').unit }}/ha
                                        </span>
                                        <span v-else>
                                            {{ getPracticalQuantityPerHa(op.dosis_por_100, op.unit?.name || op.product?.unit?.name || '').value }}
                                            {{ getPracticalQuantityPerHa(op.dosis_por_100, op.unit?.name || op.product?.unit?.name || '').unit }}/100L
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ getPracticalQuantityPerHa(op.cantidad_por_hectarea, op.unit?.name || op.product?.unit?.name || '').value }}
                                        {{ getPracticalQuantityPerHa(op.cantidad_por_hectarea, op.unit?.name || op.product?.unit?.name || '').unit }}/ha
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-dark">
                                            {{ getSimplifiedQuantity(op).value }}
                                            {{ getSimplifiedQuantity(op).unit }}
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ op.carencia }} días</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ op.reingreso }} horas</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No hay productos asociados
                    </div>
                </div>
            </div>

            <!-- Información de auditoría -->
            <div class="card">
                <div class="card-body">
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <i class="fas fa-calendar-plus me-1"></i>
                            Creado: {{ new Date(applicationOrder.created_at).toLocaleString('es-ES') }}
                        </div>
                        <div class="col-md-6 text-end">
                            <i class="fas fa-calendar-edit me-1"></i>
                            Última actualización: {{ new Date(applicationOrder.updated_at).toLocaleString('es-ES') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Edición -->
        <EditApplicationOrderModal
            :show="showEditModal"
            :order="applicationOrder"
            :products="products"
            :cost-centers="costCenters"
            :units="units"
            :groupings="groupings"
            :fruits="fruits"
            :phenological-stages="phenologicalStages"
            :machineries="machineries"
            :operators="operators"
            @close="closeEditModal"
        />
    </AppLayout>
</template>
