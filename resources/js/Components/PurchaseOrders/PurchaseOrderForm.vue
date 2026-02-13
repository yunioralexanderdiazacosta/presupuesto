<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    order: Object,
    suppliers: Array,
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

// Form
const form = useForm({
    supplier_id: props.order?.supplier.id || '',
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

// Si no hay items, agregar uno vacío
if (form.items.length === 0) {
    form.items.push({
        product_id: '',
        quantity: '',
        unit_id: '',
        unit_price: '',
        notes: ''
    });
}

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
function addItem() {
    form.items.push({
        product_id: '',
        quantity: '',
        unit_id: '',
        unit_price: '',
        notes: ''
    });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    } else {
        Swal.fire('Atención', 'Debe haber al menos un producto', 'warning');
    }
}

// Cuando se selecciona un producto, auto-completar su unidad
function onProductChange(index) {
    const item = form.items[index];
    const product = props.products.find(p => p.value === item.product_id);
    if (product && product.unit_id) {
        item.unit_id = product.unit_id;
    }
}

// Calcular subtotales
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
</script>

<template>
    <form @submit.prevent="submit">
        <!-- Información General -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small">Proveedor <span class="text-danger">*</span></label>
                <select v-model="form.supplier_id" class="form-select form-select-sm" required>
                    <option value="" disabled>Seleccione un proveedor</option>
                    <option v-for="supplier in suppliers" :key="supplier.value" :value="supplier.value">
                        {{ supplier.label }}
                    </option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small">
                    <i class="fas fa-user-check me-1"></i>Asignar Aprobador
                </label>
                <select v-model="form.assigned_to" class="form-select form-select-sm">
                    <option value="">Sin asignar</option>
                    <option v-for="approver in approvers" :key="approver.value" :value="approver.value">
                        {{ approver.label }}
                    </option>
                </select>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Usuario que recibirá notificación para aprobar
                </small>
            </div>

            <div class="col-md-4" v-if="groupings && groupings.length > 0">
                <label class="form-label small">
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

            <div :class="groupings && groupings.length > 0 ? 'col-md-8' : 'col-md-12'">
                <label class="form-label small">Centros de Costo (opcional)</label>
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

            <div class="col-md-4">
                <label class="form-label small">Fecha Orden <span class="text-danger">*</span></label>
                <input 
                    v-model="form.order_date" 
                    type="date" 
                    class="form-control form-control-sm" 
                    required
                >
            </div>

            <div class="col-md-4">
                <label class="form-label small">Fecha Entrega</label>
                <input 
                    v-model="form.delivery_date" 
                    type="date" 
                    class="form-control form-control-sm"
                >
            </div>

            <div class="col-md-4">
                <label class="form-label small">Condiciones de Pago</label>
                <input 
                    v-model="form.payment_terms" 
                    type="text" 
                    class="form-control form-control-sm"
                    placeholder="Ej: 30 días, contado"
                >
            </div>

            <div class="col-12">
                <label class="form-label small">Observaciones</label>
                <textarea 
                    v-model="form.notes" 
                    class="form-control form-control-sm" 
                    rows="2"
                    placeholder="Notas adicionales..."
                ></textarea>
            </div>
        </div>

        <!-- Items de la Orden -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Productos</h6>
                <button type="button" @click="addItem" class="btn btn-sm btn-falcon-default">
                    <i class="fas fa-plus me-1"></i> Agregar Producto
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 35%">Producto</th>
                            <th style="width: 12%">Cantidad</th>
                            <th style="width: 12%">Unidad</th>
                            <th style="width: 15%">P. Unitario</th>
                            <th style="width: 18%">Subtotal</th>
                            <th style="width: 8%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td>
                                <select 
                                    v-model="item.product_id" 
                                    @change="onProductChange(index)"
                                    class="form-select form-select-sm"
                                    required
                                >
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="product in products" :key="product.value" :value="product.value">
                                        {{ product.label }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input 
                                    v-model="item.quantity" 
                                    type="number" 
                                    step="0.001"
                                    min="0.001"
                                    class="form-control form-control-sm text-end" 
                                    required
                                >
                            </td>
                            <td>
                                <select v-model="item.unit_id" class="form-select form-select-sm" required>
                                    <option value="" disabled>Unidad</option>
                                    <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                        {{ unit.label }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input 
                                    v-model="item.unit_price" 
                                    type="number" 
                                    step="1"
                                    min="0"
                                    class="form-control form-control-sm text-end" 
                                    placeholder="$"
                                    required
                                >
                            </td>
                            <td class="text-end align-middle">
                                ${{ formatCurrency(calculateItemSubtotal(item)) }}
                            </td>
                            <td class="text-center">
                                <button 
                                    type="button" 
                                    @click="removeItem(index)"
                                    class="btn btn-sm btn-light-danger"
                                    :disabled="form.items.length === 1"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="form.items.length === 0">
                            <td colspan="6" class="text-center text-muted">No hay productos agregados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totales -->
        <div class="row justify-content-end mb-3">
            <div class="col-md-5">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-end fw-semibold">Subtotal:</td>
                        <td class="text-end">${{ formatCurrency(subtotal) }}</td>
                    </tr>
                    <tr>
                        <td class="text-end fw-semibold">IVA (19%):</td>
                        <td class="text-end">${{ formatCurrency(tax) }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-end fw-bold">TOTAL:</td>
                        <td class="text-end fw-bold fs-5">${{ formatCurrency(total) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-end gap-2">
            <button 
                type="button" 
                @click="emit('close')" 
                class="btn btn-secondary btn-sm"
                :disabled="form.processing"
            >
                Cancelar
            </button>
            <button 
                type="submit" 
                class="btn btn-primary btn-sm"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                {{ isEditing ? 'Actualizar Orden' : 'Crear Orden' }}
            </button>
        </div>
    </form>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
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
