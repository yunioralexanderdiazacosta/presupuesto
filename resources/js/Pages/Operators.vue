<script setup>
import { computed, ref } from 'vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { Link, router, Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateOperatorModal from '@/Components/Operators/CreateOperatorModal.vue';
import EditOperatorModal from '@/Components/Operators/EditOperatorModal.vue';

const props = defineProps({
    operators: Object,
    term: String
});

const form = useForm({
    id: '',
    name: '',
    position: ''
});

const title = 'Operarios';
const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const search = ref('');

const filteredOperators = computed(() => {
    if (!props.operators || !props.operators.data) return [];
    if (!search.value) return props.operators.data;
    const term = search.value.toLowerCase();
    return props.operators.data.filter(item => {
        const name = item.name ? item.name.toLowerCase() : '';
        const position = item.position ? item.position.toLowerCase() : '';
        return name.includes(term) || position.includes(term);
    });
});

const openAdd = () => {
    form.reset();
    form.clearErrors();
    $('#createOperatorModal').modal('show');
};

const openEdit = (operator) => {
    form.reset();
    form.clearErrors();
    form.id = operator.id;
    form.name = operator.name;
    form.position = operator.position;
    $('#editOperatorModal').modal('show');
};

const storeOperator = () => {
    form.post(route('operators.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createOperatorModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
};

const updateOperator = () => {
    form.post(route('operators.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editOperatorModal').modal('hide');
            msgSuccess('Actualizado correctamente');
        }
    });
};

const onDeleted = (id) => {
    Swal.fire({
        title: '¿Estás seguro de que quieres eliminar el registro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('operators.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
};

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
    });
};

// Datos para exportación Excel
const excelData = computed(() => {
    return filteredOperators.value.map(op => ({
        name: op.name,
        position: op.position
    }));
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-hard-hat me-2"></i>Operarios
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton 
                                :data="excelData" 
                                :headers="[
                                    { label: 'Nombre', key: 'name' },
                                    { label: 'Cargo', key: 'position' }
                                ]" 
                                class="btn btn-falcon-default btn-sm" 
                                filename="Operarios.xlsx" 
                            />
                            <button class="btn btn-falcon-default btn-sm" type="button" @click="openAdd()">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body bg-body-tertiary pt-2">
                <div class="tab-content border p-3 mt-3">
                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                        <SearchInput v-model="search" placeholder="Buscar por nombre o cargo..." />
                    </div>

                    <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
                        <Table sticky-header :id="'operators'" :total="filteredOperators.length" :links="operators.links">
                            <template #header>
                                <th style="white-space:nowrap;">Nombre</th>
                                <th style="white-space:nowrap;">Cargo</th>
                                <th class="text-center" style="white-space:nowrap;">Acciones</th>
                            </template>
                            <template #body>
                                <template v-if="filteredOperators.length === 0">
                                    <Empty colspan="3" />
                                </template>
                                <template v-else>
                                    <tr v-for="(operator, index) in filteredOperators" :key="index">
                                        <td style="white-space:nowrap;">{{ operator.name }}</td>
                                        <td style="white-space:nowrap;">{{ operator.position }}</td>
                                        <td class="text-center">
                                            <button type="button" v-tooltip="'Editar'" class="btn btn-link me-2 p-0" @click="openEdit(operator)">
                                                <span class="text-500 fas fa-edit"></span>
                                            </button>
                                            <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(operator.id)" class="btn btn-link p-0">
                                                <span class="text-500 fas fa-trash-alt"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </Table>
                    </div>
                </div>
            </div>
        </div>

        <CreateOperatorModal @store="storeOperator" :form="form" />
        <EditOperatorModal @update="updateOperator" :form="form" />
    </AppLayout>
</template>
