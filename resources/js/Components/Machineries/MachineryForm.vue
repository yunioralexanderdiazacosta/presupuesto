<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: Object
});

const years = computed(() => {
    const current = new Date().getFullYear();
    const list = [];
    for (let y = current; y >= 1970; y--) list.push(y);
    return list;
});
</script>

<template>
    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="type_machinery_id" class="form-label small fw-bold">
                    Tipo Maquinaria <span class="text-danger">*</span>
                </label>
                <select
                    id="type_machinery_id"
                    v-model="form.type_machinery_id"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.type_machinery_id}"
                >
                    <option :value="''" disabled>Seleccione tipo de maquinaria</option>
                    <option v-for="type in $page.props.typeMachineries" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.type_machinery_id" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="cod_machinery" class="form-label small fw-bold">
                    Código Maquinaria <span class="text-danger">*</span>
                </label>
                <input
                    id="cod_machinery"
                    v-model="form.cod_machinery"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.cod_machinery}"
                    placeholder="Ej: MAQ-001"
                />
                <InputError class="mt-1" :message="form.errors.cod_machinery" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="company_reason_id" class="form-label small fw-bold">
                    Razón Social <span class="text-danger">*</span>
                </label>
                <select
                    id="company_reason_id"
                    v-model="form.company_reason_id"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.company_reason_id}"
                >
                    <option :value="''" disabled>Seleccione razón social</option>
                    <option v-for="company in $page.props.companyReasons" :key="company.value" :value="company.value">
                        {{ company.label }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.company_reason_id" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="counter_id" class="form-label small fw-bold">
                    Tipo de Contador <span class="text-danger">*</span>
                </label>
                <select
                    id="counter_id"
                    v-model="form.counter_id"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.counter_id}"
                >
                    <option :value="''" disabled>Seleccione tipo de contador</option>
                    <option v-for="counter in $page.props.counters" :key="counter.value" :value="counter.value">
                        {{ counter.label }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.counter_id" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="brand" class="form-label small fw-bold">
                    Marca <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <input
                    id="brand"
                    v-model="form.brand"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.brand}"
                    placeholder="Ej: John Deere"
                />
                <InputError class="mt-1" :message="form.errors.brand" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="modelo" class="form-label small fw-bold">
                    Modelo <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <input
                    id="modelo"
                    v-model="form.modelo"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.modelo}"
                    placeholder="Ej: 6110D"
                />
                <InputError class="mt-1" :message="form.errors.modelo" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="patente" class="form-label small fw-bold">
                    Patente <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <input
                    id="patente"
                    v-model="form.patente"
                    type="text"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.patente}"
                    placeholder="Ej: AB-CD-12"
                />
                <InputError class="mt-1" :message="form.errors.patente" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="year" class="form-label small fw-bold">
                    Año <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <select
                    id="year"
                    v-model="form.year"
                    class="form-select form-select-sm"
                    :class="{'is-invalid': form.errors.year}"
                >
                    <option :value="null">Seleccione año...</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
                <InputError class="mt-1" :message="form.errors.year" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="volume" class="form-label small fw-bold">
                    Volumen <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <input
                    id="volume"
                    v-model="form.volume"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.volume}"
                    placeholder="0.00"
                />
                <InputError class="mt-1" :message="form.errors.volume" />
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <label for="observations" class="form-label small fw-bold">
                    Observaciones <span class="text-muted fw-normal">(Opcional)</span>
                </label>
                <textarea
                    id="observations"
                    v-model="form.observations"
                    rows="3"
                    class="form-control form-control-sm"
                    :class="{'is-invalid': form.errors.observations}"
                    placeholder="Observaciones adicionales..."
                ></textarea>
                <InputError class="mt-1" :message="form.errors.observations" />
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    v-model="form.is_active"
                    id="is_active"
                    :value="true"
                />
                <label class="form-check-label fw-bold" for="is_active">
                    Activo
                </label>
            </div>
        </div>
    </div>
</template>