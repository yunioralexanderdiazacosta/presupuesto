<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateFuelOutflowModal from '@/Components/FuelOutflows/CreateFuelOutflowModal.vue';

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
        const machinery = item.machinery?.cod_machinery?.toLowerCase() || '';
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

const showCreateModal = ref(false);

function openCreateModal() {
    showCreateModal.value = true;
}
function closeCreateModal() {
    showCreateModal.value = false;
}
function reloadAfterSave() {
    closeCreateModal();
    router.reload({ preserveScroll: true });
}

</script>
<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ title }}</h5>
                <button class="btn btn-primary btn-sm" @click="openCreateModal">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
            </div>
            <div class="card-body">
                <input v-model="term" class="form-control mb-3" placeholder="Buscar..." />
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-hover table-sm fs-10 mb-0">
                    <thead class="table-primary">
                      <tr>
                        <th>Fecha</th>
                        <th>Código Maquinaria</th>
                        <th>Operario</th>
                        <th>Centro de Costo</th>
                        <th>Tipo Combustible</th>
                        <th>Litros</th>
                        <th>Horómetro</th>
                        <th>Odómetro</th>
                        <th>Observaciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in filteredRows" :key="item.id">
                        <td>{{ item.date }}</td>
                        <td>{{ item.machinery?.cod_machinery || '-' }}</td>
                        <td>{{ item.operator?.name || '-' }}</td>
                        <td>{{ item.cost_center?.name || '-' }}</td>
                        <td>{{ item.fuel_type }}</td>
                        <td>{{ item.liters }}</td>
                        <td>{{ item.horometer }}</td>
                        <td>{{ item.odometer }}</td>
                        <td>{{ item.observations }}</td>
                      </tr>
                      <tr v-if="filteredRows.length === 0">
                        <td colspan="9" class="text-center text-muted">No hay consumos registrados.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
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
        <FuelOutflowCreateModal
            v-if="false"/>
        <CreateFuelOutflowModal
            :show="showCreateModal"
            :machineries="props.machineries"
            :operators="props.operators"
            :costCenters="props.costCenters"
            @close="closeCreateModal"
            @saved="reloadAfterSave"
        />
    </AppLayout>
</template>
