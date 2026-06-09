<script setup>
import { ref, computed } from 'vue';
import { useSeasonLock } from '@/Composables/useSeasonLock';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreatePurchaseOrderModal from '@/Components/PurchaseOrders/CreatePurchaseOrderModal.vue';
import EditPurchaseOrderModal from '@/Components/PurchaseOrders/EditPurchaseOrderModal.vue';

const isLocked = useSeasonLock();

const props = defineProps({
    purchaseOrders: Object,
    suppliers: Array,
    companyReasons: Array,
    costCenters: Array,
    groupings: Array,
    products: Array,
    units: Array,
    approvers: Array,
    filters: Object,
});

const title = 'Órdenes de Compra';

const term = ref(props.filters.term || '');
const filterStatus = ref(props.filters.status || '');
const filterSupplierId = ref(props.filters.supplier_id || '');
const filterDateFrom = ref(props.filters.date_from || '');
const filterDateTo = ref(props.filters.date_to || '');
const showFilters = ref(false);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingOrder = ref(null);

const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'draft', label: 'Borrador' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'approved', label: 'Aprobada' },
    { value: 'rejected', label: 'Rechazada' },
    { value: 'sent', label: 'Enviada' },
    { value: 'received_partial', label: 'Recibida Parcial' },
    { value: 'completed', label: 'Completada' },
    { value: 'cancelled', label: 'Cancelada' },
];

function openCreateModal() {
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(order) {
    editingOrder.value = order;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingOrder.value = null;
}

function search() {
    router.get(route('purchase-orders.index'), {
        term: term.value,
        status: filterStatus.value,
        supplier_id: filterSupplierId.value,
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    term.value = '';
    filterStatus.value = '';
    filterSupplierId.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    search();
}

function deleteOrder(orderId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción eliminará la orden de compra",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('purchase-orders.delete', orderId), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminada',
                        text: 'La orden de compra ha sido eliminada.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    Swal.fire('Error', Object.values(errors)[0], 'error');
                }
            });
        }
    });
}

function viewOrder(orderId) {
    router.visit(route('purchase-orders.show', orderId));
}

function updateStatus(orderId, newStatus) {
    const statusLabels = {
        'pending': 'Enviar a aprobación',
        'approved': 'Aprobar',
        'rejected': 'Rechazar',
        'sent': 'Marcar como enviada',
        'completed': 'Marcar como completada',
        'cancelled': 'Cancelar'
    };

    Swal.fire({
        title: '¿Está seguro?',
        text: `¿Desea ${statusLabels[newStatus] || 'cambiar el estado'}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('purchase-orders.update-status', orderId), {
                status: newStatus
            }, {
                onSuccess: () => {
                    Swal.fire('Actualizado!', 'El estado ha sido actualizado.', 'success');
                },
                onError: (errors) => {
                    Swal.fire('Error', Object.values(errors)[0], 'error');
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

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function getStatusBadgeClass(status) {
    const classes = {
        'draft': 'bg-secondary text-white',
        'pending': 'bg-warning text-dark',
        'approved': 'bg-info text-white',
        'rejected': 'bg-danger text-white',
        'sent': 'bg-primary text-white',
        'received_partial': 'bg-warning text-dark',
        'completed': 'bg-success text-white',
        'cancelled': 'bg-dark text-white',
    };
    return classes[status] || 'bg-secondary text-white';
}

function getProductsList(order) {
    if (!order.items || order.items.length === 0) return 'Sin productos';
    return order.items.map(item => item.product?.name || '-').join(', ');
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-file-invoice me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <button 
                            @click="openCreateModal" 
                            class="btn btn-falcon-default btn-sm"
                            :disabled="isLocked"
                        >
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nueva Orden</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary" style="overflow: visible;">
                <!-- Búsqueda y Filtros -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <input 
                                v-model="term" 
                                @keyup.enter="search"
                                type="text" 
                                class="form-control form-control-sm" 
                                placeholder="Buscar por N° orden, proveedor..."
                            >
                            <button @click="search" class="btn btn-falcon-default btn-sm" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button 
                            @click="showFilters = !showFilters" 
                            class="btn btn-falcon-default btn-sm"
                        >
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                        <button 
                            v-if="filterStatus || filterSupplierId || filterDateFrom || filterDateTo"
                            @click="clearFilters" 
                            class="btn btn-falcon-default btn-sm ms-1"
                        >
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>

                <!-- Panel de Filtros -->
                <div v-if="showFilters" class="card mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Estado</label>
                                <select v-model="filterStatus" @change="search" class="form-select form-select-sm">
                                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Proveedor</label>
                                <select v-model="filterSupplierId" @change="search" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option v-for="sup in suppliers" :key="sup.value" :value="sup.value">
                                        {{ sup.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Desde</label>
                                <input v-model="filterDateFrom" @change="search" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Hasta</label>
                                <input v-model="filterDateTo" @change="search" type="date" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Órdenes -->
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-sm table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>N° Orden</th>
                                <th>Razón Social</th>
                                <th style="width: 90px;">Fecha</th>
                                <th>Proveedor</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchaseOrders.data.length === 0">
                                <td colspan="8" class="text-center text-muted">No hay órdenes de compra registradas</td>
                            </tr>
                            <tr v-for="order in purchaseOrders.data" :key="order.id">
                                <td>
                                    <a href="#" @click.prevent="viewOrder(order.id)" class="text-primary fw-semibold">
                                        {{ order.order_number }}
                                    </a>
                                </td>
                                <td>
                                    <span v-if="order.company_reason" class="text-truncate d-inline-block" style="max-width: 150px;" :title="order.company_reason.name">
                                        {{ order.company_reason.name }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td style="white-space: nowrap;">{{ formatDate(order.order_date) }}</td>
                                <td>{{ order.supplier?.name || '-' }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" :title="getProductsList(order)">
                                        <span v-if="order.items && order.items.length > 0">
                                            <span v-if="order.items.length === 1">
                                                {{ order.items[0].product?.name || '-' }}
                                            </span>
                                            <span v-else>
                                                {{ order.items[0].product?.name || '-' }}
                                                <span class="badge bg-secondary ms-1">+{{ order.items.length - 1 }}</span>
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">Sin productos</span>
                                    </div>
                                </td>
                                <td class="text-end">${{ formatCurrency(order.total) }}</td>
                                <td>
                                    <span :class="['badge', getStatusBadgeClass(order.status)]">
                                        {{ order.status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button 
                                            @click="viewOrder(order.id)"
                                            class="btn btn-falcon-default btn-sm"
                                            title="Ver Detalle"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button 
                                            v-if="order.status === 'draft' || order.status === 'pending' || order.status === 'rejected'"
                                            @click="openEditModal(order)"
                                            class="btn btn-falcon-default btn-sm"
                                            title="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button 
                                                class="btn btn-falcon-default btn-sm" 
                                                type="button" 
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <template v-if="order.status === 'draft'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'pending')">
                                                            <i class="fas fa-paper-plane me-1"></i> Enviar a Aprobación
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'pending'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'approved')">
                                                            <i class="fas fa-check me-1"></i> Aprobar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="updateStatus(order.id, 'rejected')">
                                                            <i class="fas fa-times me-1"></i> Rechazar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'approved'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'sent')">
                                                            <i class="fas fa-truck me-1"></i> Marcar como Enviada
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'sent'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'completed')">
                                                            <i class="fas fa-check-double me-1"></i> Marcar como Completada
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'received_partial'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'completed')">
                                                            <i class="fas fa-check-double me-1"></i> Marcar como Completada
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'rejected'">
                                                    <li>
                                                        <a class="dropdown-item" href="#" @click.prevent="updateStatus(order.id, 'pending')">
                                                            <i class="fas fa-redo me-1"></i> Reenviar a Aprobación
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'cancelled'">
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else-if="order.status === 'completed'">
                                                    <li>
                                                        <span class="dropdown-item-text text-muted">
                                                            <i class="fas fa-check-circle me-1"></i> Orden completada
                                                        </span>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" @click.prevent="deleteOrder(order.id)">
                                                            <i class="fas fa-trash me-1"></i> Eliminar
                                                        </a>
                                                    </li>
                                                </template>
                                                <template v-else>
                                                    <li>
                                                        <span class="dropdown-item-text text-muted">No hay acciones disponibles</span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="purchaseOrders.links.length > 3" class="d-flex justify-content-center mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li v-for="(link, index) in purchaseOrders.links" :key="index" 
                                :class="['page-item', { active: link.active, disabled: !link.url }]">
                                <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label"></Link>
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreatePurchaseOrderModal 
            :show="showCreateModal"
            :suppliers="suppliers"
            :companyReasons="companyReasons"
            :costCenters="costCenters"
            :groupings="groupings"
            :products="products"
            :units="units"
            :approvers="approvers"
            @close="closeCreateModal"
        />

        <EditPurchaseOrderModal 
            :show="showEditModal"
            :order="editingOrder"
            :suppliers="suppliers"
            :companyReasons="companyReasons"
            :costCenters="costCenters"
            :groupings="groupings"
            :products="products"
            :units="units"
            :approvers="approvers"
            @close="closeEditModal"
        />
    </AppLayout>
</template>

<style scoped>
.badge {
    font-size: 0.75rem;
}
</style>
