<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import LaborRateForm from './LaborRateForm.vue';

const props = defineProps({
    show: Boolean,
    laborRate: Object,
    laborTypes: Array,
    units: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    rate: 0,
    unit_id: '',
    labor_type_id: '',
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val && props.laborRate) {
        form.name = props.laborRate.name;
        form.rate = props.laborRate.rate || 0;
        form.unit_id = props.laborRate.unit_id || '';
        form.labor_type_id = props.laborRate.labor_type_id || '';
        form.is_active = props.laborRate.is_active ?? true;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('labor-rates.update', props.laborRate.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Trato actualizado correctamente', timer: 1200, showConfirmButton: false });
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-edit text-primary me-2 fs-8"></i>
                        Editar Trato
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <LaborRateForm
                        :form="form"
                        :laborTypes="laborTypes"
                        :units="units"
                    />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
