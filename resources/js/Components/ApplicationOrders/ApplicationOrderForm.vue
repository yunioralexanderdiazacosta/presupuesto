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
    branches: Array,
    units: Array,
    groupings: Array,
    fruits: Array,
    phenologicalStages: Array,
    machineries: Array,
    operators: Array,
    isEditing: {
        type: Boolean,
        default: false
    }
});

// ==== PRODUCTOS ====
const productsOptions = ref(props.products);

// ==== TRACTORES, EQUIPOS, OPERARIOS (Multiselect tags → string separado por coma) ====
const machineryNames = computed(() => (props.machineries || []).map(m => m.label));
const operatorNames = computed(() => (props.operators || []).map(o => o.label));

// Solo nebulizadores/pulverizadores para el select de Equipos
const equipmentMachineryNames = computed(() => {
    return (props.machineries || [])
        .filter(m => /nebuliz|pulveriz/i.test(m.type || ''))
        .map(m => m.label);
});

// Tractores: excluir nebulizadores/pulverizadores
const tractorMachineryNames = computed(() => {
    return (props.machineries || [])
        .filter(m => !/nebuliz|pulveriz/i.test(m.type || ''))
        .map(m => m.label);
});

const selectedTractors = computed({
    get: () => props.form.tractors ? props.form.tractors.split(', ').filter(Boolean) : [],
    set: (val) => { props.form.tractors = val.join(', '); }
});
const selectedEquipments = computed({
    get: () => props.form.equipments ? props.form.equipments.split(', ').filter(Boolean) : [],
    set: (val) => { props.form.equipments = val.join(', '); }
});
const selectedOperators = computed({
    get: () => props.form.operators ? props.form.operators.split(', ').filter(Boolean) : [],
    set: (val) => { props.form.operators = val.join(', '); }
});
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

// Flags para saber si el producto seleccionado ya tenía carencia/reingreso
const productHasCarencia = ref(true);
const productHasReingreso = ref(true);
const isSavingCarenciaReingreso = ref(false);

// Watch para auto-asignar unit_id cuando se selecciona un producto
watch(() => newProduct.value.product_id, (productId) => {
    if (productId) {
        const product = productsOptions.value.find(p => p.value === productId);
        if (product) {
            newProduct.value.unit_id = product.unit_id || '';
            newProduct.value.carencia = product.carencia ?? '';
            newProduct.value.reingreso = product.reingreso ?? '';
            productHasCarencia.value = product.carencia !== null && product.carencia !== undefined && product.carencia !== '';
            productHasReingreso.value = product.reingreso !== null && product.reingreso !== undefined && product.reingreso !== '';
        }
    } else {
        newProduct.value.unit_id = '';
        newProduct.value.carencia = '';
        newProduct.value.reingreso = '';
        productHasCarencia.value = true;
        productHasReingreso.value = true;
    }
});

const showSaveToProduct = computed(() => {
    return newProduct.value.product_id && (!productHasCarencia.value || !productHasReingreso.value);
});

const saveCarenciaReingresoToProduct = async () => {
    if (!newProduct.value.product_id) return;
    isSavingCarenciaReingreso.value = true;
    try {
        const response = await axios.patch(
            route('api.products.carencia-reingreso', newProduct.value.product_id),
            {
                carencia: newProduct.value.carencia !== '' ? newProduct.value.carencia : null,
                reingreso: newProduct.value.reingreso !== '' ? newProduct.value.reingreso : null,
            }
        );
        // Actualizar el array local de opciones
        const idx = productsOptions.value.findIndex(p => p.value === newProduct.value.product_id);
        if (idx !== -1) {
            productsOptions.value[idx].carencia = response.data.carencia;
            productsOptions.value[idx].reingreso = response.data.reingreso;
        }
        productHasCarencia.value = response.data.carencia !== null;
        productHasReingreso.value = response.data.reingreso !== null;
        Swal.fire({ icon: 'success', title: 'Guardado en producto', showConfirmButton: false, timer: 1200 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo guardar en el producto', 'error');
    } finally {
        isSavingCarenciaReingreso.value = false;
    }
};

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

// Si es edición, el usuario ya tiene un valor guardado — no sobreescribir automáticamente
const manualOverride = ref(props.isEditing);

// Cuando cambian los CCs, auto-sincronizar superficie_total solo si no fue editado manualmente
watch(totalHectareas, (newVal) => {
    if (!manualOverride.value) {
        props.form.superficie_total = newVal;
    }
});

function resetSuperficie() {
    props.form.superficie_total = totalHectareas.value;
    manualOverride.value = false;
}

const effectiveHectareas = computed(() => {
    return Number(props.form.superficie_total || totalHectareas.value);
});

const maquinadas = computed(() => {
    const mojamiento = Number(props.form.mojamiento || 0);
    const hectareas = effectiveHectareas.value;
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
    return calculatedQuantityPerHa.value * effectiveHectareas.value;
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

    // Verificar si el producto master tiene estos datos
    const masterProduct = productsOptions.value.find(p => p.value === product.product_id);
    productHasCarencia.value = masterProduct ? (masterProduct.carencia !== null && masterProduct.carencia !== undefined && masterProduct.carencia !== '') : true;
    productHasReingreso.value = masterProduct ? (masterProduct.reingreso !== null && masterProduct.reingreso !== undefined && masterProduct.reingreso !== '') : true;
    
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
    productHasCarencia.value = true;
    productHasReingreso.value = true;
}

function removeProduct(index) {
    props.form.products.splice(index, 1);
}

function getProductName(productId) {
    const product = productsOptions.value.find(p => p.value === productId);
    return product?.label || '';
}

function getProductActiveIngredient(productId) {
    const product = productsOptions.value.find(p => p.value === productId);
    return product?.active_ingredient || '-';
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

// ==== SUCURSAL (prellenado de CCs) ====
const selectedBranch = ref(null);

// CCs filtrados por sucursal seleccionada
const filteredCostCenters = computed(() => {
    if (!selectedBranch.value || !props.costCenters) return props.costCenters || [];
    return props.costCenters.filter(cc => String(cc.branch_id) === String(selectedBranch.value));
});

// Cuando cambia la sucursal, limpiar CCs seleccionados que no pertenezcan a ella
watch(selectedBranch, (branchId) => {
    if (!branchId) return;
    const validIds = filteredCostCenters.value.map(cc => cc.value);
    const filtered = props.form.cost_centers.filter(cc => validIds.includes(cc.cost_center_id));
    props.form.cost_centers.splice(0, props.form.cost_centers.length, ...filtered);
});

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
            const cc = (props.costCenters || []).find(c => c.value === ccId);
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

// Estado para expandir/colapsar tags de CC
const expandedCC = ref(false);

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
    return getProductQuantityPerHa(product) * effectiveHectareas.value;
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

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Operarios</label>
                <Multiselect
                    v-model="selectedOperators"
                    :options="operatorNames"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    :create-option="true"
                    placeholder="Seleccione operarios..."
                    class="multiselect-blue form-control-sm"
                />
                <InputError :message="form.errors.operators" />
            </div>

            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Tractores</label>
                <Multiselect
                    v-model="selectedTractors"
                    :options="tractorMachineryNames.length > 0 ? tractorMachineryNames : machineryNames"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    :create-option="true"
                    placeholder="Seleccione tractores..."
                    class="multiselect-blue form-control-sm"
                />
                <InputError :message="form.errors.tractors" />
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Equipos</label>
                <Multiselect
                    v-model="selectedEquipments"
                    :options="equipmentMachineryNames.length > 0 ? equipmentMachineryNames : machineryNames"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    :create-option="true"
                    placeholder="Seleccione equipos..."
                    class="multiselect-blue form-control-sm"
                />
                <small v-if="equipmentMachineryNames.length > 0" class="text-muted">
                    <i class="fas fa-filter me-1"></i>Filtrado: nebulizadores/pulverizadores
                </small>
                <InputError :message="form.errors.equipments" />
            </div>
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
            <div class="col-md-3 mb-2">
                <label class="form-label small mb-1">Estado <span class="text-danger">*</span></label>
                <select v-model="form.status" class="form-select form-select-sm" :class="{'is-invalid': form.errors.status}">
                    <option value="pendiente">Pendiente</option>
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
                    <i class="fas fa-building me-1"></i>Sucursal (filtro de CC)
                </label>
                <select 
                    v-model="selectedBranch" 
                    class="form-select form-select-sm"
                >
                    <option :value="null">Todas las sucursales</option>
                    <option v-for="b in (branches || [])" :key="b.value" :value="b.value">
                        {{ b.label }}
                    </option>
                </select>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle me-1"></i>
                    Filtra los centros de costo disponibles
                </small>
            </div>
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
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-between mb-0">
                    <label class="form-label small mb-0">Seleccionar Centros de Costo <span class="text-danger">*</span>
                        <span v-if="selectedCostCenters.length > 0" class="badge bg-primary ms-1" style="font-size: 0.6rem; vertical-align: middle;">
                            {{ selectedCostCenters.length }}
                        </span>
                    </label>
                    <button
                        v-if="selectedCostCenters.length > 5"
                        type="button"
                        @click="expandedCC = !expandedCC"
                        class="btn btn-link btn-sm p-0 text-muted"
                        style="font-size: 0.65rem; text-decoration: none;"
                    >
                        <i class="fas" :class="expandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size: 0.6rem;"></i>
                        {{ expandedCC ? 'Colapsar' : 'Ver todos' }}
                    </button>
                </div>
                <Multiselect
                    v-model="selectedCostCenters"
                    :options="filteredCostCenters"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    placeholder="Seleccione centros de costo..."
                    :class="['multiselect-blue form-control-sm multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCC }, {'is-invalid': form.errors.cost_centers}]"
                />
                <InputError :message="form.errors.cost_centers" />
            </div>
        </div>

        <div class="row mb-2 mt-3" v-if="form.cost_centers.length > 0">
            <div class="col-md-3 mb-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small mb-0">
                        <i class="fas fa-ruler-combined me-1 text-success"></i>Superficie a aplicar (ha)
                    </label>
                    <button
                        v-if="manualOverride"
                        type="button"
                        @click="resetSuperficie"
                        class="btn btn-sm btn-light d-flex align-items-center gap-1 py-0 px-2"
                        style="font-size: 0.7rem;"
                        title="Restaurar al valor automático de los CCs"
                    >
                        <i class="fas fa-sync-alt fa-xs"></i> Auto
                    </button>
                </div>
                <input
                    v-model="form.superficie_total"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control form-control-sm"
                    :class="{'border-warning': manualOverride}"
                    placeholder="Ha"
                    @input="manualOverride = true"
                />
                <small class="text-muted" style="font-size: 0.7rem;">
                    Auto (CCs): {{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ha
                </small>
            </div>

            <div class="col-md-3 mb-2" v-if="form.volume">
                <div class="p-1 border border-primary rounded" style="background-color: #cfe2ff;">
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
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Carencia (días)</label>
                            <span v-if="newProduct.product_id && !productHasCarencia" class="badge bg-warning text-dark" style="font-size:0.65rem;">Sin dato</span>
                        </div>
                        <input
                            v-model="newProduct.carencia"
                            type="number"
                            min="0"
                            class="form-control form-control-sm"
                            placeholder="0"
                        />
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Reingreso (horas)</label>
                            <span v-if="newProduct.product_id && !productHasReingreso" class="badge bg-warning text-dark" style="font-size:0.65rem;">Sin dato</span>
                        </div>
                        <input
                            v-model="newProduct.reingreso"
                            type="number"
                            min="0"
                            class="form-control form-control-sm"
                            placeholder="0"
                        />
                    </div>
                </div>

                <div v-if="showSaveToProduct" class="row mb-1">
                    <div class="col-md-12">
                        <div class="alert alert-warning py-1 px-2 mb-0 d-flex align-items-center justify-content-between" style="font-size:0.8rem;">
                            <span><i class="fas fa-exclamation-triangle me-1"></i>Este producto no tiene {{ !productHasCarencia && !productHasReingreso ? 'carencia ni reingreso' : !productHasCarencia ? 'carencia' : 'reingreso' }} registrados. ¿Deseas guardarlos en el producto?</span>
                            <button
                                type="button"
                                @click="saveCarenciaReingresoToProduct"
                                :disabled="isSavingCarenciaReingreso"
                                class="btn btn-sm btn-warning ms-2 d-flex align-items-center gap-1"
                                style="font-size:0.75rem; white-space:nowrap;"
                            >
                                <i class="fas fa-save fa-xs" :class="{'fa-spin': isSavingCarenciaReingreso}"></i>
                                Guardar en producto
                            </button>
                        </div>
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
                        <th class="small">Ingrediente Activo</th>
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
                        <td class="small text-muted">{{ getProductActiveIngredient(product.product_id) }}</td>
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

/* Limitar tags visibles en el multiselect de centros de costo */
.multiselect-tags-limited .multiselect-tags {
    max-height: 32px !important;
    overflow: hidden !important;
    flex-wrap: wrap;
    transition: max-height 0.3s ease;
}

/* Estado expandido */
.multiselect-tags-expanded .multiselect-tags {
    max-height: 200px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

/* Scrollbar discreto para los tags expandidos */
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar {
    width: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.2);
    border-radius: 4px;
}
.multiselect-tags-expanded .multiselect-tags::-webkit-scrollbar-track {
    background: transparent;
}

.multiselect-blue.multiselect-tags-limited {
    height: auto !important;
    max-height: 38px !important;
    min-height: 29px !important;
    transition: max-height 0.3s ease;
}

.multiselect-blue.multiselect-tags-expanded {
    max-height: 210px !important;
}
</style>
