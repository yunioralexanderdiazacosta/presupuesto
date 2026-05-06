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
    branches: Array,
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

const totalHectareasCCs = computed(() => {
    return props.applicationOrder.order_cost_centers?.reduce((sum, occ) => {
        return sum + Number(occ.cost_center?.surface || 0);
    }, 0) || 0;
});

// Usar superficie_total guardada si existe, si no sumar desde CCs
const totalHectareas = computed(() => {
    const saved = props.applicationOrder.superficie_total;
    return (saved !== null && saved !== undefined) ? Number(saved) : totalHectareasCCs.value;
});

// Indica si se usó un valor personalizado (distinto a la suma de CCs)
const superficiePersonalizada = computed(() => {
    const saved = props.applicationOrder.superficie_total;
    if (saved === null || saved === undefined) return false;
    return Math.abs(Number(saved) - totalHectareasCCs.value) > 0.0001;
});

// Dividir CC en columnas de máximo 10
const ccColumns = computed(() => {
    const items = props.applicationOrder.order_cost_centers || [];
    const maxPerCol = 10;
    const cols = [];
    for (let i = 0; i < items.length; i += maxPerCol) {
        cols.push(items.slice(i, i + maxPerCol));
    }
    return cols.length ? cols : [[]];
});

const maquinadas = computed(() => {
    const mojamiento = Number(props.applicationOrder.mojamiento || 0);
    const hectareas = totalHectareas.value;
    const volumen = Number(props.applicationOrder.volume || 0);
    
    if (volumen === 0) return 0;
    
    return (mojamiento * hectareas) / volumen;
});

// Desglose de maquinadas para el tractorero
const maquinadasCompletas = computed(() => Math.floor(maquinadas.value));
const fraccionSaldo = computed(() => {
    const f = maquinadas.value - maquinadasCompletas.value;
    return Math.round(f * 100) / 100; // redondear a 2 decimales
});
const aguaPorMaquinada = computed(() => Number(props.applicationOrder.volume || 0));
const aguaSaldo = computed(() => Math.round(aguaPorMaquinada.value * fraccionSaldo.value));

function getProductoPorMaquinada(op) {
    const cantTotal = Number(op.cantidad_total || 0);
    const maq = maquinadas.value;
    if (maq === 0) return 0;
    return cantTotal / maq;
}

function getProductoSaldo(op) {
    return getProductoPorMaquinada(op) * fraccionSaldo.value;
}

function formatDosis(value, unitName) {
    const converted = getPracticalQuantityPerHa(value, unitName);
    return converted.value + ' ' + converted.unit;
}

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
                <div class="card-header bg-light border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark fs-9">
                            <i class="fas fa-info-circle me-2 text-muted"></i>Información General
                        </h5>
                        <span class="badge" :class="getStatusBadgeClass(applicationOrder.status)">
                            {{ getStatusLabel(applicationOrder.status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body py-2">
                    <!-- KPIs numéricos -->
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <div class="card bg-soft-primary text-center p-2">
                                <small class="text-muted fw-bold">Fecha</small>
                                <strong class="fs-9">{{ new Date(applicationOrder.date).toLocaleDateString('es-ES') }}</strong>
                            </div>
                        </div>
                        <div class="col" v-if="applicationOrder.start_date">
                            <div class="card bg-soft-info text-center p-2">
                                <small class="text-muted fw-bold">Inicio</small>
                                <strong class="fs-9">{{ new Date(applicationOrder.start_date).toLocaleDateString('es-ES') }}</strong>
                            </div>
                        </div>
                        <div class="col" v-if="applicationOrder.volume">
                            <div class="card bg-soft-secondary text-center p-2">
                                <small class="text-muted fw-bold">Volumen</small>
                                <strong class="fs-9">{{ Number(applicationOrder.volume).toLocaleString('es-ES') }} L</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-soft-secondary text-center p-2">
                                <small class="text-muted fw-bold">Mojamiento</small>
                                <strong class="fs-9">{{ Math.round(Number(applicationOrder.mojamiento)).toLocaleString('es-ES') }} L</strong>
                            </div>
                        </div>
                        <div class="col" v-if="applicationOrder.volume">
                            <div class="card bg-soft-warning text-center p-2">
                                <small class="text-muted fw-bold">Maquinadas</small>
                                <strong class="fs-9 text-primary">{{ maquinadas.toLocaleString('es-ES', {minimumFractionDigits: 1, maximumFractionDigits: 1}) }}</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-soft-success text-center p-2">
                                <small class="text-muted fw-bold">Total ha</small>
                                <strong class="fs-9 text-success">{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</strong>
                                <small v-if="superficiePersonalizada" class="text-warning" style="font-size:0.65rem;" title="Superficie personalizada (difiere de la suma de los CCs)">
                                    <i class="fas fa-edit"></i> personalizada
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle texto -->
                    <table class="w-100 fs--1 mt-1" style="border-collapse: collapse;">
                        <tr>
                            <td class="py-1 pe-3" style="width:33%; border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block">Recomendado por</small>
                                <span>{{ applicationOrder.recomendado || '—' }}</span>
                            </td>
                            <td class="py-1 pe-3" style="width:33%; border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block">Responsable</small>
                                <span>{{ applicationOrder.responsable || '—' }}</span>
                            </td>
                            <td class="py-1" style="width:34%; border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block">Temporada</small>
                                <span>{{ applicationOrder.season?.name || '—' }}</span>
                            </td>
                        </tr>
                        <tr v-if="applicationOrder.tractors || applicationOrder.equipments || applicationOrder.operators">
                            <td class="py-1 pe-3" style="border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block"><i class="fas fa-tractor me-1"></i>Tractores</small>
                                <span>{{ applicationOrder.tractors || '—' }}</span>
                            </td>
                            <td class="py-1 pe-3" style="border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block"><i class="fas fa-cogs me-1"></i>Equipos</small>
                                <span>{{ applicationOrder.equipments || '—' }}</span>
                            </td>
                            <td class="py-1" style="border-top: 1px solid #eee;">
                                <small class="text-muted fw-bold d-block"><i class="fas fa-user me-1"></i>Operarios</small>
                                <span>{{ applicationOrder.operators || '—' }}</span>
                            </td>
                        </tr>
                        <tr v-if="applicationOrder.phenological_stage || applicationOrder.observations">
                            <td class="py-1 pe-3" style="border-top: 1px solid #eee;" v-if="applicationOrder.phenological_stage">
                                <small class="text-muted fw-bold d-block">Fenología</small>
                                <span><i class="fas fa-seedling text-success me-1"></i>{{ applicationOrder.phenological_stage.name }}</span>
                            </td>
                            <td class="py-1 pe-3" :colspan="applicationOrder.phenological_stage ? 2 : 3" style="border-top: 1px solid #eee;" v-if="applicationOrder.observations">
                                <small class="text-muted fw-bold d-block">Observaciones</small>
                                <span class="fst-italic">{{ applicationOrder.observations }}</span>
                            </td>
                        </tr>
                    </table>
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
                    <div v-if="applicationOrder.order_cost_centers?.length > 0" class="row g-2">
                        <div v-for="(col, colIdx) in ccColumns" :key="colIdx" :class="ccColumns.length === 1 ? 'col-12' : ccColumns.length === 2 ? 'col-md-6' : 'col-md-4'">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light" v-if="colIdx === 0">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Centro de Costo</th>
                                        <th class="text-end" style="width: 110px;">Superficie</th>
                                    </tr>
                                </thead>
                                <thead class="table-light" v-else>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Centro de Costo</th>
                                        <th class="text-end" style="width: 110px;">Superficie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(occ, index) in col" :key="occ.id">
                                        <td>{{ colIdx * 15 + index + 1 }}</td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            {{ occ.cost_center?.name || 'N/A' }}
                                        </td>
                                        <td class="text-end">
                                            {{ Number(occ.cost_center?.surface || 0).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12">
                            <div class="bg-primary text-white fw-bold d-flex justify-content-between px-3 py-1 rounded-bottom" style="font-size: 0.85rem;">
                                <span>TOTAL CCs:</span>
                                <span>{{ totalHectareasCCs.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</span>
                            </div>
                            <div v-if="superficiePersonalizada" class="bg-warning text-dark d-flex justify-content-between px-3 py-1 rounded" style="font-size: 0.8rem; margin-top: 2px;">
                                <span><i class="fas fa-edit me-1"></i>Superficie usada en cálculos:</span>
                                <span class="fw-bold">{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</span>
                            </div>
                        </div>
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
                                    <th>Ingrediente Activo</th>
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
                                    <td class="text-muted">{{ op.product?.active_ingredient || '-' }}</td>
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

            <!-- Desglose de Maquinadas para el Tractorero -->
            <div class="card mb-3" v-if="applicationOrder.volume && maquinadas > 0 && applicationOrder.order_products?.length > 0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-tractor me-2 text-warning"></i>Receta por Maquinada
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Maquinadas Completas -->
                        <div class="col-md-6" v-if="maquinadasCompletas > 0">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white py-2">
                                    <strong><i class="fas fa-fill-drip me-1"></i>Maquinadas Completas: {{ maquinadasCompletas }}</strong>
                                </div>
                                <div class="card-body py-2">
                                    <div class="mb-2">
                                        <i class="fas fa-tint text-primary me-1"></i>
                                        <strong>Agua: {{ aguaPorMaquinada.toLocaleString('es-CL') }} L</strong>
                                    </div>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr v-for="op in applicationOrder.order_products" :key="'full-'+op.id">
                                                <td class="py-1">{{ op.product?.name }}</td>
                                                <td class="py-1 text-end fw-bold">
                                                    {{ formatDosis(getProductoPorMaquinada(op), op.unit?.name || op.product?.unit?.name || '') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Maquinada de Saldo -->
                        <div class="col-md-6" v-if="fraccionSaldo > 0">
                            <div class="card border-warning h-100">
                                <div class="card-header bg-warning text-dark py-2">
                                    <strong><i class="fas fa-fill-drip me-1"></i>Maquinada de Saldo ({{ fraccionSaldo }})</strong>
                                </div>
                                <div class="card-body py-2">
                                    <div class="mb-2">
                                        <i class="fas fa-tint text-primary me-1"></i>
                                        <strong>Agua: {{ aguaSaldo.toLocaleString('es-CL') }} L</strong>
                                    </div>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr v-for="op in applicationOrder.order_products" :key="'partial-'+op.id">
                                                <td class="py-1">{{ op.product?.name }}</td>
                                                <td class="py-1 text-end fw-bold">
                                                    {{ formatDosis(getProductoSaldo(op), op.unit?.name || op.product?.unit?.name || '') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
            :branches="branches"
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
