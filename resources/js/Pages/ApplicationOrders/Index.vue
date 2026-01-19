<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateApplicationOrderModal from '@/Components/ApplicationOrders/CreateApplicationOrderModal.vue';
import EditApplicationOrderModal from '@/Components/ApplicationOrders/EditApplicationOrderModal.vue';

const props = defineProps({
    applicationOrders: Object,
    products: Array,
    costCenters: Array,
    units: Array,
});

const title = 'Órdenes de Aplicación';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const term = ref('');
const filteredRows = computed(() => {
    if (!props.applicationOrders || !props.applicationOrders.data) return [];
    if (!term.value) return props.applicationOrders.data;
    const search = term.value.toLowerCase();
    return props.applicationOrders.data.filter(item => {
        const aplicadores = item.aplicadores?.toLowerCase() || '';
        const status = item.status?.toLowerCase() || '';
        const productos = item.order_products?.map(op => op.product?.name?.toLowerCase() || '').join(' ') || '';
        return (
            aplicadores.includes(search) ||
            status.includes(search) ||
            productos.includes(search) ||
            item.id.toString().includes(search)
        );
    });
});

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
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        
        <Breadcrumb :title="title" :links="links" />

        <div class="container-fluid mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ title }}</h5>
                    <button @click="openCreateModal" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Nueva Orden
                    </button>
                </div>

                <div class="card-body">
                    <!-- Buscador -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input
                                    v-model="term"
                                    type="text"
                                    class="form-control"
                                    placeholder="Buscar..."
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">#Orden</th>
                                    <th>Fecha</th>
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
                                        <span class="badge" :class="getStatusBadgeClass(order.status)">
                                            {{ getStatusLabel(order.status) }}
                                        </span>
                                    </td>
                                    <td>{{ Number(order.mojamiento).toLocaleString('es-ES') }}</td>
                                    <td class="small" style="max-width: 250px;">
                                        <div v-if="order.order_products?.length > 0">
                                            {{ order.order_products.map(op => op.product?.name || 'N/A').join(', ') }}
                                        </div>
                                        <span v-else class="text-muted">Sin productos</span>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <div v-if="order.order_cost_centers?.length > 0" style="max-height: 60px; overflow-y: auto;" class="small">
                                            <div v-for="(occ, index) in order.order_cost_centers" :key="index">
                                                • {{ occ.cost_center?.name || 'N/A' }}
                                            </div>
                                        </div>
                                        <span v-else class="text-muted small">Sin centros</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button
                                                @click="openEditModal(order)"
                                                class="btn btn-sm btn-warning"
                                                title="Editar"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <Link
                                                :href="route('application-orders.show', order.id)"
                                                class="btn btn-sm btn-info"
                                                title="Ver"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                            <button
                                                @click="confirmDelete(order.id)"
                                                class="btn btn-sm btn-danger"
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

                    <!-- Paginación -->
                    <div v-if="applicationOrders.links" class="d-flex justify-content-center mt-3">
                        <nav>
                            <ul class="pagination pagination-sm">
                                <li
                                    v-for="(link, index) in applicationOrders.links"
                                    :key="index"
                                    class="page-item"
                                    :class="{ active: link.active, disabled: !link.url }"
                                >
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="page-link"
                                        v-html="link.label"
                                    />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateApplicationOrderModal
            :show="showCreateModal"
            :products="products"
            :cost-centers="costCenters"
            :units="units"
            @close="closeCreateModal"
        />

        <EditApplicationOrderModal
            :show="showEditModal"
            :order="editingOrder"
            :products="products"
            :cost-centers="costCenters"
            :units="units"
            @close="closeEditModal"
        />
    </AppLayout>
</template>
