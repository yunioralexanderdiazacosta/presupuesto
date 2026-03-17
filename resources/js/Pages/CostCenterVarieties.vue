<script setup>
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    costCenters: Array,
    costCentersData: Array,
    fruits: Array,
    varieties: Array,
    rootstocks: Array,
    developmentStates: Array,
});

const title = 'Variedades por Cuartel';

// ── Estado ────────────────────────────────────────────────────────────────────
const selectedCostCenterId = ref('');
const searchTerm           = ref('');
const localCostCenters     = ref(props.costCenters.map(c => ({ ...c })));
const varieties       = ref([]);
const loading         = ref(false);
const saving          = ref(false);
const editingId       = ref(null);

const newRow = ref({
    fruit_id: '', variety_id: '', rootstock_id: '',
    development_state_id: '', surface: '', year_plantation: '', observations: ''
});
const editRow = ref({
    fruit_id: '', variety_id: '', rootstock_id: '',
    development_state_id: '', surface: '', year_plantation: '', observations: ''
});
const newRowErrors  = ref({});
const editRowErrors = ref({});

// ── Computeds ─────────────────────────────────────────────────────────────────
const selectedCostCenterData = computed(() =>
    (props.costCentersData ?? []).find(c => c.id === selectedCostCenterId.value)
);
const totalSurface = computed(() => selectedCostCenterData.value?.surface ?? 0);
const usedSurface  = computed(() =>
    varieties.value.reduce((sum, v) => sum + parseFloat(v.surface || 0), 0)
);
const remainingSurface = computed(() =>
    Math.round((totalSurface.value - usedSurface.value) * 10000) / 10000
);

// ── Computeds cuarteles ──────────────────────────────────────────────────────
const configuredCount = computed(() =>
    localCostCenters.value.filter(c => Number(c.varieties_count) > 0).length
);
const filteredCostCenters = computed(() => {
    if (!searchTerm.value) return localCostCenters.value;
    const term = searchTerm.value.toLowerCase();
    return localCostCenters.value.filter(c => c.label.toLowerCase().includes(term));
});
const ccWithVarieties    = computed(() => filteredCostCenters.value.filter(c => Number(c.varieties_count) > 0));
const ccWithoutVarieties = computed(() => filteredCostCenters.value.filter(c => Number(c.varieties_count) === 0));

const filteredVarietiesNew = computed(() => {
    if (!newRow.value.fruit_id) return [];
    return (props.varieties ?? []).filter(v => String(v.fruit_id) === String(newRow.value.fruit_id));
});
const filteredVarietiesEdit = computed(() => {
    if (!editRow.value.fruit_id) return [];
    return (props.varieties ?? []).filter(v => String(v.fruit_id) === String(editRow.value.fruit_id));
});

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(selectedCostCenterId, (id) => {
    editingId.value = null;
    if (id) loadVarieties();
    else varieties.value = [];
});
watch(() => newRow.value.fruit_id, () => { newRow.value.variety_id = ''; });
watch(() => editRow.value.fruit_id, () => { editRow.value.variety_id = ''; });

// ── API ───────────────────────────────────────────────────────────────────────
const syncLocalCount = (count) => {
    const idx = localCostCenters.value.findIndex(c => String(c.value) === String(selectedCostCenterId.value));
    if (idx !== -1) localCostCenters.value[idx].varieties_count = count;
};

const loadVarieties = async () => {
    loading.value = true;
    try {
        const res = await axios.get(route('api.cost-center-varieties', selectedCostCenterId.value));
        varieties.value = res.data;
        syncLocalCount(res.data.length);
    } catch {
        Swal.fire('Error', 'No se pudo cargar las variedades', 'error');
    } finally {
        loading.value = false;
    }
};

const addVariety = async () => {
    newRowErrors.value = {};
    saving.value = true;
    try {
        await axios.post(route('cost-center-varieties.store'), {
            ...newRow.value,
            cost_center_id: selectedCostCenterId.value
        });
        newRow.value = {
            fruit_id: '', variety_id: '', rootstock_id: '',
            development_state_id: '', surface: '', year_plantation: '', observations: ''
        };
        await loadVarieties();
        Swal.fire({ icon: 'success', title: 'Variedad agregada', timer: 900, showConfirmButton: false });
    } catch (e) {
        if (e.response?.status === 422) {
            newRowErrors.value = e.response.data.errors ?? {};
            const msg = Object.values(newRowErrors.value).flat().join('\n');
            Swal.fire('Error de validación', msg, 'error');
        } else {
            Swal.fire('Error', 'No se pudo guardar', 'error');
        }
    } finally {
        saving.value = false;
    }
};

const startEdit = (v) => {
    editingId.value = v.id;
    editRowErrors.value = {};
    editRow.value = {
        fruit_id: v.fruit_id, variety_id: v.variety_id,
        rootstock_id: v.rootstock_id, development_state_id: v.development_state_id,
        surface: v.surface, year_plantation: v.year_plantation, observations: v.observations
    };
};

const cancelEdit = () => {
    editingId.value = null;
    editRowErrors.value = {};
};

const saveEdit = async () => {
    editRowErrors.value = {};
    saving.value = true;
    try {
        await axios.post(route('cost-center-varieties.update', editingId.value), {
            ...editRow.value,
            cost_center_id: selectedCostCenterId.value
        });
        editingId.value = null;
        await loadVarieties();
        Swal.fire({ icon: 'success', title: 'Guardado', timer: 900, showConfirmButton: false });
    } catch (e) {
        if (e.response?.status === 422) {
            editRowErrors.value = e.response.data.errors ?? {};
            const msg = Object.values(editRowErrors.value).flat().join('\n');
            Swal.fire('Error de validación', msg, 'error');
        } else {
            Swal.fire('Error', 'No se pudo actualizar', 'error');
        }
    } finally {
        saving.value = false;
    }
};

const deleteVariety = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar esta variedad?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6e6e6e',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;
    try {
        await axios.delete(route('cost-center-varieties.delete', id));
        await loadVarieties();
        Swal.fire({ icon: 'success', title: 'Eliminado', timer: 800, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo eliminar', 'error');
    }
};
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-seedling me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Selector de cuartel + badges -->
                <div class="row align-items-end mb-3">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small mb-0">Selecciona un cuartel</label>
                            <span class="badge border small" :class="configuredCount === localCostCenters.length ? 'bg-success text-white' : 'bg-light text-secondary'">
                                <i class="fas fa-check-circle me-1" v-if="configuredCount === localCostCenters.length"></i>
                                {{ configuredCount }} / {{ localCostCenters.length }} configurados
                            </span>
                        </div>
                        <input
                            v-model="searchTerm"
                            type="text"
                            class="form-control form-control-sm mb-1"
                            placeholder="Filtrar cuarteles por nombre..."
                        />
                        <select v-model="selectedCostCenterId" class="form-select form-select-sm">
                            <option value="">-- Seleccione un cuartel --</option>
                            <optgroup v-if="ccWithVarieties.length" :label="`✓ Con variedades (${ccWithVarieties.length})`">
                                <option v-for="c in ccWithVarieties" :key="c.value" :value="c.value">
                                    {{ c.label }} · {{ c.varieties_count }} var.
                                </option>
                            </optgroup>
                            <optgroup v-if="ccWithoutVarieties.length" :label="`○ Sin variedades (${ccWithoutVarieties.length})`">
                                <option v-for="c in ccWithoutVarieties" :key="c.value" :value="c.value">
                                    {{ c.label }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-center gap-2 flex-wrap mt-2 mt-md-0" v-if="selectedCostCenterId">
                        <span class="badge bg-secondary" style="font-size: 0.82rem;">
                            Total cuartel: {{ totalSurface }} ha
                        </span>
                        <span class="badge bg-warning text-dark" style="font-size: 0.82rem;">
                            Usado: {{ Math.round(usedSurface * 10000) / 10000 }} ha
                        </span>
                        <span class="badge" :class="remainingSurface < 0 ? 'bg-danger' : 'bg-success'" style="font-size: 0.82rem;">
                            Disponible: {{ remainingSurface }} ha
                        </span>
                    </div>
                </div>

                <!-- Sin cuartel seleccionado -->
                <div v-if="!selectedCostCenterId" class="text-center text-muted py-5">
                    <i class="fas fa-hand-pointer fa-2x mb-2 d-block opacity-50"></i>
                    <span>Selecciona un cuartel para ver y gestionar sus variedades</span>
                </div>

                <!-- Tabla -->
                <template v-else>
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-success" role="status"></div>
                        <div class="mt-2 text-muted small">Cargando variedades...</div>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Frutal</th>
                                    <th>Variedad</th>
                                    <th>Portainjerto</th>
                                    <th>Est. Desarrollo</th>
                                    <th style="width:100px;">Sup. (ha)</th>
                                    <th style="width:100px;">Año Plant.</th>
                                    <th>Observaciones</th>
                                    <th class="text-end" style="width:80px; white-space:nowrap;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Vacío -->
                                <tr v-if="varieties.length === 0">
                                    <td colspan="8" class="text-center text-muted py-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Sin variedades registradas. Agrégalas abajo.
                                    </td>
                                </tr>

                                <template v-for="v in varieties" :key="v.id">
                                    <!-- Fila lectura -->
                                    <tr v-if="editingId !== v.id">
                                        <td>{{ v.fruit_name }}</td>
                                        <td>{{ v.variety_name }}</td>
                                        <td>{{ v.rootstock_name ?? '—' }}</td>
                                        <td>{{ v.development_state_name ?? '—' }}</td>
                                        <td>{{ v.surface }}</td>
                                        <td>{{ v.year_plantation ?? '—' }}</td>
                                        <td class="text-truncate" style="max-width:180px;">{{ v.observations ?? '—' }}</td>
                                        <td class="text-end" style="white-space:nowrap;">
                                            <button type="button" @click="startEdit(v)" v-tooltip="'Editar'"
                                                class="btn btn-icon btn-active-light-primary w-28px h-28px me-1">
                                                <i class="fas fa-pencil-alt fa-sm"></i>
                                            </button>
                                            <button type="button" @click="deleteVariety(v.id)" v-tooltip="'Eliminar'"
                                                class="btn btn-icon btn-active-light-danger w-28px h-28px">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Fila edición -->
                                    <tr v-else class="table-warning">
                                        <td>
                                            <select v-model="editRow.fruit_id" class="form-select form-select-sm"
                                                :class="{ 'is-invalid': editRowErrors.fruit_id }">
                                                <option value="">-- Frutal --</option>
                                                <option v-for="f in fruits" :key="f.value" :value="f.value">{{ f.label }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select v-model="editRow.variety_id" class="form-select form-select-sm"
                                                :class="{ 'is-invalid': editRowErrors.variety_id }"
                                                :disabled="!editRow.fruit_id">
                                                <option value="">-- Variedad --</option>
                                                <option v-for="vr in filteredVarietiesEdit" :key="vr.value" :value="vr.value">{{ vr.label }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select v-model="editRow.rootstock_id" class="form-select form-select-sm">
                                                <option value="">— Opcional —</option>
                                                <option v-for="r in rootstocks" :key="r.value" :value="r.value">{{ r.label }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select v-model="editRow.development_state_id" class="form-select form-select-sm">
                                                <option value="">— Opcional —</option>
                                                <option v-for="d in developmentStates" :key="d.value" :value="d.value">{{ d.label }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input v-model="editRow.surface" type="number" step="0.01" min="0"
                                                class="form-control form-control-sm"
                                                :class="{ 'is-invalid': editRowErrors.surface }" placeholder="0.00" />
                                        </td>
                                        <td>
                                            <input v-model="editRow.year_plantation" type="number" min="1900" max="2100"
                                                class="form-control form-control-sm" placeholder="Año" />
                                        </td>
                                        <td>
                                            <input v-model="editRow.observations" type="text"
                                                class="form-control form-control-sm" placeholder="Obs..." />
                                        </td>
                                        <td class="text-end">
                                            <button type="button" @click="saveEdit" :disabled="saving" v-tooltip="'Guardar'"
                                                class="btn btn-icon btn-active-light-success w-28px h-28px me-1">
                                                <i class="fas fa-check fa-sm text-success"></i>
                                            </button>
                                            <button type="button" @click="cancelEdit" v-tooltip="'Cancelar'"
                                                class="btn btn-icon btn-active-light-secondary w-28px h-28px">
                                                <i class="fas fa-times fa-sm text-secondary"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Fila para agregar -->
                                <tr class="table-light">
                                    <td>
                                        <select v-model="newRow.fruit_id" class="form-select form-select-sm"
                                            :class="{ 'is-invalid': newRowErrors.fruit_id }">
                                            <option value="">-- Frutal --</option>
                                            <option v-for="f in fruits" :key="f.value" :value="f.value">{{ f.label }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select v-model="newRow.variety_id" class="form-select form-select-sm"
                                            :class="{ 'is-invalid': newRowErrors.variety_id }"
                                            :disabled="!newRow.fruit_id">
                                            <option value="">-- Variedad --</option>
                                            <option v-for="v in filteredVarietiesNew" :key="v.value" :value="v.value">{{ v.label }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select v-model="newRow.rootstock_id" class="form-select form-select-sm">
                                            <option value="">— Opcional —</option>
                                            <option v-for="r in rootstocks" :key="r.value" :value="r.value">{{ r.label }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select v-model="newRow.development_state_id" class="form-select form-select-sm">
                                            <option value="">— Opcional —</option>
                                            <option v-for="d in developmentStates" :key="d.value" :value="d.value">{{ d.label }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input v-model="newRow.surface" type="number" step="0.01" min="0"
                                            class="form-control form-control-sm"
                                            :class="{ 'is-invalid': newRowErrors.surface }" placeholder="0.00" />
                                    </td>
                                    <td>
                                        <input v-model="newRow.year_plantation" type="number" min="1900" max="2100"
                                            class="form-control form-control-sm" placeholder="Año" />
                                    </td>
                                    <td>
                                        <input v-model="newRow.observations" type="text"
                                            class="form-control form-control-sm" placeholder="Obs..." />
                                    </td>
                                    <td class="text-end">
                                        <button type="button" @click="addVariety"
                                            :disabled="saving || !newRow.fruit_id || !newRow.variety_id || !newRow.surface"
                                            v-tooltip="'Agregar variedad'"
                                            class="btn btn-sm btn-success d-flex align-items-center gap-1 ms-auto px-2">
                                            <i class="fas fa-plus fa-sm"></i>
                                            <span>Agregar</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>


