<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    purchaseOrder: Object,
});

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function getStatusBadgeClass(status) {
    const classes = {
        'draft': 'bg-secondary text-white',
        'pending': 'bg-warning text-dark',
        'approved': 'bg-info text-white',
        'rejected': 'bg-danger text-white',
        'sent': 'bg-primary text-white',
        'received_partial': 'bg-warning text-dark',
        'completed': 'bg-success text-white',
        'cancelled': 'bg-dark text-white',
    };
    return classes[status] || 'bg-secondary text-white';
}

function printOrder() {
    window.print();
}
</script>

<template>
    <Head :title="`Orden de Compra ${purchaseOrder.order_number}`" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-file-invoice me-2 text-primary"></i>
                            Orden de Compra #{{ purchaseOrder.order_number }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <Link 
                            :href="route('purchase-orders.index')" 
                            class="btn btn-falcon-default btn-sm me-2"
                        >
                            <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Volver</span>
                        </Link>
                        <button @click="printOrder" class="btn btn-falcon-default btn-sm">
                            <span class="fas fa-print" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Imprimir</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Información General -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Información de la Orden
                                </h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted d-block">N° Orden</small>
                                        <strong>{{ purchaseOrder.order_number }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Estado</small>
                                        <span :class="['badge', getStatusBadgeClass(purchaseOrder.status)]">
                                            {{ purchaseOrder.status_label }}
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Fecha Orden</small>
                                        <span>{{ formatDate(purchaseOrder.order_date) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Fecha Entrega</small>
                                        <span>{{ purchaseOrder.delivery_date ? formatDate(purchaseOrder.delivery_date) : 'No especificada' }}</span>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Condiciones de Pago</small>
                                        <span>{{ purchaseOrder.payment_terms || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-building me-2"></i>Proveedor
                                </h6>
                                <div class="row g-2" v-if="purchaseOrder.supplier">
                                    <div class="col-12">
                                        <small class="text-muted d-block">Nombre</small>
                                        <strong>{{ purchaseOrder.supplier.name }}</strong>
                                    </div>
                                    <div class="col-6" v-if="purchaseOrder.supplier.rut">
                                        <small class="text-muted d-block">RUT</small>
                                        <span>{{ purchaseOrder.supplier.rut }}</span>
                                    </div>
                                    <div class="col-6" v-if="purchaseOrder.supplier.contact">
                                        <small class="text-muted d-block">Contacto</small>
                                        <span>{{ purchaseOrder.supplier.contact }}</span>
                                    </div>
                                    <div class="col-6" v-if="purchaseOrder.supplier.email">
                                        <small class="text-muted d-block">Email</small>
                                        <span>{{ purchaseOrder.supplier.email }}</span>
                                    </div>
                                    <div class="col-6" v-if="purchaseOrder.supplier.phone">
                                        <small class="text-muted d-block">Teléfono</small>
                                        <span>{{ purchaseOrder.supplier.phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-2"></i>Detalle de Productos
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-start">Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-end">P. Unitario</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in purchaseOrder.items" :key="item.id">
                                        <td class="text-start">
                                            {{ item.product.name }}
                                            <small v-if="item.notes" class="text-muted d-block">{{ item.notes }}</small>
                                        </td>
                                        <td class="text-center">{{ item.quantity }}</td>
                                        <td class="text-center">{{ item.unit.name }}</td>
                                        <td class="text-end">${{ formatCurrency(item.unit_price) }}</td>
                                        <td class="text-end fw-bold">${{ formatCurrency(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                        <td class="text-end fw-bold">${{ formatCurrency(purchaseOrder.subtotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">IVA (19%):</td>
                                        <td class="text-end fw-bold">${{ formatCurrency(purchaseOrder.tax) }}</td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                                        <td class="text-end fw-bold text-primary">${{ formatCurrency(purchaseOrder.total) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="row g-3">
                    <div class="col-md-6" v-if="purchaseOrder.cost_centers && purchaseOrder.cost_centers.length > 0">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-sitemap me-2"></i>Centros de Costo
                                </h6>
                                <ul class="mb-0">
                                    <li v-for="cc in purchaseOrder.cost_centers" :key="cc.id">
                                        {{ cc.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div :class="purchaseOrder.cost_centers && purchaseOrder.cost_centers.length > 0 ? 'col-md-6' : 'col-md-12'">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-users-cog me-2"></i>Datos de Gestión
                                </h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <small class="text-muted d-block">Solicitado por</small>
                                        <strong>{{ purchaseOrder.requested_by }}</strong>
                                    </div>
                                    <div class="col-12" v-if="purchaseOrder.approved_by">
                                        <small class="text-muted d-block">Aprobado por</small>
                                        <strong>{{ purchaseOrder.approved_by }}</strong>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block">Fecha de creación</small>
                                        <span>{{ purchaseOrder.created_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas/Observaciones -->
                <div v-if="purchaseOrder.notes" class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-2">
                            <i class="fas fa-sticky-note me-2"></i>Observaciones
                        </h6>
                        <p class="mb-0 text-muted" style="white-space: pre-line;">{{ purchaseOrder.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .btn, 
    .card-header button, 
    nav,
    .sidebar,
    .navbar {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    /* Mantener colores de badges en impresión */
    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
