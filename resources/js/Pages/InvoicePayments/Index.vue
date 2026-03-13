<script setup>
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateInvoicePaymentModal from '@/Components/InvoicePayments/CreateInvoicePaymentModal.vue';
import EditInvoicePaymentModal from '@/Components/InvoicePayments/EditInvoicePaymentModal.vue';
import PaymentStatusBadge from '@/Components/InvoicePayments/PaymentStatusBadge.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    payments: Object,
    banks: Array,
    suppliers: Array,
    filters: Object,
});

const title = 'Pagos de Facturas';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref(props.filters.term || '');
const filterDateFrom = ref(props.filters.date_from || '');
const filterDateTo = ref(props.filters.date_to || '');
const filterSupplierId = ref(props.filters.supplier_id || null);
const filterPaymentMethod = ref(props.filters.payment_method || null);
const filterBankId = ref(props.filters.bank_id || null);
const showFilters = ref(false);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingPayment = ref(null);

function openCreateModal() {
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(payment) {
    console.log('Opening edit modal for payment:', payment);
    editingPayment.value = payment;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingPayment.value = null;
}

function search() {
    router.get(route('invoice-payments.index'), {
        term: term.value,
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
        supplier_id: filterSupplierId.value,
        payment_method: filterPaymentMethod.value,
        bank_id: filterBankId.value
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    term.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterSupplierId.value = null;
    filterPaymentMethod.value = null;
    filterBankId.value = null;
    search();
}

function deletePayment(paymentId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción eliminará el registro de pago",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('invoice-payments.delete', paymentId), {
                onSuccess: () => {
                    Swal.fire('Eliminado!', 'El pago ha sido eliminado.', 'success');
                }
            });
        }
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-CL');
}

function getDueDateStatus(payment) {
    if (!payment.invoice?.due_date) return null;
    if (payment.invoice?.payment_status === 'paid') return 'paid';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(payment.invoice.due_date);
    due.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
    if (diffDays < 0) return 'overdue';
    if (diffDays <= 7) return 'soon';
    return 'ok';
}

function getDueDateDays(payment) {
    if (!payment.invoice?.due_date) return '';
    if (payment.invoice?.payment_status === 'paid') return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(payment.invoice.due_date);
    due.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
    if (diffDays < 0) return `${Math.abs(diffDays)}d atraso`;
    if (diffDays === 0) return 'Hoy';
    return `${diffDays}d`;
}

const dueDateConfig = {
    overdue: { label: 'Vencida', class: 'bg-danger text-white' },
    soon: { label: 'Por vencer', class: 'bg-warning text-dark' },
    ok: { label: 'Vigente', class: 'bg-success text-white' },
    paid: { label: 'Saldada', class: 'bg-secondary text-white' },
};
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-money-bill-wave me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <Link 
                                :href="route('invoice-payments.dashboard')"
                                class="btn btn-falcon-default btn-sm"
                            >
                                <span class="fas fa-chart-line" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Dashboard</span>
                            </Link>
                            <ExportExcelButton 
                                :route="route('invoice-payments.excel')"
                                class="btn btn-falcon-default btn-sm"
                            />
                            <button 
                                @click="openCreateModal" 
                                class="btn btn-falcon-default btn-sm"
                            >
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
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <input 
                                v-model="term" 
                                @keyup.enter="search"
                                type="text" 
                                class="form-control" 
                                placeholder="Buscar por número de documento, proveedor o número de transacción..."
                            >
                            <button @click="search" class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button 
                            @click="showFilters = !showFilters" 
                            class="btn btn-falcon-default btn-sm"
                        >
                            <i class="fas fa-filter me-1"></i>
                            {{ showFilters ? 'Ocultar' : 'Mostrar' }} Filtros
                        </button>
                    </div>
                </div>

                <!-- Panel de Filtros Avanzados -->
                <div v-if="showFilters" class="card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Desde</label>
                                <input 
                                    v-model="filterDateFrom" 
                                    type="date" 
                                    class="form-control form-control-sm"
                                >
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Hasta</label>
                                <input 
                                    v-model="filterDateTo" 
                                    type="date" 
                                    class="form-control form-control-sm"
                                >
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Proveedor</label>
                                <select
                                    v-model="filterSupplierId"
                                    class="form-select form-select-sm"
                                >
                                    <option :value="null">Todos</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Método de Pago</label>
                                <select
                                    v-model="filterPaymentMethod"
                                    class="form-select form-select-sm"
                                >
                                    <option :value="null">Todos</option>
                                    <option value="1">Transferencia</option>
                                    <option value="2">Efectivo</option>
                                    <option value="3">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Banco</label>
                                <select
                                    v-model="filterBankId"
                                    class="form-select form-select-sm"
                                >
                                    <option :value="null">Todos</option>
                                    <option v-for="bank in banks" :key="bank.id" :value="bank.id">
                                        {{ bank.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-9 d-flex align-items-end justify-content-end gap-2">
                                <button @click="search" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search me-1"></i> Aplicar Filtros
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
                <!-- Tabla de pagos -->
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover fs-10">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th>Fecha Pago</th>
                                <th>Factura</th>
                                <th>Proveedor</th>
                                <th>Tipo Doc.</th>
                                <th class="text-end">Total Factura</th>
                                <th class="text-end">Monto Pagado</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-center">Estado Pago</th>
                                <th>Vencimiento</th>
                                <th class="text-center">Estado Vcto.</th>
                                <th>Método</th>
                                <th>Banco</th>
                                <th>Nro. Transacción</th>
                                <th>Usuario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in payments.data" :key="payment.id">
                                <td>{{ formatDate(payment.payment_date) }}</td>
                                <td>{{ payment.invoice.number_document }}</td>
                                <td>{{ payment.invoice.supplier?.name ?? '-' }}</td>
                                <td>{{ payment.invoice.type_document?.name ?? '-' }}</td>
                                <td class="text-end">$ {{ formatCurrency(payment.invoice?.total_invoice) }}</td>
                                <td class="text-end">$ {{ formatCurrency(payment.amount) }}</td>
                                <td class="text-end">$ {{ formatCurrency(payment.invoice?.balance) }}</td>
                                <td class="text-center">
                                    <PaymentStatusBadge :status="payment.invoice?.payment_status ?? 'pending'" />
                                </td>
                                <td class="text-nowrap">
                                    {{ formatDate(payment.invoice?.due_date) }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <template v-if="getDueDateStatus(payment)">
                                        <span class="badge" :class="dueDateConfig[getDueDateStatus(payment)].class">
                                            {{ dueDateConfig[getDueDateStatus(payment)].label }}
                                        </span>
                                        <small v-if="getDueDateDays(payment)" class="d-block text-muted mt-1" style="font-size: 0.7rem;">
                                            {{ getDueDateDays(payment) }}
                                        </small>
                                    </template>
                                    <span v-else>-</span>
                                </td>
                                <td>
                                    <span 
                                        class="badge" 
                                        :class="{
                                            'bg-info text-white': payment.payment_method == 1,
                                            'bg-success text-white': payment.payment_method == 2,
                                            'bg-warning text-dark': payment.payment_method == 3
                                        }"
                                    >
                                        {{ payment.payment_method_name }}
                                    </span>
                                </td>
                                <td>{{ payment.bank ? payment.bank.name : '-' }}</td>
                                <td>{{ payment.transaction_number || '-' }}</td>
                                <td>{{ payment.user?.name ?? '-' }}</td>
                                <td class="text-center">
                                    <button 
                                        @click="openEditModal(payment)" 
                                        class="btn btn-falcon-default btn-sm me-1"
                                        title="Editar"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button 
                                        @click="deletePayment(payment.id)" 
                                        class="btn btn-falcon-default btn-sm"
                                        title="Eliminar"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="15" class="text-center text-muted py-4">
                                    No hay pagos registrados
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="payments.links && payments.links.length > 3" class="d-flex justify-content-center mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li 
                                v-for="(link, index) in payments.links" 
                                :key="index"
                                class="page-item"
                                :class="{ 'active': link.active, 'disabled': !link.url }"
                            >
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    class="page-link"
                                    v-html="link.label"
                                    preserve-state
                                />
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
            @close="closeCreateModal"
        />

        <EditInvoicePaymentModal
            :show="showEditModal"
            :payment="editingPayment"
            :banks="banks"
            @close="closeEditModal"
        />
    </AppLayout>
</template>
