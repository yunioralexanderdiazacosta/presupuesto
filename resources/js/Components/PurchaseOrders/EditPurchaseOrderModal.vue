<script setup>
import { ref, watch } from 'vue';
import PurchaseOrderForm from './PurchaseOrderForm.vue';

const props = defineProps({
    show: Boolean,
    order: Object,
    suppliers: Array,
    costCenters: Array,
    groupings: Array,
    products: Array,
    units: Array,
    approvers: Array,
});

const emit = defineEmits(['close']);

watch(() => props.show, (newVal) => {
    if (newVal) {
        $('#editPurchaseOrderModal').modal('show');
    } else {
        $('#editPurchaseOrderModal').modal('hide');
    }
});

function handleClose() {
    emit('close');
}
</script>

<template>
    <div 
        class="modal fade" 
        id="editPurchaseOrderModal" 
        tabindex="-1" 
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Orden de Compra
                    </h5>
                    <button 
                        type="button" 
                        class="btn-close" 
                        @click="handleClose"
                    ></button>
                </div>
                
                <div class="modal-body">
                    <PurchaseOrderForm 
                        v-if="order"
                        :order="order"
                        :suppliers="suppliers"
                        :costCenters="costCenters"
                        :groupings="groupings"
                        :products="products"
                        :units="units"
                        :approvers="approvers"
                        :isEditing="true"
                        @close="handleClose"
                    />
                    <div v-else class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
