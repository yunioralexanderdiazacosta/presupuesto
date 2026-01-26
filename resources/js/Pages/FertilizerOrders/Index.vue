<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateFertilizerOrderModal from '@/Components/FertilizerOrders/CreateFertilizerOrderModal.vue';
import EditFertilizerOrderModal from '@/Components/FertilizerOrders/EditFertilizerOrderModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    fertilizerOrders: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    irrigationPumps: { type: Array, default: () => [] },
    costCenters: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const title = 'Órdenes de Fertilizante';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const searchTerm = ref('');
const selectedFertilizerOrder = ref(null);

const filteredFertilizerOrders = computed(() => {
    if (!searchTerm.value) return props.fertilizerOrders;
    
    const term = searchTerm.value.toLowerCase();
    return props.fertilizerOrders.filter(order => 
        order.date?.toLowerCase().includes(term) ||
        order.responsable?.toLowerCase().includes(term) ||
        order.irrigation_pump?.name?.toLowerCase().includes(term)
    );
});

const openCreateModal = () => {
    const modalElement = document.getElementById('createFertilizerOrderModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
};

const openEditModal = (fertilizerOrder) => {
    selectedFertilizerOrder.value = fertilizerOrder;
    const modalElement = document.getElementById('editFertilizerOrderModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
};

const deleteFertilizerOrder = (id) => {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede revertir',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('fertilizer-orders.destroy', id))
                .then(() => {
                    Swal.fire('¡Eliminado!', 'La orden ha sido eliminada.', 'success')
                        .then(() => window.location.reload());
                })
                .catch(error => {
                    Swal.fire('Error', error.response?.data?.message || 'No se pudo eliminar la orden', 'error');
                });
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

const excelData = computed(() => {
    return props.fertilizerOrders.map(order => ({
        'Fecha': formatDate(order.date),
        'Bomba de Riego': order.irrigation_pump?.name || '',
        'Responsable': order.responsable || '',
        'Estado': order.status === 'pending' ? 'Pendiente' : order.status === 'executed' ? 'Ejecutado' : 'Cancelado',
        'Observaciones': order.observations || '',
    }));
});

const excelFilename = computed(() => `ordenes_fertilizantes_${new Date().toISOString().split('T')[0]}.xlsx`);
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        
        <Breadcrumb :title="title" :links="links" />

        <div class="container-fluid mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ title }}</h5>
                    <div class="d-flex gap-2">
                        <ExportExcelButton 
                            :data="excelData"
                            :filename="excelFilename"
                            class="btn btn-falcon-default btn-sm"
                        />
                        <button 
                            @click="openCreateModal" 
                            class="btn btn-falcon-default btn-sm"
                        >
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nueva Orden</span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input 
                                v-model="searchTerm" 
                                type="text" 
                                class="form-control" 
                                placeholder="Buscar por fecha, responsable, bomba..."
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
                                <th>Bomba de Riego</th>
                                <th>Responsable</th>
                                <th>Productos</th>
                                <th>Sectores</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in filteredFertilizerOrders" :key="order.id">
                                <td><strong class="text-primary">#{{ order.id }}</strong></td>
                                <td>{{ formatDate(order.date) }}</td>
                                <td>{{ order.irrigation_pump?.name || '-' }}</td>
                                <td>{{ order.responsable || '-' }}</td>
                                <td class="small" style="max-width: 250px;">
                                    <div v-if="order.order_products?.length > 0">
                                        {{ order.order_products.map(op => op.product?.name || 'N/A').join(', ') }}
                                    </div>
                                    <span v-else class="text-muted">Sin productos</span>
                                </td>
                                <td class="small" style="max-width: 200px;">
                                    <div v-if="order.order_irrigation_sectors?.length > 0">
                                        {{ order.order_irrigation_sectors.map(ois => ois.irrigation_sector?.name || 'N/A').join(', ') }}
                                    </div>
                                    <span v-else class="text-muted">Sin sectores</span>
                                </td>
                                <td>
                                    <span 
                                        class="badge"
                                        :class="{
                                            'bg-warning text-dark': order.status === 'pending',
                                            'bg-success text-white': order.status === 'executed',
                                            'bg-danger text-white': order.status === 'canceled'
                                        }"
                                    >
                                        {{ order.status === 'pending' ? 'Pendiente' : order.status === 'executed' ? 'Ejecutado' : 'Cancelado' }}
                                    </span>
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
                                        <button
                                            @click="deleteFertilizerOrder(order.id)"
                                            class="btn btn-sm btn-falcon-default"
                                            title="Eliminar"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredFertilizerOrders.length === 0">
                                <td colspan="8" class="text-center text-muted">
                                    No hay órdenes de fertilizante registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CreateFertilizerOrderModal 
            :products="products"
            :irrigation-pumps="irrigationPumps"
            :cost-centers="costCenters"
            :units="units"
        />

        <EditFertilizerOrderModal 
            v-if="selectedFertilizerOrder"
            :fertilizer-order="selectedFertilizerOrder"
            :products="products"
            :irrigation-pumps="irrigationPumps"
            :cost-centers="costCenters"
            :units="units"
        />
    </AppLayout>
</template>
