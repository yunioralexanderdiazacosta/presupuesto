<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    costCenter: { type: Object, default: null }
});

const page = usePage();

// ── Estado ────────────────────────────────────────────────────────────────────
const varieties    = ref([]);
const loading      = ref(false);
const saving       = ref(false);
const editingId    = ref(null);

// Fila para agregar nueva variedad
const newRow = ref({
    fruit_id: '',
    variety_id: '',
    rootstock_id: '',
    development_state_id: '',
    surface: '',
    year_plantation: '',
    observations: ''
});

// Fila en edición
const editRow = ref({
    fruit_id: '',
    variety_id: '',
    rootstock_id: '',
    development_state_id: '',
    surface: '',
    year_plantation: '',
    observations: ''
});

const newRowErrors = ref({});
const editRowErrors = ref({});

// ── Computeds ─────────────────────────────────────────────────────────────────
const fruits           = computed(() => page.props.fruits ?? []);
const allVarieties     = computed(() => page.props.varieties ?? []);
const rootstocks       = computed(() => page.props.rootstocks ?? []);
const developmentStates = computed(() => page.props.developmentStates ?? []);

const filteredVarietiesNew = computed(() => {
    if (!newRow.value.fruit_id) return [];
    return allVarieties.value.filter(v => v.fruit_id === newRow.value.fruit_id);
});

const filteredVarietiesEdit = computed(() => {
    if (!editRow.value.fruit_id) return [];
    return allVarieties.value.filter(v => v.fruit_id === editRow.value.fruit_id);
});

const totalSurface = computed(() => parseFloat(props.costCenter?.surface ?? 0));
const usedSurface  = computed(() =>
    varieties.value.reduce((sum, v) => sum + parseFloat(v.surface || 0), 0)
);
const remainingSurface = computed(() =>
    Math.round((totalSurface.value - usedSurface.value) * 10000) / 10000
);

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(() => props.costCenter, (cc) => {
    if (cc?.id) loadVarieties();
});

watch(() => newRow.value.fruit_id, () => {
    newRow.value.variety_id = '';
});

watch(() => editRow.value.fruit_id, () => {
    editRow.value.variety_id = '';
});

// ── API ───────────────────────────────────────────────────────────────────────
const loadVarieties = async () => {
    if (!props.costCenter?.id) return;
    loading.value = true;
    try {
        const res = await axios.get(route('api.cost-center-varieties', props.costCenter.id));
        varieties.value = res.data;
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
            cost_center_id: props.costCenter.id
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
        fruit_id:             v.fruit_id,
        variety_id:           v.variety_id,
        rootstock_id:         v.rootstock_id,
        development_state_id: v.development_state_id,
        surface:              v.surface,
        year_plantation:      v.year_plantation,
        observations:         v.observations
    };
};

const cancelEdit = () => {
    editingId.value = null;
    editRow.value   = {};
    editRowErrors.value = {};
};

const saveEdit = async () => {
    editRowErrors.value = {};
    saving.value = true;
    try {
        await axios.post(route('cost-center-varieties.update', editingId.value), {
            ...editRow.value,
            cost_center_id: props.costCenter.id,
            _method: 'POST'
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

defineExpose({ loadVarieties });
</script>

<template>
    <Modal :maxWidth="'mw'" dialogStyle="max-width: 1400px; width: 95%;" :id="'costCenterVarietiesDetailModal'">
        <template #header>
            <div class="d-flex flex-column text-start w-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                          style="width: 38px; height: 38px; font-size: 1.3rem;">
                        <i class="fas fa-seedling"></i>
                    </span>
                    <span>
                        <span class="fw-bold" style="font-size: 1.1rem; color: #2d3748;">
                            Variedades — {{ costCenter?.name ?? '...' }}
                        </span>
                        <br>
                        <span class="text-muted" style="font-size: 0.82rem;">
                            Gestiona las variedades plantadas en este cuartel
                        </span>
                    </span>
                </div>

                <!-- Badges superficie -->
                <div v-if="costCenter" class="d-flex gap-2 flex-wrap" style="font-size: 0.82rem;">
                    <span class="badge bg-secondary">
                        Total cuartel: {{ totalSurface }} ha
                    </span>
                    <span class="badge bg-warning text-dark">
                        Usado: {{ Math.round(usedSurface * 10000) / 10000 }} ha
                    </span>
                    <span class="badge" :class="remainingSurface < 0 ? 'bg-danger' : 'bg-success'">
                        Disponible: {{ remainingSurface }} ha
                    </span>
                </div>
            </div>
        </template>

        <template #body>
            <!-- Spinner carga inicial -->
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-success" role="status"></div>
                <div class="mt-2 text-muted small">Cargando variedades...</div>
            </div>

            <template v-else>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Frutal</th>
                                <th>Variedad</th>
                                <th>Portainjerto</th>
                                <th>Est. Desarrollo</th>
                                <th style="width:90px;">Sup. (ha)</th>
                                <th style="width:90px;">Año Plant.</th>
                                <th>Observaciones</th>
                                <th class="text-end text-nowrap" style="width:100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filas existentes -->
                            <template v-if="varieties.length === 0 && !loading">
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Sin variedades registradas. Agrégalas abajo.
                                    </td>
                                </tr>
                            </template>

                            <template v-for="v in varieties" :key="v.id">
                                <!-- Modo lectura -->
                                <tr v-if="editingId !== v.id">
                                    <td>{{ v.fruit_name }}</td>
                                    <td>{{ v.variety_name }}</td>
                                    <td>{{ v.rootstock_name ?? '—' }}</td>
                                    <td>{{ v.development_state_name ?? '—' }}</td>
                                    <td>{{ v.surface }}</td>
                                    <td>{{ v.year_plantation ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width:160px;">{{ v.observations ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end flex-nowrap gap-1">
                                            <button type="button" @click="startEdit(v)"
                                                v-tooltip="'Editar'"
                                                class="btn btn-icon btn-active-light-primary w-28px h-28px">
                                                <i class="fas fa-pencil-alt fa-sm"></i>
                                            </button>
                                            <button type="button" @click="deleteVariety(v.id)"
                                                v-tooltip="'Eliminar'"
                                                class="btn btn-icon btn-active-light-danger w-28px h-28px">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modo edición (fila expandida) -->
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
                                            <option v-for="v in filteredVarietiesEdit" :key="v.value" :value="v.value">{{ v.label }}</option>
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
                                            :class="{ 'is-invalid': editRowErrors.surface }"
                                            placeholder="0.00" />
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
                                        <div class="d-flex justify-content-end flex-nowrap gap-1">
                                            <button type="button" @click="saveEdit" :disabled="saving"
                                                v-tooltip="'Guardar cambios'"
                                                class="btn btn-icon btn-active-light-success w-28px h-28px">
                                                <i class="fas fa-check fa-sm text-success"></i>
                                            </button>
                                            <button type="button" @click="cancelEdit"
                                                v-tooltip="'Cancelar'"
                                                class="btn btn-icon btn-active-light-secondary w-28px h-28px">
                                                <i class="fas fa-times fa-sm text-secondary"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Fila para agregar nueva variedad -->
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
                                        :class="{ 'is-invalid': newRowErrors.surface }"
                                        placeholder="0.00" />
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
                                    <button type="button" @click="addVariety" :disabled="saving || !newRow.fruit_id || !newRow.variety_id || !newRow.surface"
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
        </template>

        <template #footer>
            <button type="button" data-bs-dismiss="modal" class="btn btn-light">Cerrar</button>
        </template>
    </Modal>
</template>
