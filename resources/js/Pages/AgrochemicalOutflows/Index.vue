<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExecuteApplicationOrderModal from '@/Components/AgrochemicalOutflows/ExecuteApplicationOrderModal.vue';

const props = defineProps({
    outflows: Object,
    availableOrders: Array,
    availableStocksByProduct: Object,
});

const showExecuteModal = ref(false);
const selectedOrder = ref(null);

const openExecuteModal = () => {
    showExecuteModal.value = true;
};

const handleOrderExecuted = () => {
    showExecuteModal.value = false;
    router.reload();
};

const deleteOutflow = (outflowId) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción eliminará la aplicación y revertirá el movimiento de inventario",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('agrochemical-outflows.delete', outflowId), {
                onSuccess: () => {
                    Swal.fire('Eliminado', 'La aplicación ha sido eliminada correctamente.', 'success');
                },
            });
        }
    });
};

const excelData = computed(() => {
    return props.outflows.data.map(item => ({
        'Fecha': item.date,
        'Orden': `#${item.application_order_id}`,
        'Maquinadas': item.maquinadas,
        'Producto': item.product?.name || '',
        'Cantidad': item.quantity,
        'Unidad': item.product?.unit?.name || '',
        'Factura': item.invoice_product?.invoice?.number_document || 'N/A',
        'Centro de Costo': item.cost_center?.name || '',
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Orden #</th>
                                <th>Maquinadas</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Factura Origen</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="outflow in outflows.data" :key="outflow.id">
                                <td>
                                    {{ new Date(outflow.date).toLocaleDateString('es-ES') }}
                                </td>
                                <td>
                                    #{{ outflow.application_order_id }}
                                </td>
                                <td>
                                    {{ outflow.maquinadas.toLocaleString('es-ES', {minimumFractionDigits: 2}) }}
                                </td>
                                <td>
                                    {{ outflow.product?.name }}
                                </td>
                                <td class="text-end">
                                    {{ outflow.quantity.toLocaleString('es-ES', {minimumFractionDigits: 2}) }} {{ outflow.product?.unit?.name }}
                                </td>
                                <td>
                                    <small class="text-muted">{{ outflow.invoice_product?.invoice?.number_document || 'N/A' }}</small>
                                </td>
                                <td class="text-center">
                                    <button 
                                        @click="deleteOutflow(outflow.id)"
                                        class="btn btn-sm btn-falcon-default"
                                        title="Eliminar aplicación"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="outflows.data.length === 0">
                                <td colspan="7" class="text-center text-muted">
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
