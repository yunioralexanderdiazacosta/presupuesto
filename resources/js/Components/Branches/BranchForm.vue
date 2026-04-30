<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: Object
});
</script>
<template>
    <div class="row">
        <div class="col-lg-12">
            <div class="fv-row">
                <label class="col-form-label">Nombre</label>
                <TextInput
                    id="name"
                    v-model="form.name"
                    class="form-control form-control-solid"
                    type="text"
                    :class="{'is-invalid': form.errors.name}"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
        </div>
        <div class="col-lg-12 mt-3">
            <div class="form-check form-switch">
                <input
                    id="is_default"
                    v-model="form.is_default"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                />
                <label class="form-check-label" for="is_default">
                    Sucursal predeterminada
                    <span class="text-muted small ms-1">(se cargará automáticamente en los formularios)</span>
                </label>
            </div>
        </div>
    </div>
</template>
