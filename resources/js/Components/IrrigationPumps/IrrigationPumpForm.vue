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
    props.form.sectors.splice(index, 1);
}

const getTotalSurface = () => {
    return props.form.sectors.reduce((sum, sector) => sum + parseFloat(sector.surface || 0), 0);
};
</script>

<template>
    <div class="row g-3">
        <!-- Datos de la Bomba -->
        <div class="col-12">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-tint me-2"></i>Datos de la Bomba
            </h6>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <TextInput
                v-model="form.name"
                type="text"
                class="form-control"
                placeholder="Ej: Bomba Central A"
            />
            <InputError :message="form.errors.name" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Código</label>
            <TextInput
                v-model="form.code"
                type="text"
                class="form-control"
                placeholder="Ej: BC-001"
            />
            <InputError :message="form.errors.code" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Marca</label>
            <TextInput
                v-model="form.brand"
                type="text"
                class="form-control"
                placeholder="Ej: Grundfos"
            />
            <InputError :message="form.errors.brand" class="mt-1" />
        </div>

        <div class="col-md-6">
            <label class="form-label">Modelo</label>
            <TextInput
                v-model="form.model"
                type="text"
                class="form-control"
                placeholder="Ej: CR 64-2"
            />
            <InputError :message="form.errors.model" class="mt-1" />
        </div>

        <!-- Sectores de Riego -->
        <div class="col-12 mt-4">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-layer-group me-2"></i>Sectores de Riego
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
                            <td><strong>{{ sector.name }}</strong></td>
                            <td>{{ parseFloat(sector.surface).toFixed(2) }}</td>
                            <td class="small">{{ sector.observations || '-' }}</td>
                            <td class="text-center">
                                <button
                                    @click="removeSector(index)"
                                    type="button"
                                    class="btn btn-sm btn-falcon-default"
                                    title="Eliminar"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
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
