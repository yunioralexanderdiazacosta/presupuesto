<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
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
    fruits: Array,
    phenologicalStages: Array,
    isEditing: {
        type: Boolean,
        default: false
    }
});

// ==== PRODUCTOS ====
const productsOptions = ref(props.products);
const isRefreshingProducts = ref(false);
const selectedProduct = ref(null);
const editingProductIndex = ref(null);
const newProduct = ref({
    product_id: '',
    unit_id: '',
    tipo_dosis: 'por_hectarea',
    dosis_por_100: '',
    dosis_por_hectarea: '',
    carencia: '',
    reingreso: '',
});

// Watch para auto-asignar unit_id cuando se selecciona un producto
watch(() => newProduct.value.product_id, (productId) => {
    if (productId) {
        const product = productsOptions.value.find(p => p.value === productId);
        if (product) {
            newProduct.value.unit_id = product.unit_id || '';
        }
    } else {
        newProduct.value.unit_id = '';
    }
});

const refreshProducts = async () => {
    isRefreshingProducts.value = true;
    try {
        const response = await axios.get(route('api.products'));
        productsOptions.value = response.data;
        newProduct.value.product_id = ''; // Limpiar selección actual
        Swal.fire({
            icon: 'success',
            title: 'Productos actualizados',
            showConfirmButton: false,
            timer: 1000
        });
    } catch (error) {
        console.error('Error al refrescar productos:', error);
        Swal.fire('Error', 'No se pudieron refrescar los productos', 'error');
    } finally {
        isRefreshingProducts.value = false;
    }
};

const totalHectareas = computed(() => {
    return props.form.cost_centers.reduce((sum, cc) => sum + Number(cc.surface || 0), 0);
});

const maquinadas = computed(() => {
    const mojamiento = Number(props.form.mojamiento || 0);
    const hectareas = totalHectareas.value;
    const volumen = Number(props.form.volume || 0);
    
    if (volumen === 0 || hectareas === 0) return 0;
    
    return (mojamiento * hectareas) / volumen;
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

// Computed para obtener la unidad base del producto seleccionado
const selectedProductUnit = computed(() => {
    if (!newProduct.value.product_id) return '';
    const product = productsOptions.value.find(p => p.value === newProduct.value.product_id);
    return product?.unit_name || '';
});

// Computed para vista previa de cantidad total con unidad base del producto
const previewSimplifiedQuantity = computed(() => {
    const cantidad = calculatedTotalQuantity.value;
    const prod = productsOptions.value.find(p => p.value === newProduct.value.product_id);
    const unitName = prod?.unit_name || '';
    
    return {
        value: cantidad.toFixed(2),
        unit: unitName
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
        unit_id: '',
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
        unit_id: product.unit_id || '',
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
        unit_id: '',
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
    const product = productsOptions.value.find(p => p.value === productId);
    return product?.label || '';
}

function getProductUnitName(productId) {
    const product = productsOptions.value.find(p => p.value === productId);
    return product?.unit_name || '';
}

function getProductUnit(productId) {
    const product = productsOptions.value.find(p => p.value === productId);
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

// ==== FILTRADO DE ETAPAS FENOLÓGICAS ====
const selectedFruit = ref(null); // Solo para filtrar, NO se guarda

// Computed para filtrar etapas fenológicas por frutal seleccionado
const filteredPhenologicalStages = computed(() => {
    if (!selectedFruit.value || !props.phenologicalStages) {
        return props.phenologicalStages || [];
    }
    // Usar == en lugar de === para comparar independientemente del tipo
    return props.phenologicalStages.filter(stage => stage.fruit_id == selectedFruit.value);
});

// Watch para limpiar la etapa fenológica si se cambia el frutal
watch(selectedFruit, () => {
    // Si cambia el frutal, resetear la etapa fenológica seleccionada
    props.form.phenological_stage_id = null;
});

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

// Función para obtener cantidad con unidad base del producto
function getSimplifiedQuantity(product) {
    const cantidad = getProductTotalQuantity(product);
    const prod = productsOptions.value.find(p => p.value === product.product_id);
    const unitName = prod?.unit_name || '';
    
    return {
        value: cantidad.toFixed(2),
        unit: unitName
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
                <label class="form-label small mb-1">Fecha Inicio</label>
                <input
                    v-model="form.start_date"
                    type="date"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.start_date}"
                />
                <InputError :message="form.errors.start_date" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Volumen</label>
                <input
                    v-model="form.volume"
                    type="number"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.volume}"
                    placeholder="0"
                />
                <InputError :message="form.errors.volume" />
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
        </div>

        <div class="row mb-2">
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
            <div class="col-md-4 mb-2">
                <label class="form-label small mb-1">Frutal (filtro)</label>
                <select 
                    v-model="selectedFruit" 
                    class="form-select form-select-sm"
                >
                    <option :value="null">Todos los frutales</option>
                    <option v-for="fruit in fruits" :key="fruit.value" :value="fruit.value">
                        {{ fruit.label }}
                    </option>
                </select>
                <small class="text-muted">Seleccione para filtrar etapas fenológicas</small>
            </div>

            <div class="col-md-4 mb-2">
                <label class="form-label small mb-1">Etapa Fenológica</label>
                <select 
                    v-model="form.phenological_stage_id" 
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.phenological_stage_id}"
                >
                    <option :value="null" disabled selected>Seleccione etapa fenológica...</option>
                    <option v-for="stage in filteredPhenologicalStages" :key="stage.value" :value="stage.value">
                        {{ stage.label }}
                    </option>
                </select>
                <InputError :message="form.errors.phenological_stage_id" />
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small mb-1">Aplicadores <span class="text-danger">*</span></label>
                <textarea
                    v-model="form.aplicadores"
                    rows="1"
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
                    rows="1"
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
                    <option :value="null" disabled selected>Seleccione agrupación...</option>
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
            </div>
        </div>

        <div class="row mb-2 mt-3" v-if="form.volume && totalHectareas > 0">
            <div class="col-md-3 mb-2">
                <div class="p-1 border border-success rounded bg-light-success" style="background-color: #d1f4d1;">
                    <small class="text-muted d-block" style="margin-bottom: 2px;">
                        <i class="fas fa-calculator me-1"></i>Total Hectáreas
                    </small>
                    <span class="h6 mb-0 text-success fw-bold">
                        {{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                    </span>
                    <small class="text-success ms-1">ha</small>
                </div>
            </div>
            
            <div class="col-md-3 mb-2">
                <div class="p-1 border border-primary rounded bg-light-primary" style="background-color: #cfe2ff;">
                    <small class="text-muted d-block" style="margin-bottom: 2px;">
                        <i class="fas fa-tractor me-1"></i>Maquinadas
                    </small>
                    <span class="h6 mb-0 text-primary fw-bold">
                        {{ maquinadas.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                    </span>
                </div>
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
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Producto</label>
                            <button 
                                type="button" 
                                @click="refreshProducts" 
                                :disabled="isRefreshingProducts"
                                class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                                v-tooltip="'Refrescar lista de productos'"
                                style="font-size: 0.75rem;"
                            >
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingProducts}"></i>
                            </button>
                        </div>
                        <select
                            v-model="newProduct.product_id"
                            class="form-select form-select-sm"
                        >
                            <option :value="''" disabled selected>Seleccione un producto...</option>
                            <option v-for="product in productsOptions" :key="product.value" :value="product.value">
                                {{ product.label }}
                            </option>
                        </select>
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
                        <label class="form-label small mb-1">
                            <i class="fas fa-balance-scale me-1"></i>Unidad Base
                        </label>
                        <div class="input-group input-group-sm">
                            <input
                                :value="selectedProductUnit"
                                type="text"
                                class="form-control form-control-sm bg-light"
                                readonly
                                placeholder="Seleccione producto..."
                            />
                            <span class="input-group-text bg-info text-white">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-lightbulb me-1"></i>Ingrese cantidades en esta unidad
                        </small>
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
                    <div class="col-md-12 text-end">
                        <button @click="addProduct" class="btn btn-sm" :class="editingProductIndex !== null ? 'btn-warning' : 'btn-success'" type="button">
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
                        {{ selectedProductUnit }}/ha<br>
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
