<script setup>
const props = defineProps({
    form: Object,
    contracts: Array,
    discountTypes: Array,
    months: Array,
});
</script>

<template>
    <div class="row g-3">
        <!-- Colaborador (Contrato) -->
        <div class="col-md-6">
            <label class="form-label small mb-1">Colaborador <span class="text-danger">*</span></label>
            <select v-model="form.contract_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.contract_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="c in contracts" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <div v-if="form.errors?.contract_id" class="invalid-feedback">{{ form.errors.contract_id }}</div>
        </div>

        <!-- Tipo de Descuento -->
        <div class="col-md-6">
            <label class="form-label small mb-1">Tipo de Descuento <span class="text-danger">*</span></label>
            <select v-model="form.monthly_discount_type_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.monthly_discount_type_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="t in discountTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
            <div v-if="form.errors?.monthly_discount_type_id" class="invalid-feedback">{{ form.errors.monthly_discount_type_id }}</div>
        </div>

        <!-- Mes -->
        <div class="col-md-4">
            <label class="form-label small mb-1">Mes <span class="text-danger">*</span></label>
            <select v-model="form.month_id" class="form-select form-select-sm"
                :class="{ 'is-invalid': form.errors?.month_id }">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <div v-if="form.errors?.month_id" class="invalid-feedback">{{ form.errors.month_id }}</div>
        </div>

        <!-- Monto -->
        <div class="col-md-4">
            <label class="form-label small mb-1">Monto (CLP) <span class="text-danger">*</span></label>
            <input v-model="form.amount" type="number" min="1" class="form-control form-control-sm"
                :class="{ 'is-invalid': form.errors?.amount }"
                placeholder="0" />
            <div v-if="form.errors?.amount" class="invalid-feedback">{{ form.errors.amount }}</div>
        </div>

        <!-- Observaciones -->
        <div class="col-md-8">
            <label class="form-label small mb-1">Observaciones</label>
            <input v-model="form.observations" type="text" class="form-control form-control-sm"
                placeholder="Opcional..." maxlength="500" />
        </div>
    </div>
</template>
