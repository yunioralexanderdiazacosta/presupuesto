<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ContractForm from './ContractForm.vue';

const props = defineProps({
    show: Boolean,
    employees: Array,
    companyReasons: Array,
    schedules: Array,
    contractTypes: Array,
    afps: Array,
    healthPlans: Array,
    cities: Array,
    parcels: Array,
    maritalStatuses: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    employee_id: '',
    company_reason_id: '',
    schedule_id: '',
    contract_date: '',
    contract_type: '',
    position: '',
    labor: '',
    base_salary: '',
    net_salary: '',
    afp_id: '',
    health_plan_id: '',
    city_id: '',
    parcel_id: '',
    marital_status: '',
    phone: '',
    address: '',
    email: '',
    end_date: '',
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val) form.reset();
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('contracts.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Contrato registrado correctamente', timer: 1200, showConfirmButton: false });
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-file-contract text-primary me-2 fs-8"></i>
                        Nuevo Contrato
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <ContractForm
                        :form="form"
                        :employees="employees"
                        :companyReasons="companyReasons"
                        :schedules="schedules"
                        :contractTypes="contractTypes"
                        :afps="afps"
                        :healthPlans="healthPlans"
                        :cities="cities"
                        :parcels="parcels"
                        :maritalStatuses="maritalStatuses"
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
