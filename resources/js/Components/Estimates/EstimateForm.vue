<script setup>
    // import Multiselect from '@vueform/multiselect'; (no usado actualmente)
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import { ref, computed, watch } from 'vue';
    const emit = defineEmits(['edit', 'delete', 'store']);

    const props = defineProps({
        form: Object,
        costcenters: Array,
        estimates: Array,
        estimate_statuses: Array,
        fruits: Array, // <-- agregar fruits como prop
        season_id: Number,
        enableEdit: Boolean
    });

    const fruitOptions = computed(() => props.fruits?.map(f => ({ id: f.id, name: f.name })) || []);

    // Estado local para selects
    const selectedFruitId = ref('');
    const estimateStatusOptions = computed(() => {
        if (!props.estimate_statuses) return [];
        return props.estimate_statuses.filter(s => s.fruit_id == selectedFruitId.value).map(s => ({ id: s.id, name: s.name }));
    });
    const selectedEstimateStatusId = ref('');

    // Modal y campos para nuevo estado de estimación
    const showModal = ref(false);
    const nuevoNombre = ref('');
    const nuevoFruitId = ref('');

    // Inicializar fruta al primero disponible
    watch(() => props.fruits, (fruits) => {
        if (fruits && fruits.length) {
            selectedFruitId.value = fruits[0].id;
        }
    }, { immediate: true });
    // Inicializar estado al primero disponible según fruta
    watch(() => props.estimate_statuses, (statuses) => {
        const opts = statuses.filter(s => s.fruit_id == selectedFruitId.value);
        if (opts.length) selectedEstimateStatusId.value = opts[0].id;
    }, { immediate: true });
    // Cuando cambia la fruta, reiniciar estado de estimación
    watch(selectedFruitId, (newId) => {
        const opts = props.estimate_statuses.filter(s => s.fruit_id == newId);
        selectedEstimateStatusId.value = opts.length ? opts[0].id : '';
    });

    // Guardar nuevo estado de estimación
    function guardarEstimateStatus() {
        if (!nuevoNombre.value || !nuevoFruitId.value) return;
        // Usar fetch para evitar recargar toda la página
        fetch(route('estimate-status.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: nuevoNombre.value, fruit_id: nuevoFruitId.value })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.id) {
                showModal.value = false;
                nuevoNombre.value = '';
                nuevoFruitId.value = '';
                // Recargar solo los datos del componente padre (emitir evento o recargar Inertia)
                window.location.reload();
            } else {
                alert(data.error || 'Error al guardar');
            }
        })
        .catch(() => alert('Error al guardar'));
    }

    const kilosInputs = ref([]);
    const observationsInputs = ref([]);
    const modifiedRows = ref(new Set());

    function markAsModified(idx) {
        modifiedRows.value.add(idx);
    }

    // Unificar: mostrar todos los costcenters filtrados, asociando estimate si existe
    const rows = computed(() => {
        return props.costcenters
            .filter(cc => cc.variety && cc.fruit_id == selectedFruitId.value)
            .map((cc, idx) => {
                const estimate = props.estimates.find(e => e.cost_center_id == cc.id && e.estimate_status_id == selectedEstimateStatusId.value);
                return {
                    id: estimate ? estimate.id : null,
                    costcenter: cc.name,
                    costcenterId: cc.id,
                    variety: cc.variety.name,
                    varietyId: cc.variety.id,
                    kilos: estimate ? estimate.kilos_ha : (kilosInputs.value[idx] || ''),
                    observation: estimate ? estimate.observations || '' : (observationsInputs.value[idx] || ''),
                    isExisting: !!estimate
                };
            });
    });

    function updateKilos(idx, value) {
        rows.value[idx].kilos = value;
        markAsModified(idx);
    }
    function updateObservation(idx, value) {
        rows.value[idx].observation = value;
        markAsModified(idx);
    }

    const countNewRows = computed(() => {
        return rows.value.filter(row => !row.isExisting && row.kilos && row.kilos !== '').length;
    });

    const countModifiedRows = computed(() => {
        return rows.value.filter(row => row.isExisting && modifiedRows.value.has(rows.value.indexOf(row))).length;
    });

    function handleEdit(idx) {
        // Emitir evento para editar estimate existente
        const row = rows.value[idx];
        const payload = {
            id: row.id,
            cost_center_id: row.costcenterId,
            kilos_ha: row.kilos,
            estimate_status_id: selectedEstimateStatusId.value,
            fruit_id: selectedFruitId.value,
            observations: row.observation || '',
            season_id: props.season_id
        };
        emit('edit', payload);
    }
    function handleSave() {
        const data = rows.value
            .filter(row => row.kilos && row.kilos !== '')
            .map(row => ({
                id: row.id,
                cost_center_id: row.costcenterId,
                kilos_ha: row.kilos,
                estimate_status_id: selectedEstimateStatusId.value,
                fruit_id: selectedFruitId.value,
                observations: row.observation || '',
                season_id: props.season_id,
                isExisting: row.isExisting
            }));
        emit('store', data);
    }

    function handleDelete(idx) {
        // Emitir evento para eliminar estimate existente
        emit('delete', rows.value[idx].id);
    }
</script>
<template>
    <!-- Instrucción y botón para crear nombre de estimación -->
    <div class="alert alert-info d-flex align-items-center justify-content-between mb-3" role="alert">
        <div>
            <i class="fas fa-info-circle me-2"></i>
            <strong>Paso previo:</strong> Si el nombre de estimación que necesitas no existe, créalo primero.
        </div>
        <button class="btn btn-sm btn-primary" @click="() => { showModal = true; nuevoFruitId = selectedFruitId }">
            <i class="fas fa-plus me-1"></i> Crear nombre de estimación
        </button>
    </div>

    <!-- Modal para crear nuevo estado de estimación (estilo Bootstrap mejorado) -->
    <div v-if="showModal" class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.3); z-index: 1050; position: fixed; top:0; left:0; width:100vw; height:100vh;">
      <div class="modal-dialog modal-lg mt-6 d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="modal-content border-0 shadow rounded-3 position-relative" style="min-width:350px;">
          <!-- Botón de cierre arriba a la derecha -->
          <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
            <button class="btn-close btn btn-sm btn-circle d-flex flex-right transition-base" type="button" aria-label="Close" @click="showModal = false"></button>
          </div>
          <div class="modal-body p-0">
            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
              <h5 class="mb-1" id="staticBackdropLabel">Nuevo nombre de estimación</h5>
              <p class="fs-11 mb-0">Asocia un nombre a una fruta</p>
            </div>
            <div class="p-4">
              <div class="mb-3">
                <label class="form-label">Nombre estimación</label>
                <input v-model="nuevoNombre" class="form-control" placeholder="Nombre estimación" />
              </div>
              <div class="mb-3">
                <label class="form-label">Fruta</label>
                <select v-model="nuevoFruitId" class="form-select">
                  <option v-for="f in fruitOptions" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-success btn-sm" @click="guardarEstimateStatus">Guardar</button>
                <button class="btn btn-secondary btn-sm" @click="showModal = false">Cancelar</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row mb-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label mb-1">Fruta</label>
            <select v-model="selectedFruitId" class="form-select form-select-sm">
                <option v-for="fruit in fruitOptions" :key="fruit.id" :value="fruit.id">{{ fruit.name }}</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label mb-1">Nombre estimación</label>
            <select v-model="selectedEstimateStatusId" class="form-select form-select-sm">
                <option v-for="status in estimateStatusOptions" :key="status.id" :value="status.id">{{ status.name }}</option>
            </select>
        </div>
    </div>
    
    <!-- Leyenda de colores -->
    <div class="alert alert-light border mb-3 py-2">
        <small class="d-flex flex-wrap gap-3 mb-0">
            <span><span class="badge bg-secondary me-1">Vacío</span> Sin datos</span>
            <span><span class="badge bg-success me-1">Nuevo</span> Se creará al guardar</span>
            <span><span class="badge bg-info me-1">Guardado</span> Ya existe en BD</span>
            <span><span class="badge bg-warning text-dark me-1">Modificado</span> Tiene cambios sin guardar</span>
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Centro de Costo</th>
                    <th>Variedad</th>
                    <th>Kilos</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in rows" :key="idx" 
                    :class="{ 
                        'table-success': !row.isExisting && row.kilos, 
                        'table-warning': row.isExisting && modifiedRows.has(idx)
                    }">
                    <td>
                        <span v-if="!row.isExisting && row.kilos" class="badge bg-success">Nuevo</span>
                        <span v-else-if="row.isExisting && !modifiedRows.has(idx)" class="badge bg-info">Guardado</span>
                        <span v-else-if="row.isExisting && modifiedRows.has(idx)" class="badge bg-warning text-dark">Modificado</span>
                        <span v-else class="badge bg-secondary">Vacío</span>
                    </td>
                    <td>{{ row.costcenter }}</td>
                    <td>{{ row.variety }}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            :value="row.kilos"
                            @input="updateKilos(idx, $event.target.value)"
                            min="0"
                            :class="{
                                'is-invalid': props.form?.errors?.[`kilos_ha_${idx}`],
                                'border-warning border-2': row.isExisting && modifiedRows.has(idx),
                                'border-success border-2': !row.isExisting && row.kilos
                            }"
                            placeholder="Ingresa kilos"
                        />
                        <InputError class="mt-1" :message="props.form?.errors?.[`kilos_ha_${idx}`]" />
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                            :value="row.observation"
                            @input="updateObservation(idx, $event.target.value)"
                            :class="{
                                'is-invalid': props.form?.errors?.[`observations_${idx}`],
                                'border-warning border-2': row.isExisting && modifiedRows.has(idx)
                            }"
                            placeholder="Observaciones opcionales"
                        />
                        <InputError class="mt-1" :message="props.form?.errors?.[`observations_${idx}`]" />
                    </td>
                    <td>
                        <button v-if="row.isExisting" type="button" @click="handleDelete(idx)" v-tooltip="'Eliminar'" class="btn btn-icon btn-active-light-danger w-30px h-30px">
                            <span class="svg-icon svg-icon-3">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
                                    <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
                                </svg>
                            </span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            <span v-if="countNewRows > 0 || countModifiedRows > 0">
                Se guardarán <strong>{{ countNewRows }}</strong> nuevo(s) y <strong>{{ countModifiedRows }}</strong> modificado(s)
            </span>
            <span v-else>
                No hay cambios para guardar
            </span>
        </div>
        <button type="button" class="btn btn-primary" @click="handleSave" 
                :disabled="countNewRows === 0 && countModifiedRows === 0"
                v-tooltip="'Guarda todas las estimaciones nuevas y modificadas'">
            <i class="fas fa-save me-1"></i>
            Guardar todo
            <span v-if="countNewRows > 0 || countModifiedRows > 0" class="badge bg-light text-dark ms-2">
                {{ countNewRows + countModifiedRows }}
            </span>
        </button>
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>

