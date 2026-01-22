<script setup>
import { ref, computed, watch } from 'vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    form: Object,
    availableOrders: Array,
    availableStocksByProduct: Object,
});

const selectedOrder = ref(null);
const expandedProducts = ref({}); // Track which products are expanded

// Cuando cambia la orden seleccionada, inicializar productos
watch(() => props.form.application_order_id, (orderId) => {
    if (orderId) {
        selectedOrder.value = props.availableOrders.find(o => o.id === orderId);
        if (selectedOrder.value) {
            initializeProducts();
        }
    }
});

// Toggle product expansion
function toggleProduct(productIndex) {
    expandedProducts.value[productIndex] = !expandedProducts.value[productIndex];
}

// Inicializar productos con cantidades teóricas
function initializeProducts() {
    if (!selectedOrder.value) return;
    
    props.form.products = selectedOrder.value.order_products?.map(op => {
        const productStocks = props.availableStocksByProduct[op.product_id] || [];
        
        return {
            product_id: op.product_id,
            product_name: op.product?.name,
            unit_name: op.product?.unit?.name,
            theoretical_quantity: op.cantidad_total,
            cost_center_id: selectedOrder.value.order_cost_centers?.[0]?.cost_center_id || null,
            availableInvoices: productStocks,
            lines: [
                // Primera línea con cantidad teórica precargada
                {
                    invoice_product_id: null,
                    quantity: op.cantidad_total,
                }
            ]
        };
    }) || [];
}

// Agregar línea a un producto
function addLine(productIndex) {
    props.form.products[productIndex].lines.push({
        invoice_product_id: null,
        quantity: 0,
    });
}

// Eliminar línea de un producto
function removeLine(productIndex, lineIndex) {
    if (props.form.products[productIndex].lines.length > 1) {
        props.form.products[productIndex].lines.splice(lineIndex, 1);
    }
}

// Calcular total usado en las líneas de un producto
function getTotalUsed(product) {
    return product.lines.reduce((sum, line) => sum + parseFloat(line.quantity || 0), 0);
}

// Validar si la suma de líneas excede la cantidad real definida
function isOverLimit(product) {
    return getTotalUsed(product) > parseFloat(product.real_quantity || 0);
}

// Validar si la suma de líneas es menor a la cantidad real definida
function isUnderLimit(product) {
    const total = getTotalUsed(product);
    const realQty = parseFloat(product.real_quantity || 0);
    return realQty > 0 && total < realQty;
}

// Obtener stock disponible de una factura
function getInvoiceStock(availableInvoices, invoiceProductId) {
    if (!invoiceProductId) return 0;
    const invoice = availableInvoices.find(inv => inv.invoice_product_id === invoiceProductId);
    return invoice ? invoice.stock_disponible : 0;
}

// Validar si una línea excede el stock disponible
function isLineOverStock(product, line) {
    const stock = getInvoiceStock(product.availableInvoices, line.invoice_product_id);
    return parseFloat(line.quantity || 0) > stock;
}

// Calcular stock total disponible de un producto
function getTotalStockAvailable(product) {
    if (!product.availableInvoices || product.availableInvoices.length === 0) return 0;
    return product.availableInvoices.reduce((sum, invoice) => sum + parseFloat(invoice.stock_disponible || 0), 0);
}

// Calcular totales
const totalHectareas = computed(() => {
    if (!selectedOrder.value) return 0;
    return selectedOrder.value.order_cost_centers?.reduce((sum, occ) => {
        return sum + parseFloat(occ.cost_center?.surface || 0);
    }, 0) || 0;
});

const maquinadasTeoricas = computed(() => {
    if (!selectedOrder.value) return 0;
    const mojamiento = parseFloat(selectedOrder.value.mojamiento || 0);
    const volume = parseFloat(selectedOrder.value.volume || 0);
    const hectareas = totalHectareas.value;
    
    if (!mojamiento || !volume || !hectareas) return 0;
    return ((mojamiento * hectareas) / volume).toFixed(2);
});

// Calcular variación
function calculateVariance(theoretical, real) {
    if (!theoretical || !real) return 0;
    return (((real - theoretical) / theoretical) * 100).toFixed(2);
}
</script>

<template>
    <div>
        <!-- Selección de Orden -->
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label fw-bold">Orden de Aplicación *</label>
                <select 
                    v-model="form.application_order_id" 
                    class="form-select"
                    :class="{ 'is-invalid': form.errors.application_order_id }"
                >
                    <option :value="null">Seleccione una orden...</option>
                    <option v-for="order in availableOrders" :key="order.id" :value="order.id">
                        Orden #{{ order.id }} - {{ new Date(order.date).toLocaleDateString('es-ES') }} 
                        ({{ order.order_products?.length || 0 }} productos)
                    </option>
                </select>
                <div v-if="form.errors.application_order_id" class="invalid-feedback">
                    {{ form.errors.application_order_id }}
                </div>
            </div>
        </div>

        <div v-if="selectedOrder">
            <!-- Resumen de la orden -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label text-muted small">Hectáreas Totales</label>
                    <div class="fw-bold">{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ha</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Mojamiento</label>
                    <div class="fw-bold">{{ selectedOrder.mojamiento }} L/ha</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Volumen Tanque</label>
                    <div class="fw-bold">{{ selectedOrder.volume }} L</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Maquinadas Teóricas</label>
                    <div class="fw-bold text-primary">{{ maquinadasTeoricas }}</div>
                </div>
            </div>

            <hr class="my-3">

            <!-- Datos de aplicación real -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fecha de Aplicación *</label>
                    <input 
                        v-model="form.date" 
                        type="date" 
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.date }"
                    />
                    <div v-if="form.errors.date" class="invalid-feedback">
                        {{ form.errors.date }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">Maquinadas Reales *</label>
                    <input 
                        v-model.number="form.maquinadas" 
                        type="number" 
                        step="0.01"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.maquinadas }"
                        placeholder="Ej: 5.5"
                    />
                    <div v-if="form.errors.maquinadas" class="invalid-feedback">
                        {{ form.errors.maquinadas }}
                    </div>
                    <small v-if="form.maquinadas && maquinadasTeoricas" class="text-muted">
                        Variación: {{ calculateVariance(parseFloat(maquinadasTeoricas), form.maquinadas) }}%
                    </small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Observaciones</label>
                    <textarea 
                        v-model="form.observations" 
                        class="form-control"
                        rows="2"
                        placeholder="Comentarios adicionales..."
                    ></textarea>
                </div>
            </div>

            <!-- Productos -->
            <div class="mt-4">
                <!-- Centros de Costo -->
                <div v-if="selectedOrder && selectedOrder.order_cost_centers?.length > 0" class="mb-3">
                    <h6 class="mb-2">Centros de Costo de la Orden</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span 
                            v-for="(occ, idx) in selectedOrder.order_cost_centers" 
                            :key="idx"
                            class="badge bg-primary"
                        >
                            {{ occ.cost_center?.name }} 
                            <small>({{ occ.cost_center?.surface?.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha)</small>
                        </span>
                    </div>
                </div>

                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Debe registrar TODOS los productos de la orden ({{ form.products.length }} productos). 
                    Los productos se mezclan en el tanque y se aplican juntos.
                </div>
                
                <h6 class="mb-3">Productos a Aplicar ({{ form.products.length }})</h6>
                
                <div v-for="(product, idx) in form.products" :key="idx" class="mb-3">
                    <div class="card">
                        <div 
                            class="card-header bg-light d-flex justify-content-between align-items-center"
                            style="cursor: pointer;"
                            @click="toggleProduct(idx)"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i 
                                    class="fas"
                                    :class="expandedProducts[idx] ? 'fa-chevron-down' : 'fa-chevron-right'"
                                ></i>
                                <strong>{{ product.product_name }}</strong>
                            </div>
                            <div v-if="product.real_quantity > 0">
                                <span 
                                    class="badge"
                                    :class="{
                                        'bg-danger': isOverLimit(product),
                                        'bg-warning text-dark': isUnderLimit(product),
                                        'bg-success': getTotalUsed(product) > 0 && !isOverLimit(product) && !isUnderLimit(product),
                                        'bg-secondary': getTotalUsed(product) === 0
                                    }"
                                >
                                    {{ getTotalUsed(product).toLocaleString('es-ES', {minimumFractionDigits: 2}) }} / {{ parseFloat(product.real_quantity).toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ product.unit_name }}
                                </span>
                            </div>
                            <div v-else>
                                <span class="badge bg-secondary">Pendiente</span>
                            </div>
                        </div>
                        
                        <div class="collapse" :class="{ 'show': expandedProducts[idx] }">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1 fw-bold">Cantidad Teórica</label>
                                        <div class="text-muted">
                                            {{ product.theoretical_quantity?.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ product.unit_name }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1 fw-bold">Cantidad Real Total a Usar <span class="text-danger">*</span></label>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            v-model.number="product.real_quantity"
                                            step="0.01"
                                            min="0"
                                            placeholder="Cantidad a aplicar"
                                        />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1 fw-bold">Suma de Cantidades Parciales</label>
                                        <div 
                                            class="fw-bold"
                                            :class="{
                                                'text-danger': isOverLimit(product),
                                                'text-warning': isUnderLimit(product),
                                                'text-success': getTotalUsed(product) > 0 && !isOverLimit(product) && !isUnderLimit(product),
                                                'text-muted': getTotalUsed(product) === 0
                                            }"
                                        >
                                            {{ getTotalUsed(product).toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ product.unit_name }}
                                        </div>
                                        <small 
                                            v-if="getTotalUsed(product) > 0 && product.theoretical_quantity"
                                            :class="{
                                                'text-success': calculateVariance(product.theoretical_quantity, getTotalUsed(product)) >= 0,
                                                'text-danger': calculateVariance(product.theoretical_quantity, getTotalUsed(product)) < 0
                                            }"
                                        >
                                            Variación: {{ calculateVariance(product.theoretical_quantity, getTotalUsed(product)) }}%
                                        </small>
                                        <small v-else class="text-muted">Ingrese las cantidades parciales abajo</small>
                                    </div>
                                </div>

                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60%;">Factura Origen *</th>
                                            <th style="width: 25%;">Cantidad Parcial *</th>
                                            <th style="width: 15%;" class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(line, lineIdx) in product.lines" :key="lineIdx">
                                            <td>
                                                <select 
                                                    v-model="line.invoice_product_id" 
                                                    class="form-select form-select-sm"
                                                >
                                                    <option :value="null">Seleccione factura...</option>
                                                    <option 
                                                        v-for="invoice in product.availableInvoices" 
                                                        :key="invoice.invoice_product_id" 
                                                        :value="invoice.invoice_product_id"
                                                    >
                                                        {{ invoice.number_document }} - {{ invoice.supplier }} 
                                                        (Stock: {{ invoice.stock_disponible.toLocaleString('es-ES') }} {{ invoice.unit }})
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <input 
                                                    v-model.number="line.quantity" 
                                                    type="number" 
                                                    step="0.01"
                                                    class="form-control form-control-sm"
                                                    :class="{ 'is-invalid': isLineOverStock(product, line) }"
                                                    placeholder="Cantidad"
                                                    :max="getInvoiceStock(product.availableInvoices, line.invoice_product_id)"
                                                />
                                                <small v-if="isLineOverStock(product, line)" class="text-danger">
                                                    ⚠️ Excede stock disponible ({{ getInvoiceStock(product.availableInvoices, line.invoice_product_id).toLocaleString('es-ES') }} {{ product.unit_name }})
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <button 
                                                    v-if="product.lines.length > 1"
                                                    type="button"
                                                    @click="removeLine(idx, lineIdx)"
                                                    class="btn btn-sm btn-danger"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button 
                                    type="button"
                                    @click="addLine(idx)"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="fas fa-plus"></i> Agregar otra factura
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="alert alert-warning">
            Por favor seleccione una orden de aplicación para continuar
        </div>
    </div>
</template>
