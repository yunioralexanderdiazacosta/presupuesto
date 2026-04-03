<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import BonusTypeForm from './BonusTypeForm.vue';

const props = defineProps({
    show: Boolean,
    bonusType: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    default_amount: 0,
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val && props.bonusType) {
        form.name = props.bonusType.name;
        form.default_amount = props.bonusType.default_amount || 0;
        form.is_active = props.bonusType.is_active ?? true;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('bonus-types.update', props.bonusType.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Tipo de bono actualizado correctamente', timer: 1200, showConfirmButton: false });
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
                        Editar Tipo de Bono
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <BonusTypeForm :form="form" />
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
