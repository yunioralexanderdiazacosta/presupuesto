<script setup>
import { watch, computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    laborTypes: { type: Array, default: () => [] },
    bonusTypes: { type: Array, default: () => [] },
    costCenters: { type: Array, default: () => [] },
    showEmployee: { type: Boolean, default: true },
});

// Calcular monto automáticamente
const computedAmount = computed(() => {
    return Math.round((props.form.rate || 0) * (props.form.quantity || 0));
});

watch(computedAmount, (val) => {
    props.form.amount = val;
});

// Al seleccionar labor, precargar tarifa sugerida
function onLaborTypeChange() {
    const lt = props.laborTypes.find(l => String(l.value) === String(props.form.labor_type_id));
    if (lt) {
        props.form.rate = lt.default_rate || 0;
    }
}

// Al seleccionar bono, precargar monto sugerido
function onBonusTypeChange() {
    const bt = props.bonusTypes.find(b => String(b.value) === String(props.form.bonus_type_id));
    if (bt) {
        props.form.bonus_amount = bt.default_amount || 0;
    } else {
        props.form.bonus_amount = 0;
    }
}
</script>

<template>
    <form @submit.prevent>
        <div class="row g-3">
            <!-- Empleado (solo en creación) -->
            <div v-if="showEmployee" class="col-md-6">
                <label class="form-label small mb-1">Trabajador <span class="text-danger">*</span></label>
                <select v-model="form.employee_id" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.employee_id }">
                    <option :value="''" disabled selected>Seleccione trabajador...</option>
                    <option v-for="emp in employees" :key="emp.value" :value="emp.value">
                        {{ emp.label }} ({{ emp.rut }})
                    </option>
                </select>
                <div v-if="form.errors?.employee_id" class="invalid-feedback">{{ form.errors.employee_id }}</div>
            </div>

            <!-- Labor -->
            <div :class="showEmployee ? 'col-md-6' : 'col-md-4'">
                <label class="form-label small mb-1">Labor <span class="text-danger">*</span></label>
                <select v-model="form.labor_type_id" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.labor_type_id }"
                    @change="onLaborTypeChange">
                    <option :value="''" disabled selected>Seleccione labor...</option>
                    <option v-for="lt in laborTypes" :key="lt.value" :value="lt.value">{{ lt.label }}</option>
                </select>
                <div v-if="form.errors?.labor_type_id" class="invalid-feedback">{{ form.errors.labor_type_id }}</div>
            </div>

            <!-- Tarifa -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Tarifa ($) <span class="text-danger">*</span></label>
                <input type="number" v-model="form.rate" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.rate }" min="0" />
                <div v-if="form.errors?.rate" class="invalid-feedback">{{ form.errors.rate }}</div>
            </div>

            <!-- Cantidad -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Cantidad <span class="text-danger">*</span></label>
                <input type="number" v-model="form.quantity" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.quantity }" step="0.01" min="0" />
                <div v-if="form.errors?.quantity" class="invalid-feedback">{{ form.errors.quantity }}</div>
            </div>

            <!-- Monto (calculado) -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Monto</label>
                <input type="text" class="form-control form-control-sm bg-light" disabled
                    :value="'$' + computedAmount.toLocaleString('es-CL')" />
            </div>

            <!-- Horas -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Horas <span class="text-danger">*</span></label>
                <input type="number" v-model="form.hours" class="form-control form-control-sm"
                    :class="{ 'is-invalid': form.errors?.hours }" step="0.5" min="0.5" max="24" />
                <div v-if="form.errors?.hours" class="invalid-feedback">{{ form.errors.hours }}</div>
            </div>

            <!-- Centro de Costo -->
            <div class="col-md-4">
                <label class="form-label small mb-1">Centro de Costo <span class="text-danger">*</span></label>
                <select v-model="form.cost_center_id" class="form-select form-select-sm"
                    :class="{ 'is-invalid': form.errors?.cost_center_id }">
                    <option :value="''" disabled selected>Seleccione CC...</option>
                    <option v-for="cc in costCenters" :key="cc.value" :value="cc.value">{{ cc.label }}</option>
                </select>
                <div v-if="form.errors?.cost_center_id" class="invalid-feedback">{{ form.errors.cost_center_id }}</div>
            </div>

            <!-- Tipo de Bono -->
            <div class="col-md-3">
                <label class="form-label small mb-1">Bono (opcional)</label>
                <select v-model="form.bonus_type_id" class="form-select form-select-sm"
                    @change="onBonusTypeChange">
                    <option :value="''">Sin bono</option>
                    <option v-for="bt in bonusTypes" :key="bt.value" :value="bt.value">{{ bt.label }}</option>
                </select>
            </div>

            <!-- Monto Bono -->
            <div class="col-md-2">
                <label class="form-label small mb-1">Monto Bono ($)</label>
                <input type="number" v-model="form.bonus_amount" class="form-control form-control-sm" min="0" />
            </div>

            <!-- Observaciones -->
            <div class="col-md-12">
                <label class="form-label small mb-1">Observaciones</label>
                <textarea v-model="form.observations" class="form-control form-control-sm" rows="2"
                    placeholder="Notas opcionales..."></textarea>
            </div>
        </div>
    </form>
</template>
