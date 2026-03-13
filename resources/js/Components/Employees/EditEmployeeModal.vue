<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import EmployeeForm from './EmployeeForm.vue';

const props = defineProps({
    show: Boolean,
    employee: Object,
    nationalities: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    first_name: '',
    second_name: '',
    paternal_surname: '',
    maternal_surname: '',
    rut: '',
    birth_date: '',
    nationality: 'Chilena',
    is_active: true,
});

watch(() => props.show, (val) => {
    if (val && props.employee) {
        form.first_name = props.employee.first_name;
        form.second_name = props.employee.second_name || '';
        form.paternal_surname = props.employee.paternal_surname;
        form.maternal_surname = props.employee.maternal_surname || '';
        form.rut = props.employee.rut;
        form.birth_date = props.employee.birth_date ? props.employee.birth_date.substring(0, 10) : '';
        form.nationality = props.employee.nationality || 'Chilena';
        form.is_active = props.employee.is_active ?? true;
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('employees.update', props.employee.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Colaborador actualizado correctamente', timer: 1200, showConfirmButton: false });
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
                        <i class="fas fa-user-edit text-primary me-2 fs-8"></i>
                        Editar Colaborador
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <EmployeeForm
                        :form="form"
                        :nationalities="nationalities"
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
