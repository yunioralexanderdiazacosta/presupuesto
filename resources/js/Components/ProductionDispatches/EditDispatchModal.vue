<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DispatchForm from './DispatchForm.vue';

const props = defineProps({
    show: Boolean,
    dispatch: Object,
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
    if (val && props.dispatch) {
        form.cost_center_variety_id = props.dispatch.cost_center_variety_id;
        form.exporter_id = props.dispatch.exporter_id;
        form.packing_house_id = props.dispatch.packing_house_id;
        form.dispatch_date = props.dispatch.dispatch_date;
        form.guide_number = props.dispatch.guide_number;
        form.lot_number = props.dispatch.lot_number || '';
        form.kg_dispatched = props.dispatch.kg_dispatched;
        form.bin_type_id = props.dispatch.bin_type_id || '';
        form.bins_quantity = props.dispatch.bins_quantity || '';
        form.box_type_id = props.dispatch.box_type_id || '';
        form.boxes_quantity = props.dispatch.boxes_quantity || '';
        form.carrier_id = props.dispatch.carrier_id || '';
        form.driver = props.dispatch.driver || '';
        form.license_plate = props.dispatch.license_plate || '';
        form.observations = props.dispatch.observations || '';
    }
});

function closeModal() {
    emit('close');
}

function save() {
    form.put(route('production-dispatches.update', props.dispatch.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', text: 'Despacho actualizado correctamente', timer: 1200, showConfirmButton: false });
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
                        <i class="fas fa-edit text-primary me-2 fs-8"></i>
                        Editar Despacho
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
                        <i class="fas fa-save me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
