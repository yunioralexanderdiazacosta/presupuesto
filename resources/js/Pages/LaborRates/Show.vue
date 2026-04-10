<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    laborRates: Array,
    teamName: String,
});

const searchQuery = ref('');
const filterGroup = ref('');

// Grupos únicos (por labor type)
const groups = computed(() => {
    const names = [...new Set(props.laborRates.map(lr => lr.labor_type_name))];
    return names.sort((a, b) => {
        if (a === 'Sin labor asociada') return 1;
        if (b === 'Sin labor asociada') return -1;
        return a.localeCompare(b);
    });
});

// Filtrado
const filteredRates = computed(() => {
    let items = props.laborRates;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(lr =>
            String(lr.code).includes(q) ||
            lr.name.toLowerCase().includes(q) ||
            lr.labor_type_name.toLowerCase().includes(q) ||
            lr.unit_name.toLowerCase().includes(q)
        );
    }
    if (filterGroup.value) {
        items = items.filter(lr => lr.labor_type_name === filterGroup.value);
    }
    return items;
});

// Agrupado
const groupedRates = computed(() => {
    const map = {};
    filteredRates.value.forEach(lr => {
        if (!map[lr.labor_type_name]) map[lr.labor_type_name] = [];
        map[lr.labor_type_name].push(lr);
    });
    const sorted = {};
    Object.keys(map).sort((a, b) => {
        if (a === 'Sin labor asociada') return 1;
        if (b === 'Sin labor asociada') return -1;
        return a.localeCompare(b);
    }).forEach(k => sorted[k] = map[k]);
    return sorted;
});

function fmt(val) {
    if (!val && val !== 0) return '-';
    return '$' + Number(val).toLocaleString('es-CL');
}
</script>

<template>
    <AppLayout title="Catálogo de Tratos">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-dollar-sign me-2"></i>Catálogo de Tratos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <a :href="route('labor-rates.export-pdf', { action: 'stream' })" target="_blank"
                                class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-eye" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Ver PDF</span>
                            </a>
                            <a :href="route('labor-rates.export-pdf', { action: 'download' })"
                                class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-file-pdf" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Descargar PDF</span>
                            </a>
                            <Link :href="route('daily-management.index', { tab: 'labor-rates' })"
                                class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Resumen -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-soft-primary text-center p-2">
                            <small class="text-muted">Total Tratos</small>
                            <strong class="fs-8">{{ laborRates.length }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-soft-info text-center p-2">
                            <small class="text-muted">Grupos</small>
                            <strong class="fs-8">{{ groups.length }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-soft-success text-center p-2">
                            <small class="text-muted">Equipo</small>
                            <strong class="fs-9">{{ teamName }}</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-soft-warning text-center p-2">
                            <small class="text-muted">Mostrando</small>
                            <strong class="fs-8">{{ filteredRates.length }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" v-model="searchQuery" class="form-control form-control-sm"
                            placeholder="Buscar por código, nombre, labor o unidad..." />
                    </div>
                    <div class="col-md-4">
                        <select v-model="filterGroup" class="form-select form-select-sm">
                            <option value="">Todas las labores</option>
                            <option v-for="g in groups" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-falcon-default btn-sm w-100"
                            @click="searchQuery = ''; filterGroup = ''">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>

                <!-- Grupos de tarifas -->
                <div v-for="(rates, groupName) in groupedRates" :key="groupName" class="mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="border-start border-3 border-primary ps-2">
                            <h6 class="mb-0 text-primary">
                                {{ groupName }}
                                <span class="badge bg-soft-primary text-primary ms-1">{{ rates.length }}</span>
                            </h6>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover fs--1 mb-0">
                            <thead class="bg-200">
                                <tr>
                                    <th style="width: 65px;" class="text-center">Cód.</th>
                                    <th>Trato</th>
                                    <th style="width: 100px;">Unidad</th>
                                    <th style="width: 110px;" class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lr in rates" :key="lr.id">
                                    <td class="text-center fw-bold text-success fs-8">{{ lr.code }}</td>
                                    <td class="fw-semi-bold">{{ lr.name }}</td>
                                    <td class="text-muted">{{ lr.unit_name }}</td>
                                    <td class="text-end fw-bold text-primary">{{ fmt(lr.rate) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p v-if="Object.keys(groupedRates).length === 0" class="text-muted text-center py-4">
                    No se encontraron tratos con los filtros aplicados.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
