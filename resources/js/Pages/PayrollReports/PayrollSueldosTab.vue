<template>
    <div>
        <!-- Tabla resumen de sueldos -->
        <div v-if="employees.length === 0" class="text-center text-muted py-5">
            <i class="fas fa-users fa-2x mb-2"></i>
            <p>No hay datos para mostrar.</p>
        </div>
        <div v-else>
            <div class="d-flex justify-content-end mb-2 gap-2">
                <a
                    :href="route('payroll-reports.sueldos-pdf', { month: month })"
                    target="_blank"
                    class="btn btn-falcon-default btn-sm"
                >
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </a>
                <ExportExcelButton
                    :data="excelData"
                    :headers="excelHeaders"
                    :filename="'Resumen_Sueldos_' + month + '.xlsx'"
                    class="btn btn-falcon-default btn-sm"
                />
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                    <thead class="payroll-head" style="background-color: #b8d0e0;">
                        <tr>
                            <th>RUT</th>
                            <th>ID Contrato</th>
                            <th>Nombre</th>
                            <th class="text-end">Total Diario</th>
                            <th class="text-end">Bonos Diarios</th>
                            <th class="text-end">Bonos Mensuales</th>
                            <th class="text-end">Monto HE</th>
                            <th class="text-end fw-bold">Total Haberes</th>
                            <th class="text-end text-warning-emphasis">Total Descuentos</th>
                            <th class="text-end fw-bold">Total a Pago</th>
                            <th class="text-end">Jornadas</th>
                            <th class="text-end">Promedio JH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="emp in employees" :key="emp.id">
                            <td class="text-nowrap">{{ emp.rut }}</td>
                            <td class="text-center">
                                <span v-if="emp.contract_id" class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">#{{ emp.contract_id }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-nowrap fw-semibold">{{ emp.full_name }}</td>
                            <td class="text-end">$ {{ fmt(totalDiario(emp)) }}</td>
                            <td class="text-end">$ {{ fmt(bonosDiarios(emp)) }}</td>
                            <td class="text-end">{{ emp.total_bonus_mensual > 0 ? '$ ' + fmt(emp.total_bonus_mensual) : '—' }}</td>
                            <td class="text-end">{{ emp.total_horas_extra > 0 ? '$ ' + fmt(emp.total_horas_extra) : '—' }}</td>
                            <td class="text-end fw-bold">$ {{ fmt(totalHaberes(emp)) }}</td>
                            <td class="text-end text-warning-emphasis">
                                {{ emp.total_descuentos > 0 ? '- $ ' + fmt(emp.total_descuentos) : '—' }}
                            </td>
                            <td class="text-end fw-bold">$ {{ fmt(emp.total_neto) }}</td>
                            <td class="text-end">{{ emp.total_workdays > 0 ? emp.total_workdays : '—' }}</td>
                            <td class="text-end">
                                {{ emp.total_workdays > 0 ? '$ ' + fmt(Math.round(emp.total_neto / emp.total_workdays)) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold payroll-foot">
                            <td colspan="3">TOTALES</td>
                            <td class="text-end">$ {{ fmt(totals.totalDiario) }}</td>
                            <td class="text-end">$ {{ fmt(totals.bonosDiarios) }}</td>
                            <td class="text-end">$ {{ fmt(totals.bonus_mensual) }}</td>
                            <td class="text-end">$ {{ fmt(totals.horas_extra) }}</td>
                            <td class="text-end">$ {{ fmt(totals.totalHaberes) }}</td>
                            <td class="text-end">- $ {{ fmt(totals.descuentos) }}</td>
                            <td class="text-end">$ {{ fmt(totals.neto) }}</td>
                            <td class="text-end">{{ fmt(totals.workdays) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    month: { type: String, default: '' },
});

const fmt = (val) => {
    if (!val && val !== 0) return '0';
    return Math.round(val).toLocaleString('es-CL');
};

const totalDiario = (emp) => (emp.total_tratos || 0) + (emp.total_monto_dia || 0);
const bonosDiarios = (emp) => (emp.total_bonus_diario || 0) + (emp.total_bonus_objetivo || 0);
const totalHaberes = (emp) => totalDiario(emp) + bonosDiarios(emp) + (emp.total_bonus_mensual || 0) + (emp.total_horas_extra || 0);

const totals = computed(() => {
    const list = props.employees;
    return {
        totalDiario:   list.reduce((s, e) => s + totalDiario(e), 0),
        bonosDiarios:  list.reduce((s, e) => s + bonosDiarios(e), 0),
        bonus_mensual: list.reduce((s, e) => s + (e.total_bonus_mensual || 0), 0),
        horas_extra:   list.reduce((s, e) => s + (e.total_horas_extra || 0), 0),
        totalHaberes:  list.reduce((s, e) => s + totalHaberes(e), 0),
        descuentos:    list.reduce((s, e) => s + (e.total_descuentos || 0), 0),
        neto:          list.reduce((s, e) => s + (e.total_neto || 0), 0),
        workdays:      list.reduce((s, e) => s + (e.total_workdays || 0), 0),
    };
});

const excelHeaders = [
    { label: 'RUT', key: 'rut' },
    { label: 'ID Contrato', key: 'contract_id' },
    { label: 'Nombre', key: 'full_name' },
    { label: 'Total Diario', key: 'total_diario', type: 'number' },
    { label: 'Bonos Diarios', key: 'bonos_diarios', type: 'number' },
    { label: 'Bonos Mensuales', key: 'total_bonus_mensual', type: 'number' },
    { label: 'Monto HE', key: 'total_horas_extra', type: 'number' },
    { label: 'Total Haberes', key: 'total_haberes', type: 'number' },
    { label: 'Total Descuentos', key: 'total_descuentos', type: 'number' },
    { label: 'Total a Pago', key: 'total_neto', type: 'number' },
    { label: 'Jornadas', key: 'total_workdays', type: 'number' },
    { label: 'Promedio JH', key: 'promedio_jh', type: 'number' },
];

const excelData = computed(() => props.employees.map(emp => ({
    rut: emp.rut,
    contract_id: emp.contract_id ?? '',
    full_name: emp.full_name,
    total_diario: totalDiario(emp),
    bonos_diarios: bonosDiarios(emp),
    total_bonus_mensual: emp.total_bonus_mensual || 0,
    total_horas_extra: emp.total_horas_extra || 0,
    total_haberes: totalHaberes(emp),
    total_descuentos: emp.total_descuentos || 0,
    total_neto: emp.total_neto || 0,
    total_workdays: emp.total_workdays || 0,
    promedio_jh: emp.total_workdays > 0 ? Math.round(emp.total_neto / emp.total_workdays) : 0,
})));
</script>
