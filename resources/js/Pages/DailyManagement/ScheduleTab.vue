<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    schedule: Object,
});

const days = [
    { key: 'monday_hours', label: 'Lunes' },
    { key: 'tuesday_hours', label: 'Martes' },
    { key: 'wednesday_hours', label: 'Miércoles' },
    { key: 'thursday_hours', label: 'Jueves' },
    { key: 'friday_hours', label: 'Viernes' },
    { key: 'saturday_hours', label: 'Sábado' },
    { key: 'sunday_hours', label: 'Domingo' },
];

const form = useForm({
    monday_hours: props.schedule.monday_hours,
    tuesday_hours: props.schedule.tuesday_hours,
    wednesday_hours: props.schedule.wednesday_hours,
    thursday_hours: props.schedule.thursday_hours,
    friday_hours: props.schedule.friday_hours,
    saturday_hours: props.schedule.saturday_hours,
    sunday_hours: props.schedule.sunday_hours,
});

const weeklyTotal = computed(() => {
    return days.reduce((sum, d) => sum + (parseFloat(form[d.key]) || 0), 0).toFixed(1);
});

const workDays = computed(() => {
    return days.filter(d => parseFloat(form[d.key]) > 0).length;
});

function save() {
    form.put(route('work-schedules.update'), {
        preserveScroll: true,
        onSuccess: () => Swal.fire({ icon: 'success', title: 'Horario guardado', timer: 1000, showConfirmButton: false }),
        onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos.' }),
    });
}
</script>

<template>
    <div>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <p class="text-muted small mb-3">
                    Configure las horas de trabajo por día de la semana. Este horario se usa para calcular el máximo de horas en las tarjas y la tarifa diaria.
                </p>

                <table class="table table-sm align-middle fs--1 mb-3">
                    <thead class="bg-200">
                        <tr>
                            <th>Día</th>
                            <th style="width:120px" class="text-center">Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="day in days" :key="day.key">
                            <td class="fw-semi-bold">{{ day.label }}</td>
                            <td>
                                <input
                                    type="number"
                                    v-model.number="form[day.key]"
                                    class="form-control form-control-sm text-center"
                                    min="0"
                                    max="24"
                                    step="0.5"
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-100">
                        <tr>
                            <td class="fw-bold">Total semanal</td>
                            <td class="text-center fw-bold fs-0">{{ weeklyTotal }}h</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Días laborales</td>
                            <td class="text-center text-muted small">{{ workDays }} días</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-end">
                    <button class="btn btn-falcon-default" @click="save" :disabled="form.processing">
                        <span class="fas fa-save me-1"></span>
                        Guardar horario
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
