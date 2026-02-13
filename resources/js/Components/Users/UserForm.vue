<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    availableRoles: {
        type: Array,
        default: () => []
    }
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


    <div class="row">
        <div class="col-6">
            <label class="col-form-label">Correo electronico</label>
            <TextInput
                id="email"
                v-model="form.email"
                class="form-control form-control-solid"
                type="email"
            />
            <InputError class="mt-2" :message="form.errors.email" />

        </div>

        <div class="col-6">
            <label class="col-form-label">Contraseña</label>
            <TextInput
                id="password"
                v-model="form.password"
                class="form-control form-control-solid"
                type="password"
            />
            <InputError class="mt-2" :message="form.errors.password" />
        </div>
</div>
     <div class="fv-row">
        <label for="roles" class="col-form-label">
            <i class="fas fa-user-shield me-1"></i>Roles
        </label>
        <small class="text-muted d-block mb-2">Selecciona uno o más roles para este usuario</small>
        
        <div v-for="role in availableRoles" :key="role.value" class="form-check form-check-solid mb-2">
            <input 
                class="form-check-input" 
                type="checkbox" 
                :id="`role-${role.value}`" 
                :value="role.value"
                v-model="form.roles"
            >
            <label class="form-check-label ps-2" :for="`role-${role.value}`">
                {{ role.label }}
            </label>
        </div>
        
        <InputError class="mt-2" :message="form.errors.roles" />
    </div>
</template>