<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    form: Object
});
</script>
<template>
    <div class="fv-row mb-3">
        <label class="col-form-label required">Nombre</label>
        <TextInput
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.name}"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>

    <div class="fv-row mb-3">
        <label class="col-form-label">Observaciones</label>
        <textarea v-model="form.observations" rows="3"
            class="form-control form-control-solid"
            :class="{'is-invalid': form.errors.observations}">
        </textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>
</template>
