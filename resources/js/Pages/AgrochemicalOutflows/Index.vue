<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExecuteApplicationOrderModal from '@/Components/AgrochemicalOutflows/ExecuteApplicationOrderModal.vue';
import AgrochemicalNavBar from '@/Components/AgrochemicalOutflows/AgrochemicalNavBar.vue';

const props = defineProps({
    outflows: Array,
    availableOrders: Array,
    availableStocksByProduct: Object,
    branches: { type: Array, default: () => [] },
});

const showExecuteModal = ref(false);
const expandedRows = ref({});
const preselectedOrderId = ref(null);

const toggleExpand = (orderId) => {
    expandedRows.value[orderId] = !expandedRows.value[orderId];
};

const openExecuteModal = () => {
    preselectedOrderId.value = null;
    showExecuteModal.value = true;
};

const handleOrderExecuted = () => {
    showExecuteModal.value = false;
    preselectedOrderId.value = null;
    router.reload();
};

const revertOutflow = (row) => {
    Swal.fire({
        title: '¿Rehacer esta aplicación?',
        html: `Se eliminarán todos los registros de la <strong>Orden #${row.application_order_id}</strong> y la orden volverá a estado <strong>pendiente</strong> para que pueda re-ejecutarla.<br><br>El stock de las facturas se liberará automáticamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e6a800',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, rehacer',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('agrochemical-outflows.revert', row.application_order_id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Aplicación revertida',
                        text: 'La orden está disponible para re-ejecutar. Se abrirá el formulario.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    // Esperar a que Inertia actualice las props, luego abrir modal con la orden preseleccionada
                    setTimeout(() => {
                        preselectedOrderId.value = row.application_order_id;
                        showExecuteModal.value = true;
                    }, 500);
                },
                onError: (errors) => {
                    Swal.fire('Error', errors.error || 'No se pudo revertir la aplicación', 'error');
                },
            });
        }
    });
};

const deleteOutflow = (row) => {
    Swal.fire({
        title: '¿Eliminar esta aplicación?',
        html: `Se eliminarán todos los registros de la <strong>Orden #${row.application_order_id}</strong> y se revertirá el inventario.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('agrochemical-outflows.revert', row.application_order_id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Eliminado', 'La aplicación ha sido eliminada correctamente.', 'success');
                },
            });
        }
    });
};

const truncateCuarteles = (list, max = 2) => {
    if (!list || list.length <= max) return list ? list.join(', ') : '';
    return list.slice(0, max).join(', ');
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
        <AgrochemicalNavBar />

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
                                <tr @click="toggleExpand(row.application_order_id)" 
                                    style="cursor: pointer;"
                                    :class="{ 'bg-soft-primary': expandedRows[row.application_order_id] }"
                                >
                                    <td>
                                        <i class="fas fa-xs me-1" 
                                           :class="expandedRows[row.application_order_id] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        {{ new Date(String(row.date).substring(0, 10) + 'T12:00:00').toLocaleDateString('es-CL') }}
                                    </td>
                                    <td>
                                        <a :href="route('application-orders.show', row.application_order_id)" 
                                           class="text-primary fw-semibold" target="_blank"
                                           @click.stop>
                                            #{{ row.application_order_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <small v-tooltip="row.cuarteles_list?.length > 2 ? row.cuarteles : null">
                                            {{ truncateCuarteles(row.cuarteles_list) }}
                                            <span v-if="row.cuarteles_list?.length > 2" class="badge bg-soft-secondary text-secondary ms-1" style="font-size: 0.65rem;">
                                                +{{ row.cuarteles_list.length - 2 }}
                                            </span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ row.productos }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        {{ Number(row.cantidad_total).toLocaleString('es-CL', {minimumFractionDigits: 2}) }}
                                        <small class="text-muted ms-1">{{ row.unidad }}</small>
                                    </td>
                                    <td class="text-end">
                                        {{ Number(row.maquinadas).toLocaleString('es-CL', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ row.facturas }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button 
                                                @click.stop="revertOutflow(row)"
                                                class="btn btn-sm btn-falcon-default"
                                                title="Rehacer aplicación"
                                            >
                                                <i class="fas fa-redo"></i>
                                            </button>
                                            <button 
                                                @click.stop="deleteOutflow(row)"
                                                class="btn btn-sm btn-falcon-default"
                                                title="Eliminar aplicación"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Detalle expandido -->
                                <tr v-if="expandedRows[row.application_order_id]">
                                    <td colspan="8" class="p-0">
                                        <div class="mx-3 my-2 px-3 py-2 bg-light rounded border-start border-3 border-primary rounded-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-list-ul text-primary me-2"></i>
                                                <strong class="small text-primary">Detalle por cuartel — Orden #{{ row.application_order_id }}</strong>
                                            </div>
                                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.75rem;">
                                                <thead>
                                                    <tr class="table-light">
                                                        <th class="ps-3">Cuartel</th>
                                                        <th>Producto</th>
                                                        <th class="text-center">Cantidad</th>
                                                        <th>Factura</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="d in row.detalle" :key="d.id">
                                                        <td class="ps-3">
                                                            <i class="fas fa-map-marker-alt text-muted me-1" style="font-size: 0.6rem;"></i>
                                                            {{ d.cuartel }}
                                                        </td>
                                                        <td>{{ d.producto }}</td>
                                                        <td class="text-center fw-semibold">
                                                            {{ Number(d.cantidad).toLocaleString('es-CL', {minimumFractionDigits: 2}) }}
                                                            <small class="text-muted ms-1">{{ d.unidad }}</small>
                                                        </td>
                                                        <td><small class="text-muted">{{ d.factura }}</small></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
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
            :branches="branches"
            :preselected-order-id="preselectedOrderId"
            @close="showExecuteModal = false; preselectedOrderId = null;"
            @saved="handleOrderExecuted"
        />
    </AppLayout>
</template>
