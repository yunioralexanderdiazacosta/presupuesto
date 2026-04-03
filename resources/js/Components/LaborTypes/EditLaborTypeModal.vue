<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import LaborTypeForm from './LaborTypeForm.vue';

const props = defineProps({
    show: Boolean,
    laborType: Object,
    level3s: Array,
    units: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    level3_id: '',
    unit_id: '',
    default_rate: 0,
    default_bonus: 0,
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val && props.laborType) {
        form.name = props.laborType.name;
        form.level3_id = props.laborType.level3_id || '';
        form.unit_id = props.laborType.unit_id || '';
        form.default_rate = props.laborType.default_rate || 0;
        form.default_bonus = props.laborType.default_bonus || 0;
        form.is_active = props.laborType.is_active ?? true;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('labor-types.update', props.laborType.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Labor actualizada correctamente', timer: 1200, showConfirmButton: false });
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
                        Editar Labor
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <LaborTypeForm
                        :form="form"
                        :level3s="level3s"
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
