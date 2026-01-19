<script setup>
import { ref, computed } from 'vue';
import Multiselect from '@vueform/multiselect';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    form: Object,
    products: Array,
    costCenters: Array,
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
    tipo_dosis: 'por_hectarea',
    dosis_por_100: '',
    dosis_por_hectarea: '',
    carencia: '',
    reingreso: '',
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
</script>

<template>
    <div class="container-fluid">
        <!-- Datos Generales -->
        <div class="row mb-3">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-2">
                    <i class="fas fa-info-circle me-2"></i>Datos Generales
                </h6>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Fecha <span class="text-danger">*</span></label>
                <input
                    v-model="form.date"
                    type="date"
                    class="form-control"
                    :class="{'is-invalid': form.errors.date}"
                />
                <InputError :message="form.errors.date" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Mojamiento (Litros) <span class="text-danger">*</span></label>
                <input
                    v-model="form.mojamiento"
                    type="number"
                    step="0.01"
                    class="form-control"
                    :class="{'is-invalid': form.errors.mojamiento}"
                    placeholder="Ej: 1500"
                />
                <InputError :message="form.errors.mojamiento" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Recomendado por <span class="text-danger">*</span></label>
                <input
                    v-model="form.recomendado"
                    type="text"
                    class="form-control"
                    :class="{'is-invalid': form.errors.recomendado}"
                    placeholder="Nombre"
                />
                <InputError :message="form.errors.recomendado" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Responsable <span class="text-danger">*</span></label>
                <input
                    v-model="form.responsable"
                    type="text"
                    class="form-control"
                    :class="{'is-invalid': form.errors.responsable}"
                    placeholder="Nombre del responsable"
                />
                <InputError :message="form.errors.responsable" />
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Aplicadores <span class="text-danger">*</span></label>
                <textarea
                    v-model="form.aplicadores"
                    rows="3"
                    class="form-control"
                    :class="{'is-invalid': form.errors.aplicadores}"
                    placeholder="Nombres de los aplicadores..."
                ></textarea>
                <InputError :message="form.errors.aplicadores" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Estado <span class="text-danger">*</span></label>
                <select v-model="form.status" class="form-select" :class="{'is-invalid': form.errors.status}">
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <InputError :message="form.errors.status" />
            </div>

            <div class="col-md-3">
                <label class="form-label">Observaciones</label>
                <textarea
                    v-model="form.observations"
                    rows="3"
                    class="form-control"
                    placeholder="Observaciones generales..."
                ></textarea>
            </div>
        </div>

        <hr class="my-4">

        <!-- Centros de Costo -->
        <div class="row mb-3">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-2">
                    <i class="fas fa-map-marker-alt me-2"></i>Centros de Costo
                </h6>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Seleccionar Centros de Costo <span class="text-danger">*</span></label>
                <Multiselect
                    v-model="selectedCostCenters"
                    :options="costCenters"
                    mode="tags"
                    :searchable="true"
                    :close-on-select="false"
                    placeholder="Seleccione centros de costo..."
                    class="form-control"
                    :class="{'is-invalid': form.errors.cost_centers}"
                />
                <InputError :message="form.errors.cost_centers" />
                <small v-if="totalHectareas > 0" class="text-muted">
                    <i class="fas fa-calculator me-1"></i>
                    Total: <strong>{{ totalHectareas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} ha</strong>
                </small>
            </div>
        </div>

        <hr class="my-4">

        <!-- Productos -->
        <div class="row mb-3">
            <div class="col-md-12">
                <h6 class="text-primary border-bottom pb-2">
                    <i class="fas fa-flask me-2"></i>Productos
                </h6>
            </div>
        </div>

        <!-- Formulario agregar producto -->
        <div class="card mb-3" :class="{'border-warning': editingProductIndex !== null}">
            <div class="card-header" :class="{'bg-warning': editingProductIndex !== null, 'bg-light': editingProductIndex === null}">
                <div class="d-flex justify-content-between align-items-center">
                    <strong v-if="editingProductIndex === null">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Producto
                    </strong>
                    <strong v-else class="text-dark">
                        <i class="fas fa-edit me-2"></i>Editando Producto
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
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Producto</label>
                        <Multiselect
                            v-model="newProduct.product_id"
                            :options="products"
                            :searchable="true"
                            placeholder="Seleccione un producto..."
                            class="form-control"
                        />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de Dosis</label>
                        <div class="btn-group w-100" role="group">
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
                            <label class="btn btn-outline-primary" for="tipo_100_litros">Por 100 Litros</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3" v-if="newProduct.tipo_dosis === 'por_hectarea'">
                        <label class="form-label">Dosis por Hectárea</label>
                        <input
                            v-model="newProduct.dosis_por_hectarea"
                            type="number"
                            step="0.01"
                            class="form-control"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="col-md-3" v-if="newProduct.tipo_dosis === 'por_100_litros'">
                        <label class="form-label">Dosis por 100L</label>
                        <input
                            v-model="newProduct.dosis_por_100"
                            type="number"
                            step="0.01"
                            class="form-control"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Carencia (días)</label>
                        <input
                            v-model="newProduct.carencia"
                            type="number"
                            class="form-control"
                            placeholder="0"
                        />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Reingreso (días)</label>
                        <input
                            v-model="newProduct.reingreso"
                            type="number"
                            class="form-control"
                            placeholder="0"
                        />
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button @click="addProduct" class="btn w-100" :class="editingProductIndex !== null ? 'btn-warning' : 'btn-success'" type="button">
                            <i :class="editingProductIndex !== null ? 'fas fa-save' : 'fas fa-plus'" class="me-1"></i>
                            {{ editingProductIndex !== null ? 'Actualizar Producto' : 'Agregar Producto' }}
                        </button>
                    </div>
                </div>

                <!-- Vista previa de cálculos -->
                <div v-if="newProduct.product_id && totalHectareas > 0" class="alert alert-info mt-2">
                    <strong>Vista Previa:</strong><br>
                    Cantidad por hectárea: <strong>{{ calculatedQuantityPerHa.toFixed(2) }}</strong> 
                    {{ getProductUnit(newProduct.product_id) }}/ha<br>
                    Cantidad total: <strong>{{ calculatedTotalQuantity.toFixed(2) }}</strong> 
                    {{ getProductUnit(newProduct.product_id) }}
                </div>
            </div>
        </div>

        <!-- Tabla de productos agregados -->
        <div v-if="form.products.length > 0" class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Tipo Dosis</th>
                        <th class="text-end">Dosis</th>
                        <th class="text-end">Cantidad/ha</th>
                        <th class="text-end">Cantidad Total</th>
                        <th class="text-center">Carencia</th>
                        <th class="text-center">Reingreso</th>
                        <th class="text-center" style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(product, index) in form.products" :key="index">
                        <td>{{ getProductName(product.product_id) }}</td>
                        <td>
                            <span v-if="product.tipo_dosis === 'por_hectarea'" class="badge bg-primary">Por Hectárea</span>
                            <span v-else class="badge bg-info">Por 100L</span>
                        </td>
                        <td class="text-end">
                            <span v-if="product.tipo_dosis === 'por_hectarea'">
                                {{ Number(product.dosis_por_hectarea).toFixed(2) }} {{ getProductUnit(product.product_id) }}/ha
                            </span>
                            <span v-else>
                                {{ Number(product.dosis_por_100).toFixed(2) }} {{ getProductUnit(product.product_id) }}/100L
                            </span>
                        </td>
                        <td class="text-end">
                            {{ getProductQuantityPerHa(product).toFixed(2) }} {{ getProductUnit(product.product_id) }}/ha
                        </td>
                        <td class="text-end">
                            <strong>{{ getProductTotalQuantity(product).toFixed(2) }}</strong> {{ getProductUnit(product.product_id) }}
                        </td>
                        <td class="text-center">{{ product.carencia }} días</td>
                        <td class="text-center">{{ product.reingreso }} días</td>
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
