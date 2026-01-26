<script setup>
import { ref } from 'vue';
import FertilizerOrderForm from './FertilizerOrderForm.vue';

defineProps({
    products: Array,
    irrigationPumps: Array,
    costCenters: Array,
    units: Array,
});
</script>

<template>
    <div class="modal fade" id="createFertilizerOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-seedling me-2"></i>Nueva Orden de Fertilizante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <FertilizerOrderForm 
                        :products="products"
                        :irrigation-pumps="irrigationPumps"
                        :cost-centers="costCenters"
                        :units="units"
                        modal-id="createFertilizerOrderModal"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
