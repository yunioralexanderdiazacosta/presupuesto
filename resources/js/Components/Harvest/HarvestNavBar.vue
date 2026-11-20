<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

// Agrupadas por etapa del flujo; los grupos se separan con una línea
const groups = [
    [
        { label: 'Ingresar Cosecha', route: 'production-dispatches.index', icon: 'fas fa-truck-loading' },
    ],
    [
        { label: 'Resumen Diario', route: 'daily-harvest-summary.index', icon: 'fas fa-calendar-day' },
    ],
    [
        { label: 'Análisis de Cosecha', route: 'production-analysis.index', icon: 'fas fa-chart-bar' },
        { label: 'Clasificación', route: 'production-analysis.classifications', icon: 'fas fa-sort-amount-down' },
    ],
];

const normalizePath = (path) => path.replace(/\/+$/, '') || '/';

const pathOf = (routeName) => {
    try {
        return normalizePath(route(routeName, {}, false));
    } catch {
        return null;
    }
};

// Misma regla del menú lateral: no se muestran los módulos bloqueados para la empresa o el usuario
const blockedPaths = computed(() => new Set(page.props.moduleAccess?.blockedUris || []));

const visibleGroups = computed(() => groups
    .map(tabs => tabs.filter(tab => !blockedPaths.value.has(pathOf(tab.route))))
    .filter(tabs => tabs.length));

// Coincidencia exacta de la ruta (production-analysis es prefijo de production-analysis/classifications)
const currentPath = computed(() => normalizePath(page.url.split('?')[0]));
const isActive = (routeName) => pathOf(routeName) === currentPath.value;
</script>

<template>
    <div class="harvest-nav mb-2">
        <nav class="d-flex align-items-center px-2 py-1 rounded" style="background-color: #f9fafb; border: 1px solid #a3d9a5; border-radius: 10px !important;">
            <span class="text-muted me-2 flex-shrink-0" style="font-size: 0.8rem; white-space: nowrap;">
                <i class="fas fa-apple-alt me-1"></i>Cosecha:
            </span>
            <div class="d-flex align-items-center flex-grow-1 flex-wrap">
                <template v-for="(tabs, gi) in visibleGroups" :key="gi">
                    <span v-if="gi > 0" class="harvest-nav-divider"></span>
                    <Link
                        v-for="tab in tabs"
                        :key="tab.route"
                        :href="route(tab.route)"
                        class="harvest-nav-tab"
                        :class="{ 'active': isActive(tab.route) }"
                    >
                        <i :class="tab.icon" class="me-1"></i>
                        <span>{{ tab.label }}</span>
                    </Link>
                </template>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.harvest-nav nav {
    min-height: 42px;
}

.harvest-nav-tab {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #5e6e82;
    text-decoration: none;
    border-radius: 8px;
    white-space: nowrap;
    transition: all 0.15s ease;
    line-height: 1.4;
}

.harvest-nav-tab:hover {
    color: #2c7be5;
    background-color: rgba(44, 123, 229, 0.08);
}

.harvest-nav-tab.active {
    color: #fff;
    background-color: #2c7be5;
}

.harvest-nav-tab i {
    font-size: 0.75rem;
}

.harvest-nav-divider {
    width: 1px;
    height: 18px;
    margin: 0 8px;
    background-color: #d8e2ef;
}
</style>
