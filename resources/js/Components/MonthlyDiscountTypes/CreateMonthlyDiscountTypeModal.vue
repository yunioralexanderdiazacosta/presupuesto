<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import MonthlyDiscountTypeForm from './MonthlyDiscountTypeForm.vue';

const props = defineProps({ show: Boolean });
const emit = defineEmits(['close', 'saved']);

const form = useForm({ name: '', is_active: true });

watch(() => props.show, (val) => { if (val) form.reset(); });

function closeModal() { emit('close'); }

function save() {
    form.post(route('monthly-discount-types.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', timer: 1200, showConfirmButton: false });
            form.reset();
            emit('saved');
        },
        onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos.' }),
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title"><i class="fas fa-minus-circle text-danger me-2"></i>Nuevo Tipo de Descuento Mensual</h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <MonthlyDiscountTypeForm :form="form" />
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
