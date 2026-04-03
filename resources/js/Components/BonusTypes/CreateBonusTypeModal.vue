<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import BonusTypeForm from './BonusTypeForm.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    default_amount: 0,
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val) form.reset();
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('bonus-types.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Tipo de bono registrado correctamente', timer: 1200, showConfirmButton: false });
            form.reset();
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
                        <i class="fas fa-gift text-primary me-2 fs-8"></i>
                        Nuevo Tipo de Bono
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <BonusTypeForm :form="form" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
