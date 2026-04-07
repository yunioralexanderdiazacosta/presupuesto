<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const currentUrl = usePage().url;

const tabs = [
    { label: 'Órdenes de Aplicación', route: 'application-orders.index', icon: 'fas fa-clipboard-list' },
    { label: 'Aplicaciones', route: 'agrochemical-outflows.index', icon: 'fas fa-spray-can' },
    { label: 'Libro de Campo', route: 'libro-campo.index', icon: 'fas fa-book' },
];

function isActive(routeName) {
    try {
        const tabUrl = route(routeName);
        return currentUrl.startsWith(new URL(tabUrl, window.location.origin).pathname);
    } catch {
        return false;
    }
}
</script>

<template>
    <div class="agrochemical-nav mb-2">
        <nav class="d-flex align-items-center px-2 py-1 rounded" style="background-color: #f9fafb; border: 1px solid #a3d9a5; border-radius: 10px !important;">
            <span class="text-muted me-2 flex-shrink-0" style="font-size: 0.8rem; white-space: nowrap;">
                <i class="fas fa-flask me-1"></i>Agroquímicos:
            </span>
            <div class="d-flex align-items-center flex-grow-1">
                <Link
                    v-for="tab in tabs"
                    :key="tab.route"
                    :href="route(tab.route)"
                    class="agrochemical-tab flex-fill text-center"
                    :class="{ 'active': isActive(tab.route) }"
                    preserve-state
                >
                    <i :class="tab.icon" class="me-1"></i>
                    <span>{{ tab.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.agrochemical-nav nav {
    min-height: 42px;
}

.agrochemical-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 10px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #5e6e82;
    text-decoration: none;
    border-radius: 8px;
    white-space: nowrap;
    transition: all 0.15s ease;
    line-height: 1.4;
}

.agrochemical-tab:hover {
    color: #2c7be5;
    background-color: rgba(44, 123, 229, 0.08);
}

.agrochemical-tab.active {
    color: #fff;
    background-color: #2c7be5;
}

.agrochemical-tab i {
    font-size: 0.75rem;
}
</style>
