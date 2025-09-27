<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    fuelOutflows: Object,
    machineries: Array,
    operators: Array,
    costCenters: Array,
});

const title = 'Consumos de Combustible';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const filteredRows = computed(() => {
    if (!props.fuelOutflows || !props.fuelOutflows.data) return [];
    if (!term.value) return props.fuelOutflows.data;
    const search = term.value.toLowerCase();
    return props.fuelOutflows.data.filter(item => {
        const machinery = item.machinery?.name?.toLowerCase() || '';
        const operator = item.operator?.name?.toLowerCase() || '';
        const costCenter = item.cost_center?.name?.toLowerCase() || '';
        const fuelType = item.fuel_type?.toLowerCase() || '';
        return (
            machinery.includes(search) ||
            operator.includes(search) ||
            costCenter.includes(search) ||
            fuelType.includes(search)
        );
    });
});

const columns = [
    { key: 'date', label: 'Fecha' },
    { key: 'machinery.name', label: 'Maquinaria' },
    { key: 'operator.name', label: 'Operario' },
    { key: 'cost_center.name', label: 'Centro de Costo' },
    { key: 'fuel_type', label: 'Tipo Combustible' },
    { key: 'liters', label: 'Litros' },
    { key: 'horometer', label: 'Horómetro' },
    { key: 'odometer', label: 'Odómetro' },
    { key: 'observations', label: 'Observaciones' },
];

</script>
<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ title }}</h5>
                <Link :href="route('fuel-outflows.create')" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo
                </Link>
            </div>
            <div class="card-body">
                <input v-model="term" class="form-control mb-3" placeholder="Buscar..." />
                <Table :columns="columns" :rows="filteredRows" />
                <div v-if="props.fuelOutflows && props.fuelOutflows.links" class="mt-3">
                    <nav>
                        <ul class="pagination">
                            <li v-for="link in props.fuelOutflows.links" :key="link.label" :class="['page-item', { active: link.active }]">
                                <a v-if="link.url" class="page-link" @click.prevent="router.get(link.url)">
                                    <span v-html="link.label" />
                                </a>
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
