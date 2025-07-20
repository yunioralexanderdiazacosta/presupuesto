<script setup>
    import Multiselect from '@vueform/multiselect';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import { ref, computed } from 'vue';
    const emit = defineEmits(['store']);

    const props = defineProps({
        form: Object,
        costcenters: Array,
        estimates: Array,
        estimate_statuses: Array,
        season_id: Number
    });

    // Depuración: mostrar estimate_statuses en consola
    console.log('estimate_statuses:', props.estimate_statuses);
    // Usar directamente la lista de estados enviada por el backend
    const availableEstimateStatuses = computed(() => {
        return props.estimate_statuses ? props.estimate_statuses.map(s => ({ id: s.id, name: s.name })) : [];
    });
    const selectedEstimateStatusId = ref(availableEstimateStatuses.value[0]?.id || '');
    const kilosInputs = ref([]);
    const observationsInputs = ref([]);

    const rows = computed(() => {
        let result = [];
        props.costcenters.forEach((cc, idx) => {
            if (cc.variety) {
                result.push({
                    costcenter: cc.name,
                    costcenterId: cc.id,
                    variety: cc.variety.name,
                    varietyId: cc.variety.id,
                    kilos: kilosInputs.value[idx] || '',
                    observation: observationsInputs.value[idx] || ''
                });
            }
        });
        if (result.length === 0) {
            result.push({ costcenter: '', variety: '', kilos: '', observation: '' });
        }
        return result;
    });

    function updateKilos(idx, value) {
        kilosInputs.value[idx] = value;
    }
    function updateObservation(idx, value) {
        observationsInputs.value[idx] = value;
    }

    function handleSave() {
        const data = rows.value
            .filter(row => row.kilos && row.kilos !== '')
            .map(row => ({
                cost_center_id: row.costcenterId,
                kilos_ha: row.kilos,
                estimate_status_id: selectedEstimateStatusId.value,
                observations: row.observation || '',
                season_id: props.season_id
            }));
        emit('store', data);
    }
</script>
<template>
    <div class="mb-3">
        <label class="form-label mb-1">Estado de la estimación</label>
        <select v-model="selectedEstimateStatusId" class="form-select form-select-sm">
            <option v-for="status in availableEstimateStatuses" :key="status.id" :value="status.id">{{ status.name }}</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm fs-10 mb-0">
            <thead>
                <tr>
                    <th>Centro de Costo</th>
                    <th>Variedad</th>
                    <th>Kilos</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in rows" :key="idx">
                    <td>{{ row.costcenter }}</td>
                    <td>{{ row.variety }}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm" v-model="kilosInputs[idx]" @input="updateKilos(idx, $event.target.value)" min="0" />
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" v-model="observationsInputs[idx]" @input="updateObservation(idx, $event.target.value)" />
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

