<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import Swal from 'sweetalert2';

const props = defineProps({
    fertilizerOrder: { type: Object, default: null },
    products: { type: Array, default: () => [] },
    irrigationPumps: { type: Array, default: () => [] },
    costCenters: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    modalId: String,
    isEdit: { type: Boolean, default: false },
});

const form = useForm({
    date: '',
    irrigation_pump_id: null,
    responsable: '',
    observations: '',
    products: [],
    irrigation_sectors: [],
    cost_centers: [],
});

const selectedPump = ref(null);
const availableSectors = computed(() => {
    if (!selectedPump.value) return [];
    const pump = props.irrigationPumps.find(p => p.value === selectedPump.value);
    return pump?.sectors || [];
});

const totalSurface = computed(() => {
    if (form.irrigation_sectors.length === 0) return 0;
    return form.irrigation_sectors.reduce((sum, sectorId) => {
        const sector = availableSectors.value.find(s => s.value === sectorId);
        return sum + (sector?.surface || 0);
    }, 0);
});

watch(selectedPump, (newPump) => {
    form.irrigation_pump_id = newPump;
    form.irrigation_sectors = [];
});

const addProduct = () => {
    form.products.push({
        product_id: null,
        dosis_por_hectarea: 0,
        cantidad_total: 0,
        unit_id: null,
    });
};

const removeProduct = (index) => {
    form.products.splice(index, 1);
};

const calculateCantidadTotal = (product) => {
    product.cantidad_total = (product.dosis_por_hectarea || 0) * totalSurface.value;
};

watch(() => form.irrigation_sectors, () => {
    form.products.forEach(calculateCantidadTotal);
}, { deep: true });

watch(() => form.products, () => {
    form.products.forEach(product => {
        watch(() => product.dosis_por_hectarea, () => {
            calculateCantidadTotal(product);
        });
    });
}, { deep: true });

const submit = () => {
    if (form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }

    if (form.irrigation_sectors.length === 0) {
        Swal.fire('Error', 'Debe seleccionar al menos un sector de riego', 'error');
        return;
    }

    const url = props.isEdit 
        ? route('fertilizer-orders.update', props.fertilizerOrder.id)
        : route('fertilizer-orders.store');

    const method = props.isEdit ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: props.isEdit ? 'Orden actualizada correctamente' : 'Orden creada correctamente',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                closeModal();
                window.location.reload();
            });
        },
        onError: (errors) => {
            console.error('Errors:', errors);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: Object.values(errors).flat().join('\n')
            });
        }
    });
};

const closeModal = () => {
    const modalElement = document.getElementById(props.modalId);
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
};

onMounted(() => {
    if (props.isEdit && props.fertilizerOrder) {
        form.date = props.fertilizerOrder.date;
        form.irrigation_pump_id = props.fertilizerOrder.irrigation_pump_id;
        selectedPump.value = props.fertilizerOrder.irrigation_pump_id;
        form.responsable = props.fertilizerOrder.responsable || '';
        form.observations = props.fertilizerOrder.observations || '';
        
        form.products = props.fertilizerOrder.order_products?.map(op => ({
            product_id: op.product_id,
            dosis_por_hectarea: parseFloat(op.dosis_por_hectarea),
            cantidad_total: parseFloat(op.cantidad_total),
            unit_id: op.unit_id,
        })) || [];

        form.irrigation_sectors = props.fertilizerOrder.order_irrigation_sectors?.map(ois => ois.irrigation_sector_id) || [];
        form.cost_centers = props.fertilizerOrder.order_cost_centers?.map(occ => occ.cost_center_id) || [];
    } else {
        form.date = new Date().toISOString().split('T')[0];
        addProduct();
    }
});
</script>

<template>
    <form @submit.prevent="submit">
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
                <!-- Fecha -->
                <div class="col-md-4 mb-2">
                    <label class="form-label small mb-1">Fecha <span class="text-danger">*</span></label>
                    <input 
                        v-model="form.date" 
                        type="date" 
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors.date }"
                    />
                    <div v-if="form.errors.date" class="invalid-feedback">{{ form.errors.date }}</div>
                </div>

                <!-- Bomba de Riego -->
                <div class="col-md-4 mb-2">
                    <label class="form-label small mb-1">Bomba de Riego</label>
                    <Multiselect
                        v-model="selectedPump"
                        :options="props.irrigationPumps"
                        :searchable="true"
                        :close-on-select="true"
                        placeholder="Seleccionar bomba"
                        label="label"
                        value-prop="value"
                        class="form-control-sm"
                        :class="{ 'is-invalid': form.errors.irrigation_pump_id }"
                    />
                    <div v-if="form.errors.irrigation_pump_id" class="invalid-feedback">{{ form.errors.irrigation_pump_id }}</div>
                </div>

                <!-- Responsable -->
                <div class="col-md-4 mb-2">
                    <label class="form-label small mb-1">Responsable</label>
                    <input 
                        v-model="form.responsable" 
                        type="text" 
                        class="form-control form-control-sm"
                        placeholder="Nombre del responsable"
                    />
                </div>
            </div>

            <hr class="my-2">

            <!-- Sectores de Riego -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-water me-1"></i>Sectores de Riego
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 mb-2">
                    <label class="form-label small mb-1">Sectores de Riego <span class="text-danger">*</span></label>
                    <Multiselect
                        v-model="form.irrigation_sectors"
                        :options="availableSectors"
                        mode="tags"
                        :searchable="true"
                        :close-on-select="false"
                        placeholder="Seleccionar sectores"
                        label="label"
                        value-prop="value"
                        :disabled="!selectedPump"
                        class="form-control-sm"
                        :class="{ 'is-invalid': form.errors.irrigation_sectors }"
                    />
                    <div v-if="form.errors.irrigation_sectors" class="invalid-feedback d-block">{{ form.errors.irrigation_sectors }}</div>
                    <small class="text-muted" v-if="totalSurface > 0">
                        Superficie total seleccionada: <strong>{{ totalSurface.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} ha</strong>
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

            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small mb-0">Productos <span class="text-danger">*</span></label>
                    <button 
                        type="button" 
                        class="btn btn-sm btn-success"
                        @click="addProduct"
                    >
                        <i class="fas fa-plus me-1"></i> Agregar Producto
                    </button>
                </div>

                <div v-if="form.products.length === 0" class="alert alert-info small">
                    No hay productos agregados. Haga clic en "Agregar Producto" para comenzar.
                </div>

                <div v-for="(product, index) in form.products" :key="index" class="card mb-2">
                    <div class="card-body p-2">
                        <div class="row align-items-end">
                            <!-- Producto -->
                            <div class="col-md-4 mb-2">
                                <label class="form-label small mb-1">Producto</label>
                                <select
                                    v-model="product.product_id"
                                    class="form-select form-select-sm"
                                >
                                    <option :value="null">Seleccionar producto...</option>
                                    <option 
                                        v-for="prod in props.products" 
                                        :key="prod.value" 
                                        :value="prod.value"
                                    >
                                        {{ prod.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Dosis por Hectárea -->
                            <div class="col-md-3 mb-2">
                                <label class="form-label small mb-1">Dosis/ha</label>
                                <input 
                                    v-model.number="product.dosis_por_hectarea"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control form-control-sm"
                                    placeholder="0.00"
                                />
                            </div>

                            <!-- Cantidad Total (readonly) -->
                            <div class="col-md-3 mb-2">
                                <label class="form-label small mb-1">Cantidad Total</label>
                                <input 
                                    :value="product.cantidad_total.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"
                                    type="text"
                                    class="form-control form-control-sm"
                                    readonly
                                />
                            </div>

                            <!-- Acciones -->
                            <div class="col-md-2 mb-2">
                                <button 
                                    type="button"
                                    class="btn btn-falcon-default btn-sm w-100"
                                    @click="removeProduct(index)"
                                    :disabled="form.products.length === 1"
                                >
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
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
                <div class="col-12 mb-2">
                    <label class="form-label small mb-1">Centros de Costo</label>
                    <Multiselect
                        v-model="form.cost_centers"
                        :options="props.costCenters"
                        mode="tags"
                        :searchable="true"
                        :close-on-select="false"
                        placeholder="Seleccionar centros de costo"
                        label="label"
                        value-prop="value"
                        class="form-control-sm"
                    />
                </div>
            </div>

            <hr class="my-2">

            <!-- Observaciones -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-comment me-1"></i>Observaciones
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 mb-2">
                    <textarea 
                        v-model="form.observations"
                        class="form-control form-control-sm"
                        rows="3"
                        placeholder="Observaciones adicionales..."
                    ></textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button 
                    type="button" 
                    class="btn btn-secondary btn-sm"
                    @click="closeModal"
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
                    {{ isEdit ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
