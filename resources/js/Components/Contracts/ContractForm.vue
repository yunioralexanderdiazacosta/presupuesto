<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    form: { type: Object, required: true },
    isEditing: { type: Boolean, default: false },
    employeeName: { type: String, default: '' },
    employees: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    schedules: { type: Array, default: () => [] },
    contractTypes: { type: Array, default: () => [] },
    afps: { type: Array, default: () => [] },
    healthPlans: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    parcels: { type: Array, default: () => [] },
    maritalStatuses: { type: Array, default: () => [] },
    banks: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    accountTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);
const form = props.form;

// Empleados disponibles (sin contrato activo)
const employeeOptions = ref([...props.employees]);
const isRefreshingEmployees = ref(false);

async function refreshEmployees() {
    isRefreshingEmployees.value = true;
    try {
        const response = await axios.get(route('api.available-employees'));
        employeeOptions.value = response.data;
        form.employee_id = '';
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingEmployees.value = false;
    }
}

const scheduleOptions = ref(props.schedules);
const isRefreshingSchedules = ref(false);
const newScheduleName = ref('');
const showAddSchedule = ref(false);

const cityOptions = ref(props.cities);
const isRefreshingCities = ref(false);
const newCityName = ref('');
const showAddCity = ref(false);

const parcelOptions = ref(props.parcels);
const isRefreshingParcels = ref(false);

const showEndDate = computed(() => form.contract_type === 'Plazo Fijo');

const needsBankDetails = computed(() => {
    if (!form.payment_method_id) return false;
    const selected = props.paymentMethods.find(pm => String(pm.value) === String(form.payment_method_id));
    return selected && selected.label !== 'Efectivo';
});

const isBancoEstado = computed(() => {
    if (!form.bank_id) return false;
    const selected = props.banks.find(b => String(b.value) === String(form.bank_id));
    return selected && selected.label.toLowerCase().includes('estado');
});

const filteredAccountTypes = computed(() => {
    if (isBancoEstado.value) return props.accountTypes;
    return props.accountTypes.filter(at => at.label !== 'Cuenta RUT');
});

const isCuentaRut = computed(() => {
    if (!form.account_type_id) return false;
    const selected = props.accountTypes.find(at => String(at.value) === String(form.account_type_id));
    return selected && selected.label === 'Cuenta RUT';
});

function getRutBody() {
    const emp = employeeOptions.value.find(e => String(e.value) === String(form.employee_id));
    if (!emp) return '';
    const match = emp.label.match(/\(([^)]+)\)/);
    if (!match) return '';
    const clean = match[1].replace(/[.\-]/g, '');
    return clean.slice(0, -1);
}

watch(() => form.payment_method_id, () => {
    if (!needsBankDetails.value) {
        form.bank_id = '';
        form.account_type_id = '';
        form.account_number = '';
    }
});

watch(() => form.bank_id, () => {
    if (!isBancoEstado.value && isCuentaRut.value) {
        form.account_type_id = '';
        form.account_number = '';
    }
});

watch(() => form.account_type_id, () => {
    if (isCuentaRut.value) {
        form.account_number = getRutBody();
    }
});

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

const refreshParcels = async () => {
    isRefreshingParcels.value = true;
    try {
        const response = await axios.get(route('api.parcels'));
        parcelOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingParcels.value = false;
    }
};
</script>

<template>
    <form @submit.prevent>
        <div class="container-fluid">
            <!-- Sección: Datos del Contrato -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-file-contract me-1"></i>Datos del Contrato
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <!-- Colaborador -->
                <div class="col-md-6 mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small mb-0">Colaborador <span class="text-danger">*</span></label>
                        <button
                            v-if="!isEditing"
                            type="button"
                            @click="refreshEmployees"
                            :disabled="isRefreshingEmployees"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Refrescar lista'"
                            style="font-size: 0.75rem;"
                        >
                            <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingEmployees}"></i>
                        </button>
                    </div>
                    <input v-if="isEditing" type="text" class="form-control form-control-sm bg-light" :value="employeeName" disabled />
                    <template v-else>
                        <select v-model="form.employee_id" class="form-select form-select-sm"
                            :class="{ 'is-invalid': form.errors?.employee_id }">
                            <option value="" disabled selected>Seleccione colaborador</option>
                            <option v-for="emp in employeeOptions" :key="emp.value" :value="emp.value">{{ emp.label }}</option>
                        </select>
                        <div v-if="form.errors?.employee_id" class="invalid-feedback">{{ form.errors.employee_id }}</div>
                    </template>
                </div>

                <!-- Empresa -->
                <div class="col-md-4 mb-2">
                    <label class="form-label small mb-1">Empresa <span class="text-danger">*</span></label>
                    <select v-model="form.company_reason_id" class="form-select form-select-sm"
                        :class="{ 'is-invalid': form.errors?.company_reason_id }">
                        <option value="" disabled selected>Seleccione empresa</option>
                        <option v-for="c in companyReasons" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <div v-if="form.errors?.company_reason_id" class="invalid-feedback">{{ form.errors.company_reason_id }}</div>
                </div>

                <!-- Parcela -->
                <div class="col-md-2 mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label small mb-0">Parcela</label>
                        <button type="button" @click="refreshParcels" :disabled="isRefreshingParcels"
                            class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2"
                            v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                            <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingParcels}"></i>
                        </button>
                    </div>
                    <select v-model="form.parcel_id" class="form-select form-select-sm">
                        <option value="">Sin parcela</option>
                        <option v-for="p in parcelOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>
            </div>

            <div class="row mb-2">
                <!-- Fecha Contrato -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Fecha Contrato <span class="text-danger">*</span></label>
                    <input type="date" v-model="form.contract_date" class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors?.contract_date }" />
                    <div v-if="form.errors?.contract_date" class="invalid-feedback">{{ form.errors.contract_date }}</div>
                </div>

                <!-- Tipo Contrato -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Tipo de Contrato <span class="text-danger">*</span></label>
                    <select v-model="form.contract_type" class="form-select form-select-sm"
                        :class="{ 'is-invalid': form.errors?.contract_type }">
                        <option value="" disabled selected>Seleccione tipo</option>
                        <option v-for="t in contractTypes" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <div v-if="form.errors?.contract_type" class="invalid-feedback">{{ form.errors.contract_type }}</div>
                </div>

                <!-- Fecha Término (solo Plazo Fijo) -->
                <div class="col-md-3 mb-2" v-if="showEndDate">
                    <label class="form-label small mb-1">Fecha de Término <span class="text-danger">*</span></label>
                    <input type="date" v-model="form.end_date" class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors?.end_date }" />
                    <div v-if="form.errors?.end_date" class="invalid-feedback">{{ form.errors.end_date }}</div>
                </div>

                <!-- Cargo -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Cargo</label>
                    <input type="text" v-model="form.position" class="form-control form-control-sm" placeholder="Cargo" />
                </div>

                <!-- Labor -->
                <div class="col-md-3 mb-2" :class="{ 'col-md-3': showEndDate }">
                    <label class="form-label small mb-1">Labor</label>
                    <input type="text" v-model="form.labor" class="form-control form-control-sm" placeholder="Labor" />
                </div>

                <!-- Estado -->
                <div class="col-md-2 mb-2" v-if="!showEndDate">
                    <label class="form-label small mb-1">Estado</label>
                    <select v-model="form.is_active" class="form-select form-select-sm">
                        <option :value="true">Vigente</option>
                        <option :value="false">Finalizado</option>
                    </select>
                </div>
            </div>

            <hr class="my-2">

            <!-- Sección: Remuneración y Previsión -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-money-bill-wave me-1"></i>Remuneración y Previsión
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <!-- Sueldo Base -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Sueldo Base <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" v-model="form.base_salary" class="form-control form-control-sm"
                            :class="{ 'is-invalid': form.errors?.base_salary }" placeholder="0" min="0" />
                    </div>
                    <div v-if="form.errors?.base_salary" class="invalid-feedback d-block">{{ form.errors.base_salary }}</div>
                </div>

                <!-- Sueldo Líquido -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Sueldo Líquido <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" v-model="form.net_salary" class="form-control form-control-sm"
                            :class="{ 'is-invalid': form.errors?.net_salary }" placeholder="0" min="0" />
                    </div>
                    <div v-if="form.errors?.net_salary" class="invalid-feedback d-block">{{ form.errors.net_salary }}</div>
                </div>

                <!-- Horario -->
                <div class="col-md-6 mb-2">
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
                        <option value="" disabled selected>Seleccione horario</option>
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
            </div>

            <div class="row mb-2">
                <!-- AFP -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">AFP</label>
                    <select v-model="form.afp_id" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione AFP</option>
                        <option v-for="a in afps" :key="a.value" :value="a.value">{{ a.label }}</option>
                    </select>
                </div>

                <!-- Salud -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Salud</label>
                    <select v-model="form.health_plan_id" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione plan</option>
                        <option v-for="h in healthPlans" :key="h.value" :value="h.value">{{ h.label }}</option>
                    </select>
                </div>

                <!-- Estado Civil -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Estado Civil</label>
                    <select v-model="form.marital_status" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione</option>
                        <option v-for="m in maritalStatuses" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>

                <!-- Estado (si Plazo Fijo) -->
                <div class="col-md-3 mb-2" v-if="showEndDate">
                    <label class="form-label small mb-1">Estado</label>
                    <select v-model="form.is_active" class="form-select form-select-sm">
                        <option :value="true">Vigente</option>
                        <option :value="false">Finalizado</option>
                    </select>
                </div>
            </div>

            <hr class="my-2">

            <!-- Sección: Datos Personales -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-user me-1"></i>Datos Personales
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <!-- Ciudad -->
                <div class="col-md-3 mb-2">
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
                        <option value="" disabled selected>Seleccione ciudad</option>
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

                <!-- Dirección -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Dirección</label>
                    <input type="text" v-model="form.address" class="form-control form-control-sm" placeholder="Dirección" />
                </div>

                <!-- Teléfono -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Teléfono</label>
                    <input type="text" v-model="form.phone" class="form-control form-control-sm" placeholder="+56 9 1234 5678" />
                </div>

                <!-- Email -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Email</label>
                    <input type="email" v-model="form.email" class="form-control form-control-sm"
                        :class="{ 'is-invalid': form.errors?.email }" placeholder="correo@ejemplo.cl" />
                    <div v-if="form.errors?.email" class="invalid-feedback">{{ form.errors.email }}</div>
                </div>
            </div>

            <hr class="my-2">

            <!-- Sección: Datos Bancarios -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h6 class="text-primary border-bottom pb-1 mb-2">
                        <i class="fas fa-university me-1"></i>Datos Bancarios
                    </h6>
                </div>
            </div>

            <div class="row mb-2">
                <!-- Forma de Pago -->
                <div class="col-md-3 mb-2">
                    <label class="form-label small mb-1">Forma de Pago</label>
                    <select v-model="form.payment_method_id" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione</option>
                        <option v-for="pm in paymentMethods" :key="pm.value" :value="pm.value">{{ pm.label }}</option>
                    </select>
                </div>

                <!-- Banco -->
                <div class="col-md-3 mb-2" v-if="needsBankDetails">
                    <label class="form-label small mb-1">Banco</label>
                    <select v-model="form.bank_id" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione</option>
                        <option v-for="b in banks" :key="b.value" :value="b.value">{{ b.label }}</option>
                    </select>
                </div>

                <!-- Tipo de Cuenta -->
                <div class="col-md-3 mb-2" v-if="needsBankDetails">
                    <label class="form-label small mb-1">Tipo de Cuenta</label>
                    <select v-model="form.account_type_id" class="form-select form-select-sm">
                        <option value="" disabled selected>Seleccione</option>
                        <option v-for="at in filteredAccountTypes" :key="at.value" :value="at.value">{{ at.label }}</option>
                    </select>
                </div>

                <!-- Número de Cuenta -->
                <div class="col-md-3 mb-2" v-if="needsBankDetails">
                    <label class="form-label small mb-1">N° de Cuenta</label>
                    <input type="text" v-model="form.account_number" class="form-control form-control-sm"
                        :placeholder="isCuentaRut ? 'Se completa automáticamente' : 'Número de cuenta'"
                        :readonly="isCuentaRut" :class="{ 'bg-light': isCuentaRut }" />
                </div>
            </div>
        </div>
    </form>
</template>
