<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DailyYieldForm from './DailyYieldForm.vue';

const props = defineProps({
    show: Boolean,
    dailyYield: Object,
    laborTypes: Array,
    bonusTypes: Array,
    costCenters: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    labor_type_id: '',
    rate: 0,
    quantity: 0,
    amount: 0,
    workdays: 1,
    bonus_type_id: '',
    bonus_amount: 0,
    cost_center_ids: [],
    observations: '',
});

watch(() => props.show, (val) => {
    if (val && props.dailyYield) {
        form.labor_type_id = props.dailyYield.labor_type_id || '';
        form.rate = props.dailyYield.rate || 0;
        form.quantity = props.dailyYield.quantity || 0;
        form.amount = props.dailyYield.amount || 0;
        form.workdays = props.dailyYield.workdays || 1;
        form.bonus_type_id = props.dailyYield.bonus_type_id || '';
        form.bonus_amount = props.dailyYield.bonus_amount || 0;
        form.cost_center_ids = props.dailyYield.cost_center_ids || [];
        form.observations = props.dailyYield.observations || '';
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('daily-yields.update', props.dailyYield.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Tarja actualizada', timer: 1200, showConfirmButton: false });
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-edit text-primary me-2 fs-8"></i>
                        Editar Tarja — {{ dailyYield?.employee?.full_name }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <DailyYieldForm
                        :form="form"
                        :laborTypes="laborTypes"
                        :bonusTypes="bonusTypes"
                        :costCenters="costCenters"
                        :showEmployee="false"
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
