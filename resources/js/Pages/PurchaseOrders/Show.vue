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
</script>

<template>
    <Head :title="`Orden de Compra ${purchaseOrder.order_number}`" />
    <AppLayout>
        <!-- Header corporativo solo visible al imprimir -->
        <div class="print-header d-none">
            <div class="print-header-inner">
                <div class="print-header-brand">
                    <span class="print-header-logo">⬡</span>
                    <div>
                        <div class="print-header-title">Alisoft</div>
                        <div class="print-header-subtitle">Software de Gestión Agrícola</div>
                    </div>
                </div>
                <div class="print-header-doc">
                    <div class="print-header-doc-number">Orden de Compra #{{ purchaseOrder.order_number }}</div>
                    <div class="print-header-doc-date">Emitido: {{ new Date().toLocaleDateString('es-CL') }}</div>
                </div>
            </div>
        </div>

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
                        <a :href="route('purchase-orders.pdf', purchaseOrder.id)" target="_blank" class="btn btn-falcon-default btn-sm">
                            <span class="fas fa-print" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Imprimir</span>
                        </a>
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
                                    <div class="col-12">
                                        <small class="text-muted d-block">Razón Social (empresa pagadora)</small>
                                        <template v-if="purchaseOrder.company_reason">
                                            <strong class="text-primary">{{ purchaseOrder.company_reason.name }}</strong>
                                            <span v-if="purchaseOrder.company_reason.rut" class="text-muted ms-1 small">
                                                ({{ purchaseOrder.company_reason.rut }})
                                            </span>
                                        </template>
                                        <span v-else class="text-muted">No asignada</span>
                                    </div>
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
                                    <span class="badge bg-secondary ms-2" style="font-size:0.7rem;">{{ purchaseOrder.cost_centers.length }}</span>
                                </h6>
                                <div class="row g-0 border rounded" style="font-size:0.72rem;">
                                    <div
                                        v-for="(cc, idx) in purchaseOrder.cost_centers"
                                        :key="cc.id"
                                        class="col-3 px-2 py-1"
                                        :class="{
                                            'border-end': (idx + 1) % 4 !== 0,
                                            'border-top': idx >= 4,
                                            'bg-light': Math.floor(idx / 4) % 2 === 1,
                                        }"
                                    >
                                        {{ cc.name }}
                                    </div>
                                </div>
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
        <!-- Footer corporativo solo visible al imprimir -->
        <div class="print-footer d-none">
            <div class="print-footer-line"></div>
            <div class="print-footer-inner">
                <span>Alisoft &mdash; Software de Gestión Agrícola</span>
                <span class="print-footer-sep">|</span>
                <span>Documento generado el {{ new Date().toLocaleDateString('es-CL', { year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
                <span class="print-footer-sep">|</span>
                <span>Este documento es de uso interno y confidencial</span>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .btn,
    .card-header button,
    .card-header {
        display: none !important;
    }

    /* Cards sin sombra y con padding reducido */
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        margin-bottom: 6px !important;
    }

    .card-body {
        padding: 6px 8px !important;
    }

    /* Textos más pequeños */
    h4, h5, h6, .card-title {
        font-size: 0.82rem !important;
        margin-bottom: 4px !important;
    }

    p, li, span, td, th, small, strong, label {
        font-size: 0.75rem !important;
        line-height: 1.3 !important;
    }

    /* Reducir gaps y márgenes de rows */
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .g-3, .g-2, .g-1 {
        --bs-gutter-x: 6px !important;
        --bs-gutter-y: 6px !important;
    }

    .mb-3, .mb-4 { margin-bottom: 6px !important; }
    .mt-3, .mt-4 { margin-top: 6px !important; }
    .py-1 { padding-top: 2px !important; padding-bottom: 2px !important; }

    /* Mantener colores de badges en impresión */
    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        font-size: 0.65rem !important;
        padding: 2px 5px !important;
    }

    /* Tabla de ítems más compacta */
    table { font-size: 0.7rem !important; }
    th, td { padding: 3px 5px !important; }
}
</style>

<!-- Estilos globales de impresión: afectan al AppLayout (navbar, sidebar, topbar) -->
<style>
@media print {
    @page { margin: 8mm 10mm; }

    body, html { font-size: 10px !important; }

    /* Ocultar sidebar vertical, topbar y cualquier nav del layout */
    .navbar-vertical,
    .navbar-top,
    .navbar-glass,
    nav.navbar,
    .sidebar,
    .sidebar-hidden,
    .navbar-toggler,
    footer {
        display: none !important;
    }

    /* Quitar el margen izquierdo que Bootstrap aplica para el sidebar */
    .content,
    main,
    #main-content,
    .main-content,
    [class*="content-"] {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* ── Header corporativo ── */
    .print-header {
        display: block !important;
        margin-bottom: 8px;
    }

    .print-header-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0 6px 0;
        border-bottom: 2px solid #1a6b3a;
    }

    .print-header-brand {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .print-header-logo {
        font-size: 1.6rem;
        color: #1a6b3a;
        line-height: 1;
    }

    .print-header-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1a6b3a;
        letter-spacing: 0.04em;
        line-height: 1.1;
    }

    .print-header-subtitle {
        font-size: 0.62rem;
        color: #555;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .print-header-doc {
        text-align: right;
    }

    .print-header-doc-number {
        font-size: 0.88rem;
        font-weight: 700;
        color: #222;
    }

    .print-header-doc-date {
        font-size: 0.65rem;
        color: #777;
    }

    /* ── Footer corporativo ── */
    .print-footer {
        display: block !important;
        margin-top: 10px;
    }

    .print-footer-line {
        height: 1.5px;
        background: #1a6b3a;
        margin-bottom: 4px;
    }

    .print-footer-inner {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        font-size: 0.62rem;
        color: #666;
        text-align: center;
        flex-wrap: wrap;
    }

    .print-footer-sep {
        color: #1a6b3a;
        font-weight: bold;
    }
}
</style>
