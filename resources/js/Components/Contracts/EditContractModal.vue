<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ContractForm from './ContractForm.vue';

const props = defineProps({
    show: Boolean,
    contract: Object,
    employees: Array,
    companyReasons: Array,
    schedules: Array,
    contractTypes: Array,
    afps: Array,
    healthPlans: Array,
    cities: Array,
    parcels: Array,
    maritalStatuses: Array,
    banks: Array,
    paymentMethods: Array,
    accountTypes: Array,
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
    payment_method_id: '',
    bank_id: '',
    account_type_id: '',
    account_number: '',
});

watch(() => props.show, (val) => {
    if (val && props.contract) {
        form.employee_id = props.contract.employee_id;
        form.company_reason_id = props.contract.company_reason_id;
        form.schedule_id = props.contract.schedule_id || '';
        form.contract_date = props.contract.contract_date ? props.contract.contract_date.substring(0, 10) : '';
        form.contract_type = props.contract.contract_type;
        form.position = props.contract.position || '';
        form.labor = props.contract.labor || '';
        form.base_salary = props.contract.base_salary || '';
        form.net_salary = props.contract.net_salary || '';
        form.afp_id = props.contract.afp_id || '';
        form.health_plan_id = props.contract.health_plan_id || '';
        form.city_id = props.contract.city_id || '';
        form.parcel_id = props.contract.parcel_id || '';
        form.marital_status = props.contract.marital_status || '';
        form.phone = props.contract.phone || '';
        form.address = props.contract.address || '';
        form.email = props.contract.email || '';
        form.end_date = props.contract.end_date ? props.contract.end_date.substring(0, 10) : '';
        form.is_active = props.contract.is_active ?? true;
        form.payment_method_id = props.contract.payment_method_id || '';
        form.bank_id = props.contract.bank_id || '';
        form.account_type_id = props.contract.account_type_id || '';
        form.account_number = props.contract.account_number || '';
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('contracts.update', props.contract.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Contrato actualizado correctamente', timer: 1200, showConfirmButton: false });
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
                        Editar Contrato
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <ContractForm
                        :form="form"
                        :isEditing="true"
                        :employeeName="contract?.employee?.full_name || contract?.employee_name || ''"
                        :employees="employees"
                        :companyReasons="companyReasons"
                        :schedules="schedules"
                        :contractTypes="contractTypes"
                        :afps="afps"
                        :healthPlans="healthPlans"
                        :cities="cities"
                        :parcels="parcels"
                        :maritalStatuses="maritalStatuses"
                        :banks="banks"
                        :paymentMethods="paymentMethods"
                        :accountTypes="accountTypes"
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
