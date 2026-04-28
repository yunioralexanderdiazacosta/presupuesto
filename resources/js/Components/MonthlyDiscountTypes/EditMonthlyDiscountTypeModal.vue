<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import MonthlyDiscountTypeForm from './MonthlyDiscountTypeForm.vue';

const props = defineProps({ show: Boolean, discountType: Object });
const emit = defineEmits(['close', 'saved']);

const form = useForm({ name: '', is_active: true });

watch(() => props.discountType, (item) => {
    if (item) {
        form.name      = item.name      ?? '';
        form.is_active = item.is_active ?? true;
    }
}, { immediate: true });

function closeModal() { emit('close'); }

function save() {
    form.put(route('monthly-discount-types.update', props.discountType.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', timer: 1200, showConfirmButton: false });
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
                    <h5 class="modal-title"><i class="fas fa-edit text-warning me-2"></i>Editar Tipo de Descuento Mensual</h5>
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
