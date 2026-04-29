<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateOvertimeHourModal from '@/Components/OvertimeHours/CreateOvertimeHourModal.vue';
import EditOvertimeHourModal from '@/Components/OvertimeHours/EditOvertimeHourModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    overtimeHours: Array,
    contracts:     Array,
    months:        Array,
    overtimeTypes: Array,
    costCenters:   Array,
    groupings:     Array,
    laborTypes:    Array,
    level3s:       Array,
});

const showCreate     = ref(false);
const showEdit       = ref(false);
const selected       = ref(null);
const filterContract = ref('');
const filterMonth    = ref('');

const filteredOvertimeHours = computed(() => {
    if (!props.overtimeHours) return [];
    return props.overtimeHours.filter(r => {
        const matchContract = !filterContract.value || String(r.contract_id) === String(filterContract.value);
        const matchMonth    = !filterMonth.value    || String(r.month_id)    === String(filterMonth.value);
        return matchContract && matchMonth;
    });
});

function openEdit(record) {
    selected.value = record;
    showEdit.value = true;
}

function confirmDelete(record) {
    Swal.fire({
        title: '¿Eliminar hora extra?',
        text: `${record.employee_name} — ${record.hours} hrs (${record.month_name})`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('overtime-hours.delete', record.id), {
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false }),
            });
        }
    });
}

// Calcular costo usando snapshots (valores fijados al momento del registro)
function calcCost(record) {
    const s = record.base_salary_snapshot;
    const f = record.hourly_rate_factor_snapshot;
    const m = record.overtime_multiplier_snapshot;
    if (!s || !f || !m || !record.hours) return null;
    return record.hours * s * f * m;
}

function calcCostTooltip(record) {
    if (!record.base_salary_snapshot) return 'Sin sueldo base registrado al momento del ingreso';
    return `Sueldo base: $${formatCLP(record.base_salary_snapshot)}\nFactor hora: ${record.hourly_rate_factor_snapshot}\nMultiplicador: ${record.overtime_multiplier_snapshot}×\nFórmula: ${record.hours}hrs × $${formatCLP(record.base_salary_snapshot)} × ${record.hourly_rate_factor_snapshot} × ${record.overtime_multiplier_snapshot}`;
}

function formatCLP(value) {
    if (value === null || value === undefined) return '-';
    return Math.round(value).toLocaleString('es-CL');
}

const excelHeaders = [
    { label: 'ID',            key: 'id' },
    { label: 'Colaborador',   key: 'employee_name' },
    { label: 'Mes',           key: 'month_name' },
    { label: 'Tipo HE',       key: 'overtime_type_name' },
    { label: 'Labor',         key: 'labor_type_name' },
    { label: 'Centros de Costo', key: 'cost_center_names' },
    { label: 'Horas',         key: 'hours', type: 'number' },
    { label: 'Costo Estimado', key: '__cost' },
    { label: 'Ingresado por', key: 'created_by' },
];

const excelData = computed(() =>
    filteredOvertimeHours.value.map(r => ({
        ...r,
        __cost: calcCost(r) !== null ? Math.round(calcCost(r)) : 0,
    }))
);
</script>

<template>
    <AppLayout title="Horas Extras">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-clock me-2"></i>Horas Extras
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                filename="horas-extras.xlsx"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <a
                                :href="route('overtime-hours.pdf', { contract_id: filterContract || '', month_id: filterMonth || '' })"
                                target="_blank"
                                class="btn btn-falcon-default btn-sm"
                                v-tooltip="'Exportar PDF con los filtros activos'"
                            >
                                <span class="fas fa-file-pdf" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">PDF</span>
                            </a>
                            <button class="btn btn-falcon-default btn-sm" @click="showCreate = true">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nueva</span>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Filtros -->
                <div class="row g-2 mt-2">
                    <div class="col-md-5">
                        <select v-model="filterContract" class="form-select form-select-sm">
                            <option value="">Todos los colaboradores</option>
                            <option v-for="c in contracts" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filterMonth" class="form-select form-select-sm">
                            <option value="">Todos los meses</option>
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-falcon-default btn-sm w-100" @click="filterContract = ''; filterMonth = ''">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover fs-10 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Colaborador</th>
                                <th>Mes</th>
                                <th>Tipo HE</th>
                                <th>Labor</th>
                                <th>Centros de Costo</th>
                                <th class="text-end">Horas</th>
                                <th class="text-end">Costo Estimado</th>
                                <th>Ingresado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredOvertimeHours.length === 0">
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-clock me-2"></i>No hay horas extras registradas.
                                </td>
                            </tr>
                            <tr v-for="item in filteredOvertimeHours" :key="item.id">
                                <td class="text-muted">{{ item.id }}</td>
                                <td>{{ item.employee_name }}</td>
                                <td>{{ item.month_name }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size:0.7rem;">
                                        {{ item.overtime_type_name }}
                                    </span>
                                </td>
                                <td>{{ item.labor_type_name }}</td>
                                <td>
                                    <template v-if="item.cost_center_names">
                                        <span v-for="(cc, idx) in item.cost_center_names.split(', ').slice(0, 3)" :key="idx"
                                            class="badge bg-light text-dark border me-1" style="font-size:0.7rem;">
                                            {{ cc }}
                                        </span>
                                        <span v-if="item.cost_center_names.split(', ').length > 3"
                                            class="badge bg-secondary text-white"
                                            style="font-size:0.7rem; cursor:default;"
                                            v-tooltip="item.cost_center_names.split(', ').slice(3).join('\n')">
                                            +{{ item.cost_center_names.split(', ').length - 3 }}
                                        </span>
                                    </template>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="text-end fw-semibold">{{ item.hours }}</td>
                                <td class="text-end">
                                    <span v-if="calcCost(item) !== null"
                                        class="fw-semibold text-success"
                                        v-tooltip="calcCostTooltip(item)">
                                        ${{ formatCLP(calcCost(item)) }}
                                    </span>
                                    <span v-else class="text-muted" v-tooltip="calcCostTooltip(item)">-</span>
                                </td>
                                <td class="text-muted small" v-tooltip="item.created_at">{{ item.created_by }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-link p-0 me-2 text-warning" @click="openEdit(item)"
                                        v-tooltip="'Editar'">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-link p-0 text-danger" @click="confirmDelete(item)"
                                        v-tooltip="'Eliminar'">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="filteredOvertimeHours.length > 0" class="table-dark">
                            <tr>
                                <td colspan="6" class="fw-bold">Total</td>
                                <td class="text-end fw-bold">
                                    {{ filteredOvertimeHours.reduce((s, r) => s + (parseFloat(r.hours) || 0), 0).toFixed(2) }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    ${{ formatCLP(filteredOvertimeHours.reduce((s, r) => s + (calcCost(r) || 0), 0)) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Crear -->
        <CreateOvertimeHourModal
            :show="showCreate"
            :contracts="contracts"
            :months="months"
            :overtimeTypes="overtimeTypes"
            :costCenters="costCenters"
            :groupings="groupings"
            :laborTypes="laborTypes"
            :level3s="level3s"
            @close="showCreate = false"
            @saved="showCreate = false"
        />

        <!-- Modal Editar -->
        <EditOvertimeHourModal
            :show="showEdit"
            :overtimeHour="selected"
            :contracts="contracts"
            :months="months"
            :overtimeTypes="overtimeTypes"
            :costCenters="costCenters"
            :groupings="groupings"
            :laborTypes="laborTypes"
            :level3s="level3s"
            @close="showEdit = false"
            @saved="showEdit = false"
        />
    </AppLayout>
</template>
