<template>
    <div>
        <div v-if="rows.length === 0" class="text-center text-muted py-5">
            <i class="fas fa-file-contract fa-2x mb-2"></i>
            <p>No hay datos de contratos para el mes seleccionado.</p>
        </div>
        <div v-else>
            <div class="d-flex justify-content-end mb-2 gap-2">
                <ExportExcelButton
                    :data="excelData"
                    :headers="excelHeaders"
                    :filename="'Resumen_Liquidacion_' + month + '.xlsx'"
                    class="btn btn-falcon-default btn-sm"
                />
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size: 0.76rem;">
                    <thead>
                        <tr>
                            <th class="text-center text-nowrap">ID Contrato</th>
                            <th class="text-nowrap">RUT</th>
                            <th class="text-nowrap">Nombre</th>
                            <th class="text-nowrap">F. Contrato</th>
                            <th class="text-nowrap">Tipo</th>
                            <th class="text-nowrap">AFP</th>
                            <th class="text-nowrap">Salud</th>
                            <th class="text-end text-nowrap">Sueldo Base</th>
                            <th class="text-end text-nowrap">S.B. Prop.</th>
                            <th class="text-end text-nowrap">Total Haberes</th>
                            <th class="text-end text-nowrap">Bono Ajuste</th>
                            <th class="text-end text-nowrap">Jornadas</th>
                            <th class="text-end text-nowrap">JH Vacaciones</th>
                            <th class="text-end text-nowrap">Licencias</th>
                            <th class="text-end text-nowrap">Cargas Fam.</th>
                            <th class="text-end text-nowrap">Anticipos</th>
                            <th class="text-end text-nowrap">Otros Desc.</th>
                            <th class="text-end text-nowrap">Total Líquido</th>
                            <th class="text-nowrap">F. Término</th>
                            <th class="text-nowrap">Causal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.contract_id">
                            <td class="text-center">
                                <span class="badge bg-soft-primary text-primary" style="font-size:0.7rem;">#{{ row.contract_id }}</span>
                            </td>
                            <td class="text-nowrap">{{ row.rut }}</td>
                            <td class="text-nowrap fw-semibold">{{ row.full_name }}</td>
                            <td class="text-nowrap text-muted">{{ row.contract_date ?? '—' }}</td>
                            <td class="text-nowrap">
                                <span :class="contractTypeBadge(row.contract_type)" style="font-size:0.68rem;">
                                    {{ contractTypeLabel(row.contract_type) }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ row.afp }}</td>
                            <td class="text-nowrap">{{ row.health_plan }}</td>
                            <td class="text-end">{{ row.base_salary > 0 ? '$ ' + fmt(row.base_salary) : '—' }}</td>
                            <td class="text-end fw-semibold text-primary">{{ row.sueldo_base_prop > 0 ? '$ ' + fmt(row.sueldo_base_prop) : '—' }}</td>
                            <td class="text-end fw-bold">{{ row.total_haberes > 0 ? '$ ' + fmt(row.total_haberes) : '—' }}</td>
                            <td class="text-end fw-semibold text-success">{{ bonoAjuste(row) > 0 ? '$ ' + fmt(bonoAjuste(row)) : '—' }}</td>
                            <td class="text-end fw-semibold">{{ row.total_jornadas > 0 ? row.total_jornadas : '—' }}</td>
                            <td class="text-end">{{ row.jh_vacaciones > 0 ? row.jh_vacaciones : '—' }}</td>
                            <td class="text-end">{{ row.licencias > 0 ? row.licencias : '—' }}</td>
                            <td class="text-end">{{ row.cargas_familiares > 0 ? '$ ' + fmt(row.cargas_familiares) : '—' }}</td>
                            <td class="text-end text-warning-emphasis">
                                {{ row.anticipos > 0 ? '- $ ' + fmt(row.anticipos) : '—' }}
                            </td>
                            <td class="text-end text-warning-emphasis">
                                {{ row.otros_descuentos > 0 ? '- $ ' + fmt(row.otros_descuentos) : '—' }}
                            </td>
                            <td class="text-end fw-bold text-primary">
                                $ {{ fmt((row.total_haberes || 0) - (row.anticipos || 0) - (row.otros_descuentos || 0)) }}
                            </td>
                            <td class="text-nowrap">
                                <span v-if="row.end_date" class="text-danger">{{ row.end_date }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-nowrap" style="max-width: 160px; white-space: normal;">
                                <span v-if="row.causal_termino" class="text-danger" style="font-size:0.7rem;">{{ row.causal_termino }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7">TOTALES</td>
                            <td class="text-end">$ {{ fmt(totals.base_salary) }}</td>
                            <td class="text-end">$ {{ fmt(totals.sueldo_base_prop) }}</td>
                            <td class="text-end">$ {{ fmt(totals.total_haberes) }}</td>
                            <td class="text-end">$ {{ fmt(totals.bono_ajuste) }}</td>
                            <td class="text-end">{{ fmt(totals.total_jornadas) }}</td>
                            <td class="text-end">{{ fmt(totals.jh_vacaciones) }}</td>
                            <td class="text-end">{{ fmt(totals.licencias) }}</td>
                            <td class="text-end">$ {{ fmt(totals.cargas_familiares) }}</td>
                            <td class="text-end">- $ {{ fmt(totals.anticipos) }}</td>
                            <td class="text-end">- $ {{ fmt(totals.otros_descuentos) }}</td>
                            <td class="text-end">$ {{ fmt(totals.total_liquido) }}</td>
                            <td colspan="2"></td>
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
    rows: { type: Array, default: () => [] },
    month: { type: String, default: '' },
});

const fmt = (val) => {
    const n = Number(val) || 0;
    return Math.round(n).toLocaleString('es-CL');
};

const bonoAjuste = (row) => {
    const diff = (row.total_haberes || 0) - (row.base_salary || 0);
    if (diff <= 0 || !(row.jornadas_efectivas > 0)) return 0;
    return Math.round((diff / 30) * row.jornadas_efectivas);
};

const contractTypeLabel = (type) => {
    if (!type) return '—';
    const t = String(type).toLowerCase();
    if (t.includes('indefinid')) return 'Indefinido';
    if (t.includes('plazo') || t.includes('fijo')) return 'Plazo Fijo';
    if (t.includes('faena')) return 'Faena';
    return type;
};

const contractTypeBadge = (type) => {
    if (!type) return 'badge bg-secondary';
    const t = String(type).toLowerCase();
    if (t.includes('indefinid')) return 'badge bg-soft-success text-success';
    if (t.includes('plazo') || t.includes('fijo')) return 'badge bg-soft-warning text-warning';
    if (t.includes('faena')) return 'badge bg-soft-info text-info';
    return 'badge bg-secondary';
};

const totals = computed(() => ({
    base_salary:       props.rows.reduce((s, r) => s + (r.base_salary || 0), 0),
    sueldo_base_prop:  props.rows.reduce((s, r) => s + (r.sueldo_base_prop || 0), 0),
    total_haberes:     props.rows.reduce((s, r) => s + (r.total_haberes || 0), 0),
    bono_ajuste:       props.rows.reduce((s, r) => s + bonoAjuste(r), 0),
    total_jornadas:    props.rows.reduce((s, r) => s + (r.total_jornadas || 0), 0),
    jh_vacaciones:     props.rows.reduce((s, r) => s + (r.jh_vacaciones || 0), 0),
    licencias:         props.rows.reduce((s, r) => s + (r.licencias || 0), 0),
    cargas_familiares: props.rows.reduce((s, r) => s + (r.cargas_familiares || 0), 0),
    anticipos:         props.rows.reduce((s, r) => s + (r.anticipos || 0), 0),
    otros_descuentos:  props.rows.reduce((s, r) => s + (r.otros_descuentos || 0), 0),
    total_liquido:     props.rows.reduce((s, r) => s + ((r.total_haberes || 0) - (r.anticipos || 0) - (r.otros_descuentos || 0)), 0),
}));

const excelHeaders = [
    { label: 'ID Contrato',    key: 'contract_id' },
    { label: 'RUT',            key: 'rut' },
    { label: 'Nombre',         key: 'full_name' },
    { label: 'F. Contrato',    key: 'contract_date' },
    { label: 'Tipo',           key: 'contract_type' },
    { label: 'AFP',            key: 'afp' },
    { label: 'Salud',          key: 'health_plan' },
    { label: 'Sueldo Base',    key: 'base_salary',        type: 'number' },
    { label: 'S.B. Prop.',     key: 'sueldo_base_prop',   type: 'number' },
    { label: 'Total Haberes',  key: 'total_haberes',       type: 'number' },
    { label: 'Bono Ajuste',    key: 'bono_ajuste',         type: 'number' },
    { label: 'Jornadas',       key: 'total_jornadas',      type: 'number' },
    { label: 'JH Vacaciones',  key: 'jh_vacaciones',      type: 'number' },
    { label: 'Licencias',      key: 'licencias',          type: 'number' },
    { label: 'Cargas Fam.',    key: 'cargas_familiares',  type: 'number' },
    { label: 'Anticipos',      key: 'anticipos',          type: 'number' },
    { label: 'Otros Desc.',    key: 'otros_descuentos',   type: 'number' },
    { label: 'Total Líquido',  key: 'total_liquido',      type: 'number' },
    { label: 'F. Término',     key: 'end_date' },
    { label: 'Causal',         key: 'causal_termino' },
];

const excelData = computed(() => props.rows.map(r => ({
    contract_id:       r.contract_id,
    rut:               r.rut,
    full_name:         r.full_name,
    contract_date:     r.contract_date ?? '',
    contract_type:     r.contract_type ?? '',
    afp:               r.afp,
    health_plan:       r.health_plan,
    base_salary:       r.base_salary || 0,
    sueldo_base_prop:  r.sueldo_base_prop || 0,
    total_haberes:     r.total_haberes || 0,
    bono_ajuste:       bonoAjuste(r),
    total_jornadas:    r.total_jornadas || 0,
    jh_vacaciones:     r.jh_vacaciones || 0,
    licencias:         r.licencias || 0,
    cargas_familiares: r.cargas_familiares || 0,
    anticipos:         r.anticipos || 0,
    otros_descuentos:  r.otros_descuentos || 0,
    total_liquido:     (r.total_haberes || 0) - (r.anticipos || 0) - (r.otros_descuentos || 0),
    end_date:          r.end_date ?? '',
    causal_termino:    r.causal_termino ?? '',
})));
</script>

<style scoped>
thead th {
    background-color: #4a5568 !important;
    color: #fff !important;
    font-weight: normal;
}
tfoot tr td {
    background-color: #4a5568 !important;
    color: #fff !important;
    font-weight: bold;
}
</style>
