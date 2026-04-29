<template>
    <div>
        <!-- Acciones -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="text-muted" style="font-size: 0.8rem;">
                <i class="fas fa-info-circle me-1"></i>
                Nómina de pago para <strong>{{ monthLabel }}</strong> &mdash; {{ employees.length }} colaborador(es)
            </div>
            <div class="d-flex gap-2">
                <ExportExcelButton
                    :data="excelData"
                    :headers="excelHeaders"
                    :filename="`nomina-pago-${month}`"
                    class="btn btn-falcon-default btn-sm"
                />
                <a
                    :href="route('payroll-reports.nomina-pdf', { month })"
                    target="_blank"
                    class="btn btn-falcon-default btn-sm"
                >
                    <i class="fas fa-file-pdf me-1"></i>Ver PDF
                </a>
            </div>
        </div>

        <!-- Tabla vacía -->
        <div v-if="employees.length === 0" class="text-center text-muted py-5">
            <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
            <p>No hay datos de nómina para <strong>{{ monthLabel }}</strong>.</p>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                <thead class="nomina-head">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">ID Contrato</th>
                        <th>RUT</th>
                        <th>Nombre Completo</th>
                        <th>Banco</th>
                        <th>Tipo Cuenta</th>
                        <th>N° Cuenta</th>
                        <th>Método Pago</th>
                        <th class="text-end fw-bold">Total a Pagar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(emp, idx) in employees" :key="emp.id">
                        <td class="text-center text-muted">{{ idx + 1 }}</td>
                        <td class="text-center">
                            <span v-if="emp.contract_id" class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">
                                #{{ emp.contract_id }}
                            </span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-nowrap">{{ emp.rut }}</td>
                        <td class="text-nowrap fw-semibold">{{ emp.full_name }}</td>
                        <td class="text-nowrap">{{ emp.bank_name }}</td>
                        <td class="text-nowrap">{{ emp.account_type_name }}</td>
                        <td class="text-nowrap font-monospace">{{ emp.account_number }}</td>
                        <td class="text-nowrap">{{ emp.payment_method_name }}</td>
                        <td class="text-end fw-bold text-primary">$ {{ fmt(emp.total_neto) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="nomina-foot">
                        <td colspan="8" class="fw-bold">TOTAL A PAGAR</td>
                        <td class="text-end fw-bold">$ {{ fmt(totalNeto) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    month: { type: String, required: true },
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const monthLabel = computed(() => {
    const [y, m] = props.month.split('-');
    return monthNames[parseInt(m) - 1] + ' ' + y;
});

const totalNeto = computed(() => props.employees.reduce((s, e) => s + (e.total_neto || 0), 0));

const fmt = (val) => {
    const n = Number(val) || 0;
    return n.toLocaleString('es-ES');
};

const excelHeaders = [
    { label: '#', key: '__n' },
    { label: 'ID Contrato', key: 'contract_id' },
    { label: 'RUT', key: 'rut' },
    { label: 'Nombre Completo', key: 'full_name' },
    { label: 'Banco', key: 'bank_name' },
    { label: 'Tipo Cuenta', key: 'account_type_name' },
    { label: 'N° Cuenta', key: 'account_number' },
    { label: 'Método Pago', key: 'payment_method_name' },
    { label: 'Total a Pagar', key: 'total_neto', type: 'number' },
];

const excelData = computed(() =>
    props.employees.map((emp, idx) => ({
        ...emp,
        __n: idx + 1,
    }))
);
</script>

<style scoped>
.nomina-head th {
    background-color: #1a3c5e !important;
    color: #fff !important;
    font-weight: normal !important;
}
.nomina-foot td {
    background-color: transparent !important;
    color: #333 !important;
    border-top: 2px solid #ccc;
    font-weight: bold;
}
</style>
