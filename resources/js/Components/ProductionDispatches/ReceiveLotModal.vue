<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import LotForm from './LotForm.vue';

const props = defineProps({
    show: Boolean,
    dispatch: Object,
    lot: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id: null,
    lot_number: '',
    reception_date: '',
    kg_received: '',
    observations: '',
});

const today = () => new Date().toLocaleDateString('en-CA');

watch(() => props.show, (val) => {
    if (!val) return;
    form.clearErrors();
    form.id = props.lot?.id ?? null;
    form.lot_number = props.lot?.lot_number || '';
    form.reception_date = props.lot?.reception_date || today();
    form.kg_received = props.lot ? Number(props.lot.kg_received) : '';
    form.observations = props.lot?.observations || '';
});

function closeModal() {
    emit('close');
}

function save() {
    const options = {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: props.lot ? 'Lote actualizado correctamente' : 'Lote recepcionado correctamente', timer: 1200, showConfirmButton: false });
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        },
    };

    if (props.lot) {
        form.put(route('production-lots.update', props.lot.id), options);
    } else {
        form.post(route('production-lots.store', props.dispatch.id), options);
    }
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show && dispatch">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-dolly text-primary me-2 fs-8"></i>
                        {{ lot ? 'Editar Lote' : 'Recepcionar Lote' }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <LotForm :form="form" :dispatch="dispatch" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i>{{ lot ? 'Actualizar' : 'Recepcionar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
