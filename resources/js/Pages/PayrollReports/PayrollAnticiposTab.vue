<template>
    <div>
        <!-- Acciones -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="text-muted" style="font-size: 0.8rem;">
                <i class="fas fa-info-circle me-1"></i>
                Anticipos (Aguinaldo) para <strong>{{ monthLabel }}</strong> &mdash; {{ anticipos.length }} registro(s)
            </div>
            <div class="d-flex gap-2">
                <ExportExcelButton
                    :data="excelData"
                    :headers="excelHeaders"
                    :filename="`anticipos-${month}`"
                    class="btn btn-falcon-default btn-sm"
                />
                <a
                    :href="route('payroll-reports.anticipos-pdf', { month })"
                    target="_blank"
                    class="btn btn-falcon-default btn-sm"
                >
                    <i class="fas fa-file-pdf me-1"></i>Ver PDF
                </a>
            </div>
        </div>

        <!-- Sin datos -->
        <div v-if="anticipos.length === 0" class="text-center text-muted py-5">
            <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
            <p>No hay registros de anticipos (Aguinaldo) para <strong>{{ monthLabel }}</strong>.</p>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                <thead class="anticipos-head">
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">ID Contrato</th>
                        <th>RUT</th>
                        <th>Nombre Completo</th>
                        <th>Banco</th>
                        <th>Tipo Cuenta</th>
                        <th>N° Cuenta</th>
                        <th>Método Pago</th>
                        <th>Observaciones</th>
                        <th class="text-end fw-bold">Monto Anticipo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, idx) in anticipos" :key="idx">
                        <td class="text-center text-muted">{{ idx + 1 }}</td>
                        <td class="text-center">
                            <span class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">#{{ row.contract_id }}</span>
                        </td>
                        <td class="text-nowrap">{{ row.rut }}</td>
                        <td class="text-nowrap fw-semibold">{{ row.full_name }}</td>
                        <td class="text-nowrap">{{ row.bank_name }}</td>
                        <td class="text-nowrap">{{ row.account_type_name }}</td>
                        <td class="text-nowrap font-monospace">{{ row.account_number }}</td>
                        <td class="text-nowrap">{{ row.payment_method_name }}</td>
                        <td class="text-muted" style="font-size:0.75rem;">{{ row.observations || '—' }}</td>
                        <td class="text-end fw-bold text-primary">$ {{ fmt(row.amount) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="anticipos-foot">
                        <td colspan="9" class="fw-bold">TOTAL ANTICIPOS</td>
                        <td class="text-end fw-bold">$ {{ fmt(totalAnticipo) }}</td>
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
    anticipos: { type: Array, default: () => [] },
    month: { type: String, required: true },
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const monthLabel = computed(() => {
    const [y, m] = props.month.split('-');
    return monthNames[parseInt(m) - 1] + ' ' + y;
});

const totalAnticipo = computed(() => props.anticipos.reduce((s, r) => s + (r.amount || 0), 0));

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
    { label: 'Observaciones', key: 'observations' },
    { label: 'Monto Anticipo', key: 'amount', type: 'number' },
];

const excelData = computed(() =>
    props.anticipos.map((row, idx) => ({ ...row, __n: idx + 1 }))
);
</script>

<style scoped>
.anticipos-head th {
    background-color: #1a3c5e !important;
    color: #fff !important;
    font-weight: normal !important;
}
.anticipos-foot td {
    background-color: transparent !important;
    color: #333 !important;
    border-top: 2px solid #ccc;
    font-weight: bold;
}
</style>
