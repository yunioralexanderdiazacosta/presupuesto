<template>
    <AppLayout title="Detalle Nota">
        <div class="my-3">
            <!-- Header Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row flex-between-center">
                        <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                                <i :class="['fas me-2', note.type?.toLowerCase() === 'credito' || note.type?.toLowerCase() === 'nc' ? 'fa-minus-circle text-warning' : 'fa-plus-circle text-info']"></i>
                                Nota de {{ note.type?.toLowerCase() === 'credito' || note.type?.toLowerCase() === 'nc' ? 'Crédito' : 'Débito' }} #{{ note.number }}
                            </h5>
                        </div>
                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                            <button @click="$inertia.visit(route('credit_debit_notes.index'))" class="btn btn-falcon-default btn-sm">
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
                                            <small class="text-muted d-block">Tipo</small>
                                            <span :class="['badge', note.type?.toLowerCase() === 'credito' || note.type?.toLowerCase() === 'nc' ? 'bg-warning' : 'bg-info']">
                                                {{ note.type?.toLowerCase() === 'credito' || note.type?.toLowerCase() === 'nc' ? 'Crédito' : 'Débito' }}
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Número</small>
                                            <strong>{{ note.number }}</strong>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">Proveedor</small>
                                            <strong>{{ supplier?.name || '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fecha</small>
                                            <span>{{ formatDate(note.date) }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Mes Contable</small>
                                            <span>{{ note.month?.name || '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="fas fa-file-invoice me-2"></i>Factura Relacionada
                                    </h6>
                                    <div v-if="invoice" class="row g-2">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Número de Factura</small>
                                            <strong>{{ invoice.number_document || '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Fecha Factura</small>
                                            <span>{{ formatDate(invoice.date) }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Mes Contable</small>
                                            <span>{{ invoice.month?.name || '-' }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Sin factura asociada
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="note.reason" class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-2">
                                        <i class="fas fa-comment-alt me-2"></i>Motivo
                                    </h6>
                                    <p class="mb-0">{{ note.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Items -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>Detalle de Items
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-start">Producto</th>
                                            <th class="text-center">Unidad</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Precio Unitario</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in items" :key="index">
                                            <td class="text-start">{{ item.product_name || '-' }}</td>
                                            <td class="text-center">{{ item.unit_name || '-' }}</td>
                                            <td class="text-end">{{ formatNumber(item.quantity) }}</td>
                                            <td class="text-end">{{ formatCLP(item.unit_price) }}</td>
                                            <td class="text-end fw-bold">{{ formatCLP(item.quantity * item.unit_price) }}</td>
                                        </tr>
                                        <tr v-if="items.length === 0">
                                            <td colspan="5" class="text-center text-muted py-3">
                                                <i class="fas fa-inbox me-2"></i>No hay items registrados
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                                            <td class="text-end fw-bold fs-6">{{ formatCLP(totalAmount) }}</td>
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

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({ 
    note: Object, 
    supplier: Object, 
    invoice: Object, 
    items: Array 
});

// Calcular el total de la nota
const totalAmount = computed(() => {
    if (!props.items || props.items.length === 0) return 0;
    return props.items.reduce((sum, item) => {
        return sum + (item.quantity * item.unit_price);
    }, 0);
});

// Formatear moneda
const formatCLP = (value) => {
    if (!value && value !== 0) return '$0';
    return '$' + parseFloat(value).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
};

// Formatear números
const formatNumber = (value) => {
    if (!value && value !== 0) return '0';
    return parseFloat(value).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
};

// Formatear fechas
const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('es-CL', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    });
};
</script>
