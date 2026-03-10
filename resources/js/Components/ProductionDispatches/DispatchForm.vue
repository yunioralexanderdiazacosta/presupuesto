<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    form: { type: Object, required: true },
    costCenterVarieties: { type: Array, default: () => [] },
    exporters: { type: Array, default: () => [] },
    packingHouses: { type: Array, default: () => [] },
    binTypes: { type: Array, default: () => [] },
    boxTypes: { type: Array, default: () => [] },
    carriers: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:form']);

const form = props.form;

const exporterOptions = ref(props.exporters);
const packingHouseOptions = ref(props.packingHouses);
const binTypeOptions = ref(props.binTypes);
const boxTypeOptions = ref(props.boxTypes);
const carrierOptions = ref(props.carriers);
const isRefreshingExporters = ref(false);
const isRefreshingPackings = ref(false);
const isRefreshingBinTypes = ref(false);
const isRefreshingBoxTypes = ref(false);
const isRefreshingCarriers = ref(false);

const refreshExporters = async () => {
    isRefreshingExporters.value = true;
    try {
        const response = await axios.get(route('api.exporters'));
        exporterOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingExporters.value = false;
    }
};

const refreshPackingHouses = async () => {
    isRefreshingPackings.value = true;
    try {
        const response = await axios.get(route('api.packing-houses'));
        packingHouseOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingPackings.value = false;
    }
};

const refreshBinTypes = async () => {
    isRefreshingBinTypes.value = true;
    try {
        const response = await axios.get(route('api.bin-types'));
        binTypeOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingBinTypes.value = false;
    }
};

const refreshBoxTypes = async () => {
    isRefreshingBoxTypes.value = true;
    try {
        const response = await axios.get(route('api.box-types'));
        boxTypeOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingBoxTypes.value = false;
    }
};

const refreshCarriers = async () => {
    isRefreshingCarriers.value = true;
    try {
        const response = await axios.get(route('api.carriers'));
        carrierOptions.value = response.data;
        Swal.fire({ icon: 'success', title: 'Lista actualizada', showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo refrescar la lista', 'error');
    } finally {
        isRefreshingCarriers.value = false;
    }
};

watch(form, () => emit('update:form', form), { deep: true });
</script>

<template>
    <form @submit.prevent>
        <!-- Sección: Origen y Destino -->
        <div class="card border mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="mb-0 fs-10 fw-bold"><i class="fas fa-map-marker-alt me-1 text-primary"></i>Origen y Destino</h6>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-md-12">
                        <label class="form-label small mb-1">Cuartel - Variedad</label>
                        <select v-model="form.cost_center_variety_id" class="form-select form-select-sm" required>
                            <option :value="''" disabled>Seleccione cuartel...</option>
                            <option v-for="opt in costCenterVarieties" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Exportadora</label>
                            <button type="button" @click="refreshExporters" :disabled="isRefreshingExporters" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2" v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingExporters}"></i>
                            </button>
                        </div>
                        <select v-model="form.exporter_id" class="form-select form-select-sm" required>
                            <option :value="''" disabled>Seleccione exportadora...</option>
                            <option v-for="opt in exporterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Packing</label>
                            <button type="button" @click="refreshPackingHouses" :disabled="isRefreshingPackings" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2" v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingPackings}"></i>
                            </button>
                        </div>
                        <select v-model="form.packing_house_id" class="form-select form-select-sm" required>
                            <option :value="''" disabled>Seleccione packing...</option>
                            <option v-for="opt in packingHouseOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección: Documento y Despacho -->
        <div class="card border mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="mb-0 fs-10 fw-bold"><i class="fas fa-file-alt me-1 text-primary"></i>Documento y Despacho</h6>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Fecha Despacho</label>
                        <input type="date" v-model="form.dispatch_date" class="form-control form-control-sm" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">N° Guía Despacho</label>
                        <input type="text" v-model="form.guide_number" class="form-control form-control-sm" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">N° Lote / Partida</label>
                        <input type="text" v-model="form.lot_number" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Kg Despachados</label>
                        <input type="number" v-model="form.kg_dispatched" class="form-control form-control-sm" step="0.01" min="0" required />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección: Envases -->
        <div class="card border mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="mb-0 fs-10 fw-bold"><i class="fas fa-box me-1 text-primary"></i>Envases</h6>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Tipo Bins</label>
                            <button type="button" @click="refreshBinTypes" :disabled="isRefreshingBinTypes" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2" v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingBinTypes}"></i>
                            </button>
                        </div>
                        <select v-model="form.bin_type_id" class="form-select form-select-sm">
                            <option :value="''" disabled>Seleccione...</option>
                            <option v-for="opt in binTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cantidad Bins</label>
                        <input type="number" v-model="form.bins_quantity" class="form-control form-control-sm" min="0" />
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Tipo Caja</label>
                            <button type="button" @click="refreshBoxTypes" :disabled="isRefreshingBoxTypes" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2" v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingBoxTypes}"></i>
                            </button>
                        </div>
                        <select v-model="form.box_type_id" class="form-select form-select-sm">
                            <option :value="''" disabled>Seleccione...</option>
                            <option v-for="opt in boxTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Cantidad Cajas</label>
                        <input type="number" v-model="form.boxes_quantity" class="form-control form-control-sm" min="0" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección: Transporte -->
        <div class="card border mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="mb-0 fs-10 fw-bold"><i class="fas fa-truck me-1 text-primary"></i>Transporte</h6>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Transportista</label>
                            <button type="button" @click="refreshCarriers" :disabled="isRefreshingCarriers" class="btn btn-sm btn-light-primary d-flex align-items-center gap-1 py-0 px-2" v-tooltip="'Refrescar lista'" style="font-size: 0.75rem;">
                                <i class="fas fa-sync-alt fa-xs" :class="{'fa-spin': isRefreshingCarriers}"></i>
                            </button>
                        </div>
                        <select v-model="form.carrier_id" class="form-select form-select-sm">
                            <option :value="''" disabled>Seleccione...</option>
                            <option v-for="opt in carrierOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Chofer</label>
                        <input type="text" v-model="form.driver" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Patente</label>
                        <input type="text" v-model="form.license_plate" class="form-control form-control-sm" maxlength="10" />
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small mb-1">Observaciones</label>
                        <textarea v-model="form.observations" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
