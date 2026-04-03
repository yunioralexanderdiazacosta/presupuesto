<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DailyYieldForm from './DailyYieldForm.vue';

const props = defineProps({
    show: Boolean,
    employees: Array,
    laborTypes: Array,
    bonusTypes: Array,
    costCenters: Array,
    date: String,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    employee_id: '',
    date: '',
    labor_type_id: '',
    rate: 0,
    quantity: 0,
    amount: 0,
    hours: 8,
    bonus_type_id: '',
    bonus_amount: 0,
    cost_center_id: '',
    observations: '',
});

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        form.date = props.date;
        form.hours = 8;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('daily-yields.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Tarja registrada', timer: 1200, showConfirmButton: false });
            form.reset();
            form.date = props.date;
            form.hours = 8;
            emit('saved');
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-clipboard-list text-primary me-2 fs-8"></i>
                        Nueva Tarja
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <DailyYieldForm
                        :form="form"
                        :employees="employees"
                        :laborTypes="laborTypes"
                        :bonusTypes="bonusTypes"
                        :costCenters="costCenters"
                        :showEmployee="true"
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
