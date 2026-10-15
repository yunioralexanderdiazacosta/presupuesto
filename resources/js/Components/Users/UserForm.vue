<script setup>
import { ref } from 'vue';
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

const selectedCount = (group) => group.modules.filter(m => props.form.allowed_modules.includes(m.key)).length;

// Submenús dentro de cada grupo (campo "section" del catálogo), en el orden en que aparecen
const sectionsOf = (group) => {
    const sections = [];
    group.modules.forEach(module => {
        let section = sections.find(s => s.name === module.section);
        if (!section) {
            section = { name: module.section, modules: [] };
            sections.push(section);
        }
        section.modules.push(module);
    });
    return sections;
};

const groupIcons = {
    'Presupuestos': 'fas fa-lock',
    'Gestión': 'fas fa-tags',
    'Remuneraciones': 'fas fa-money-check-alt',
    'Evaluación de Proyectos': 'fas fa-seedling',
    'Parámetros': 'fas fa-user',
};
const groupIcon = (name) => groupIcons[name] || 'fas fa-folder';

// Grupos abiertos: por defecto todos cerrados para ver de un vistazo el estado de cada grupo
const openGroups = ref([]);
const isOpen = (name) => openGroups.value.includes(name);
const toggleOpen = (name) => {
    openGroups.value = isOpen(name)
        ? openGroups.value.filter(n => n !== name)
        : [...openGroups.value, name];
};
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
                <div class="module-access">
                    <div v-for="group in moduleGroups" :key="group.group" class="module-group" :class="{ 'is-off': groupState(group) === 'none' }">
                        <div class="module-group-header" @click="toggleOpen(group.group)">
                            <span class="module-group-icon">
                                <i :class="groupIcon(group.group)"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="module-group-title">{{ group.group }}</div>
                                <div class="module-group-sub">
                                    {{ selectedCount(group) }} de {{ group.modules.length }} módulos con acceso
                                </div>
                            </div>
                            <span
                                class="badge rounded-pill me-2"
                                :class="{
                                    'badge-subtle-success': groupState(group) === 'all',
                                    'badge-subtle-warning': groupState(group) === 'some',
                                    'badge-subtle-secondary': groupState(group) === 'none'
                                }"
                            >
                                {{ groupState(group) === 'all' ? 'Completo' : groupState(group) === 'some' ? 'Parcial' : 'Sin acceso' }}
                            </span>
                            <div class="form-check form-switch mb-0 me-2" @click.stop v-tooltip="'Marcar o desmarcar todo el grupo'">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    :id="`group-${group.group}`"
                                    :checked="groupState(group) === 'all'"
                                    :indeterminate.prop="groupState(group) === 'some'"
                                    :disabled="!canManageModules"
                                    @change="toggleGroup(group, $event.target.checked)"
                                >
                            </div>
                            <i class="fas fa-chevron-down module-chevron" :class="{ 'is-open': isOpen(group.group) }"></i>
                        </div>

                        <div v-show="isOpen(group.group)" class="module-group-body">
                            <div v-for="section in sectionsOf(group)" :key="section.name" class="module-section">
                                <div v-if="section.name !== group.group || sectionsOf(group).length > 1" class="module-section-title">
                                    {{ section.name }}
                                </div>
                                <div class="row g-1">
                                    <div v-for="module in section.modules" :key="module.key" class="col-md-6">
                                        <label
                                            class="module-item"
                                            :class="{ 'is-checked': form.allowed_modules.includes(module.key), 'is-disabled': !canManageModules }"
                                            :for="`module-${module.key}`"
                                        >
                                            <input
                                                class="form-check-input mt-0"
                                                type="checkbox"
                                                :id="`module-${module.key}`"
                                                :value="module.key"
                                                v-model="form.allowed_modules"
                                                :disabled="!canManageModules"
                                            >
                                            <span class="module-item-label">{{ module.label }}</span>
                                        </label>
                                    </div>
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

<style scoped>
.module-access {
    max-height: 380px;
    overflow-y: auto;
    padding-right: 4px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.module-group {
    flex-shrink: 0;
    border: 1px solid var(--falcon-border-color, #d8e2ef);
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}
.module-group.is-off .module-group-icon {
    background: #edf2f9;
    color: #9da9bb;
}
.module-group-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0.75rem;
    cursor: pointer;
    background: var(--falcon-body-bg, #f9fafd);
    user-select: none;
}
.module-group-header:hover {
    background: #edf2f9;
}
.module-group-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(44, 123, 229, 0.12);
    color: #2c7be5;
    font-size: 0.85rem;
}
.module-group-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: #344050;
    line-height: 1.2;
}
.module-group-sub {
    font-size: 0.7rem;
    color: #9da9bb;
}
.module-chevron {
    font-size: 0.7rem;
    color: #9da9bb;
    transition: transform 0.2s;
}
.module-chevron.is-open {
    transform: rotate(180deg);
}
.module-group-body {
    padding: 0.6rem 0.75rem 0.75rem 1.25rem;
    margin-left: 1rem;
    border-top: 1px solid var(--falcon-border-color, #d8e2ef);
    border-left: 2px solid rgba(44, 123, 229, 0.25);
    margin-bottom: 0.5rem;
    margin-top: 0.25rem;
}
.module-section + .module-section {
    margin-top: 0.6rem;
}
.module-section-title {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #9da9bb;
    margin-bottom: 0.3rem;
}
.module-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.5rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.78rem;
    color: #748194;
    transition: background 0.15s, border-color 0.15s;
}
.module-item:hover:not(.is-disabled) {
    background: #f9fafd;
    border-color: #d8e2ef;
}
.module-item.is-checked {
    color: #344050;
    background: rgba(44, 123, 229, 0.06);
}
.module-item.is-disabled {
    cursor: not-allowed;
    opacity: 0.75;
}
.module-item-label {
    line-height: 1.2;
}
</style>