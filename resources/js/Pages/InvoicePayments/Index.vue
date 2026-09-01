<script setup>
import { ref, computed } from 'vue';
import { useSeasonLock } from '@/Composables/useSeasonLock';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateInvoicePaymentModal from '@/Components/InvoicePayments/CreateInvoicePaymentModal.vue';
import EditInvoicePaymentModal from '@/Components/InvoicePayments/EditInvoicePaymentModal.vue';
import PaymentStatusBadge from '@/Components/InvoicePayments/PaymentStatusBadge.vue';
import InvoiceDebtReportModal from '@/Components/InvoicePayments/InvoiceDebtReportModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const isLocked = useSeasonLock();

const props = defineProps({
    invoices: Object,
    banks: Array,
    suppliers: Array,
    summary: Object,
    filters: Object,
});

const title = 'Facturas';

const term                 = ref(props.filters.term || '');
const filterDateFrom       = ref(props.filters.date_from || '');
const filterDateTo         = ref(props.filters.date_to || '');
const filterDueDateFrom    = ref(props.filters.due_date_from || '');
const filterDueDateTo      = ref(props.filters.due_date_to || '');
const filterSupplierId     = ref(props.filters.supplier_id || null);
const filterPaymentStatus  = ref(props.filters.payment_status || null);
const filterPaymentType    = ref(props.filters.payment_type ?? '1'); // Default: Crédito
const showFilters          = ref(false);

// Alto máximo de la tabla: la mantiene dentro de la pantalla (con scroll interno)
// en vez de que la página entera se desborde hacia abajo. Descuenta más espacio
// cuando el panel de filtros avanzados está abierto, ya que ahora ocupa dos filas.
const tableMaxHeight = computed(() => showFilters.value ? 'calc(100vh - 720px)' : 'calc(100vh - 520px)');

// Filas expandidas (para ver pagos de una factura)
const expandedRows = ref({});
function toggleRow(id) {
    expandedRows.value[id] = !expandedRows.value[id];
}

// Modales
const showCreateModal = ref(false);
const showEditModal   = ref(false);
const editingPayment  = ref(null);
const editingSupplierAccounts = ref([]);
const preselectedInvoice = ref(null);
const showDebtReportModal = ref(false);

function openCreateModal(invoice = null) {
    preselectedInvoice.value = invoice;
    showCreateModal.value = true;
}
function closeCreateModal() {
    showCreateModal.value = false;
    preselectedInvoice.value = null;
}
function openEditModal(payment, invoice = null) {
    editingPayment.value = payment;
    editingSupplierAccounts.value = invoice?.bank_accounts || [];
    showEditModal.value = true;
}
function closeEditModal() {
    showEditModal.value = false;
    editingPayment.value = null;
    editingSupplierAccounts.value = [];
}

function search() {
    router.get(route('invoice-payments.index'), {
        term:           term.value,
        date_from:      filterDateFrom.value,
        date_to:        filterDateTo.value,
        due_date_from:  filterDueDateFrom.value,
        due_date_to:    filterDueDateTo.value,
        supplier_id:    filterSupplierId.value,
        payment_status: filterPaymentStatus.value,
        payment_type:   filterPaymentType.value,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    term.value                = '';
    filterDateFrom.value      = '';
    filterDateTo.value        = '';
    filterDueDateFrom.value   = '';
    filterDueDateTo.value     = '';
    filterSupplierId.value    = null;
    filterPaymentStatus.value = null;
    filterPaymentType.value   = '1'; // Volver a Crédito por defecto
    search();
}

function deletePayment(paymentId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará el registro de pago',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('invoice-payments.delete', paymentId), {
                onSuccess: () => Swal.fire('Eliminado', 'El pago ha sido eliminado.', 'success'),
            });
        }
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('es-CL');
}

function getDueDateStatus(invoice) {
    if (!invoice.due_date) return null;
    if (invoice.is_annulled) return null;
    if (invoice.payment_status === 'paid') return 'paid';
    const today = new Date(); today.setHours(0,0,0,0);
    const due   = new Date(invoice.due_date); due.setHours(0,0,0,0);
    const diff  = Math.ceil((due - today) / 86400000);
    if (diff < 0) return 'overdue';
    if (diff <= 7) return 'soon';
    return 'ok';
}

function getDueDateDays(invoice) {
    if (!invoice.due_date || invoice.payment_status === 'paid') return '';
    const today = new Date(); today.setHours(0,0,0,0);
    const due   = new Date(invoice.due_date); due.setHours(0,0,0,0);
    const diff  = Math.ceil((due - today) / 86400000);
    if (diff < 0) return `${Math.abs(diff)}d atraso`;
    if (diff === 0) return 'Hoy';
    return `${diff}d`;
}

const dueDateConfig = {
    overdue: { label: 'Vencida',    class: 'bg-danger text-white' },
    soon:    { label: 'Por vencer', class: 'bg-warning text-dark' },
    ok:      { label: 'Vigente',    class: 'bg-success text-white' },
    paid:    { label: 'Saldada',    class: 'bg-secondary text-white' },
};

const statusLabels = {
    pending:  'Pendiente',
    partial:  'Parcial',
    paid:     'Pagada',
    annulled: 'Anulada',
};

// Texto para el tooltip con el detalle de notas de crédito/débito de una factura
function notesTooltip(invoice) {
    if (!invoice.notes || invoice.notes.length === 0) return '';
    return invoice.notes.map((n) => {
        const tipo = n.type === 'credito' ? 'NC' : 'ND';
        const anul = n.is_annulment ? ' (ANULACIÓN)' : '';
        return `${tipo} N° ${n.number}${anul}: $${formatCurrency(n.total)}`;
    }).join('\n');
}

const excelHeaders = [
    { label: 'Fecha Factura',  key: 'fecha' },
    { label: 'N° Documento',   key: 'number_document' },
    { label: 'Proveedor',      key: 'supplier' },
    { label: 'RUT Proveedor',  key: 'supplier_rut' },
    { label: 'Razón Social',   key: 'company_reason' },
    { label: 'Tipo Doc.',      key: 'type_document' },
    { label: 'Total Factura',  key: 'total_invoice',  type: 'number' },
    { label: 'Saldo',          key: 'balance',        type: 'number' },
    { label: 'Estado Pago',    key: 'payment_status' },
    { label: 'Vencimiento',    key: 'due_date' },
    { label: 'Fecha Pago',     key: 'payment_date' },
    { label: 'Monto Pagado',   key: 'payment_amount', type: 'number' },
    { label: 'Banco',          key: 'bank' },
    { label: 'Método Pago',    key: 'payment_method' },
    { label: 'N° Transacción', key: 'transaction_number' },
    { label: 'Cuenta Prov. - Banco',  key: 'sba_bank' },
    { label: 'Cuenta Prov. - Tipo',   key: 'sba_type' },
    { label: 'Cuenta Prov. - N°',     key: 'sba_number' },
];

const excelData = computed(() => {
    const rows = [];
    for (const invoice of (props.invoices?.data ?? [])) {
        const base = {
            fecha:            formatDate(invoice.date),
            number_document:  invoice.number_document,
            supplier:         invoice.supplier?.name ?? '-',
            supplier_rut:     invoice.supplier?.rut ?? '-',
            company_reason:   invoice.company_reason ?? '-',
            type_document:    invoice.type_document ?? '-',
            total_invoice:    invoice.total_invoice,
            balance:          invoice.balance,
            payment_status:   statusLabels[invoice.payment_status] ?? invoice.payment_status,
            due_date:         formatDate(invoice.due_date),
        };
        if (invoice.payments && invoice.payments.length > 0) {
            for (const p of invoice.payments) {
                rows.push({
                    ...base,
                    payment_date:       formatDate(p.payment_date),
                    payment_amount:     p.amount,
                    bank:               p.bank ?? '-',
                    payment_method:     p.payment_method_name ?? '-',
                    transaction_number: p.transaction_number ?? '-',
                    sba_bank:           p.supplier_bank_account_bank ?? '-',
                    sba_type:           p.supplier_bank_account_type ?? '-',
                    sba_number:         p.supplier_bank_account_number ?? '-',
                });
            }
        } else {
            rows.push({
                ...base,
                payment_date:       '',
                payment_amount:     '',
                bank:               '',
                payment_method:     '',
                transaction_number: '',
                sba_bank:           '',
                sba_type:           '',
                sba_number:         '',
            });
        }
    }
    return rows;
});
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <Link :href="route('invoices.index')" class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </Link>
                            <button type="button" class="btn btn-falcon-default btn-sm" @click="showDebtReportModal = true">
                                <span class="fas fa-table" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Informe de Deuda</span>
                            </button>
                            <ExportExcelButton :data="excelData" :headers="excelHeaders" filename="facturas.xlsx" class="btn btn-falcon-default btn-sm excel-toolbar-btn">
                                <span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Excel</span>
                            </ExportExcelButton>
                            <button @click="openCreateModal()" class="btn btn-falcon-default btn-sm" :disabled="isLocked">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Registrar Pago</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Búsqueda y Filtros -->
                <div class="row mb-3">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <input v-model="term" @keyup.enter="search" type="text" class="form-control"
                                placeholder="Buscar por número de documento o proveedor...">
                            <button @click="search" class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filterPaymentType" @change="search" class="form-select form-select-sm">
                            <option value="">Todos (Crédito + Contado)</option>
                            <option value="1">Crédito</option>
                            <option value="2">Contado</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-end">
                        <button @click="showFilters = !showFilters" class="btn btn-falcon-default btn-sm">
                            <i class="fas fa-filter me-1"></i>
                            {{ showFilters ? 'Ocultar' : 'Mostrar' }} Filtros
                        </button>
                    </div>
                </div>

                <!-- Cards KPI por estado de pago -->
                <div class="row g-2 mb-3" v-if="summary">
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #6c757d !important;"
                            :class="filterPaymentStatus === null ? 'bg-soft-secondary' : ''"
                            @click="filterPaymentStatus = null; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-semibold">Total</small>
                                    <span class="badge bg-secondary rounded-pill">{{ summary.total.count }}</span>
                                </div>
                                <div class="fw-bold fs-9">$ {{ formatCurrency(summary.total.amount) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">monto total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #dc3545 !important;"
                            :class="filterPaymentStatus === 'pending' ? 'bg-soft-danger' : ''"
                            @click="filterPaymentStatus = 'pending'; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-danger fw-semibold">Pendientes</small>
                                    <span class="badge bg-danger rounded-pill">{{ summary.pending.count }}</span>
                                </div>
                                <div class="fw-bold fs-9 text-danger">$ {{ formatCurrency(summary.pending.amount) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">por pagar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #fd7e14 !important;"
                            :class="filterPaymentStatus === 'partial' ? 'bg-soft-warning' : ''"
                            @click="filterPaymentStatus = 'partial'; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold" style="color:#fd7e14;">Parciales</small>
                                    <span class="badge rounded-pill" style="background:#fd7e14;">{{ summary.partial.count }}</span>
                                </div>
                                <div class="fw-bold fs-9" style="color:#fd7e14;">$ {{ formatCurrency(summary.partial.balance) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">saldo restante</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #198754 !important;"
                            :class="filterPaymentStatus === 'paid' ? 'bg-soft-success' : ''"
                            @click="filterPaymentStatus = 'paid'; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-success fw-semibold">Pagadas</small>
                                    <span class="badge bg-success rounded-pill">{{ summary.paid.count }}</span>
                                </div>
                                <div class="fw-bold fs-9 text-success">$ {{ formatCurrency(summary.paid.amount) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">pagado</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #212529 !important;"
                            :class="filterPaymentStatus === 'annulled' ? 'bg-soft-secondary' : ''"
                            @click="filterPaymentStatus = 'annulled'; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold text-dark">Anuladas</small>
                                    <span class="badge bg-dark rounded-pill">{{ summary.annulled?.count ?? 0 }}</span>
                                </div>
                                <div class="fw-bold fs-9 text-dark">$ {{ formatCurrency(summary.annulled?.amount ?? 0) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">no se pagan</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div
                            class="card border-0 shadow-sm h-100"
                            style="cursor:pointer; border-left: 3px solid #842029 !important;"
                            :class="filterPaymentStatus === 'overdue' ? 'bg-soft-danger' : ''"
                            @click="filterPaymentStatus = 'overdue'; search()"
                        >
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-semibold" style="color:#842029;">Vencido</small>
                                    <span class="badge rounded-pill" style="background:#842029;">{{ summary.overdue?.count ?? 0 }}</span>
                                </div>
                                <div class="fw-bold fs-9" style="color:#842029;">$ {{ formatCurrency(summary.overdue?.amount ?? 0) }}</div>
                                <small class="text-muted" style="font-size:0.7rem;">atrasado por pagar</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros rápidos de estado -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button
                        v-for="(label, key) in { '': 'Todos', pending: 'Pendientes', partial: 'Parciales', paid: 'Pagadas', annulled: 'Anuladas', overdue: 'Vencidas' }"
                        :key="key"
                        @click="filterPaymentStatus = key || null; search()"
                        class="btn btn-sm"
                        :class="filterPaymentStatus === (key || null) ? 'btn-primary' : 'btn-falcon-default'"
                    >
                        {{ label }}
                    </button>
                </div>

                <!-- Panel de Filtros Avanzados -->
                <div v-if="showFilters" class="card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Factura Desde</label>
                                <input v-model="filterDateFrom" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Factura Hasta</label>
                                <input v-model="filterDateTo" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Vencimiento Desde</label>
                                <input v-model="filterDueDateFrom" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Vencimiento Hasta</label>
                                <input v-model="filterDueDateTo" type="date" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-md-4">
                                <label class="form-label small">Proveedor</label>
                                <select v-model="filterSupplierId" class="form-select form-select-sm">
                                    <option :value="null">Todos</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-8 d-flex align-items-end justify-content-end gap-2">
                                <button @click="search" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search me-1"></i> Aplicar
                                </button>
                                <button @click="clearFilters" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-body bg-body-tertiary">
                <div class="table-responsive" :style="{ maxHeight: tableMaxHeight, minHeight: '200px', overflowY: 'auto' }">
                    <table id="invoice-payments-table" class="table table-sm table-hover fs-10">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Fecha</th>
                                <th>N° Documento</th>
                                <th style="max-width:200px;">Proveedor</th>
                                <th>Razón Social</th>
                                <th>Tipo Doc.</th>
                                <th class="text-end">Total Factura</th>
                                <th class="text-end">Total Pagado</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-center">Estado Pago</th>
                                <th>Vencimiento</th>
                                <th class="text-center">Estado Vcto.</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="invoice in invoices.data" :key="invoice.id">
                                <!-- Fila principal de factura -->
                                <tr
                                    :class="{ 'table-active': expandedRows[invoice.id] }"
                                    :style="invoice.payments?.length > 0 ? 'cursor:pointer;' : ''"
                                    @click="invoice.payments?.length > 0 && toggleRow(invoice.id)"
                                >
                                    <td class="text-center">
                                        <i v-if="invoice.payments?.length > 0"
                                            class="fas text-muted"
                                            :class="expandedRows[invoice.id] ? 'fa-chevron-up' : 'fa-chevron-down'"
                                        ></i>
                                    </td>
                                    <td class="text-nowrap">{{ formatDate(invoice.date) }}</td>
                                    <td class="fw-semibold">
                                        <div class="d-flex align-items-center flex-wrap gap-1">
                                            <span>{{ invoice.number_document }}</span>
                                            <span
                                                v-if="invoice.is_annulled"
                                                class="badge bg-dark text-white"
                                                v-tooltip="notesTooltip(invoice)"
                                                style="cursor:help;"
                                            >
                                                <i class="fas fa-ban fa-xs me-1"></i>ANULADA
                                            </span>
                                            <template v-else-if="invoice.paid_via_expense_report">
                                                <Link
                                                    v-if="invoice.expense_report"
                                                    :href="route('expense-reports.show', invoice.expense_report.id)"
                                                    class="badge bg-info text-white text-decoration-none"
                                                    v-tooltip="'Pagada mediante la rendición ' + invoice.expense_report.number + '. No se registra pago en este módulo.'"
                                                    @click.stop
                                                >
                                                    <i class="fas fa-receipt fa-xs me-1"></i>RENDICIÓN {{ invoice.expense_report.number }}
                                                </Link>
                                            </template>
                                            <template v-else-if="invoice.has_notes">
                                                <span
                                                    v-if="invoice.credit_total > 0"
                                                    class="badge bg-danger text-white"
                                                    v-tooltip="notesTooltip(invoice)"
                                                    style="cursor:help;"
                                                >
                                                    <i class="fas fa-file-invoice-dollar fa-xs me-1"></i>NC
                                                </span>
                                                <span
                                                    v-if="invoice.debit_total > 0"
                                                    class="badge bg-primary text-white"
                                                    v-tooltip="notesTooltip(invoice)"
                                                    style="cursor:help;"
                                                >
                                                    <i class="fas fa-file-invoice-dollar fa-xs me-1"></i>ND
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" :title="invoice.supplier?.name">{{ invoice.supplier?.name ?? '-' }}</td>
                                    <td>{{ invoice.company_reason ?? '-' }}</td>
                                    <td>{{ invoice.type_document ?? '-' }}</td>
                                    <td class="text-end text-nowrap">
                                        $ {{ formatCurrency(invoice.total_invoice) }}
                                        <span v-if="invoice.iva > 0"
                                            v-tooltip="'Neto: $' + formatCurrency(invoice.total_neto) + ' + IVA: $' + formatCurrency(invoice.iva)"
                                            class="text-muted ms-1" style="font-size:0.7rem; cursor:help;">
                                            <i class="fas fa-info-circle fa-xs"></i>
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">$ {{ formatCurrency(invoice.total_paid) }}</td>
                                    <td class="text-end text-nowrap" :class="{ 'text-danger fw-bold': invoice.balance > 0 }">
                                        $ {{ formatCurrency(invoice.balance) }}
                                    </td>
                                    <td class="text-center">
                                        <PaymentStatusBadge :status="invoice.payment_status" />
                                    </td>
                                    <td class="text-nowrap">{{ formatDate(invoice.due_date) }}</td>
                                    <td class="text-center text-nowrap">
                                        <template v-if="getDueDateStatus(invoice)">
                                            <span class="badge" :class="dueDateConfig[getDueDateStatus(invoice)].class">
                                                {{ dueDateConfig[getDueDateStatus(invoice)].label }}
                                            </span>
                                            <small v-if="getDueDateDays(invoice)" class="text-muted ms-1" style="font-size:0.7rem;">
                                                {{ getDueDateDays(invoice) }}
                                            </small>
                                        </template>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            v-if="invoice.payment_status !== 'paid' && !invoice.is_annulled"
                                            @click.stop="openCreateModal(invoice)"
                                            class="btn btn-falcon-default btn-sm py-0 px-2"
                                            v-tooltip="'Registrar pago'"
                                        >
                                            <i class="fas fa-dollar-sign fa-sm"></i>
                                        </button>
                                        <span
                                            v-else-if="invoice.is_annulled"
                                            class="text-muted"
                                            v-tooltip="'Factura anulada por nota de crédito. No se puede pagar.'"
                                            style="cursor:help;"
                                        >
                                            <i class="fas fa-ban"></i>
                                        </span>
                                        <span
                                            v-else-if="invoice.paid_via_expense_report"
                                            class="text-info"
                                            v-tooltip="'Pagada mediante rendición. No se registra pago en este módulo.'"
                                            style="cursor:help;"
                                        >
                                            <i class="fas fa-receipt"></i>
                                        </span>
                                    </td>
                                </tr>

                                <!-- Fila expandida: detalle de pagos -->
                                <tr v-if="expandedRows[invoice.id]" class="bg-100 no-export">
                                    <td colspan="12" class="p-0">
                                        <div class="px-4 py-2">
                                            <p class="text-muted small mb-2 fw-semibold">
                                                <i class="fas fa-list me-1"></i> Pagos registrados
                                            </p>
                                            <table class="table table-sm table-bordered mb-0 fs-10">
                                                <thead class="bg-200">
                                                    <tr>
                                                        <th>Fecha Pago</th>
                                                        <th class="text-end">Monto</th>
                                                        <th>Método</th>
                                                        <th>Banco</th>
                                                        <th>Cuenta Proveedor</th>
                                                        <th>Nro. Transacción</th>
                                                        <th>Usuario</th>
                                                        <th class="text-center">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="payment in invoice.payments" :key="payment.id">
                                                        <td>{{ formatDate(payment.payment_date) }}</td>
                                                        <td class="text-end">$ {{ formatCurrency(payment.amount) }}</td>
                                                        <td>
                                                            <span class="badge"
                                                                :class="{
                                                                    'bg-info text-white':    payment.payment_method == 1,
                                                                    'bg-success text-white': payment.payment_method == 2,
                                                                    'bg-warning text-dark':  payment.payment_method == 3
                                                                }">
                                                                {{ payment.payment_method_name }}
                                                            </span>
                                                        </td>
                                                        <td>{{ payment.bank ?? '-' }}</td>
                                                        <td>{{ payment.supplier_bank_account ?? '-' }}</td>
                                                        <td>{{ payment.transaction_number || '-' }}</td>
                                                        <td>{{ payment.user ?? '-' }}</td>
                                                        <td class="text-center">
                                                            <button @click="openEditModal(payment, invoice)" class="btn btn-falcon-default btn-sm me-1" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button @click="deletePayment(payment.id)" class="btn btn-falcon-default btn-sm" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="invoices.data.length === 0">
                                <td colspan="12" class="text-center text-muted py-4">
                                    No hay facturas registradas con los filtros seleccionados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="invoices.links && invoices.links.length > 3" class="d-flex justify-content-center mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li v-for="(link, index) in invoices.links" :key="index"
                                class="page-item"
                                :class="{ 'active': link.active, 'disabled': !link.url }">
                                <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label" preserve-state />
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateInvoicePaymentModal
            :show="showCreateModal"
            :banks="banks"
            :preselected-invoice="preselectedInvoice"
            @close="closeCreateModal"
        />
        <EditInvoicePaymentModal
            :show="showEditModal"
            :payment="editingPayment"
            :banks="banks"
            :supplier-accounts="editingSupplierAccounts"
            @close="closeEditModal"
        />
        <InvoiceDebtReportModal
            :show="showDebtReportModal"
            :filters="{ term, date_from: filterDateFrom, date_to: filterDateTo, supplier_id: filterSupplierId, payment_type: filterPaymentType }"
            @close="showDebtReportModal = false"
        />
    </AppLayout>
</template>

<style scoped>
:deep(.excel-toolbar-btn) {
    margin-bottom: 0 !important;
}
</style>
