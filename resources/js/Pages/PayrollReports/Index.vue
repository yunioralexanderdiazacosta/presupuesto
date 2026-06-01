<template>
    <AppLayout title="Reporte Mensual de Remuneraciones">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            Reporte Mensual de Remuneraciones
                        </h5>
                    </div>
                    <div class="col-auto ms-auto ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="month"
                                v-model="selectedMonth"
                                class="form-control form-control-sm"
                                style="width: 160px;"
                                @change="filterByMonth"
                            />
                            <select
                                v-model="selectedCompanyReason"
                                class="form-select form-select-sm"
                                style="min-width: 180px; max-width: 260px;"
                            >
                                <option value="">Todas las razones sociales</option>
                                <option v-for="cr in companyReasons" :key="cr.value" :value="String(cr.value)">
                                    {{ cr.label }}
                                </option>
                            </select>
                            <select
                                v-model="selectedEmployee"
                                class="form-select form-select-sm"
                                style="min-width: 220px; max-width: 320px;"
                            >
                                <option value="">Todos los colaboradores</option>
                                <option v-for="emp in employees" :key="emp.id" :value="String(emp.id)">
                                    {{ emp.full_name }} &mdash; {{ emp.rut }}
                                </option>
                            </select>
                            <!-- Botón imprimir seleccionados -->
                            <button
                                v-if="selectedIds.length > 0"
                                class="btn btn-falcon-default btn-sm"
                                @click="printSelected"
                                title="Imprimir PDF de los seleccionados"
                            >
                                <i class="fas fa-print me-1"></i>
                                Imprimir ({{ selectedIds.length }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Tabs -->
                <ul class="nav nav-pills mb-3" style="font-size: 0.82rem;">
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3" :class="{ active: activeTab === 'resumen' }"
                            href="#" @click.prevent="activeTab = 'resumen'">
                            <i class="fas fa-table me-1"></i>Resumen Mensual
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3" :class="{ active: activeTab === 'nomina' }"
                            href="#" @click.prevent="activeTab = 'nomina'">
                            <i class="fas fa-file-invoice-dollar me-1"></i>Nómina de Pago
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3" :class="{ active: activeTab === 'anticipos' }"
                            href="#" @click.prevent="activeTab = 'anticipos'">
                            <i class="fas fa-hand-holding-usd me-1"></i>Anticipos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3" :class="{ active: activeTab === 'sueldos' }"
                            href="#" @click.prevent="activeTab = 'sueldos'">
                            <i class="fas fa-money-check-alt me-1"></i>Resumen de Sueldos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3" :class="{ active: activeTab === 'liquidacion' }"
                            href="#" @click.prevent="activeTab = 'liquidacion'">
                            <i class="fas fa-file-contract me-1"></i>Resumen Liquidación
                        </a>
                    </li>
                </ul>

                <!-- TAB: Resumen Mensual -->
                <div v-show="activeTab === 'resumen'">
                <div class="row g-2 mb-3" v-if="filteredEmployees.length > 0">
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">Tratos</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.tratos) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">Monto Día</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.monto_dia) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">Bonos Diarios</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.bonus_diario) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">Bonos Objetivo</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.bonus_objetivo) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">Bonos Mensuales</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.bonus_mensual) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1">
                            <div class="text-muted" style="font-size: 0.7rem;">HH.EE.</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">$ {{ fmt(filteredTotals.horas_extra) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1" style="background: #fff3cd;">
                            <div class="text-muted" style="font-size: 0.7rem;">Descuentos</div>
                            <div class="fw-bold text-dark" style="font-size: 0.75rem;">- $ {{ fmt(filteredTotals.descuentos) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="card text-center py-2 px-1" style="background: #4a5568; color: white;">
                            <div style="font-size: 0.7rem; opacity: 0.8;">Total Neto</div>
                            <div style="font-size: 0.65rem; font-weight: 600;">$ {{ fmt(filteredTotals.neto) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de empleados -->
                <div v-if="filteredEmployees.length === 0" class="text-center text-muted py-5">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <p>No hay datos de remuneraciones para <strong>{{ monthLabel }}</strong>.</p>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                        <thead class="payroll-head">
                            <tr>
                                <th class="text-center" style="width:36px;">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll" title="Seleccionar todos" />
                                </th>
                                <th class="text-center">Contrato</th>
                                <th>RUT</th>
                                <th>Nombre</th>
                                <th>Cargo</th>
                                <th class="text-end">Tratos</th>
                                <th class="text-end">Monto Día</th>
                                <th class="text-end">Bon. Diarios</th>
                                <th class="text-end">Bon. Objetivo</th>
                                <th class="text-end">Bon. Mens.</th>
                                <th class="text-end">HH.EE.</th>
                                <th class="text-end">Descuentos</th>
                                <th class="text-end fw-bold">Total Neto</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="emp in filteredEmployees" :key="emp.id">
                                <td class="text-center">
                                    <input type="checkbox" :value="emp.id" v-model="selectedIds" />
                                </td>
                                <td class="text-center">
                                    <span v-if="emp.contract_id" class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">#{{ emp.contract_id }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-nowrap">{{ emp.rut }}</td>
                                <td class="text-nowrap fw-semibold">{{ emp.full_name }}</td>
                                <td class="text-nowrap text-muted">{{ emp.position || '—' }}</td>
                                <td class="text-end">{{ emp.total_tratos > 0 ? '$ ' + fmt(emp.total_tratos) : '—' }}</td>
                                <td class="text-end">{{ emp.total_monto_dia > 0 ? '$ ' + fmt(emp.total_monto_dia) : '—' }}</td>
                                <td class="text-end">{{ emp.total_bonus_diario > 0 ? '$ ' + fmt(emp.total_bonus_diario) : '—' }}</td>
                                <td class="text-end">{{ emp.total_bonus_objetivo > 0 ? '$ ' + fmt(emp.total_bonus_objetivo) : '—' }}</td>
                                <td class="text-end">{{ emp.total_bonus_mensual > 0 ? '$ ' + fmt(emp.total_bonus_mensual) : '—' }}</td>
                                <td class="text-end">{{ emp.total_horas_extra > 0 ? '$ ' + fmt(emp.total_horas_extra) : '—' }}</td>
                                <td class="text-end text-warning-emphasis">
                                    {{ emp.total_descuentos > 0 ? '- $ ' + fmt(emp.total_descuentos) : '—' }}
                                </td>
                                <td class="text-end fw-bold text-primary">$ {{ fmt(emp.total_neto) }}</td>
                                <td class="text-center">
                                    <Link
                                        :href="route('payroll-reports.show', { employee: emp.employee_id, month: selectedMonth })"
                                        class="btn btn-falcon-default btn-sm"
                                    >
                                        <i class="fas fa-eye me-1"></i>Ver
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold payroll-foot">
                                <td></td>
                                <td colspan="4">TOTALES</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.tratos) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.monto_dia) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.bonus_diario) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.bonus_objetivo) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.bonus_mensual) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.horas_extra) }}</td>
                                <td class="text-end">- $ {{ fmt(filteredTotals.descuentos) }}</td>
                                <td class="text-end">$ {{ fmt(filteredTotals.neto) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                </div><!-- /TAB resumen -->

                <!-- TAB: Nómina de Pago -->
                <div v-show="activeTab === 'nomina'">
                    <PayrollNominaTab :employees="filteredEmployees" :month="selectedMonth" />
                </div><!-- /TAB nomina -->

                <!-- TAB: Anticipos -->
                <div v-show="activeTab === 'anticipos'">
                    <PayrollAnticiposTab :anticipos="filteredAnticipos" :month="selectedMonth" />
                </div><!-- /TAB anticipos -->

                <!-- TAB: Resumen de Sueldos -->
                <div v-show="activeTab === 'sueldos'">
                    <PayrollSueldosTab :employees="filteredEmployees" :month="selectedMonth" />
                </div><!-- /TAB sueldos -->

                <!-- TAB: Resumen Liquidación -->
                <div v-show="activeTab === 'liquidacion'">
                    <PayrollLiquidacionTab :rows="filteredLiquidacion" :month="selectedMonth" />
                </div><!-- /TAB liquidacion -->

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PayrollNominaTab from './PayrollNominaTab.vue';
import PayrollAnticiposTab from './PayrollAnticiposTab.vue';
import PayrollSueldosTab from './PayrollSueldosTab.vue';
import PayrollLiquidacionTab from './PayrollLiquidacionTab.vue';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    anticipos: { type: Array, default: () => [] },
    liquidacion: { type: Array, default: () => [] },
    companyReasons: { type: Array, default: () => [] },
    month: { type: String, required: true },
    totals: { type: Object, required: true },
});

const selectedMonth = ref(props.month);
const selectedEmployee = ref('');
const selectedCompanyReason = ref('');
const activeTab = ref('resumen');
const selectedIds = ref([]);

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const monthLabel = computed(() => {
    const [y, m] = selectedMonth.value.split('-');
    return monthNames[parseInt(m) - 1] + ' ' + y;
});

// Filter rows based on selection
const filteredEmployees = computed(() => {
    let list = props.employees;
    if (selectedCompanyReason.value) {
        list = list.filter(e => String(e.company_reason_id) === String(selectedCompanyReason.value));
    }
    if (selectedEmployee.value) {
        list = list.filter(e => String(e.id) === String(selectedEmployee.value));
    }
    return list;
});

const filteredAnticipos = computed(() => {
    if (!selectedCompanyReason.value) return props.anticipos;
    return props.anticipos.filter(a => String(a.company_reason_id) === String(selectedCompanyReason.value));
});

const filteredLiquidacion = computed(() => filteredEmployees.value);

// Recalculate totals from filtered list
const filteredTotals = computed(() => {
    const list = filteredEmployees.value;
    return {
        tratos:        list.reduce((s, e) => s + (e.total_tratos || 0), 0),
        monto_dia:     list.reduce((s, e) => s + (e.total_monto_dia || 0), 0),
        bonus_diario:  list.reduce((s, e) => s + (e.total_bonus_diario || 0), 0),
        bonus_objetivo:list.reduce((s, e) => s + (e.total_bonus_objetivo || 0), 0),
        bonus_mensual: list.reduce((s, e) => s + (e.total_bonus_mensual || 0), 0),
        horas_extra:   list.reduce((s, e) => s + (e.total_horas_extra || 0), 0),
        descuentos:    list.reduce((s, e) => s + (e.total_descuentos || 0), 0),
        neto:          list.reduce((s, e) => s + (e.total_neto || 0), 0),
    };
});

const fmt = (val) => {
    const n = Number(val) || 0;
    return n.toLocaleString('es-ES');
};

const filterByMonth = () => {
    selectedEmployee.value = '';
    selectedCompanyReason.value = '';
    selectedIds.value = [];
    router.get(route('payroll-reports.index'), { month: selectedMonth.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const allSelected = computed(() =>
    filteredEmployees.value.length > 0 &&
    filteredEmployees.value.every(e => selectedIds.value.includes(e.id))
);

const toggleAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredEmployees.value.map(e => e.id);
    }
};

const printSelected = () => {
    if (selectedIds.value.length === 0) return;
    // Mapear contract_ids seleccionados a employee_ids (deduplicando)
    const empIds = [...new Set(
        selectedIds.value.map(cId => {
            const row = props.employees.find(e => String(e.id) === String(cId));
            return row ? row.employee_id : null;
        }).filter(Boolean)
    )];
    let url = route('payroll-reports.bulk-pdf') + '?month=' + selectedMonth.value;
    empIds.forEach(id => { url += '&employee_ids[]=' + id; });
    window.open(url, '_blank');
};
</script>

<style scoped>
.payroll-head th {
    background-color: #4a5568 !important;
    color: #fff !important;
    font-weight: normal !important;
}
.payroll-foot td {
    background-color: #4a5568 !important;
    color: #fff !important;
    font-weight: normal !important;
}
</style>
