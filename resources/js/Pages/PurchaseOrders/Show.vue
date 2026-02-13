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
                    <div class="col-auto">
                        <h5 class="fs-9 mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Orden de Compra {{ purchaseOrder.order_number }}
                        </h5>
                    </div>
                    <div class="col-auto">
                        <Link 
                            :href="route('purchase-orders.index')" 
                            class="btn btn-falcon-default btn-sm me-2"
                        >
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </Link>
                        <button @click="printOrder" class="btn btn-falcon-default btn-sm">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted mb-3">Información de la Orden</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="fw-semibold" style="width: 40%;">N° Orden:</td>
                                <td>{{ purchaseOrder.order_number }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Fecha Orden:</td>
                                <td>{{ formatDate(purchaseOrder.order_date) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Fecha Entrega:</td>
                                <td>{{ purchaseOrder.delivery_date ? formatDate(purchaseOrder.delivery_date) : 'No especificada' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Estado:</td>
                                <td>
                                    <span :class="['badge', getStatusBadgeClass(purchaseOrder.status)]">
                                        {{ purchaseOrder.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Condiciones Pago:</td>
                                <td>{{ purchaseOrder.payment_terms || '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted mb-3">Proveedor</h6>
                        <table class="table table-sm table-borderless" v-if="purchaseOrder.supplier">
                            <tr>
                                <td class="fw-semibold" style="width: 40%;">Nombre:</td>
                                <td>{{ purchaseOrder.supplier.name }}</td>
                            </tr>
                            <tr v-if="purchaseOrder.supplier.rut">
                                <td class="fw-semibold">RUT:</td>
                                <td>{{ purchaseOrder.supplier.rut }}</td>
                            </tr>
                            <tr v-if="purchaseOrder.supplier.contact">
                                <td class="fw-semibold">Contacto:</td>
                                <td>{{ purchaseOrder.supplier.contact }}</td>
                            </tr>
                            <tr v-if="purchaseOrder.supplier.email">
                                <td class="fw-semibold">Email:</td>
                                <td>{{ purchaseOrder.supplier.email }}</td>
                            </tr>
                            <tr v-if="purchaseOrder.supplier.phone">
                                <td class="fw-semibold">Teléfono:</td>
                                <td>{{ purchaseOrder.supplier.phone }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Items de la Orden -->
                <h6 class="text-uppercase text-muted mb-3">Productos</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 40%;">Producto</th>
                                <th class="text-center" style="width: 15%;">Cantidad</th>
                                <th class="text-center" style="width: 10%;">Unidad</th>
                                <th class="text-end" style="width: 15%;">P. Unitario</th>
                                <th class="text-end" style="width: 20%;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in purchaseOrder.items" :key="item.id">
                                <td>
                                    {{ item.product.name }}
                                    <small v-if="item.notes" class="text-muted d-block">{{ item.notes }}</small>
                                </td>
                                <td class="text-center">{{ item.quantity }}</td>
                                <td class="text-center">{{ item.unit.name }}</td>
                                <td class="text-end">${{ formatCurrency(item.unit_price) }}</td>
                                <td class="text-end">${{ formatCurrency(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Subtotal:</td>
                                <td class="text-end">${{ formatCurrency(purchaseOrder.subtotal) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">IVA (19%):</td>
                                <td class="text-end">${{ formatCurrency(purchaseOrder.tax) }}</td>
                            </tr>
                            <tr class="table-active">
                                <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">${{ formatCurrency(purchaseOrder.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Información Adicional -->
                <div class="row">
                    <div class="col-md-6" v-if="purchaseOrder.cost_centers && purchaseOrder.cost_centers.length > 0">
                        <h6 class="text-uppercase text-muted mb-2">Centros de Costo</h6>
                        <ul class="mb-0">
                            <li v-for="cc in purchaseOrder.cost_centers" :key="cc.id">
                                {{ cc.name }}
                            </li>
                        </ul>
                    </div>
                    <div :class="purchaseOrder.cost_centers && purchaseOrder.cost_centers.length > 0 ? 'col-md-6' : 'col-md-12'">
                        <h6 class="text-uppercase text-muted mb-2">Datos de Gestión</h6>
                        <p class="mb-1"><strong>Solicitado por:</strong> {{ purchaseOrder.requested_by }}</p>
                        <p class="mb-1" v-if="purchaseOrder.approved_by">
                            <strong>Aprobado por:</strong> {{ purchaseOrder.approved_by }}
                        </p>
                        <p class="mb-1"><strong>Creado:</strong> {{ purchaseOrder.created_at }}</p>
                    </div>
                </div>

                <!-- Notas -->
                <div v-if="purchaseOrder.notes" class="mt-3">
                    <h6 class="text-uppercase text-muted mb-2">Observaciones</h6>
                    <p class="text-muted">{{ purchaseOrder.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .btn, .card-header button, nav {
        display: none !important;
    }
}
</style>
