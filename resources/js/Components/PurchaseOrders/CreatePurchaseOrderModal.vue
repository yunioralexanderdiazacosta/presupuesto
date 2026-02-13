<script setup>
import { ref, watch } from 'vue';
import PurchaseOrderForm from './PurchaseOrderForm.vue';

const props = defineProps({
    show: Boolean,
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
        $('#createPurchaseOrderModal').modal('show');
    } else {
        $('#createPurchaseOrderModal').modal('hide');
    }
});

function handleClose() {
    emit('close');
}
</script>

<template>
    <div 
        class="modal fade" 
        id="createPurchaseOrderModal" 
        tabindex="-1" 
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Orden de Compra
                    </h5>
                    <button 
                        type="button" 
                        class="btn-close" 
                        @click="handleClose"
                    ></button>
                </div>
                
                <div class="modal-body">
                    <PurchaseOrderForm 
                        :suppliers="suppliers"
                        :costCenters="costCenters"
                        :groupings="groupings"
                        :products="products"
                        :units="units"
                        :approvers="approvers"
                        @close="handleClose"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
