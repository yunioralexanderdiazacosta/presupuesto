<script setup>
import { ref, watch, onMounted } from 'vue';
import PurchaseOrderForm from './PurchaseOrderForm.vue';

const props = defineProps({
    show: Boolean,
    suppliers: Array,
    companyReasons: Array,
    costCenters: Array,
    groupings: Array,
    products: Array,
    units: Array,
    approvers: Array,
});

const emit = defineEmits(['close']);
const formRef = ref(null);

watch(() => props.show, (newVal) => {
    if (newVal) {
        $('#createPurchaseOrderModal').modal('show');
    } else {
        $('#createPurchaseOrderModal').modal('hide');
    }
});

onMounted(() => {
    $('#createPurchaseOrderModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function handleClose() {
    $('#createPurchaseOrderModal').modal('hide');
    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '');
    }, 300);
}

function save() {
    if (formRef.value) {
        formRef.value.submit();
    }
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Orden de Compra
                    </h5>
                    <button 
                        type="button" 
                        class="btn-close btn-close-white" 
                        @click="handleClose"
                    ></button>
                </div>
                
                <div class="modal-body">
                    <PurchaseOrderForm 
                        ref="formRef"
                        :suppliers="suppliers"
                        :companyReasons="companyReasons"
                        :costCenters="costCenters"
                        :groupings="groupings"
                        :products="products"
                        :units="units"
                        :approvers="approvers"
                        @close="handleClose"
                    />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="handleClose">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="save"
                        :disabled="formRef?.form?.processing"
                    >
                        <i class="fas fa-save me-1"></i>
                        {{ formRef?.form?.processing ? 'Guardando...' : 'Crear Orden' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
