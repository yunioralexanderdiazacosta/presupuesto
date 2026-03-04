<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import Swal from 'sweetalert2';

const props = defineProps({
    form: { type: Object, default: null },
    fertilizerOrder: { type: Object, default: null },
    products: { type: Array, default: () => [] },
    irrigationPumps: { type: Array, default: () => [] },
    costCenters: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    groupings: { type: Array, default: () => [] },
    modalId: String,
    isEdit: { type: Boolean, default: false },
    isEditing: { type: Boolean, default: false },
});

// Usar form externo si existe (modo edición), sino crear uno interno (modo creación)
const localForm = props.form || useForm({
    date: '',
    irrigation_pump_id: null,
    responsable: '',
    observations: '',
    products: [],
    irrigation_sectors: [],
    cost_centers: [],
});

const form = localForm;

const selectedPump = ref(null);
const selectedGrouping = ref(null);
const expandedCC = ref(false);

// Inicializar selectedPump si estamos en modo edición
if (props.fertilizerOrder && props.fertilizerOrder.irrigation_pump_id) {
    selectedPump.value = props.fertilizerOrder.irrigation_pump_id;
}

// Watch para sincronizar selectedPump cuando cambia el form desde fuera
watch(() => props.form?.irrigation_pump_id, (newValue) => {
    if (newValue && selectedPump.value !== newValue) {
        selectedPump.value = newValue;
    }
}, { immediate: true });

const availableSectors = computed(() => {
    if (!selectedPump.value) return [];
    const pump = props.irrigationPumps.find(p => p.value === selectedPump.value);
    return pump?.sectors || [];
});

const totalSurface = computed(() => {
    if (form.irrigation_sectors.length === 0) return 0;
    return form.irrigation_sectors.reduce((sum, sectorId) => {
        const sector = availableSectors.value.find(s => s.value === sectorId);
        const surface = parseFloat(sector?.surface) || 0;
        return sum + surface;
    }, 0);
});

watch(selectedPump, (newPump, oldPump) => {
    form.irrigation_pump_id = newPump;
    // Solo limpiar sectores si estamos cambiando la bomba manualmente (no en carga inicial)
    if (oldPump !== null) {
        form.irrigation_sectors = [];
    }
});

watch(selectedGrouping, (groupingId) => {
    if (!groupingId) return;
    const grouping = props.groupings?.find(g => g.id == groupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        const groupCCs = grouping.cost_centers.map(cc => cc.id);
        form.cost_centers = groupCCs;
    }
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
    const dosis = parseFloat(product.dosis_por_hectarea) || 0;
    const surface = totalSurface.value || 0;
    const calculated = dosis * surface;
    product.cantidad_total = isNaN(calculated) ? 0 : calculated;
};

// Recalcular cantidades cuando cambian los sectores
watch(() => form.irrigation_sectors, () => {
    form.products.forEach(calculateCantidadTotal);
}, { deep: true });

// Recalcular cantidad cuando cambia la dosis de un producto
watch(() => form.products.map(p => p.dosis_por_hectarea), () => {
    form.products.forEach(calculateCantidadTotal);
}, { deep: true });

// Recalcular cantidades cuando availableSectors está disponible (en modo edición)
watch(() => availableSectors.value, (newSectors) => {
    if (newSectors.length > 0 && form.irrigation_sectors.length > 0 && form.products.length > 0) {
        // Esperar un tick para asegurar que totalSurface se haya actualizado
        setTimeout(() => {
            form.products.forEach(calculateCantidadTotal);
        }, 100);
    }
}, { immediate: true, deep: true });

const submit = () => {
    // Si está en modo edición (desde EditModal), no hacer nada aquí
    // El modal maneja el guardado
    if (props.isEditing) {
        return;
    }

    // Modo creación
    if (form.products.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return;
    }

    if (form.irrigation_sectors.length === 0) {
        Swal.fire('Error', 'Debe seleccionar al menos un sector de riego', 'error');
        return;
    }

    form.post(route('fertilizer-orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Orden creada correctamente',
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
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small mb-0">Centros de Costo</label>
                        <div class="d-flex align-items-center gap-1">
                            <span v-if="form.cost_centers.length > 0" class="badge bg-soft-primary text-primary" style="font-size: 0.7rem;">
                                {{ form.cost_centers.length }} seleccionados
                            </span>
                            <button
                                v-if="form.cost_centers.length > 5"
                                type="button"
                                @click="expandedCC = !expandedCC"
                                class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                                style="font-size: 0.7rem;"
                            >
                                <i class="fas" :class="expandedCC ? 'fa-compress-alt' : 'fa-expand-alt'" style="font-size: 0.65rem;"></i>
                                {{ expandedCC ? 'Colapsar' : 'Ver todos' }}
                            </button>
                        </div>
                    </div>
                    <Multiselect
                        v-model="form.cost_centers"
                        :options="props.costCenters"
                        mode="tags"
                        :searchable="true"
                        :close-on-select="false"
                        placeholder="Seleccionar centros de costo"
                        label="label"
                        value-prop="value"
                        class="form-control-sm multiselect-tags-limited"
                        :class="{ 'multiselect-tags-expanded': expandedCC }"
                    />
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
                                    :value="(product.cantidad_total || 0).toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 2 })"
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

            <!-- Botones (solo en modo creación) -->
            <div v-if="!isEditing" class="d-flex justify-content-end gap-2 mt-3">
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
                    Guardar
                </button>
            </div>
        </div>
    </form>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>

<style scoped>
.multiselect-tags-limited :deep(.multiselect-tags) {
    max-height: 32px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.multiselect-tags-expanded :deep(.multiselect-tags) {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
}
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar {
    width: 4px;
}
.multiselect-tags-expanded :deep(.multiselect-tags)::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 4px;
}
</style>
