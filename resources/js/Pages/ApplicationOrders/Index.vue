<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { useSeasonLock } from '@/Composables/useSeasonLock';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AgrochemicalNavBar from '@/Components/AgrochemicalOutflows/AgrochemicalNavBar.vue';
import CreateApplicationOrderModal from '@/Components/ApplicationOrders/CreateApplicationOrderModal.vue';
import EditApplicationOrderModal from '@/Components/ApplicationOrders/EditApplicationOrderModal.vue';
import ApplicationOrderCalendar from '@/Components/ApplicationOrders/ApplicationOrderCalendar.vue';
const isLocked = useSeasonLock();
const props = defineProps({
    applicationOrders: Array,
    products: Array,
    costCenters: Array,
    branches: Array,
    units: Array,
    groupings: Array,
    fruits: Array,
    phenologicalStages: Array,
    machineries: Array,
    operators: Array,
});

const title = 'Órdenes de Aplicación';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const viewMode = ref(localStorage.getItem('applicationOrdersView') === 'calendar' ? 'calendar' : 'table');
watch(viewMode, (val) => localStorage.setItem('applicationOrdersView', val));

// termInput es lo que se escribe; term es el valor aplicado al presionar Buscar/Enter
const termInput = ref('');
const term = ref('');
const applySearch = () => { term.value = termInput.value.trim(); };
const clearSearch = () => { termInput.value = ''; term.value = ''; };
const statusFilter = ref('');
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'completada', label: 'Completada' },
    { value: 'completada_sin_salida', label: 'Completada sin salida' },
    { value: 'cancelada', label: 'Cancelada' },
];

// Una orden completada sin salidas de agroquímicos fue marcada manualmente
const isManualCompleted = (order) =>
    order.status === 'completada' && Number(order.agrochemical_outflows_count || 0) === 0;

// Con salidas registradas, el estado solo se cambia revirtiendo desde Salidas
const hasOutflows = (order) => Number(order.agrochemical_outflows_count || 0) > 0;

// Ordenamiento por columna (fecha o número de orden)
const sortBy = ref('id');
const sortDir = ref('desc');

function toggleSort(field) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
}

const filteredRows = computed(() => {
    if (!props.applicationOrders) return [];
    let rows = props.applicationOrders;

    if (term.value) {
        const search = term.value.toLowerCase();
        rows = rows.filter(item => {
            const operators = item.operators?.toLowerCase() || '';
            const status = item.status?.toLowerCase() || '';
            const productos = item.order_products?.map(op => op.product?.name?.toLowerCase() || '').join(' ') || '';
            return (
                operators.includes(search) ||
                status.includes(search) ||
                productos.includes(search) ||
                item.id.toString().includes(search)
            );
        });
    }

    if (statusFilter.value === 'completada_sin_salida') {
        rows = rows.filter(item => isManualCompleted(item));
    } else if (statusFilter.value) {
        rows = rows.filter(item => item.status === statusFilter.value);
    }

    rows = [...rows].sort((a, b) => {
        let cmp = 0;
        if (sortBy.value === 'date') {
            cmp = new Date(a.date) - new Date(b.date);
        } else if (sortBy.value === 'id') {
            cmp = a.id - b.id;
        }
        return sortDir.value === 'asc' ? cmp : -cmp;
    });

    return rows;
});

// Función para generar HTML del tooltip con los centros de costo restantes
function getCostCentersTooltip(order) {
    if (!order.order_cost_centers || order.order_cost_centers.length <= 3) return '';
    const remaining = order.order_cost_centers.slice(3);
    return remaining.map(occ => `• ${occ.cost_center?.name || 'N/A'}`).join('<br>');
}

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingOrder = ref(null);

function openCreateModal() {
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(order) {
    console.log('openEditModal llamado con order:', order);
    editingOrder.value = order;
    showEditModal.value = true;
    console.log('showEditModal:', showEditModal.value);
}

function closeEditModal() {
    showEditModal.value = false;
    editingOrder.value = null;
}

function confirmDelete(orderId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('application-orders.delete', orderId), {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'La orden ha sido eliminada.', 'success');
                }
            });
        }
    });
}

function getStatusBadgeClass(status) {
    const classes = {
        'pendiente': 'bg-warning text-dark',
        'en_proceso': 'bg-info text-white',
        'completada': 'bg-success text-white',
        'cancelada': 'bg-danger text-white'
    };
    return classes[status] || 'bg-secondary';
}

function getStatusLabel(status) {
    const labels = {
        'pendiente': 'Pendiente',
        'en_proceso': 'En Proceso',
        'completada': 'Completada',
        'cancelada': 'Cancelada'
    };
    return labels[status] || status;
}

// Menú rápido de cambio de estado (posición fija para no quedar recortado por el scroll de la tabla)
const statusMenu = ref({ open: false, order: null, top: 0, left: 0 });

const statusMenuOptions = computed(() => {
    const order = statusMenu.value.order;
    if (!order) return [];
    return ['pendiente', 'completada', 'cancelada']
        .filter(s => s !== order.status)
        .map(s => ({ value: s, label: s === 'pendiente' && order.status === 'cancelada' ? 'Reactivar (Pendiente)' : getStatusLabel(s) }));
});

function openStatusMenu(event, order) {
    if (isLocked.value || hasOutflows(order)) return;
    const rect = event.currentTarget.getBoundingClientRect();
    statusMenu.value = {
        open: true,
        order,
        top: rect.bottom + 4,
        left: Math.min(rect.left, window.innerWidth - 220),
    };
}

function closeStatusMenu() {
    statusMenu.value.open = false;
}

async function changeStatus(newStatus) {
    const order = statusMenu.value.order;
    closeStatusMenu();
    if (!order) return;

    let confirmOptions = null;
    if (newStatus === 'completada') {
        confirmOptions = {
            title: '¿Completar sin salida?',
            text: 'La orden se marcará como completada sin registrar la salida de agroquímicos. No se descontará stock ni se cargará el costo.',
            confirmButtonText: 'Sí, marcar sin salida',
        };
    } else if (newStatus === 'cancelada') {
        confirmOptions = {
            title: '¿Cancelar la orden?',
            text: `La orden #${order.id} quedará cancelada.`,
            confirmButtonText: 'Sí, cancelar',
        };
    }

    if (confirmOptions) {
        const result = await Swal.fire({
            ...confirmOptions,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: 'Volver',
        });
        if (!result.isConfirmed) return;
    }

    router.patch(route('application-orders.status', order.id), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Estado actualizado', showConfirmButton: false, timer: 1000 });
        },
        onError: (errors) => {
            Swal.fire('Error', errors.error || 'No se pudo cambiar el estado', 'error');
        },
    });
}

// Función para inicializar tooltips
function initTooltips() {
    nextTick(() => {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => {
            // Destruir tooltip existente si existe
            if (el._tooltip) {
                el._tooltip.dispose();
            }
            // Crear nuevo tooltip
            if (window.bootstrap) {
                el._tooltip = new window.bootstrap.Tooltip(el);
            }
        });
    });
}

// Inicializar tooltips al montar
onMounted(() => {
    initTooltips();
});

// Reinicializar tooltips cuando cambien los datos filtrados
watch(filteredRows, () => {
    initTooltips();
});
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        
        <Breadcrumb :title="title" :links="links" />

        <AgrochemicalNavBar />

        <div class="card my-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ title }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center gap-1" role="group">
                            <button
                                type="button"
                                class="btn btn-falcon-default btn-sm"
                                :class="{ active: viewMode === 'table' }"
                                @click="viewMode = 'table'"
                            >
                                <span class="fas fa-list"></span>
                                <span class="d-none d-sm-inline-block ms-1">Tabla</span>
                            </button>
                            <button
                                type="button"
                                class="btn btn-falcon-default btn-sm"
                                :class="{ active: viewMode === 'calendar' }"
                                @click="viewMode = 'calendar'"
                            >
                                <span class="fas fa-calendar-alt"></span>
                                <span class="d-none d-sm-inline-block ms-1">Calendario</span>
                            </button>
                        </div>
                        <button @click="openCreateModal" class="btn btn-falcon-default btn-sm" :disabled="isLocked">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nueva Orden</span>
                        </button>
                    </div>
                </div>

                <div v-if="viewMode === 'calendar'" class="card-body">
                    <ApplicationOrderCalendar :orders="applicationOrders" />
                </div>

                <div v-show="viewMode === 'table'" class="card-body">
                    <!-- Buscador y filtros -->
                    <div class="row mb-3 g-2">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input
                                    v-model="termInput"
                                    type="text"
                                    class="form-control"
                                    placeholder="Buscar..."
                                    @keyup.enter="applySearch"
                                />
                                <button
                                    v-if="termInput || term"
                                    type="button"
                                    class="btn btn-falcon-default btn-sm"
                                    title="Limpiar búsqueda"
                                    @click="clearSearch"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                                <button type="button" class="btn btn-falcon-default btn-sm" @click="applySearch">
                                    <span class="fas fa-search"></span>
                                    <span class="d-none d-sm-inline-block ms-1">Buscar</span>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <select v-model="statusFilter" class="form-select">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                        <table class="table table-hover table-sm">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 80px; cursor: pointer;" @click="toggleSort('id')">
                                        #Orden
                                        <i v-if="sortBy === 'id'" :class="sortDir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="ms-1"></i>
                                        <i v-else class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    </th>
                                    <th style="cursor: pointer;" @click="toggleSort('date')">
                                        Fecha
                                        <i v-if="sortBy === 'date'" :class="sortDir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'" class="ms-1"></i>
                                        <i v-else class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    </th>
                                    <th>Estado</th>
                                    <th>Mojamiento (L)</th>
                                    <th>Productos</th>
                                    <th>Centros de Costo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in filteredRows" :key="order.id">
                                    <td><strong class="text-primary">#{{ order.id }}</strong></td>
                                    <td>{{ new Date(order.date).toLocaleDateString('es-ES') }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="badge border-0"
                                            :class="[
                                                getStatusBadgeClass(order.status),
                                                { 'badge-manual-completed': isManualCompleted(order) },
                                            ]"
                                            :style="{ cursor: (isLocked || hasOutflows(order)) ? 'default' : 'pointer' }"
                                            :title="hasOutflows(order)
                                                ? 'Tiene salidas registradas: para cambiar el estado revierta la aplicación desde Salidas'
                                                : (isManualCompleted(order) ? 'Completada sin salida de agroquímicos. Click para cambiar el estado' : 'Click para cambiar el estado')"
                                            @click.stop="openStatusMenu($event, order)"
                                        >
                                            <i v-if="isManualCompleted(order)" class="fas fa-exclamation-circle me-1"></i>
                                            {{ getStatusLabel(order.status) }}<span v-if="isManualCompleted(order)"> (sin salida)</span>
                                            <i v-if="!isLocked && !hasOutflows(order)" class="fas fa-caret-down ms-1"></i>
                                        </button>
                                    </td>
                                    <td>{{ Number(order.mojamiento).toLocaleString('es-ES') }}</td>
                                    <td style="max-width: 250px;">
                                        <div v-if="order.order_products?.length > 0">
                                            {{ order.order_products.map(op => op.product?.name || 'N/A').join(', ') }}
                                        </div>
                                        <span v-else class="text-muted">Sin productos</span>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <div v-if="order.order_cost_centers?.length > 0">
                                            {{ order.order_cost_centers.slice(0, 3).map(occ => occ.cost_center?.name || 'N/A').join(', ') }}
                                            <!-- Si hay más de 3, mostrar "y X más" con tooltip -->
                                            <span 
                                                v-if="order.order_cost_centers.length > 3"
                                                class="text-primary fw-bold"
                                                style="cursor: pointer;"
                                                :data-bs-title="getCostCentersTooltip(order)"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-html="true"
                                            >
                                                y {{ order.order_cost_centers.length - 3 }} más...
                                            </span>
                                        </div>
                                        <span v-else class="text-muted">Sin centros</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                @click="openEditModal(order)"
                                                class="btn btn-sm btn-falcon-default"
                                                title="Editar"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <Link
                                                :href="route('application-orders.show', order.id)"
                                                class="btn btn-sm btn-falcon-default"
                                                title="Ver"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                            <button
                                                @click="confirmDelete(order.id)"
                                                class="btn btn-sm btn-falcon-default"
                                                title="Eliminar"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredRows.length === 0">
                                    <td colspan="7" class="text-center text-muted">
                                        No hay órdenes de aplicación registradas
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>

        <!-- Menú rápido de estado -->
        <Teleport to="body">
            <template v-if="statusMenu.open">
                <div class="status-menu-backdrop" @click="closeStatusMenu"></div>
                <div class="status-menu shadow" :style="{ top: statusMenu.top + 'px', left: statusMenu.left + 'px' }">
                    <div class="status-menu-title">Cambiar estado · #{{ statusMenu.order?.id }}</div>
                    <button
                        v-for="opt in statusMenuOptions"
                        :key="opt.value"
                        type="button"
                        class="status-menu-item"
                        @click="changeStatus(opt.value)"
                    >
                        <span class="badge me-2" :class="getStatusBadgeClass(opt.value)">&nbsp;</span>{{ opt.label }}
                    </button>
                </div>
            </template>
        </Teleport>

        <!-- Modales -->
        <CreateApplicationOrderModal
            :show="showCreateModal"
            :products="products"
            :cost-centers="costCenters"
            :branches="branches"
            :units="units"
            :groupings="groupings"
            :fruits="fruits"
            :phenological-stages="phenologicalStages"
            :machineries="machineries"
            :operators="operators"
            @close="closeCreateModal"
        />

        <EditApplicationOrderModal
            :show="showEditModal"
            :order="editingOrder"
            :products="products"
            :cost-centers="costCenters"
            :branches="branches"
            :units="units"
            :groupings="groupings"
            :fruits="fruits"
            :phenological-stages="phenologicalStages"
            :machineries="machineries"
            :operators="operators"
            @close="closeEditModal"
        />
    </AppLayout>
</template>

<style scoped>
.badge-manual-completed {
    outline: 2px dashed #00a862;
    outline-offset: 1px;
}

.status-menu-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1990;
}

.status-menu {
    position: fixed;
    z-index: 2000;
    min-width: 200px;
    background: #fff;
    border: 1px solid #d8e2ef;
    border-radius: 8px;
    padding: 6px;
}

.status-menu-title {
    font-size: 0.7rem;
    color: #9da9bb;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 4px 8px;
}

.status-menu-item {
    display: flex;
    align-items: center;
    width: 100%;
    background: transparent;
    border: 0;
    border-radius: 6px;
    padding: 6px 8px;
    font-size: 0.8rem;
    text-align: left;
    color: #344050;
}

.status-menu-item:hover {
    background: #edf2f9;
}
</style>
