<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    laborTypes: Array,
    teamName: String,
});

const searchQuery = ref('');
const filterGroup = ref('');

// Grupos únicos
const groups = computed(() => {
    const names = [...new Set(props.laborTypes.map(lt => lt.level3_name))];
    return names.sort((a, b) => {
        if (a === 'Sin clasificación') return 1;
        if (b === 'Sin clasificación') return -1;
        return a.localeCompare(b);
    });
});

// Filtrado
const filteredTypes = computed(() => {
    let items = props.laborTypes;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(lt =>
            String(lt.code).includes(q) ||
            lt.name.toLowerCase().includes(q) ||
            lt.level3_name.toLowerCase().includes(q)
        );
    }
    if (filterGroup.value) {
        items = items.filter(lt => lt.level3_name === filterGroup.value);
    }
    return items;
});

// Agrupado
const groupedTypes = computed(() => {
    const map = {};
    filteredTypes.value.forEach(lt => {
        if (!map[lt.level3_name]) map[lt.level3_name] = [];
        map[lt.level3_name].push(lt);
    });
    // Ordenar keys
    const sorted = {};
    Object.keys(map).sort((a, b) => {
        if (a === 'Sin clasificación') return 1;
        if (b === 'Sin clasificación') return -1;
        return a.localeCompare(b);
    }).forEach(k => sorted[k] = map[k]);
    return sorted;
});

function fmt(val) {
    if (!val) return '-';
    return '$' + Number(val).toLocaleString('es-CL');
}
</script>

<template>
    <AppLayout title="Catálogo de Labores">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-hard-hat me-2"></i>Catálogo de Labores
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <a :href="route('labor-types.export-pdf', { action: 'stream' })" target="_blank"
                                class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-eye" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Ver PDF</span>
                            </a>
                            <a :href="route('labor-types.export-pdf', { action: 'download' })"
                                class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-file-pdf" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Descargar PDF</span>
                            </a>
                            <Link :href="route('daily-management.index', { tab: 'labor-types' })"
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
                            <small class="text-muted">Total Labores</small>
                            <strong class="fs-8">{{ laborTypes.length }}</strong>
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
                            <strong class="fs-8">{{ filteredTypes.length }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" v-model="searchQuery" class="form-control form-control-sm"
                            placeholder="Buscar por código, nombre o grupo..." />
                    </div>
                    <div class="col-md-4">
                        <select v-model="filterGroup" class="form-select form-select-sm">
                            <option value="">Todos los grupos</option>
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

                <!-- Grupos de labores -->
                <div v-for="(labors, groupName) in groupedTypes" :key="groupName" class="mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="border-start border-3 border-primary ps-2">
                            <h6 class="mb-0 text-primary">
                                {{ groupName }}
                                <span class="badge bg-soft-primary text-primary ms-1">{{ labors.length }}</span>
                            </h6>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover fs--1 mb-0">
                            <thead class="bg-200">
                                <tr>
                                    <th style="width: 65px;" class="text-center">Cód.</th>
                                    <th>Labor</th>
                                    <th style="width: 100px;">Unidad</th>
                                    <th style="width: 90px;" class="text-end">Tarifa Ref.</th>
                                    <th style="width: 80px;" class="text-end">Bono Ref.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lt in labors" :key="lt.id">
                                    <td class="text-center fw-bold text-primary fs-8">{{ lt.code }}</td>
                                    <td class="fw-semi-bold">{{ lt.name }}</td>
                                    <td class="text-muted">{{ lt.unit_name }}</td>
                                    <td class="text-end">{{ fmt(lt.default_rate) }}</td>
                                    <td class="text-end">{{ fmt(lt.default_bonus) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <p v-if="Object.keys(groupedTypes).length === 0" class="text-muted text-center py-4">
                    No se encontraron labores con los filtros aplicados.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
