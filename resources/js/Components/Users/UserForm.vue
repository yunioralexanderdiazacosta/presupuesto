<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object
});
</script>
<template>
    <div class="fv-row">
        <label class="col-form-label">Nombre</label>
        <TextInput
            id="name"
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>


    <div class="fv-row">
        <label class="col-form-label">Correo electronico</label>
        <TextInput
            id="email"
            v-model="form.email"
            class="form-control form-control-solid"
            type="email"
        />
        <InputError class="mt-2" :message="form.errors.email" />
    </div>

     <div class="fv-row">
        <label class="col-form-label">Contraseña</label>
        <TextInput
            id="email"
            v-model="form.password"
            class="form-control form-control-solid"
            type="password"
        />
        <InputError class="mt-2" :message="form.errors.password" />
    </div>

     <div class="fv-row">
        <label for="role" class="col-form-label">Rol</label><br>
        
        <div class="form-check form-check-solid form-check-inline mb-3 mt-3">
            <input class="form-check-input" type="radio" v-model="form.role" id="admin" value="Admin">
            <label class="form-check-label ps-1" for="admin">Administrador</label>
        </div>
         <div class="form-check form-check-solid form-check-inline mb-3 mt-3">
            <input class="form-check-input" type="radio" v-model="form.role" id="admin" value="Normal">
            <label class="form-check-label ps-1" for="admin">Digitador</label>
        </div>
        <small class="text-danger mt-2" :v-if="form.errors.role">{{form.errors.role}}</small>
    </div>
</template>