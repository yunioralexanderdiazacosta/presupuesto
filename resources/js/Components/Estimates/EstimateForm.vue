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
    }
    function updateObservation(idx, value) {
        rows.value[idx].observation = value;
    }

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
    <!-- Botón para crear nombre de estimación (siempre visible, alineado a la izquierda) -->
    <div class="mb-2 d-flex justify-content-end">
        <button v-if="true" class="btn btn-primary btn-sm" @click="() => { showModal = true; nuevoFruitId = selectedFruitId }">
            Crear nombre de estimación
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
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
            <thead>
                <tr>
                    <th>Centro de Costo</th>
                    <th>Variedad</th>
                    <th>Kilos</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in rows" :key="idx">
                    <td>{{ row.costcenter }}</td>
                    <td>{{ row.variety }}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            :value="row.kilos"
                            @input="updateKilos(idx, $event.target.value)"
                            min="0"
                            :class="{'is-invalid': props.form?.errors?.[`kilos_ha_${idx}`]}"
                        />
                        <InputError class="mt-1" :message="props.form?.errors?.[`kilos_ha_${idx}`]" />
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                            :value="row.observation"
                            @input="updateObservation(idx, $event.target.value)"
                            :class="{'is-invalid': props.form?.errors?.[`observations_${idx}`]}"
                        />
                        <InputError class="mt-1" :message="props.form?.errors?.[`observations_${idx}`]" />
                    </td>
                    <td>
                        <button v-if="row.isExisting" type="button" @click="handleEdit(idx)" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-2">
                            <span class="svg-icon svg-icon-3">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                    <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                </svg>
                            </span>
                        </button>
                        <button v-if="row.isExisting" type="button" @click="handleDelete(idx)" v-tooltip="'Eliminar'" class="btn btn-icon btn-active-light-primary w-30px h-30px">
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
    <div class="mt-3 text-end">
        <button type="button" class="btn btn-primary" @click="handleSave">Guardar</button>
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>

