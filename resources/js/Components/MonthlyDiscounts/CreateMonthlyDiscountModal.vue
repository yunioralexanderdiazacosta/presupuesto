<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import MonthlyDiscountForm from './MonthlyDiscountForm.vue';

const props = defineProps({
    show: Boolean,
    contracts: Array,
    discountTypes: Array,
    months: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    contract_id: null,
    monthly_discount_type_id: null,
    month_id: null,
    amount: '',
    observations: '',
});

watch(() => props.show, (val) => {
    if (val) form.reset();
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('monthly-discounts.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Descuento registrado correctamente.', timer: 1200, showConfirmButton: false });
            form.reset();
            emit('saved');
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        },
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-minus-circle text-danger me-2 fs-8"></i>
                        Nuevo Descuento Mensual
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <MonthlyDiscountForm
                        :form="form"
                        :contracts="contracts"
                        :discountTypes="discountTypes"
                        :months="months"
                    />
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
