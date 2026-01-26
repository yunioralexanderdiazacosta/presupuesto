<script setup>
import { ref, watch, onMounted } from 'vue';
import FertilizerOrderForm from './FertilizerOrderForm.vue';

const props = defineProps({
    show: Boolean,
    products: Array,
    irrigationPumps: Array,
    costCenters: Array,
    units: Array,
    groupings: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

watch(() => props.show, (val) => {
    if (val) {
        setTimeout(() => {
            const modalElement = document.getElementById('createFertilizerOrderModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }, 100);
    } else {
        const modalElement = document.getElementById('createFertilizerOrderModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
});

onMounted(() => {
    const modalElement = document.getElementById('createFertilizerOrderModal');
    modalElement.addEventListener('hidden.bs.modal', () => {
        emit('close');
    });
});
</script>

<template>
    <div class="modal fade" id="createFertilizerOrderModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-seedling me-2"></i>Nueva Orden de Fertilizante
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <FertilizerOrderForm 
                        :products="products"
                        :irrigation-pumps="irrigationPumps"
                        :cost-centers="costCenters"
                        :units="units"
                        :groupings="groupings"
                        modal-id="createFertilizerOrderModal"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
