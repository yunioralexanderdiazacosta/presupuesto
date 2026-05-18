<script setup>
import { ref, watch, onMounted } from 'vue';
import PurchaseOrderForm from './PurchaseOrderForm.vue';

const props = defineProps({
    show: Boolean,
    order: Object,
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
        $('#editPurchaseOrderModal').modal('show');
    } else {
        $('#editPurchaseOrderModal').modal('hide');
    }
});

onMounted(() => {
    $('#editPurchaseOrderModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function handleClose() {
    $('#editPurchaseOrderModal').modal('hide');
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
        id="editPurchaseOrderModal" 
        tabindex="-1" 
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
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
                        ref="formRef"
                        :order="order"
                        :suppliers="suppliers"
                        :companyReasons="companyReasons"
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

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="handleClose">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn-warning"
                        @click="save"
                        :disabled="formRef?.form?.processing"
                    >
                        <i class="fas fa-save me-1"></i>
                        {{ formRef?.form?.processing ? 'Guardando...' : 'Actualizar Orden' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
