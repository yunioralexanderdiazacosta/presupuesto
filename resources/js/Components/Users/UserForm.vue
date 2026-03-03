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
    <div class="row g-3">
        <!-- Nombre -->
        <div class="col-md-6">
            <label class="form-label small mb-1">
                <i class="fas fa-user me-1"></i>Nombre
            </label>
            <TextInput
                id="name"
                v-model="form.name"
                class="form-control form-control-sm"
                type="text"
                placeholder="Nombre completo"
            />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>

        <!-- Usuario -->
        <div class="col-md-6">
            <label class="form-label small mb-1">
                <i class="fas fa-id-badge me-1"></i>Usuario
            </label>
            <TextInput
                id="username"
                v-model="form.username"
                class="form-control form-control-sm"
                type="text"
                placeholder="Nombre de usuario"
            />
            <InputError class="mt-1" :message="form.errors.username" />
        </div>

        <!-- Correo electrónico -->
        <div class="col-md-6">
            <label class="form-label small mb-1">
                <i class="fas fa-envelope me-1"></i>Correo electrónico
            </label>
            <TextInput
                id="email"
                v-model="form.email"
                class="form-control form-control-sm"
                type="email"
                placeholder="correo@ejemplo.com"
            />
            <InputError class="mt-1" :message="form.errors.email" />
        </div>

        <!-- Contraseña -->
        <div class="col-md-6">
            <label class="form-label small mb-1">
                <i class="fas fa-lock me-1"></i>Contraseña
            </label>
            <TextInput
                id="password"
                v-model="form.password"
                class="form-control form-control-sm"
                type="password"
                placeholder="••••••••"
            />
            <InputError class="mt-1" :message="form.errors.password" />
        </div>

        <!-- Roles -->
        <div class="col-12">
            <label class="form-label small mb-1">
                <i class="fas fa-user-shield me-1"></i>Roles
            </label>
            <div class="d-flex flex-wrap gap-3 mt-1">
                <div v-for="role in availableRoles" :key="role.value" class="form-check">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        :id="`role-${role.value}`" 
                        :value="role.value"
                        v-model="form.roles"
                    >
                    <label class="form-check-label small" :for="`role-${role.value}`">
                        {{ role.label }}
                    </label>
                </div>
            </div>
            <InputError class="mt-1" :message="form.errors.roles" />
        </div>
    </div>
</template>