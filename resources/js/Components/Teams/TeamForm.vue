<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object
});
</script>
<template>
    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Nombre del contacto</label>
        <TextInput
            id="name"
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
        />
        <InputError class="mt-2" :message="form.errors.team_name" />
    </div>

    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Nombre de la empresa</label>
        <TextInput
            id="team_name"
            v-model="form.team_name"
            class="form-control form-control-solid"
            type="text"
        />
        <InputError class="mt-2" :message="form.errors.team_name" />
    </div>

    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Correo electronico</label>
        <TextInput
            id="email"
            v-model="form.email"
            class="form-control form-control-solid"
            type="email"
        />
        <InputError class="mt-2" :message="form.errors.email" />
    </div>

     <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Contraseña</label>
        <TextInput
            id="email"
            v-model="form.password"
            class="form-control form-control-solid"
            type="password"
        />
        <InputError class="mt-2" :message="form.errors.password" />
    </div>

    <div class="fv-row mb-3">
        <label for="address" class="form-label fs-6 fw-bold mb-3">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>
</template>