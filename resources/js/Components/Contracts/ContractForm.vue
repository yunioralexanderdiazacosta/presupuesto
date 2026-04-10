<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    form: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    schedules: { type: Array, default: () => [] },
    contractTypes: { type: Array, default: () => [] },
    afps: { type: Array, default: () => [] },
    healthPlans: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    maritalStatuses: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);
const form = props.form;

const scheduleOptions = ref(props.schedules);
const isRefreshingSchedules = ref(false);
const newScheduleName = ref('');
const showAddSchedule = ref(false);

const cityOptions = ref(props.cities);
const isRefreshingCities = ref(false);
const newCityName = ref('');
const showAddCity = ref(false);

const showEndDate = computed(() => form.contract_type === 'Plazo Fijo');

watch(form, () => emit('update:form', form), { deep: true });

watch(() => form.contract_type, (val) => {
    if (val !== 'Plazo Fijo') {
        form.end_date = '';
    }
});

const refreshSchedules = async () => {
    isRefreshingSchedules.value = true;
    try {
        const response = await axios.get(route('api.schedules'));
        scheduleOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingSchedules.value = false;
    }
};

const addSchedule = async () => {
    if (!newScheduleName.value.trim()) return;
    try {
        const response = await axios.post(route('api.schedules.store'), { name: newScheduleName.value.trim() });
        scheduleOptions.value.push({ value: response.data.id, label: response.data.name });
        form.schedule_id = response.data.id;
        newScheduleName.value = '';
        showAddSchedule.value = false;
        Swal.fire({ icon: 'success', title: 'Horario creado', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo crear el horario', 'error');
    }
};

// Cities
const refreshCities = async () => {
    isRefreshingCities.value = true;
    try {
        const response = await axios.get(route('api.cities'));
        cityOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingCities.value = false;
    }
};

const addCity = async () => {
    if (!newCityName.value.trim()) return;
    try {
        const response = await axios.post(route('api.cities.store'), { name: newCityName.value.trim() });
        cityOptions.value.push({ value: response.data.id, label: response.data.name });
        form.city_id = response.data.id;
        newCityName.value = '';
        showAddCity.value = false;
        Swal.fire({ icon: 'success', title: 'Ciudad creada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo crear la ciudad', 'error');
    }
};
</script>

<template>
    <form @submit.prevent>
        <div class="row g-3">
            <!-- Colaborador -->
            <div class="col-md-6">
                <label class="form-label small mb-1">Colaborador <span class="text-danger">*</span></label>
                <select v-model="form.employee_id" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.employee_id }">
                    <option value="" disabled>Seleccione colaborador</option>
                    <option v-for="emp in employees" :key="emp.value" :value="emp.value">{{ emp.label }}</option>
                </select>
                <div v-if="form.errors?.employee_id" class="invalid-feedback">{{ form.errors.employee_id }}</div>
            </div>

            <!-- Empresa -->
            <div class="col-md-6">
                <label class="form-label small mb-1">Empresa <span class="text-danger">*</span></label>
                <select v-model="form.company_reason_id" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.company_reason_id }">
                    <option value="" disabled>Seleccione empresa</option>
                    <option v-for="c in companyReasons" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <div v-if="form.errors?.company_reason_id" class="invalid-feedback">{{ form.errors.company_reason_id }}</div>
            </div>

            <!-- Fecha Contrato -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Fecha Contrato <span class="text-danger">*</span></label>
                <input type="date" v-model="form.contract_date" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.contract_date }" />
                <div v-if="form.errors?.contract_date" class="invalid-feedback">{{ form.errors.contract_date }}</div>
            </div>

            <!-- Tipo Contrato -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Tipo de Contrato <span class="text-danger">*</span></label>
                <select v-model="form.contract_type" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.contract_type }">
                    <option value="" disabled>Seleccione tipo</option>
                    <option v-for="t in contractTypes" :key="t" :value="t">{{ t }}</option>
                </select>
                <div v-if="form.errors?.contract_type" class="invalid-feedback">{{ form.errors.contract_type }}</div>
            </div>

            <!-- Fecha Término (solo Plazo Fijo) -->
            <div class="col-md-3" v-if="showEndDate">
                <label class="form-label small mb-1">Fecha de Término <span class="text-danger">*</span></label>
                <input type="date" v-model="form.end_date" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.end_date }" />
                <div v-if="form.errors?.end_date" class="invalid-feedback">{{ form.errors.end_date }}</div>
            </div>

            <!-- Cargo -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Cargo</label>
                <input type="text" v-model="form.position" class="form-control form-control-sm" placeholder="Cargo" />
            </div>

            <!-- Labor -->
            <div class="col-md-3" :class="{ 'col-md-6': !showEndDate }">
                <label class="form-label small mb-1">Labor</label>
                <input type="text" v-model="form.labor" class="form-control form-control-sm" placeholder="Labor" />
            </div>

            <hr class="my-2">

            <!-- Sueldo Base -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Sueldo Base <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" v-model="form.base_salary" class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors?.base_salary }" placeholder="0" min="0" />
                </div>
                <div v-if="form.errors?.base_salary" class="invalid-feedback d-block">{{ form.errors.base_salary }}</div>
            </div>

            <!-- Sueldo Líquido -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Sueldo Líquido <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" v-model="form.net_salary" class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors?.net_salary }" placeholder="0" min="0" />
                </div>
                <div v-if="form.errors?.net_salary" class="invalid-feedback d-block">{{ form.errors.net_salary }}</div>
            </div>

            <!-- Horario -->
            <div class="col-md-5">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small mb-0">Horario</label>
                    <div class="d-flex gap-1">
                        <button type="button" @click="showAddSchedule = !showAddSchedule"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Agregar horario'" style="font-size: 0.75rem;">
                            <i class="fas fa-plus fa-xs"></i>
                        </button>
                        <button type="button" @click="refreshSchedules" :disabled="isRefreshingSchedules"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                            <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingSchedules}"></i>
                        </button>
                    </div>
                </div>
                <select v-model="form.schedule_id" class="form-select form-select-sm">
                    <option value="">Seleccione horario</option>
                    <option v-for="s in scheduleOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <div v-if="showAddSchedule" class="input-group input-group-sm mt-1">
                    <input type="text" v-model="newScheduleName" class="form-control" placeholder="Nuevo horario..."
                        @keyup.enter="addSchedule" />
                    <button class="btn btn-outline-primary" type="button" @click="addSchedule">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>

            <!-- AFP -->
            <div class="col-md-3">
                <label class="form-label small mb-1">AFP</label>
                <select v-model="form.afp_id" class="form-select form-select-sm">
                    <option value="">Seleccione AFP</option>
                    <option v-for="a in afps" :key="a.value" :value="a.value">{{ a.label }}</option>
                </select>
            </div>

            <!-- Salud -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Salud</label>
                <select v-model="form.health_plan_id" class="form-select form-select-sm">
                    <option value="">Seleccione plan</option>
                    <option v-for="h in healthPlans" :key="h.value" :value="h.value">{{ h.label }}</option>
                </select>
            </div>

            <!-- Estado Civil -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Estado Civil</label>
                <select v-model="form.marital_status" class="form-select form-select-sm">
                    <option value="">Seleccione</option>
                    <option v-for="m in maritalStatuses" :key="m" :value="m">{{ m }}</option>
                </select>
            </div>

            <!-- Ciudad -->
            <div class="col-md-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small mb-0">Ciudad</label>
                    <div class="d-flex gap-1">
                        <button type="button" @click="showAddCity = !showAddCity"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Agregar ciudad'" style="font-size: 0.75rem;">
                            <i class="fas fa-plus fa-xs"></i>
                        </button>
                        <button type="button" @click="refreshCities" :disabled="isRefreshingCities"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                            <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingCities}"></i>
                        </button>
                    </div>
                </div>
                <select v-model="form.city_id" class="form-select form-select-sm">
                    <option value="">Seleccione ciudad</option>
                    <option v-for="c in cityOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <div v-if="showAddCity" class="input-group input-group-sm mt-1">
                    <input type="text" v-model="newCityName" class="form-control" placeholder="Nueva ciudad..."
                        @keyup.enter="addCity" />
                    <button class="btn btn-outline-primary" type="button" @click="addCity">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>

            <hr class="my-2">

            <!-- Teléfono -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Teléfono</label>
                <input type="text" v-model="form.phone" class="form-control form-control-sm" placeholder="+56 9 1234 5678" />
            </div>

            <!-- Email -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Email</label>
                <input type="email" v-model="form.email" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.email }" placeholder="correo@ejemplo.cl" />
                <div v-if="form.errors?.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <!-- Dirección -->
            <div class="col-md-4">
                <label class="form-label small mb-1">Dirección</label>
                <input type="text" v-model="form.address" class="form-control form-control-sm" placeholder="Dirección" />
            </div>

            <!-- Estado -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Estado</label>
                <select v-model="form.is_active" class="form-select form-select-sm">
                    <option :value="true">Vigente</option>
                    <option :value="false">Finalizado</option>
                </select>
            </div>
        </div>
    </form>
</template>
