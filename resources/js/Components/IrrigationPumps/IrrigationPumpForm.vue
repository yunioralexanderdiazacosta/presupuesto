<script setup>
import { ref } from 'vue';
import Swal from 'sweetalert2';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    isEditing: {
        type: Boolean,
        default: false
    }
});

const newSector = ref({
    name: '',
    surface: '',
    observations: ''
});

const editingIndex = ref(null);

function addSector() {
    if (!newSector.value.name || !newSector.value.surface) {
        Swal.fire('Error', 'El nombre y superficie del sector son obligatorios', 'error');
        return;
    }

    if (parseFloat(newSector.value.surface) <= 0) {
        Swal.fire('Error', 'La superficie debe ser mayor a 0', 'error');
        return;
    }

    props.form.sectors.push({ ...newSector.value });
    
    // Resetear formulario de sector
    newSector.value = {
        name: '',
        surface: '',
        observations: ''
    };
}

function removeSector(index) {
    const sector = props.form.sectors[index];
    
    // Verificar si tiene órdenes asociadas
    if (sector.orders_count && sector.orders_count > 0) {
        Swal.fire({
            title: 'No se puede eliminar',
            html: `El sector "<strong>${sector.name}</strong>" está siendo usado en <strong>${sector.orders_count}</strong> orden(es) de fertilizante.<br><br>Debe eliminar primero las órdenes asociadas.`,
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    Swal.fire({
        title: '¿Eliminar sector?',
        text: `Se eliminará el sector "${sector.name}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            props.form.sectors.splice(index, 1);
            if (editingIndex.value === index) {
                editingIndex.value = null;
            }
        }
    });
}

function editSector(index) {
    editingIndex.value = index;
}

function cancelEdit() {
    editingIndex.value = null;
}

function saveEdit(index) {
    const sector = props.form.sectors[index];
    
    if (!sector.name || !sector.surface) {
        Swal.fire('Error', 'El nombre y superficie son obligatorios', 'error');
        return;
    }

    if (parseFloat(sector.surface) <= 0) {
        Swal.fire('Error', 'La superficie debe ser mayor a 0', 'error');
        return;
    }

    editingIndex.value = null;
}

const getTotalSurface = () => {
    return props.form.sectors.reduce((sum, sector) => sum + parseFloat(sector.surface || 0), 0);
};
</script>

<template>
    <div class="row g-3">
        <!-- Datos de la Bomba -->
        <div class="col-12">
            <h6 class="pump-section-title">
                <i class="fas fa-tint"></i>Datos de la Bomba
            </h6>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <TextInput
                v-model="form.name"
                type="text"
                class="form-control form-control-sm"
                placeholder="Ej: Bomba Central A"
            />
            <InputError :message="form.errors.name" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Código</label>
            <TextInput
                v-model="form.code"
                type="text"
                class="form-control form-control-sm"
                placeholder="Ej: BC-001"
            />
            <InputError :message="form.errors.code" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Marca</label>
            <TextInput
                v-model="form.brand"
                type="text"
                class="form-control form-control-sm"
                placeholder="Ej: Grundfos"
            />
            <InputError :message="form.errors.brand" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Modelo</label>
            <TextInput
                v-model="form.model"
                type="text"
                class="form-control form-control-sm"
                placeholder="Ej: CR 64-2"
            />
            <InputError :message="form.errors.model" class="mt-1" />
        </div>

        <div class="col-12"><hr class="pump-section-divider"></div>

        <!-- Sectores de Riego -->
        <div class="col-12">
            <h6 class="pump-section-title">
                <i class="fas fa-layer-group"></i>Sectores de Riego
                <span class="badge bg-secondary ms-2">{{ form.sectors.length }} sectores</span>
                <span v-if="form.sectors.length > 0" class="badge bg-info ms-1">
                    Total: {{ getTotalSurface().toFixed(2) }} ha
                </span>
            </h6>
        </div>

        <!-- Formulario para agregar sector -->
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-plus-circle me-2"></i>Agregar Sector</strong>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Nombre del Sector <span class="text-danger">*</span></label>
                            <input
                                v-model="newSector.name"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Ej: Sector Norte"
                            />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Superficie (ha) <span class="text-danger">*</span></label>
                            <input
                                v-model="newSector.surface"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="form-control form-control-sm"
                                placeholder="0.00"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Observaciones</label>
                            <input
                                v-model="newSector.observations"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Opcional"
                            />
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button
                                @click="addSector"
                                type="button"
                                class="btn btn-sm btn-primary w-100"
                            >
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de sectores agregados -->
        <div class="col-12" v-if="form.sectors.length > 0">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nombre del Sector</th>
                            <th>Superficie (ha)</th>
                            <th>Observaciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(sector, index) in form.sectors" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <input 
                                    v-if="editingIndex === index"
                                    v-model="sector.name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Nombre del sector"
                                />
                                <div v-else>
                                    <strong>{{ sector.name }}</strong>
                                    <span 
                                        v-if="sector.orders_count && sector.orders_count > 0" 
                                        class="badge bg-info ms-2"
                                        title="Este sector está siendo usado en órdenes"
                                    >
                                        <i class="fas fa-lock me-1"></i>{{ sector.orders_count }} orden(es)
                                    </span>
                                </div>
                            </td>
                            <td>
                                <input 
                                    v-if="editingIndex === index"
                                    v-model="sector.surface"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="form-control form-control-sm"
                                    style="width: 100px;"
                                />
                                <span v-else>{{ parseFloat(sector.surface).toFixed(2) }}</span>
                            </td>
                            <td class="small">
                                <input 
                                    v-if="editingIndex === index"
                                    v-model="sector.observations"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Observaciones"
                                />
                                <span v-else>{{ sector.observations || '-' }}</span>
                            </td>
                            <td class="text-center">
                                <div v-if="editingIndex === index" class="btn-group btn-group-sm">
                                    <button
                                        @click="saveEdit(index)"
                                        type="button"
                                        class="btn btn-success btn-sm"
                                        title="Guardar"
                                    >
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        title="Cancelar"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div v-else class="btn-group btn-group-sm">
                                    <button
                                        @click="editSector(index)"
                                        type="button"
                                        class="btn btn-falcon-default btn-sm"
                                        title="Editar"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        @click="removeSector(index)"
                                        type="button"
                                        class="btn btn-falcon-default btn-sm"
                                        :disabled="sector.orders_count && sector.orders_count > 0"
                                        :title="sector.orders_count && sector.orders_count > 0 ? 'No se puede eliminar: sector en uso' : 'Eliminar'"
                                        :class="{ 'opacity-50': sector.orders_count && sector.orders_count > 0 }"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total:</strong></td>
                            <td><strong>{{ getTotalSurface().toFixed(2) }} ha</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="col-12" v-else>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Debe agregar al menos un sector de riego
            </div>
        </div>
    </div>
</template>

<style scoped>
.pump-section-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--kt-text-muted, #6c757d);
    margin-bottom: 0.9rem;
}
.pump-section-title i {
    color: #2c7be5;
}
.pump-section-divider {
    border: none;
    height: 1px;
    background: linear-gradient(90deg, rgba(44,123,229,0.15) 0%, rgba(44,123,229,0.35) 50%, rgba(44,123,229,0.15) 100%);
    margin: 0.25rem 0 1rem;
}
</style>
