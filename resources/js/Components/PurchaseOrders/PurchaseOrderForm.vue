<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    order: Object,
    suppliers: Array,
    companyReasons: Array,
    costCenters: Array,
    groupings: Array,
    products: Array,
    units: Array,
    approvers: Array,
    isEditing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

// Agrupación para autocompletar centros de costo
const selectedGrouping = ref(null);

// Centros de costo seleccionados
const selectedCostCenters = ref(props.order?.cost_centers ? props.order.cost_centers.map(cc => cc.id) : []);

// Watch para cuando se selecciona una agrupación, autocompletar los centros de costo
watch(selectedGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        const groupCCIds = grouping.cost_centers.map(cc => cc.id);
        selectedCostCenters.value = groupCCIds;
    }
});

// Nuevo item para agregar desde el card
const newItem = ref({
    product_id: '',
    quantity: '',
    unit_id: '',
    unit_price: '',
    notes: ''
});

// Form
const form = useForm({
    supplier_id: props.order?.supplier.id || '',
    company_reason_id: props.order?.company_reason_id || '',
    assigned_to: props.order?.assigned_to || '',
    cost_center_ids: props.order?.cost_centers ? props.order.cost_centers.map(cc => cc.id) : [],
    order_date: props.order ? formatDateForInput(props.order.order_date) : new Date().toISOString().split('T')[0],
    delivery_date: props.order && props.order.delivery_date ? formatDateForInput(props.order.delivery_date) : '',
    payment_terms: props.order?.payment_terms || '',
    notes: props.order?.notes || '',
    items: props.order?.items ? props.order.items.map(item => ({
        product_id: item.product.id,
        quantity: item.quantity,
        unit_id: item.unit.id,
        unit_price: item.unit_price,
        notes: item.notes || ''
    })) : []
});

// Sincronizar selectedCostCenters con form.cost_center_ids
watch(selectedCostCenters, (newVal) => {
    form.cost_center_ids = newVal;
});

function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    // Si ya está en formato yyyy-mm-dd, retornar tal cual
    if (/^\d{4}-\d{2}-\d{2}/.test(dateString)) {
        return dateString.split('T')[0]; // Quitar parte de hora si existe
    }
    
    // Si está en formato dd-mm-yyyy, convertir
    const parts = dateString.split('-');
    if (parts.length === 3 && parts[0].length === 2) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`; // dd-mm-yyyy to yyyy-mm-dd
    }
    
    return dateString;
}

// Agregar/Eliminar items
function addItemFromCard() {
    if (!newItem.value.product_id) {
        Swal.fire('Atención', 'Debe seleccionar un producto', 'warning');
        return;
    }
    if (!newItem.value.quantity || newItem.value.quantity <= 0) {
        Swal.fire('Atención', 'Debe ingresar la cantidad', 'warning');
        return;
    }
    if (!newItem.value.unit_id) {
        Swal.fire('Atención', 'Debe seleccionar la unidad', 'warning');
        return;
    }
    if (newItem.value.unit_price === '' || newItem.value.unit_price < 0) {
        Swal.fire('Atención', 'Debe ingresar el precio unitario', 'warning');
        return;
    }

    form.items.push({ ...newItem.value });

    // Resetear
    newItem.value = {
        product_id: '',
        quantity: '',
        unit_id: '',
        unit_price: '',
        notes: ''
    };
}

function onNewItemProductChange(productId) {
    const product = props.products.find(p => p.value === productId);
    if (product && product.unit_id) {
        newItem.value.unit_id = product.unit_id;
    }
}

function calculateNewItemSubtotal() {
    const qty = parseFloat(newItem.value.quantity) || 0;
    const price = parseFloat(newItem.value.unit_price) || 0;
    return qty * price;
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function getProductName(productId) {
    const product = props.products.find(p => p.value === productId);
    return product?.label || '';
}

function getUnitName(unitId) {
    const unit = props.units.find(u => u.value === unitId);
    return unit?.label || '';
}

// Cuando se selecciona un producto, auto-completar su unidad

function calculateItemSubtotal(item) {
    const qty = parseFloat(item.quantity) || 0;
    const price = parseFloat(item.unit_price) || 0;
    return qty * price;
}

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + calculateItemSubtotal(item), 0);
});

const tax = computed(() => {
    return subtotal.value * 0.19;
});

const total = computed(() => {
    return subtotal.value + tax.value;
});

// Submit
function submit() {
    // Validaciones básicas
    if (!form.supplier_id) {
        Swal.fire('Atención', 'Debe seleccionar un proveedor', 'warning');
        return;
    }

    if (!form.order_date) {
        Swal.fire('Atención', 'Debe ingresar la fecha de orden', 'warning');
        return;
    }

    if (form.items.length === 0) {
        Swal.fire('Atención', 'Debe agregar al menos un producto', 'warning');
        return;
    }

    // Validar que todos los items tengan datos
    const invalidItem = form.items.find(item => 
        !item.product_id || !item.quantity || !item.unit_id || item.unit_price === ''
    );

    if (invalidItem) {
        Swal.fire('Atención', 'Complete todos los campos de los productos', 'warning');
        return;
    }

    const action = props.isEditing ? 'actualizada' : 'creada';

    if (props.isEditing) {
        form.put(route('purchase-orders.update', props.order.id), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: `Orden ${action} exitosamente`,
                    showConfirmButton: false,
                    timer: 1500
                });
                emit('close');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                Swal.fire('Error', Object.values(errors)[0] || 'Ocurrió un error al guardar', 'error');
            }
        });
    } else {
        form.post(route('purchase-orders.store'), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: `Orden ${action} exitosamente`,
                    showConfirmButton: false,
                    timer: 1500
                });
                form.reset();
                selectedCostCenters.value = [];
                selectedGrouping.value = null;
                emit('close');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);
                Swal.fire('Error', Object.values(errors)[0] || 'Ocurrió un error al guardar', 'error');
            }
        });
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
}

// Exponer form y submit al componente padre (modal)
defineExpose({ form, submit });
</script>

<template>
    <div class="container-fluid">
        <!-- Información General -->
        <div class="row mb-2">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-1 mb-2">
                    <i class="fas fa-info-circle me-1"></i>Información General
                </h6>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small mb-1">Proveedor <span class="text-danger">*</span></label>
                <Multiselect
                    v-model="form.supplier_id"
                    :options="suppliers"
                    :searchable="true"
                    :close-on-select="true"
                    placeholder="Seleccione un proveedor..."
                    class="multiselect-blue form-control-sm"
                />
            </div>

            <div class="col-md-6 mb-2">
                <label class="form-label small mb-1">
                    <i class="fas fa-building me-1"></i>Razón Social <span class="text-muted small">(empresa pagadora)</span>
                </label>
                <Multiselect
                    v-model="form.company_reason_id"
                    :options="companyReasons || []"
                    :searchable="true"
                    :close-on-select="true"
                    placeholder="Seleccione una razón social..."
                    class="multiselect-blue form-control-sm"
                />
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small mb-1">
                    <i class="fas fa-user-check me-1"></i>Asignar Aprobador
                </label>
                <Multiselect
                    v-model="form.assigned_to"
                    :options="approvers"
                    :searchable="true"
                    :close-on-select="true"
                    placeholder="Sin asignar"
                    class="multiselect-blue form-control-sm"
                />
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Usuario que recibirá notificación para aprobar
                </small>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <label class="form-label small mb-1">Fecha Orden <span class="text-danger">*</span></label>
                <input 
                    v-model="form.order_date" 
                    type="date" 
                    class="form-control form-control-sm" 
                    required
                >
            </div>

            <div class="col-md-4 mb-2">
                <label class="form-label small mb-1">Fecha Entrega</label>
                <input 
                    v-model="form.delivery_date" 
                    type="date" 
                    class="form-control form-control-sm"
                >
            </div>

            <div class="col-md-4 mb-2">
                <label class="form-label small mb-1">Condiciones de Pago</label>
                <input 
                    v-model="form.payment_terms" 
                    type="text" 
                    class="form-control form-control-sm"
                    placeholder="Ej: 30 días, contado"
                >
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-12 mb-2">
                <label class="form-label small mb-1">Observaciones</label>
                <textarea 
                    v-model="form.notes" 
                    class="form-control form-control-sm" 
                    rows="2"
                    placeholder="Notas adicionales..."
                ></textarea>
            </div>
        </div>

        <hr class="my-2">

        <!-- Centros de Costo -->
        <div class="row mb-2">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-1 mb-2">
                    <i class="fas fa-map-marker-alt me-1"></i>Centros de Costo
                </h6>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-4 mb-2" v-if="groupings && groupings.length > 0">
                <label class="form-label small mb-1">
                    <i class="fas fa-layer-group me-1"></i>Agrupación (Preselección rápida)
                </label>
                <select 
                    v-model="selectedGrouping" 
                    class="form-select form-select-sm"
                >
                    <option :value="null" disabled selected>Seleccione agrupación...</option>
                    <option v-for="g in groupings" :key="g.id" :value="g.id">
                        {{ g.name }}
                    </option>
                </select>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Preselección rápida de centros
                </small>
            </div>

            <div :class="groupings && groupings.length > 0 ? 'col-md-8' : 'col-md-12'" class="mb-2">
                <label class="form-label small mb-1">Seleccionar Centros de Costo</label>
                <Multiselect
                    v-model="selectedCostCenters"
                    :options="costCenters"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    :create-option="false"
                    placeholder="Seleccione centros de costo..."
                    class="multiselect-blue form-control-sm"
                />
                <small class="text-muted d-block mt-1">
                    Puede seleccionar múltiples centros de costo o dejar vacío
                </small>
            </div>
        </div>

        <hr class="my-2">

        <!-- Productos -->
        <div class="row mb-2">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-1 mb-2">
                    <i class="fas fa-boxes me-1"></i>Productos
                </h6>
            </div>
        </div>

        <!-- Card para agregar productos -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header py-2 bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="small">
                        <i class="fas fa-plus-circle me-1"></i>Agregar Producto
                    </strong>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row mb-2">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small mb-1">Producto <span class="text-danger">*</span></label>
                        <select
                            v-model="newItem.product_id"
                            class="form-select form-select-sm"
                            @change="onNewItemProductChange(newItem.product_id)"
                        >
                            <option value="" disabled selected>Seleccione un producto...</option>
                            <option v-for="product in products" :key="product.value" :value="product.value">
                                {{ product.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label small mb-1">Cantidad <span class="text-danger">*</span></label>
                        <input 
                            v-model="newItem.quantity" 
                            type="number" 
                            step="0.001"
                            min="0.001"
                            class="form-control form-control-sm text-end" 
                            placeholder="0"
                        >
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label small mb-1">Unidad <span class="text-danger">*</span></label>
                        <select v-model="newItem.unit_id" class="form-select form-select-sm">
                            <option value="" disabled selected>Unidad</option>
                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                {{ unit.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label small mb-1">P. Unitario <span class="text-danger">*</span></label>
                        <input 
                            v-model="newItem.unit_price" 
                            type="number" 
                            step="1"
                            min="0"
                            class="form-control form-control-sm text-end" 
                            placeholder="$"
                        >
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" @click="addItemFromCard" class="btn btn-sm btn-success w-100">
                            <i class="fas fa-plus me-1"></i>Agregar
                        </button>
                    </div>
                </div>
                <div class="row mb-1" v-if="newItem.product_id && newItem.quantity && newItem.unit_price">
                    <div class="col-md-12">
                        <div class="alert alert-info py-1 mb-0">
                            <small>
                                <i class="fas fa-calculator me-1"></i>
                                Subtotal: <strong>${{ formatCurrency(calculateNewItemSubtotal()) }}</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de productos agregados -->
        <div v-if="form.items.length > 0" class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small" style="width: 35%">Producto</th>
                        <th class="small text-end" style="width: 12%">Cantidad</th>
                        <th class="small" style="width: 12%">Unidad</th>
                        <th class="small text-end" style="width: 15%">P. Unitario</th>
                        <th class="small text-end" style="width: 18%">Subtotal</th>
                        <th class="small text-center" style="width: 8%">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in form.items" :key="index">
                        <td class="small align-middle">{{ getProductName(item.product_id) }}</td>
                        <td class="small text-end align-middle">{{ parseFloat(item.quantity).toLocaleString('es-ES', {minimumFractionDigits: 0, maximumFractionDigits: 3}) }}</td>
                        <td class="small align-middle">{{ getUnitName(item.unit_id) }}</td>
                        <td class="small text-end align-middle">${{ formatCurrency(item.unit_price) }}</td>
                        <td class="small text-end align-middle fw-semibold">${{ formatCurrency(calculateItemSubtotal(item)) }}</td>
                        <td class="text-center align-middle">
                            <button 
                                type="button" 
                                @click="removeItem(index)"
                                class="btn btn-sm btn-light-danger"
                                title="Eliminar"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="text-center text-muted py-3 border rounded mb-3">
            <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
            <small>No hay productos agregados. Use el formulario de arriba para agregar.</small>
        </div>

        <!-- Totales -->
        <div class="row justify-content-end mb-3">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body py-2 px-3">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-end fw-semibold small">Subtotal:</td>
                                <td class="text-end small">${{ formatCurrency(subtotal) }}</td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold small">IVA (19%):</td>
                                <td class="text-end small">${{ formatCurrency(tax) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold fs-5 text-primary">${{ formatCurrency(total) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.btn-light-danger {
    background-color: #fee;
    color: #c00;
    border: none;
}

.btn-light-danger:hover {
    background-color: #fcc;
}
</style>
