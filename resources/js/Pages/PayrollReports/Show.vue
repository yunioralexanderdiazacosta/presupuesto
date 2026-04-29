<template>
    <AppLayout :title="`Remuneraciones — ${employee.full_name} — ${monthLabel}`">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center pe-0">
                        <Link
                            :href="route('payroll-reports.index', { month })"
                            class="btn btn-falcon-default btn-sm me-2"
                        >
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </Link>
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-user-tie me-2"></i>
                            {{ employee.full_name }} &mdash; {{ monthLabel }}
                        </h5>
                    </div>
                    <div class="col-auto ms-auto text-end ps-0">
                        <a
                            :href="route('payroll-reports.pdf', { employee: employee.id, month })"
                            target="_blank"
                            class="btn btn-falcon-default btn-sm"
                        >
                            <i class="fas fa-file-pdf me-1"></i>Ver PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- Info del empleado -->
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <div class="row g-2" style="font-size: 0.82rem;">
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">RUT</span>
                                <strong>{{ employee.rut }}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Cargo</span>
                                <strong>{{ employee.position || '—' }}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Razón Social</span>
                                <strong>{{ employee.company_reason || '—' }}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Tipo Contrato</span>
                                <strong>{{ employee.contract_type || '—' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Tratos</div>
                            <div class="fw-bold">$ {{ fmt(totals.tratos) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Monto Día</div>
                            <div class="fw-bold">$ {{ fmt(totals.monto_dia) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Bonos Diarios</div>
                            <div class="fw-bold">$ {{ fmt(totals.bonus_diario) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Bonos Objetivo</div>
                            <div class="fw-bold">$ {{ fmt(totals.bonus_objetivo) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Bonos Mensuales</div>
                            <div class="fw-bold">$ {{ fmt(totals.bonus_mensual) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">HH.EE.</div>
                            <div class="fw-bold">$ {{ fmt(totals.horas_extra) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2" style="background: #fff3cd;">
                            <div class="text-muted mb-1" style="font-size: 0.7rem;">Descuentos</div>
                            <div class="fw-bold">- $ {{ fmt(totals.descuentos) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl">
                        <div class="card text-center py-2" style="background: #1a3c5e; color: white;">
                            <div class="mb-1" style="font-size: 0.7rem; opacity: 0.8;">Total Neto</div>
                            <div class="fw-bold">$ {{ fmt(totals.neto) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tarjas Diarias -->
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-day text-primary"></i>
                        <strong style="font-size: 0.85rem;">Tarjas Diarias</strong>
                        <span class="badge bg-secondary ms-1">{{ daysWorked }} días trabajados</span>
                        <span class="badge bg-secondary">{{ totals.workdays }} jornadas</span>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="daysWithData.length === 0" class="text-muted text-center py-4">
                            Sin tarjas registradas para este mes.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                                <thead class="payroll-head">
                                    <tr>
                                        <th style="width: 80px;">Fecha</th>
                                        <th>Tipo Labor</th>
                                        <th>Tarifa / Tipo</th>
                                        <th class="text-end">Tarifa $</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Jornada</th>
                                        <th class="text-end">Total Trato</th>
                                        <th class="text-center">Nombre Bono</th>
                                        <th class="text-end">Monto Bono</th>
                                        <th class="text-end">Precio Objetivo</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="date in dates" :key="date">
                                        <template v-if="days[date] && days[date].lines.length > 0">
                                            <tr v-for="(line, li) in days[date].lines" :key="`${date}-${li}`">
                                                <td class="text-nowrap">
                                                    <span v-if="li === 0" class="fw-semibold text-primary">
                                                        {{ formatDate(date) }}
                                                    </span>
                                                </td>
                                                <td>{{ line.labor_type || '—' }}</td>
                                                <td>
                                                    {{ line.labor_rate || '—' }}
                                                    <span v-if="line.payment_type === 'dia'" class="text-muted" style="font-size: 0.7rem;"> (día)</span>
                                                </td>
                                                <td class="text-end">{{ line.rate ? fmt(line.rate) : '—' }}</td>
                                                <td class="text-end">
                                                    {{ line.payment_type === 'trato' ? line.quantity : '—' }}
                                                </td>
                                                <td class="text-end">
                                                    {{ line.payment_type === 'dia' ? line.workdays : '—' }}
                                                </td>
                                                <td class="text-end fw-semibold">$ {{ fmt(line.amount) }}</td>
                                                <td class="text-center">{{ line.bonus_type || '' }}</td>
                                                <td class="text-end">
                                                    <span v-if="line.bonus_amount > 0">$ {{ fmt(line.bonus_amount) }}</span>
                                                    <span v-else class="text-muted">—</span>
                                                </td>
                                                <td class="text-end">
                                                    <span v-if="line.target_price_bonus > 0">$ {{ fmt(line.target_price_bonus) }}</span>
                                                    <span v-else class="text-muted">—</span>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    $ {{ fmt(line.amount + line.bonus_amount + line.target_price_bonus) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold payroll-foot">
                                        <td colspan="6">TOTALES</td>
                                        <td class="text-end">$ {{ fmt(totals.tratos + totals.monto_dia) }}</td>
                                        <td class="text-center"></td>
                                        <td class="text-end">$ {{ fmt(totals.bonus_diario) }}</td>
                                        <td class="text-end">$ {{ fmt(totals.bonus_objetivo) }}</td>
                                        <td class="text-end">$ {{ fmt(totals.tratos + totals.monto_dia + totals.bonus_diario + totals.bonus_objetivo) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fila: Descuentos + Bonos Mensuales + Horas Extra en 1 fila -->
                <div class="row g-3 mb-3">
                    <!-- Descuentos Mensuales -->
                    <div class="col-12 col-lg-4" v-if="monthlyDiscounts.length > 0">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex align-items-center gap-2">
                                <i class="fas fa-minus-circle text-danger"></i>
                                <strong style="font-size: 0.85rem;">Descuentos Mensuales</strong>
                                <span class="badge bg-danger ms-1">- $ {{ fmt(totals.descuentos) }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                                        <thead class="head-danger">
                                            <tr>
                                                <th>Tipo de Descuento</th>
                                                <th class="text-end">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="d in monthlyDiscounts" :key="d.id">
                                                <td class="fw-semibold">{{ d.type }}</td>
                                                <td class="text-end fw-semibold text-danger">- $ {{ fmt(d.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold foot-danger">
                                                <td>TOTAL DESCUENTOS</td>
                                                <td class="text-end">- $ {{ fmt(totals.descuentos) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bonos Mensuales -->
                    <div class="col-12 col-lg-4" v-if="monthlyBonuses.length > 0">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex align-items-center gap-2">
                                <i class="fas fa-gift text-success"></i>
                                <strong style="font-size: 0.85rem;">Bonos Mensuales</strong>
                                <span class="badge bg-success ms-1">$ {{ fmt(totals.bonus_mensual) }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                                        <thead class="head-success">
                                            <tr>
                                                <th>Tipo de Bono</th>
                                                <th>Labor</th>
                                                <th class="text-end">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="b in monthlyBonuses" :key="b.id">
                                                <td class="fw-semibold">{{ b.type }}</td>
                                                <td>{{ b.labor_type || '—' }}</td>
                                                <td class="text-end fw-semibold">$ {{ fmt(b.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold foot-success">
                                                <td colspan="2">TOTAL BONOS MENSUALES</td>
                                                <td class="text-end">$ {{ fmt(totals.bonus_mensual) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horas Extra -->
                    <div class="col-12 col-lg-4" v-if="overtimeHours.length > 0">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex align-items-center gap-2">
                                <i class="fas fa-clock text-warning"></i>
                                <strong style="font-size: 0.85rem;">Horas Extra</strong>
                                <span class="badge bg-warning text-dark ms-1">$ {{ fmt(totals.horas_extra) }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                                        <thead class="head-warning">
                                            <tr>
                                                <th>Tipo</th>
                                                <th class="text-center">Horas</th>
                                                <th class="text-end">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="o in overtimeHours" :key="o.id">
                                                <td class="fw-semibold">{{ o.type }}</td>
                                                <td class="text-center">{{ o.hours }}</td>
                                                <td class="text-end fw-semibold">$ {{ fmt(o.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold foot-warning">
                                                <td colspan="2">TOTAL HORAS EXTRA</td>
                                                <td class="text-end">$ {{ fmt(totals.horas_extra) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Neto final -->
                <div class="row g-3">
                    <div class="col-12 col-lg-6 ms-auto">
                        <div class="card border-2" style="border-color: #1a3c5e !important;">
                            <div class="card-body py-3 px-3 d-flex justify-content-between align-items-center">
                                <span class="fw-semibold" style="font-size: 1rem; color: #1a3c5e;">
                                    Total Neto a Pagar &mdash; {{ monthLabel }}
                                </span>
                                <span class="fw-bold" style="font-size: 1.3rem; color: #1a3c5e;">
                                    $ {{ fmt(totals.neto) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    month: { type: String, required: true },
    dates: { type: Array, default: () => [] },
    days: { type: Object, default: () => ({}) },
    monthlyBonuses: { type: Array, default: () => [] },
    monthlyDiscounts: { type: Array, default: () => [] },
    overtimeHours: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const monthLabel = computed(() => {
    const [y, m] = props.month.split('-');
    return monthNames[parseInt(m) - 1] + ' ' + y;
});

const daysWorked = computed(() => props.totals.days_worked ?? 0);

const daysWithData = computed(() =>
    props.dates.filter(d => props.days[d] && props.days[d].lines.length > 0)
);

const fmt = (val) => {
    const n = Number(val) || 0;
    return n.toLocaleString('es-ES');
};

const formatDate = (date) => {
    const d = new Date(date + 'T00:00:00');
    return d.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit' });
};
</script>

<style scoped>
/* Tarjas - azul marino */
.payroll-head th {
    background-color: #1a3c5e !important;
    color: #fff !important;
}
.payroll-foot td {
    background-color: #1a3c5e !important;
    color: #fff !important;
    font-weight: bold;
}
/* Descuentos */
.head-danger th {
    background-color: transparent !important;
    color: #333 !important;
    border-bottom: 2px solid #dee2e6;
}
.foot-danger td {
    background-color: transparent !important;
    color: #333 !important;
    border-top: 2px solid #dee2e6;
}
/* Bonos Mensuales */
.head-success th {
    background-color: transparent !important;
    color: #333 !important;
    border-bottom: 2px solid #dee2e6;
}
.foot-success td {
    background-color: transparent !important;
    color: #333 !important;
    border-top: 2px solid #dee2e6;
}
/* Horas Extra */
.head-warning th {
    background-color: transparent !important;
    color: #333 !important;
    border-bottom: 2px solid #dee2e6;
}
.foot-warning td {
    background-color: transparent !important;
    color: #333 !important;
    border-top: 2px solid #dee2e6;
}
</style>
