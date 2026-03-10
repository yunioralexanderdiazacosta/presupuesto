<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DispatchForm from './DispatchForm.vue';

const props = defineProps({
    show: Boolean,
    costCenterVarieties: Array,
    exporters: Array,
    packingHouses: Array,
    binTypes: Array,
    boxTypes: Array,
    carriers: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    cost_center_variety_id: '',
    exporter_id: '',
    packing_house_id: '',
    dispatch_date: '',
    guide_number: '',
    lot_number: '',
    kg_dispatched: '',
    bin_type_id: '',
    bins_quantity: '',
    box_type_id: '',
    boxes_quantity: '',
    carrier_id: '',
    driver: '',
    license_plate: '',
    observations: '',
});

watch(() => props.show, (val) => {
    if (val) form.reset();
});

function closeModal() {
    emit('close');
}

function save() {
    form.post(route('production-dispatches.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Despacho registrado correctamente', timer: 1200, showConfirmButton: false });
            form.reset();
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-truck-loading text-primary me-2 fs-8"></i>
                        Nuevo Despacho
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <DispatchForm
                        :form="form"
                        :costCenterVarieties="costCenterVarieties"
                        :exporters="exporters"
                        :packingHouses="packingHouses"
                        :binTypes="binTypes"
                        :boxTypes="boxTypes"
                        :carriers="carriers"
                    />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
