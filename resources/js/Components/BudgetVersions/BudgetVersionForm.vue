<script setup>
import { computed } from 'vue';
import Multiselect from '@vueform/multiselect';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    versions: { type: Array, default: () => [] },
    mode: { type: String, default: 'create' },
});

const sourceOptions = computed(() =>
    (props.versions || []).map(v => ({
        value: v.id,
        label: v.is_official ? `${v.name} (oficial)` : v.name,
    }))
);
</script>
<template>
    <div class="fv-row">
        <label class="col-form-label">Nombre</label>
        <TextInput
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
            placeholder="Ej: Presupuesto 2026-2027 v2"
            :class="{'is-invalid': form.errors.name}"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>

    <div class="fv-row">
        <label class="col-form-label">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-solid" :class="{'is-invalid': form.errors.observations}"></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>

    <template v-if="mode === 'create'">
        <div class="fv-row">
            <label class="col-form-label">Copiar desde</label>
            <Multiselect
                placeholder="Seleccione la versión de origen"
                v-model="form.source_version_id"
                :close-on-select="true"
                :options="sourceOptions"
                class="multiselect-blue form-control"
                :class="{'is-invalid': form.errors.source_version_id}"
                :searchable="true"
            />
            <InputError class="mt-2" :message="form.errors.source_version_id" />
            <small class="text-muted d-block mt-1">Se copian todas las líneas del presupuesto (agroquímicos, fertilizantes, insumos, servicios, cosecha, mano de obra, administración y generales de campo).</small>
        </div>

        <div class="form-check mt-3">
            <input id="activateVersion" v-model="form.activate" class="form-check-input" type="checkbox" />
            <label class="form-check-label" for="activateVersion">Trabajar en esta versión al crearla</label>
        </div>
    </template>
</template>
