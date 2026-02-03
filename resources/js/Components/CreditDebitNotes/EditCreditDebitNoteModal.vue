<template>
    <div class="modal fade" id="editCreditDebitNoteModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2 text-primary"></i>Editar Nota de {{ form.type === 'credito' ? 'Crédito' : 'Débito' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Alerta informativa -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Por motivos de integridad de datos, solo puedes editar el número, fecha y motivo. Los demás campos no son modificables.
                    </div>

                    <!-- Campos de solo lectura -->
                    <div class="card mb-3 bg-light">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Información no editable</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tipo</label>
                                    <input type="text" class="form-control" :value="form.type === 'credito' ? 'Crédito' : 'Débito'" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Proveedor</label>
                                    <input type="text" class="form-control" :value="getSupplierName()" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Factura</label>
                                    <input type="text" class="form-control" :value="getInvoiceNumber()" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Afecta inventario</label>
                                    <input type="text" class="form-control" :value="form.affects_inventory ? 'Sí' : 'No'" disabled>
                                </div>
                                <div class="col-md-12" v-if="form.type === 'credito'">
                                    <label class="form-label fw-bold">Anula factura completa</label>
                                    <input type="text" class="form-control" :value="form.is_annulment ? 'Sí' : 'No'" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos (solo lectura) -->
                    <div class="card mb-3 bg-light">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td>{{ getProductName(item.product_id) }}</td>
                                            <td class="text-end">{{ formatNumber(item.quantity) }}</td>
                                            <td class="text-end">${{ formatNumber(item.unit_price) }}</td>
                                            <td class="text-end">${{ formatNumber(item.quantity * item.unit_price) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total:</td>
                                            <td class="text-end fw-bold">${{ formatNumber(calculateTotal()) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Campos editables -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Información editable</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        Número <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.number"
                                        :class="{ 'is-invalid': form.errors?.number }"
                                    >
                                    <div v-if="form.errors?.number" class="invalid-feedback">
                                        {{ form.errors.number }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        Fecha <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        v-model="form.date"
                                        :class="{ 'is-invalid': form.errors?.date }"
                                    >
                                    <div v-if="form.errors?.date" class="invalid-feedback">
                                        {{ form.errors.date }}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Motivo</label>
                                    <textarea
                                        class="form-control"
                                        v-model="form.reason"
                                        rows="3"
                                        placeholder="Describe el motivo de esta nota..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="$emit('update')" :disabled="form.processing">
                        <i :class="['fas me-1', form.processing ? 'fa-spinner fa-spin' : 'fa-save']"></i>
                        {{ form.processing ? 'Guardando...' : 'Actualizar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    form: Object,
    suppliers: Array,
    invoices: Array,
    products: Array,
    units: Array
});

defineEmits(['update']);

const getSupplierName = () => {
    const supplier = props.suppliers.find(s => s.value === props.form.supplier_id);
    return supplier ? supplier.label : 'N/A';
};

const getInvoiceNumber = () => {
    const invoice = props.invoices.find(i => i.value === props.form.invoice_id);
    return invoice ? invoice.label : 'N/A';
};

const getProductName = (productId) => {
    const product = props.products.find(p => p.value === productId);
    return product ? product.label : 'N/A';
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const calculateTotal = () => {
    if (!props.form.items || props.form.items.length === 0) return 0;
    return props.form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0));
    }, 0);
};
</script>

<style scoped>
.modal-header.bg-light {
    border-bottom: 2px solid #e9ecef;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}
</style>
