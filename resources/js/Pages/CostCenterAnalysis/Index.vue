<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Multiselect from '@vueform/multiselect';
import CompareCostCentersModal from '@/Components/CostCenterAnalysis/CompareCostCentersModal.vue';

const props = defineProps({
    costCenters: Array,
    fruits: Array,
    varieties: Array,
});

const title = 'Análisis Comparativo de Cuarteles';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const MAX_SELECTION = 4;

const search = ref('');
const fruitFilter = ref('');
const varietyFilter = ref('');
const selectedIds = ref([]);

const filteredVarieties = computed(() => {
    if (!fruitFilter.value) return props.varieties ?? [];
    return (props.varieties ?? []).filter(v => String(v.fruit_id) === String(fruitFilter.value));
});

const filteredCostCenters = computed(() => {
    let rows = props.costCenters ?? [];

    if (fruitFilter.value) {
        rows = rows.filter(cc => String(cc.fruit_id) === String(fruitFilter.value));
    }
    if (varietyFilter.value) {
        rows = rows.filter(cc => String(cc.variety_id) === String(varietyFilter.value));
    }
    if (search.value.trim()) {
        const term = search.value.trim().toLowerCase();
        rows = rows.filter(cc => cc.name.toLowerCase().includes(term));
    }

    return rows;
});

function isSelected(id) {
    return selectedIds.value.includes(id);
}

function toggleSelection(id) {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter(i => i !== id);
        return;
    }
    if (selectedIds.value.length >= MAX_SELECTION) {
        Swal.fire({
            icon: 'info',
            title: `Máximo ${MAX_SELECTION} cuarteles`,
            text: `Puedes comparar hasta ${MAX_SELECTION} cuarteles a la vez.`,
            timer: 1800,
            showConfirmButton: false,
        });
        return;
    }
    selectedIds.value = [...selectedIds.value, id];
}

function formatNumber(val) {
    if (val === null || val === undefined) return '-';
    return Number(val).toLocaleString('es-CL', { maximumFractionDigits: 0 });
}

const comparing = ref(false);
const showModal = ref(false);
const compareData = ref([]);

async function compare() {
    if (selectedIds.value.length < 2) {
        Swal.fire({ icon: 'info', title: 'Selecciona al menos 2 cuarteles', timer: 1500, showConfirmButton: false });
        return;
    }
    comparing.value = true;
    try {
        const res = await axios.get(route('cost-center-analysis.compare'), {
            params: { ids: selectedIds.value },
        });
        compareData.value = res.data.cost_centers;
        showModal.value = true;
    } catch (e) {
        Swal.fire('Error', e.response?.data?.message || 'No se pudo generar la comparación', 'error');
    } finally {
        comparing.value = false;
    }
}

function closeModal() {
    showModal.value = false;
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-balance-scale-right me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary" v-show="selectedIds.length">
                                {{ selectedIds.length }} seleccionado(s)
                            </span>
                            <button
                                class="btn btn-falcon-default btn-sm"
                                :disabled="selectedIds.length < 2 || comparing"
                                @click="compare"
                            >
                                <span class="fas fa-spinner fa-spin me-1" v-if="comparing"></span>
                                <span class="fas fa-columns me-1" v-else></span>
                                Comparar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="alert alert-info d-flex align-items-center py-2 mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Selecciona entre 2 y {{ MAX_SELECTION }} cuarteles para comparar costos de agroquímicos/fertilizantes, kilos por hectárea y calibre, y entender por qué su desempeño difiere.</small>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <Multiselect
                            v-model="fruitFilter"
                            :options="fruits"
                            placeholder="Filtrar por frutal"
                            class="multiselect-blue form-control"
                            :searchable="true"
                            :close-on-select="true"
                        />
                    </div>
                    <div class="col-md-3">
                        <Multiselect
                            v-model="varietyFilter"
                            :options="filteredVarieties"
                            placeholder="Filtrar por variedad"
                            class="multiselect-blue form-control"
                            :searchable="true"
                            :close-on-select="true"
                        />
                    </div>
                    <div class="col-md-4">
                        <input v-model="search" type="text" class="form-control form-control-sm" placeholder="Buscar cuartel por nombre..." />
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Cuartel</th>
                                <th>Frutal</th>
                                <th>Variedad</th>
                                <th>Sucursal</th>
                                <th class="text-end">Superficie (ha)</th>
                                <th class="text-end">Costo Total</th>
                                <th class="text-end">Costo/Ha</th>
                                <th class="text-end">Kg Total</th>
                                <th class="text-end">Kg/Ha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cc in filteredCostCenters"
                                :key="cc.id"
                                :class="{ 'table-primary': isSelected(cc.id) }"
                                style="cursor: pointer;"
                                @click="toggleSelection(cc.id)"
                            >
                                <td @click.stop>
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :checked="isSelected(cc.id)"
                                        @change="toggleSelection(cc.id)"
                                    />
                                </td>
                                <td class="fw-medium">{{ cc.name }}</td>
                                <td>{{ cc.fruit_name }}</td>
                                <td>{{ cc.variety_name }}</td>
                                <td>{{ cc.branch_name || '-' }}</td>
                                <td class="text-end">{{ formatNumber(cc.surface) }}</td>
                                <td class="text-end">${{ formatNumber(cc.costo_total) }}</td>
                                <td class="text-end">${{ formatNumber(cc.costo_ha) }}</td>
                                <td class="text-end">{{ formatNumber(cc.kg_total) }}</td>
                                <td class="text-end">{{ formatNumber(cc.kg_ha) }}</td>
                            </tr>
                        </tbody>
                        <tbody v-if="filteredCostCenters.length === 0">
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Sin cuarteles para mostrar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <CompareCostCentersModal
            :show="showModal"
            :cost-centers="compareData"
            @close="closeModal"
        />
    </AppLayout>
</template>
