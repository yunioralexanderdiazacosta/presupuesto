<script setup>
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    form: { type: Object, required: true },
    fruitOptions: { type: Array, default: () => [] },
    copySources: { type: Array, default: () => [] },
});

const modes = [
    { value: 'empty', icon: 'fa-file', title: 'Partir en blanco', text: 'Digitas los kilos/ha desde cero.' },
    { value: 'copy', icon: 'fa-copy', title: 'Copiar una existente', text: 'Parte con los valores de otra estimación.' },
];

const selectedSource = () => props.copySources.find(s => String(s.value) === String(props.form.copy_from_id));
</script>

<template>
    <div>
        <div class="mb-3">
            <label class="form-label fw-bold small mb-1">Nombre de la estimación</label>
            <div class="input-group has-validation">
                <span class="input-group-text bg-white"><i class="fas fa-tag text-primary"></i></span>
                <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.name }"
                    placeholder="Ej: Estimación Marzo"
                    maxlength="255"
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small mb-1">Especie</label>
            <Multiselect
                v-model="form.fruit_id"
                :options="fruitOptions"
                placeholder="Seleccione especie..."
                :searchable="true"
                :can-clear="false"
                class="multiselect-blue"
            />
            <div v-if="form.errors.fruit_id" class="text-danger small mt-1">{{ form.errors.fruit_id }}</div>
        </div>

        <div class="mb-1">
            <label class="form-label fw-bold small mb-2">¿Cómo quieres partir?</label>
            <div class="row g-2">
                <div v-for="m in modes" :key="m.value" class="col-sm-6">
                    <div
                        class="border rounded-3 p-3 h-100 d-flex align-items-start gap-2"
                        :class="form.mode === m.value ? 'border-primary shadow-sm' : 'bg-white'"
                        :style="form.mode === m.value ? 'background-color: #eff6ff;' : ''"
                        style="cursor: pointer; transition: all .15s;"
                        @click="form.mode = m.value"
                    >
                        <span
                            class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            :class="form.mode === m.value ? 'bg-primary text-white' : 'bg-light text-muted'"
                            style="width: 34px; height: 34px;"
                        >
                            <i class="fas" :class="m.icon"></i>
                        </span>
                        <span>
                            <span class="fw-semibold d-block">{{ m.title }}</span>
                            <span class="text-muted small">{{ m.text }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="form.mode === 'copy'" class="mt-3 p-3 rounded-3 border bg-white">
            <label class="form-label fw-bold small mb-1">Estimación a copiar</label>
            <Multiselect
                v-model="form.copy_from_id"
                :options="copySources"
                placeholder="Seleccione una estimación..."
                :searchable="true"
                :can-clear="false"
                no-options-text="Sin estimaciones con datos"
                class="multiselect-blue"
            />
            <div v-if="form.errors.copy_from_id" class="text-danger small mt-1">{{ form.errors.copy_from_id }}</div>
            <div v-if="copySources.length === 0" class="text-muted small mt-2">
                <i class="fas fa-info-circle me-1"></i>Esta especie aún no tiene estimaciones con datos en la temporada activa.
            </div>
            <div v-else-if="selectedSource()" class="text-muted small mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Se copiarán los kilos/ha y observaciones de <strong>{{ selectedSource().count }}</strong>
                {{ selectedSource().count === 1 ? 'registro' : 'registros' }} (cuartel + variedad):
                {{ selectedSource().cuarteles }} {{ selectedSource().cuarteles === 1 ? 'cuartel' : 'cuarteles' }} y
                {{ selectedSource().variedades }} {{ selectedSource().variedades === 1 ? 'variedad' : 'variedades' }}.
                Después podrás ajustar cada valor.
            </div>
        </div>
    </div>
</template>
