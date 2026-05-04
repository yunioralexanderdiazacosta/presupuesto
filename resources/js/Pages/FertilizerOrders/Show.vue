<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import EditFertilizerOrderModal from '@/Components/FertilizerOrders/EditFertilizerOrderModal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    fertilizerOrder: Object,
    products: Array,
    irrigationPumps: Array,
    costCenters: Array,
    branches: { type: Array, default: () => [] },
    units: Array,
    groupings: { type: Array, default: () => [] },
});

const title = 'Detalle de Orden de Fertilizante';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Órdenes de Fertilizante', link: 'fertilizer-orders.index' },
    { title, active: true },
];

const showEditModal = ref(false);

const totalSurface = computed(() => {
    return props.fertilizerOrder.order_irrigation_sectors?.reduce((sum, ois) => {
        return sum + Number(ois.surface || 0);
    }, 0) || 0;
});

function openEditModal() {
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
}

function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'bg-warning text-dark',
        'executed': 'bg-success text-white',
        'canceled': 'bg-danger text-white'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'Pendiente',
        'executed': 'Ejecutado',
        'canceled': 'Cancelado'
    };
    return labels[status] || status;
}

function printPDF() {
    window.open(route('fertilizer-orders.pdf', props.fertilizerOrder.id), '_blank');
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
            router.delete(route('fertilizer-orders.destroy', props.fertilizerOrder.id), {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'La orden ha sido eliminada.', 'success');
                }
            });
        }
    });
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
                        <Link :href="route('fertilizer-orders.index')" class="btn btn-secondary">
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
                        <div class="col-md-3">
                            <label class="text-muted small">Fecha:</label>
                            <p class="fw-bold mb-0">{{ new Date(fertilizerOrder.date).toLocaleDateString('es-ES') }}</p>
                        </div>
                        <div class="col-md-3" v-if="fertilizerOrder.irrigation_pump">
                            <label class="text-muted small">Bomba de Riego:</label>
                            <p class="fw-bold mb-0">{{ fertilizerOrder.irrigation_pump.name }}</p>
                        </div>
                        <div class="col-md-3" v-if="fertilizerOrder.responsable">
                            <label class="text-muted small">Responsable:</label>
                            <p class="fw-bold mb-0">{{ fertilizerOrder.responsable }}</p>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small">Estado:</label>
                            <p class="mb-0">
                                <span class="badge" :class="getStatusBadgeClass(fertilizerOrder.status)">
                                    {{ getStatusLabel(fertilizerOrder.status) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-3">
                            <label class="text-muted small">Superficie Total:</label>
                            <p class="fw-bold mb-0 text-success">{{ totalSurface.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</p>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted small">Temporada:</label>
                            <p class="fw-bold mb-0">{{ fertilizerOrder.season?.name || 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row g-3 mt-1" v-if="fertilizerOrder.observations">
                        <div class="col-md-12">
                            <label class="text-muted small">Observaciones:</label>
                            <p class="mb-0">{{ fertilizerOrder.observations }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="card mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-flask me-2 text-muted"></i>Productos Aplicados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Dosis/ha</th>
                                    <th class="text-end">Cantidad Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="op in fertilizerOrder.order_products" :key="op.id">
                                    <td class="fw-bold">{{ op.product?.name || 'N/A' }}</td>
                                    <td class="text-end">
                                        {{ Number(op.dosis_por_hectarea).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}
                                        {{ op.unit?.name || op.product?.unit?.name || '' }}/ha
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        {{ Number(op.cantidad_total).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}
                                        {{ op.unit?.name || op.product?.unit?.name || '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sectores de Riego -->
            <div class="card mb-3">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-water me-2 text-muted"></i>Sectores de Riego
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Sector</th>
                                    <th class="text-end">Superficie (ha)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ois in fertilizerOrder.order_irrigation_sectors" :key="ois.id">
                                    <td class="fw-bold">{{ ois.irrigation_sector?.name || 'N/A' }}</td>
                                    <td class="text-end">
                                        {{ Number(ois.surface).toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha
                                    </td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end text-success">
                                        {{ totalSurface.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Centros de Costo -->
            <div class="card mb-3" v-if="fertilizerOrder.order_cost_centers && fertilizerOrder.order_cost_centers.length > 0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-map-marker-alt me-2 text-muted"></i>Centros de Costo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4" v-for="occ in fertilizerOrder.order_cost_centers" :key="occ.id">
                            <div class="border-start border-primary border-3 ps-2 mb-2">
                                <strong>{{ occ.cost_center?.name || 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de edición -->
        <EditFertilizerOrderModal
            :show="showEditModal"
            @close="closeEditModal"
            :fertilizer-order="fertilizerOrder"
            :products="products"
            :irrigation-pumps="irrigationPumps"
            :cost-centers="costCenters"
            :branches="branches"
            :units="units"
            :groupings="groupings"
        />
    </AppLayout>
</template>
