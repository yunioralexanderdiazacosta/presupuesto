<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object,
    availableRoles: {
        type: Array,
        default: () => []
    },
    moduleGroups: {
        type: Array,
        default: () => []
    },
    canManageModules: {
        type: Boolean,
        default: true
    }
});

const allKeys = () => props.moduleGroups.flatMap(g => g.modules.map(m => m.key));

const groupState = (group) => {
    const selected = group.modules.filter(m => props.form.allowed_modules.includes(m.key)).length;
    if (selected === 0) return 'none';
    return selected === group.modules.length ? 'all' : 'some';
};

const toggleGroup = (group, checked) => {
    const keys = group.modules.map(m => m.key);
    const rest = props.form.allowed_modules.filter(k => !keys.includes(k));
    props.form.allowed_modules = checked ? [...rest, ...keys] : rest;
};

const selectAll = () => { props.form.allowed_modules = allKeys(); };
const selectNone = () => { props.form.allowed_modules = []; };
const onlyExpenseReports = () => { props.form.allowed_modules = ['expense-reports.index']; };
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

        <!-- Acceso por módulos -->
        <div class="col-12">
            <label class="form-label small mb-1">
                <i class="fas fa-th-large me-1"></i>Acceso a módulos
            </label>
            <div class="form-text mt-0">
                Todo marcado: el usuario ve todos los módulos de su empresa. Desmarca los que no debe ver; esos desaparecen del menú y quedan bloqueados.
            </div>
            <div v-if="!canManageModules" class="form-text text-warning">
                No puedes cambiar tu propio acceso a módulos; otro Admin o el Super Admin puede hacerlo.
            </div>

            <div class="mt-2">
                <div v-if="canManageModules" class="d-flex flex-wrap gap-1 mb-2">
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="selectAll">Marcar todo</button>
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="selectNone">Desmarcar todo</button>
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="onlyExpenseReports">Solo Rendiciones de Gastos</button>
                </div>
                <div class="border rounded p-2 bg-body-tertiary" style="max-height: 320px; overflow-y: auto;">
                    <div v-for="group in moduleGroups" :key="group.group" class="mb-2">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                :id="`group-${group.group}`"
                                :checked="groupState(group) === 'all'"
                                :indeterminate.prop="groupState(group) === 'some'"
                                :disabled="!canManageModules"
                                @change="toggleGroup(group, $event.target.checked)"
                            >
                            <label class="form-check-label small fw-semibold" :for="`group-${group.group}`">
                                {{ group.group }}
                            </label>
                        </div>
                        <div class="row g-1 ps-4">
                            <div v-for="module in group.modules" :key="module.key" class="col-md-6">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        :id="`module-${module.key}`"
                                        :value="module.key"
                                        v-model="form.allowed_modules"
                                        :disabled="!canManageModules"
                                    >
                                    <label class="form-check-label small" :for="`module-${module.key}`">
                                        {{ module.label }}
                                        <span class="text-muted" style="font-size: 0.65rem;" v-if="module.section !== group.group">· {{ module.section }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <InputError class="mt-1" :message="form.errors.allowed_modules" />
            </div>
        </div>
    </div>
</template>