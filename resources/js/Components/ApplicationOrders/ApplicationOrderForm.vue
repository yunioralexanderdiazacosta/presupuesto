<script setup>
import { ref, computed, watch } from 'vue';
import Multiselect from '@vueform/multiselect';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    form: Object,
    products: Array,
    costCenters: Array,
    units: Array,
    groupings: Array,
    isEditing: {
        type: Boolean,
        default: false
    }
});

// ==== PRODUCTOS ====
const selectedProduct = ref(null);
const editingProductIndex = ref(null);
const newProduct = ref({
    product_id: '',
    unit_id: null,
    tipo_dosis: 'por_hectarea',
    dosis_por_100: '',
    dosis_por_hectarea: '',
    carencia: '',
    reingreso: '',
});

// Watcher para actualizar unit_id automáticamente cuando se selecciona un producto
watch(() => newProduct.value.product_id, (newProductId) => {
    if (newProductId) {
        const product = props.products.find(p => p.value === newProductId);
        if (product) {
            newProduct.value.unit_id = product.unit_id;
        }
    }
});

const totalHectareas = computed(() => {
    return props.form.cost_centers.reduce((sum, cc) => sum + Number(cc.surface || 0), 0);
});

const calculatedQuantityPerHa = computed(() => {
    if (newProduct.value.tipo_dosis === 'por_hectarea') {
        return Number(newProduct.value.dosis_por_hectarea || 0);
    } else if (newProduct.value.tipo_dosis === 'por_100_litros') {
        const hectolitros = Number(props.form.mojamiento || 0) / 100;
        return Number(newProduct.value.dosis_por_100 || 0) * hectolitros;
    }
    return 0;
});

const calculatedTotalQuantity = computed(() => {
    return calculatedQuantityPerHa.value * totalHectareas.value;
});

// Computed para vista previa simplificada
const previewSimplifiedQuantity = computed(() => {
    const cantidad = calculatedTotalQuantity.value;
    const unitId = newProduct.value.unit_id || null;
    
    // Obtener el nombre de la unidad
    let unitName = '';
    if (unitId) {
        const unit = props.units.find(u => u.value === unitId);
        unitName = unit?.label || '';
    } else {
        const prod = props.products.find(p => p.value === newProduct.value.product_id);
        unitName = prod?.unit_name || '';
    }
    
    unitName = unitName.toLowerCase();
    
    // Convertir cc a lt si es >= 1000
    if (unitName === 'cc' && cantidad >= 1000) {
        return {
            value: (cantidad / 1000).toFixed(2),
            unit: 'lt'
        };
    }
    
    // Convertir gr a kg si es >= 1000
    if (unitName === 'gr' && cantidad >= 1000) {
        return {
            value: (cantidad / 1000).toFixed(2),
            unit: 'kg'
        };
    }
    
    // No convertir, devolver original
    return {
        value: cantidad.toFixed(2),
        unit: getUnitName(unitId) || getProductUnit(newProduct.value.product_id)
    };
});

function addProduct() {
    if (!newProduct.value.product_id) {
        Swal.fire('Error', 'Debe seleccionar un producto', 'error');
        return;
    }
    
    if (newProduct.value.tipo_dosis === 'por_hectarea' && !newProduct.value.dosis_por_hectarea) {
        Swal.fire('Error', 'Debe ingresar la dosis por hectárea', 'error');
        return;
    }
    
    if (newProduct.value.tipo_dosis === 'por_100_litros' && !newProduct.value.dosis_por_100) {
        Swal.fire('Error', 'Debe ingresar la dosis por 100 litros', 'error');
        return;
    }
    
    if (!newProduct.value.carencia || !newProduct.value.reingreso) {
        Swal.fire('Error', 'Debe ingresar carencia y reingreso', 'error');
        return;
    }
    
    // Si estamos editando, actualizar el producto existente
    if (editingProductIndex.value !== null) {
        props.form.products[editingProductIndex.value] = { ...newProduct.value };
        Swal.fire({
            icon: 'success',
            title: 'Producto actualizado',
            timer: 1500,
            showConfirmButton: false
        });
        cancelEditProduct();
        return;
    }
    
    // Verificar si el producto ya existe (solo al agregar nuevo)
    const exists = props.form.products.find(p => p.product_id === newProduct.value.product_id);
    if (exists) {
        Swal.fire('Advertencia', 'Este producto ya fue agregado', 'warning');
        return;
    }
    
    props.form.products.push({ ...newProduct.value });
    
    // Resetear form
    newProduct.value = {
        product_id: '',
        unit_id: null,
        tipo_dosis: 'por_hectarea',
        dosis_por_100: '',
        dosis_por_hectarea: '',
        carencia: '',
        reingreso: '',
    };
    selectedProduct.value = null;
}

function editProduct(index) {
    const product = props.form.products[index];
    editingProductIndex.value = index;
    
    newProduct.value = {
        product_id: product.product_id,
        unit_id: product.unit_id,
        tipo_dosis: product.tipo_dosis,
        dosis_por_100: product.dosis_por_100,
        dosis_por_hectarea: product.dosis_por_hectarea,
        carencia: product.carencia,
        reingreso: product.reingreso,
    };
    
    selectedProduct.value = product.product_id;
    
    // Scroll al formulario de productos
    document.querySelector('.card.mb-3')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelEditProduct() {
    editingProductIndex.value = null;
    newProduct.value = {
        product_id: '',
        unit_id: null,
        tipo_dosis: 'por_hectarea',
        dosis_por_100: '',
        dosis_por_hectarea: '',
        carencia: '',
        reingreso: '',
    };
    selectedProduct.value = null;
}

function removeProduct(index) {
    props.form.products.splice(index, 1);
}

function getProductName(productId) {
    const product = props.products.find(p => p.value === productId);
    return product ? product.label : '';
}

function getProductUnit(productId) {
    const product = props.products.find(p => p.value === productId);
    return product?.unit_name || '';
}

function getUnitName(unitId) {
    const unit = props.units.find(u => u.value === unitId);
    return unit?.label || '';
}

// ==== CENTROS DE COSTO ====
const selectedCostCenters = computed({
    get: () => props.form.cost_centers.map(cc => cc.cost_center_id),
    set: (newValue) => {
        // Actualizar el array según la nueva selección
        const newCostCenters = newValue.map(ccId => {
            // Si ya existe, mantener los datos
            const existing = props.form.cost_centers.find(cc => cc.cost_center_id === ccId);
            if (existing) return existing;
            
            // Si no existe, crear nuevo
            const cc = props.costCenters.find(c => c.value === ccId);
            return {
                cost_center_id: ccId,
                surface: cc?.surface || 0,
            };
        });
        
        props.form.cost_centers.splice(0, props.form.cost_centers.length, ...newCostCenters);
    }
});

// ==== AGRUPACIÓN ====
const selectedGrouping = ref(null);

// Watch para aplicar agrupación automáticamente
watch(selectedGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        const groupCCs = grouping.cost_centers.map(cc => cc.id);
        selectedCostCenters.value = groupCCs;
    }
});

// Calcular cantidades para productos ya agregados
function getProductQuantityPerHa(product) {
    if (product.tipo_dosis === 'por_hectarea') {
        return Number(product.dosis_por_hectarea || 0);
    } else if (product.tipo_dosis === 'por_100_litros') {
        const hectolitros = Number(props.form.mojamiento || 0) / 100;
        return Number(product.dosis_por_100 || 0) * hectolitros;
    }
    return 0;
}

function getProductTotalQuantity(product) {
    return getProductQuantityPerHa(product) * totalHectareas.value;
}

// Función para convertir y simplificar cantidades (cc a lt, gr a kg)
function getSimplifiedQuantity(product) {
    const cantidad = getProductTotalQuantity(product);
    const unitId = product.unit_id || null;
    
    // Obtener el nombre de la unidad
    let unitName = '';
    if (unitId) {
        const unit = props.units.find(u => u.value === unitId);
        unitName = unit?.label || '';
    } else {
        const prod = props.products.find(p => p.value === product.product_id);
        unitName = prod?.unit_name || '';
    }
    
    unitName = unitName.toLowerCase();
    
    // Convertir cc a lt si es >= 1000
    if (unitName === 'cc' && cantidad >= 1000) {
        return {
            value: (cantidad / 1000).toFixed(2),
            unit: 'lt'
        };
    }
    
    // Convertir gr a kg si es >= 1000
    if (unitName === 'gr' && cantidad >= 1000) {
        return {
            value: (cantidad / 1000).toFixed(2),
            unit: 'kg'
        };
    }
    
    // No convertir, devolver original
    return {
        value: cantidad.toFixed(2),
        unit: getUnitName(unitId) || getProductUnit(product.product_id)
    };
}
</script>

<template>
    <div class="container-fluid">
        <!-- Datos Generales -->
        <div class="row mb-2">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-1 mb-2">
                    <i class="fas fa-info-circle me-1"></i>Datos Generales
                </h6>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Fecha <span class="text-danger">*</span></label>
                <input
                    v-model="form.date"
                    type="date"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.date}"
                />
                <InputError :message="form.errors.date" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Mojamiento (L) <span class="text-danger">*</span></label>
                <input
                    v-model="form.mojamiento"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.mojamiento}"
                    placeholder="Ej: 1500"
                />
                <InputError :message="form.errors.mojamiento" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Recomendado por <span class="text-danger">*</span></label>
                <input
                    v-model="form.recomendado"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.recomendado}"
                    placeholder="Nombre"
                />
                <InputError :message="form.errors.recomendado" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Responsable <span class="text-danger">*</span></label>
                <input
                    v-model="form.responsable"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.responsable}"
                    placeholder="Nombre del responsable"
                />
                <InputError :message="form.errors.responsable" />
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small mb-1">Aplicadores <span class="text-danger">*</span></label>
                <textarea
                    v-model="form.aplicadores"
                    rows="2"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.aplicadores}"
                    placeholder="Nombres de los aplicadores..."
                ></textarea>
                <InputError :message="form.errors.aplicadores" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Estado <span class="text-danger">*</span></label>
                <select v-model="form.status" class="form-select form-select-sm" :class="{'is-invalid': form.errors.status}">
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <InputError :message="form.errors.status" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Observaciones</label>
                <textarea
                    v-model="form.observations"
                    rows="2"
                    class="form-control form-control-sm"
                    placeholder="Observaciones generales..."
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
            <div class="col-md-4">
                <label class="form-label small mb-1">
                    <i class="fas fa-layer-group me-1"></i>Agrupación (Preselección rápida)
                </label>
                <select 
                    v-model="selectedGrouping" 
                    class="form-select form-select-sm"
                >
                    <option :value="null">Seleccione agrupación...</option>
                    <option v-for="g in (groupings || [])" :key="g.id" :value="g.id">
                        {{ g.name }}
                    </option>
                </select>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Preselección rápida
                </small>
            </div>
            <div class="col-md-8">
                <label class="form-label small mb-1">Seleccionar Centros de Costo <span class="text-danger">*</span></label>
                <Multiselect
                    v-model="selectedCostCenters"
                    :options="costCenters"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    placeholder="Seleccione centros de costo..."
                    class="multiselect-blue form-control-sm"
                    :class="{'is-invalid': form.errors.cost_centers}"
                />
                <InputError :message="form.errors.cost_centers" />
                <small v-if="totalHectareas > 0" class="text-muted d-block mt-1">
                    <i class="fas fa-calculator me-1"></i>
                    Total: <strong>{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</strong>
                </small>
            </div>
        </div>

        <hr class="my-2">

        <!-- Productos -->
        <div class="row mb-2">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-1 mb-2">
                    <i class="fas fa-flask me-1"></i>Productos
                </h6>
            </div>
        </div>

        <!-- Formulario agregar producto -->
        <div class="card mb-2 shadow-sm" :class="{'border-warning': editingProductIndex !== null}">
            <div class="card-header py-2" :class="{'bg-warning': editingProductIndex !== null, 'bg-light': editingProductIndex === null}">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="small" v-if="editingProductIndex === null">
                        <i class="fas fa-plus-circle me-1"></i>Agregar Producto
                    </strong>
                    <strong class="small text-dark" v-else>
                        <i class="fas fa-edit me-1"></i>Editando Producto
                    </strong>
                    <button 
                        v-if="editingProductIndex !== null" 
                        @click="cancelEditProduct" 
                        class="btn btn-sm btn-secondary"
                        type="button"
                    >
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Producto</label>
                        <Multiselect
                            v-model="newProduct.product_id"
                            :options="products"
                            :searchable="true"
                            placeholder="Seleccione un producto..."
                            class="multiselect-blue form-control-sm"
                        />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small mb-1">Tipo de Dosis</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input
                                type="radio"
                                class="btn-check"
                                id="tipo_hectarea"
                                v-model="newProduct.tipo_dosis"
                                value="por_hectarea"
                            />
                            <label class="btn btn-outline-primary" for="tipo_hectarea">Por Hectárea</label>

                            <input
                                type="radio"
                                class="btn-check"
                                id="tipo_100_litros"
                                v-model="newProduct.tipo_dosis"
                                value="por_100_litros"
                            />
                            <label class="btn btn-outline-primary" for="tipo_100_litros">Por 100L</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3" v-if="newProduct.tipo_dosis === 'por_hectarea'">
                        <label class="form-label small mb-1">Dosis por Hectárea</label>
                        <input
                            v-model="newProduct.dosis_por_hectarea"
                            type="number"
                            step="0.01"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="col-md-3" v-if="newProduct.tipo_dosis === 'por_100_litros'">
                        <label class="form-label small mb-1">Dosis por 100L</label>
                        <input
                            v-model="newProduct.dosis_por_100"
                            type="number"
                            step="0.01"
                            class="form-control form-control-sm"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">Unidad</label>
                        <Multiselect
                            v-model="newProduct.unit_id"
                            :options="units"
                            :searchable="true"
                            placeholder="Seleccione unidad..."
                            class="multiselect-blue form-control-sm"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">Carencia (días)</label>
                        <input
                            v-model="newProduct.carencia"
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small mb-1">Reingreso (horas)</label>
                        <input
                            v-model="newProduct.reingreso"
                            type="number"
                            class="form-control form-control-sm"
                            placeholder="0"
                        />
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-12">
                        <button @click="addProduct" class="btn btn-sm w-100" :class="editingProductIndex !== null ? 'btn-warning' : 'btn-success'" type="button">
                            <i :class="editingProductIndex !== null ? 'fas fa-save' : 'fas fa-plus'" class="me-1"></i>
                            {{ editingProductIndex !== null ? 'Actualizar Producto' : 'Agregar Producto' }}
                        </button>
                    </div>
                </div>

                <!-- Vista previa de cálculos -->
                <div v-if="newProduct.product_id && totalHectareas > 0" class="alert alert-info py-2 mt-2 mb-0">
                    <strong class="small">Vista Previa:</strong><br>
                    <small>
                        Cantidad por hectárea: <strong>{{ calculatedQuantityPerHa.toFixed(2) }}</strong> 
                        {{ getUnitName(newProduct.unit_id) || getProductUnit(newProduct.product_id) }}/ha<br>
                        Cantidad total: <strong>{{ Number(previewSimplifiedQuantity.value).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}</strong> 
                        {{ previewSimplifiedQuantity.unit }}
                    </small>
                </div>
            </div>
        </div>

        <!-- Tabla de productos agregados -->
        <div v-if="form.products.length > 0" class="table-responsive mt-2">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small">Producto</th>
                        <th class="small">Tipo Dosis</th>
                        <th class="text-end small">Dosis</th>
                        <th class="text-end small">Cantidad/ha</th>
                        <th class="text-end small">Cantidad Total</th>
                        <th class="text-center small">Carencia</th>
                        <th class="text-center small">Reingreso</th>
                        <th class="text-center small" style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(product, index) in form.products" :key="index">
                        <td class="small">{{ getProductName(product.product_id) }}</td>
                        <td>
                            <span v-if="product.tipo_dosis === 'por_hectarea'" class="badge badge-sm bg-primary">Hectárea</span>
                            <span v-else class="badge badge-sm bg-info">100L</span>
                        </td>
                        <td class="text-end small">
                            <span v-if="product.tipo_dosis === 'por_hectarea'">
                                {{ Number(product.dosis_por_hectarea).toFixed(2) }} {{ getUnitName(product.unit_id) || getProductUnit(product.product_id) }}/ha
                            </span>
                            <span v-else>
                                {{ Number(product.dosis_por_100).toFixed(2) }} {{ getUnitName(product.unit_id) || getProductUnit(product.product_id) }}/100L
                            </span>
                        </td>
                        <td class="text-end small">
                            {{ getProductQuantityPerHa(product).toFixed(2) }} {{ getUnitName(product.unit_id) || getProductUnit(product.product_id) }}/ha
                        </td>
                        <td class="text-end small">
                            <strong>{{ Number(getSimplifiedQuantity(product).value).toLocaleString('es-ES', {minimumFractionDigits: 2}) }}</strong> {{ getSimplifiedQuantity(product).unit }}
                        </td>
                        <td class="text-center small">{{ product.carencia }} d</td>
                        <td class="text-center small">{{ product.reingreso }} h</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button @click="editProduct(index)" class="btn btn-sm btn-warning" type="button" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="removeProduct(index)" class="btn btn-sm btn-danger" type="button" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
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
</style>
