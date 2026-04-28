<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import MonthlyBonusForm from './MonthlyBonusForm.vue';

const props = defineProps({
    show: Boolean,
    bonus: Object,
    contracts: Array,
    bonusTypes: Array,
    months: Array,
    costCenters: Array,
    groupings: Array,
    laborTypes: Array,
    level3s: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    contract_id: null,
    monthly_bonus_type_id: null,
    month_id: null,
    cost_center_id: null,
    cost_center_ids: [],
    labor_type_id: null,
    amount: '',
    observations: '',
});

watch(() => props.bonus, (newBonus) => {
    if (newBonus) {
        form.contract_id           = newBonus.contract_id           ?? null;
        form.monthly_bonus_type_id = newBonus.bonus_type_id         ?? null;
        form.month_id              = newBonus.month_id              ?? null;
        form.cost_center_ids        = newBonus.cost_center_ids        ?? [];
        form.labor_type_id         = newBonus.labor_type_id         ?? null;
        form.amount                = newBonus.amount                ?? '';
        form.observations          = newBonus.observations          ?? '';
    }
}, { immediate: true });

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('monthly-bonuses.update', props.bonus.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Bono actualizado correctamente.', timer: 1200, showConfirmButton: false });
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-edit text-warning me-2 fs-8"></i>
                        Editar Bono Mensual
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <MonthlyBonusForm
                        :form="form"
                        :contracts="contracts"
                        :bonusTypes="bonusTypes"
                        :months="months"
                        :costCenters="costCenters"
                        :groupings="groupings"
                        :laborTypes="laborTypes"
                        :level3s="level3s"
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
