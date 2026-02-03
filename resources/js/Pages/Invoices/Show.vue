<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    invoice: Object,
    supplier: Object,
    companyReason: Object,
    typeDocument: Object,
    invoiceProducts: Array,
    grant_total: String
});

const title = 'Detalle de Factura';
const links = [
    { title: 'Tablero', link: 'dashboard' }, 
    { title: 'Facturas', link: 'invoices.index' }, 
    { title: title, active: true }
];

// Calcular el total neto de la factura
const totalNeto = computed(() => {
    if (!props.invoiceProducts || props.invoiceProducts.length === 0) return 0;
    return props.invoiceProducts.reduce((sum, item) => {
        return sum + (parseFloat(item.amount) * parseFloat(item.unit_price));
    }, 0);
});

// Calcular IVA (19%) solo si es factura
const totalIva = computed(() => {
    const tipoDoc = props.typeDocument?.name?.toLowerCase() || '';
    if (tipoDoc === 'factura') {
        return totalNeto.value * 0.19;
    }
    return 0;
});

// Calcular total general (neto + IVA)
const totalGeneral = computed(() => {
    return totalNeto.value + totalIva.value;
});

// Helpers de formateo
const formatCLP = (value) => {
    if (!value && value !== 0) return '$0';
    return '$' + parseFloat(value).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
};

const formatNumber = (value) => {
    if (!value && value !== 0) return '0';
    return parseFloat(value).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(date);
};
</script>

<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <Breadcrumb :links="links" />
        
        <div class="my-3">
            <!-- Header Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row flex-between-center">
                        <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                <i class="fas fa-file-invoice me-2 text-primary"></i>
                                Factura #{{ invoice.number_document }}
                            </h5>
                        </div>
                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                            <button @click="$inertia.visit(route('invoices.index'))" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body bg-body-tertiary">
                    <!-- Información Principal -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Información General
                                    </h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tipo Documento</small>
                                            <span class="badge bg-primary">{{ typeDocument?.name || '-' }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Número</small>
                                            <strong>{{ invoice.number_document }}</strong>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">Proveedor</small>
                                            <strong>{{ supplier?.name || '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fecha Emisión</small>
                                            <span>{{ formatDate(invoice.date) }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fecha Vencimiento</small>
                                            <span>{{ formatDate(invoice.due_date) }}</span>
                                        </div>
                                        <div class="col-12" v-if="invoice.month">
                                            <small class="text-muted d-block">Mes Contable</small>
                                            <span>{{ invoice.month }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="fas fa-building me-2"></i>Datos Adicionales
                                    </h6>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Razón Social</small>
                                            <strong>{{ companyReason?.name || '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tipo de Pago</small>
                                            <span :class="['badge', invoice.payment_type == 1 ? 'bg-success' : 'bg-info']">
                                                {{ invoice.payment_type == 1 ? 'Crédito' : 'Contado' }}
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Plazo</small>
                                            <span>{{ invoice.payment_term || '-' }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Caja Chica</small>
                                            <span :class="['badge', invoice.petty_cash ? 'bg-success' : 'bg-secondary']">
                                                {{ invoice.petty_cash ? 'Sí' : 'No' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Productos -->
                    <div class="card">
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
                                            <th class="text-end">Precio Unitario</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(product, index) in invoiceProducts" :key="index">
                                            <td class="text-start">{{ product.product_name || '-' }}</td>
                                            <td class="text-center">{{ formatNumber(product.amount) }}</td>
                                            <td class="text-end">{{ formatCLP(product.unit_price) }}</td>
                                            <td class="text-end fw-bold">{{ formatCLP(product.amount * product.unit_price) }}</td>
                                        </tr>
                                        <tr v-if="!invoiceProducts || invoiceProducts.length === 0">
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <i class="fas fa-inbox me-2"></i>No hay productos registrados
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">TOTAL NETO:</td>
                                            <td class="text-end fw-bold">{{ formatCLP(totalNeto) }}</td>
                                        </tr>
                                        <tr v-if="totalIva > 0">
                                            <td colspan="3" class="text-end fw-bold">IVA (19%):</td>
                                            <td class="text-end fw-bold">{{ formatCLP(totalIva) }}</td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-end fw-bold fs-6">TOTAL GENERAL:</td>
                                            <td class="text-end fw-bold fs-6 text-primary">{{ formatCLP(totalGeneral) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
