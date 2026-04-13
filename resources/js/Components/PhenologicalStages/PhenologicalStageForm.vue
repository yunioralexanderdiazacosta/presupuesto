<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Multiselect from '@vueform/multiselect';

defineProps({
    form: Object,
    fruits: Array
});
</script>
<template>
    <div class="row">
        <div class="col-12 mb-3">
            <label class="col-form-label required">Nombre</label>
            <TextInput
                id="name"
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                placeholder="Ej: Floración, Cuajado, Envero..."
                :class="{'is-invalid': form.errors.name}"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>
        <div class="col-12 mb-3">
            <label class="col-form-label required">Frutal</label>
            <Multiselect
                v-model="form.fruit_id"
                :options="fruits"
                :searchable="true"
                :canClear="true"
                placeholder="Seleccione un frutal"
                class="multiselect-blue form-control"
                :class="{'is-invalid': form.errors.fruit_id}"
            />
            <InputError class="mt-2" :message="form.errors.fruit_id" />
        </div>
    </div>
</template>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
}
</style>
