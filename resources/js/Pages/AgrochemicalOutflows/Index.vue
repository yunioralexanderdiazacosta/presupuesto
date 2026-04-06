<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExecuteApplicationOrderModal from '@/Components/AgrochemicalOutflows/ExecuteApplicationOrderModal.vue';

const props = defineProps({
    outflows: Array,
    availableOrders: Array,
    availableStocksByProduct: Object,
});

const showExecuteModal = ref(false);
const selectedOrder = ref(null);
const expandedRows = ref({});

const toggleExpand = (orderId) => {
    expandedRows.value[orderId] = !expandedRows.value[orderId];
};

const openExecuteModal = () => {
    showExecuteModal.value = true;
};

const handleOrderExecuted = () => {
    showExecuteModal.value = false;
    router.reload();
};

const deleteOutflow = (outflowIds) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción eliminará la aplicación completa y revertirá los movimientos de inventario",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Eliminar todos los outflows del grupo
            const promises = outflowIds.map(id => 
                new Promise((resolve, reject) => {
                    router.delete(route('agrochemical-outflows.delete', id), {
                        preserveScroll: true,
                        onSuccess: resolve,
                        onError: reject,
                    });
                })
            );
            // El último delete recarga la página por Inertia
            Swal.fire('Eliminado', 'La aplicación ha sido eliminada correctamente.', 'success');
        }
    });
};

const excelData = computed(() => {
    return props.outflows.map(item => ({
        'Fecha': item.date,
        'Orden': `#${item.application_order_id}`,
        'Maquinadas': item.maquinadas,
        'Productos': item.productos,
        'Cuarteles': item.cuarteles,
        'Cantidad Total': item.cantidad_total,
        'Unidad': item.unidad,
        'Facturas': item.facturas,
        'Observaciones': item.observations || '',
    }));
});
</script>

<template>
    <AppLayout title="Aplicaciones de Agroquímicos">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-spray-can me-2"></i>Aplicaciones de Agroquímicos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <ExportExcelButton 
                                :data="excelData"
                                filename="aplicaciones-agroquimicos"
                                sheet-name="Aplicaciones"
                                class="btn btn-falcon-default btn-sm d-flex align-items-center"
                            />
                            <button 
                                @click="openExecuteModal"
                                class="btn btn-falcon-default btn-sm"
                            >
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Registrar Aplicación</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Tabla de aplicaciones -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm" style="font-size: 0.8rem;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Orden #</th>
                                <th>Cuarteles</th>
                                <th>Productos</th>
                                <th class="text-end">Cantidad Total</th>
                                <th class="text-end">Maquinadas</th>
                                <th>Facturas</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="row in outflows" :key="row.application_order_id">
                                <tr @click="toggleExpand(row.application_order_id)" style="cursor: pointer;">
                                    <td>
                                        <i class="fas fa-xs me-1" 
                                           :class="expandedRows[row.application_order_id] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ new Date(row.date + 'T12:00:00').toLocaleDateString('es-CL') }}
                                    </td>
                                    <td>
                                        <a :href="route('application-orders.show', row.application_order_id)" 
                                           class="text-primary fw-semibold" target="_blank"
                                           @click.stop>
                                            #{{ row.application_order_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <small>{{ row.cuarteles }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ row.productos }}</span>
                                    </td>
                                    <td class="text-end">
                                        {{ Number(row.cantidad_total).toLocaleString('es-CL', {minimumFractionDigits: 2}) }} {{ row.unidad }}
                                    </td>
                                    <td class="text-end">
                                        {{ Number(row.maquinadas).toLocaleString('es-CL', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ row.facturas }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button 
                                            @click.stop="deleteOutflow(row.outflow_ids)"
                                            class="btn btn-sm btn-falcon-default"
                                            title="Eliminar aplicación"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Detalle expandido -->
                                <tr v-if="expandedRows[row.application_order_id]">
                                    <td colspan="8" class="p-0">
                                        <table class="table table-sm mb-0 bg-light" style="font-size: 0.75rem;">
                                            <thead>
                                                <tr class="table-light">
                                                    <th class="ps-4">Cuartel</th>
                                                    <th>Producto</th>
                                                    <th class="text-end">Cantidad</th>
                                                    <th>Factura</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="d in row.detalle" :key="d.id">
                                                    <td class="ps-4">{{ d.cuartel }}</td>
                                                    <td>{{ d.producto }}</td>
                                                    <td class="text-end">{{ Number(d.cantidad).toLocaleString('es-CL', {minimumFractionDigits: 2}) }} {{ d.unidad }}</td>
                                                    <td><small class="text-muted">{{ d.factura }}</small></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="outflows.length === 0">
                                <td colspan="8" class="text-center text-muted py-4">
                                    No hay aplicaciones registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de ejecución -->
        <ExecuteApplicationOrderModal
            :show="showExecuteModal"
            :available-orders="availableOrders"
            :available-stocks-by-product="availableStocksByProduct"
            @close="showExecuteModal = false"
            @saved="handleOrderExecuted"
        />
    </AppLayout>
</template>
