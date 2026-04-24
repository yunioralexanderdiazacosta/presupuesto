<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    nationalHolidays: Array,
    teamHolidays:     Array,
});

const title = 'Feriados';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

// Formulario agregar feriado del equipo
const form = useForm({
    date:         '',
    name:         '',
    is_recurring: false,
});

function submitHoliday() {
    if (!form.date || !form.name) {
        Swal.fire('Atención', 'Completa la fecha y el nombre del feriado.', 'warning');
        return;
    }
    form.post(route('holidays.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Feriado agregado', showConfirmButton: false, timer: 1500 });
            form.reset();
        },
        onError: (errors) => {
            Swal.fire('Error', Object.values(errors).flat().join('<br>'), 'error');
        },
    });
}

function deleteHoliday(id, name) {
    Swal.fire({
        title: '¿Eliminar feriado?',
        text: name,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('holidays.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminado', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
}

// Filtro para feriados nacionales
const filterYear = ref(new Date().getFullYear().toString());
const years = [...new Set(props.nationalHolidays?.map(h => h.date.substring(0, 4)) ?? [])].sort().reverse();

const filteredNational = computed(() => {
    if (!props.nationalHolidays) return [];
    if (!filterYear.value) return props.nationalHolidays;
    return props.nationalHolidays.filter(h => h.date.startsWith(filterYear.value));
});

import { computed } from 'vue';
</script>

<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <Breadcrumb :links="links" />

        <div class="card my-3">
            <div class="card-header" style="background: linear-gradient(135deg, #eaf2fb 0%, #f4f9fd 100%); border-bottom: 2px solid #2980b9;">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0" style="color: #1a5276;">
                            <i class="fas fa-calendar-alt me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="row g-4">

                    <!-- Feriados Nacionales -->
                    <div class="col-lg-7">
                        <div class="card h-100" style="border: 1px solid #aed6f1;">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #eaf2fb;">
                                <h6 class="mb-0" style="color: #1a5276;">
                                    <i class="fas fa-flag me-2"></i>Feriados Nacionales (Chile)
                                </h6>
                                <select v-model="filterYear" class="form-select form-select-sm w-auto">
                                    <option value="">Todos los años</option>
                                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                                <table class="table table-sm table-hover fs-10 mb-0">
                                    <thead style="background-color: #eaf2fb; position: sticky; top: 0;">
                                        <tr>
                                            <th style="color: #1a5276;">Fecha</th>
                                            <th style="color: #1a5276;">Feriado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="h in filteredNational" :key="h.id">
                                            <td class="text-nowrap">{{ h.date_label }}</td>
                                            <td>{{ h.name }}</td>
                                        </tr>
                                        <tr v-if="!filteredNational.length">
                                            <td colspan="2" class="text-center text-muted py-3">Sin registros para el año seleccionado.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-light" style="font-size: 0.75rem; color: #5d6d7e;">
                                <i class="fas fa-info-circle me-1"></i>
                                Los feriados nacionales son precargados por el sistema y aplican a todos los equipos.
                            </div>
                        </div>
                    </div>

                    <!-- Feriados del Equipo -->
                    <div class="col-lg-5">
                        <div class="card h-100" style="border: 1px solid #aed6f1;">
                            <div class="card-header" style="background-color: #eaf2fb;">
                                <h6 class="mb-0" style="color: #1a5276;">
                                    <i class="fas fa-plus-circle me-2"></i>Feriados del Equipo
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Formulario agregar -->
                                <div class="row g-2 mb-3">
                                    <div class="col-sm-5">
                                        <label class="form-label small fw-semibold">Fecha</label>
                                        <input v-model="form.date" type="date" class="form-control form-control-sm" />
                                    </div>
                                    <div class="col-sm-7">
                                        <label class="form-label small fw-semibold">Nombre</label>
                                        <input v-model="form.name" type="text" class="form-control form-control-sm" placeholder="Ej: Feriado regional..." />
                                    </div>
                                    <div class="col-12 d-flex align-items-center justify-content-between">
                                        <div class="form-check mb-0">
                                            <input v-model="form.is_recurring" class="form-check-input" type="checkbox" id="chkRecurring" />
                                            <label class="form-check-label small" for="chkRecurring">Se repite cada año</label>
                                        </div>
                                        <button
                                            class="btn btn-sm"
                                            style="background-color: #2980b9; color: #fff; border-color: #2980b9;"
                                            @click="submitHoliday"
                                            :disabled="form.processing"
                                        >
                                            <i class="fas fa-plus fa-xs me-1"></i>Agregar
                                        </button>
                                    </div>
                                </div>

                                <!-- Listado -->
                                <div style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-hover fs-10 mb-0">
                                        <thead style="background-color: #eaf2fb; position: sticky; top: 0;">
                                            <tr>
                                                <th style="color: #1a5276;">Fecha</th>
                                                <th style="color: #1a5276;">Nombre</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="h in teamHolidays" :key="h.id">
                                                <td class="text-nowrap">{{ h.date_label }}</td>
                                                <td>
                                                    {{ h.name }}
                                                    <span v-if="h.is_recurring" class="badge bg-info ms-1" style="font-size: 0.65rem;">anual</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-falcon-default" @click="deleteHoliday(h.id, h.name)" title="Eliminar">
                                                        <i class="fas fa-trash text-danger fa-xs"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!teamHolidays || !teamHolidays.length">
                                                <td colspan="3" class="text-center text-muted py-3">Sin feriados propios.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
