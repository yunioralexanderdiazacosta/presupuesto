<script setup>
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import EstimateStatusForm from './EstimateStatusForm.vue';

const props = defineProps({
    show: Boolean,
    fruitOptions: { type: Array, default: () => [] },
    estimateStatuses: { type: Array, default: () => [] },
    estimates: { type: Array, default: () => [] },
    defaultFruitId: { type: [String, Number], default: '' },
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({
    name: '',
    fruit_id: '',
    mode: 'empty',
    copy_from_id: '',
    errors: {},
});
const processing = ref(false);

// Solo se pueden copiar estimaciones de la misma especie que tengan datos en la temporada activa.
// Cada registro es una combinación cuartel-variedad, por eso se cuentan cuarteles y variedades distintos.
const copySources = computed(() =>
    props.estimateStatuses
        .filter(s => String(s.fruit_id) === String(form.fruit_id))
        .map(s => {
            const records = new Map();
            props.estimates
                .filter(e => String(e.estimate_status_id) === String(s.id))
                .forEach(e => records.set(String(e.cost_center_variety_id), e.cost_center_variety));
            const ccvs = [...records.values()];
            return {
                value: s.id,
                name: s.name,
                count: records.size,
                cuarteles: new Set(ccvs.map(c => String(c?.cost_center_id))).size,
                variedades: new Set(ccvs.map(c => String(c?.variety_id))).size,
            };
        })
        .filter(s => s.count > 0)
        .map(s => ({
            ...s,
            label: `${s.name} (${s.cuarteles} ${s.cuarteles === 1 ? 'cuartel' : 'cuarteles'}, ${s.variedades} ${s.variedades === 1 ? 'variedad' : 'variedades'})`,
        }))
);

watch(() => props.show, (val) => {
    if (!val) return;
    form.name = '';
    form.fruit_id = props.defaultFruitId;
    form.mode = 'empty';
    form.copy_from_id = '';
    form.errors = {};
});

// Al cambiar de especie la estimación elegida para copiar deja de ser válida
watch(() => form.fruit_id, () => {
    form.copy_from_id = '';
});

const canSave = computed(() =>
    !processing.value
    && form.name.trim() !== ''
    && form.fruit_id !== ''
    && (form.mode === 'empty' || form.copy_from_id !== '')
);

async function save() {
    if (!canSave.value) return;
    processing.value = true;
    form.errors = {};

    try {
        const { data } = await axios.post(route('estimate-status.store'), {
            name: form.name.trim(),
            fruit_id: form.fruit_id,
            copy_from_id: form.mode === 'copy' ? form.copy_from_id : null,
        });

        Swal.fire({
            icon: 'success',
            title: 'Nombre de estimación creado',
            text: data.copied > 0 ? `Se copiaron ${data.copied} ${data.copied === 1 ? 'registro' : 'registros'}.` : undefined,
            showConfirmButton: false,
            timer: 1600,
        });
        emit('saved', data);
        emit('close');
    } catch (error) {
        const res = error.response;
        if (res?.status === 422) {
            const errors = res.data?.errors || {};
            Object.keys(errors).forEach(key => { form.errors[key] = errors[key][0]; });
        } else {
            Swal.fire('Error', res?.data?.message || 'No se pudo guardar. Intenta nuevamente.', 'error');
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div v-if="show" class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.4); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-tag"></i>
                        </span>
                        <span>
                            <span class="fw-bold d-block" style="font-size: 1.1rem;">Nuevo nombre de estimación</span>
                            <span class="text-muted small">Crea un pronóstico nuevo o parte de uno existente</span>
                        </span>
                    </div>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <div class="modal-body">
                    <EstimateStatusForm :form="form" :fruit-options="fruitOptions" :copy-sources="copySources" />
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-sm btn-secondary" @click="emit('close')">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="!canSave">
                        <i class="fas me-1" :class="processing ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                        {{ form.mode === 'copy' ? 'Crear y copiar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
