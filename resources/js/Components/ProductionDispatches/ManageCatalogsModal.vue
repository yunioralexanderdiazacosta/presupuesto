<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    fruits: Array,
});

const emit = defineEmits(['close']);

const activeTab = ref('exporters');

// === EXPORTERS ===
const exporters = ref([]);
const loadingExporters = ref(false);
const exporterForm = useForm({ name: '', rut: '', contact: '' });
const editingExporterId = ref(null);

async function loadExporters() {
    loadingExporters.value = true;
    try {
        const res = await axios.get(route('api.exporters'));
        exporters.value = res.data;
    } finally {
        loadingExporters.value = false;
    }
}

function saveExporter() {
    const wasEditing = editingExporterId.value !== null;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            exporterForm.reset();
            editingExporterId.value = null;
            loadExporters();
            Swal.fire({ icon: 'success', title: wasEditing ? 'Exportadora actualizada' : 'Exportadora creada', timer: 1000, showConfirmButton: false });
        },
        onError: () => Swal.fire('Error', 'No se pudo guardar', 'error'),
    };

    if (editingExporterId.value !== null) {
        exporterForm.put(route('exporters.update', editingExporterId.value), options);
    } else {
        exporterForm.post(route('exporters.store'), options);
    }
}

function editExporter(exporter) {
    editingExporterId.value = exporter.value;
    exporterForm.name = exporter.name;
    exporterForm.rut = exporter.rut || '';
    exporterForm.contact = exporter.contact || '';
}

function cancelEditExporter() {
    editingExporterId.value = null;
    exporterForm.reset();
    exporterForm.clearErrors();
}

function deleteExporter(id) {
    Swal.fire({
        title: '¿Eliminar exportadora?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('exporters.delete', id)).then(() => {
                loadExporters();
                Swal.fire({ icon: 'success', title: 'Eliminada', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar. Puede tener despachos asociados.', 'error'));
        }
    });
}

// === PACKING HOUSES ===
const packingHouses = ref([]);
const loadingPackings = ref(false);
const packingForm = useForm({ name: '', address: '' });
const editingPackingHouseId = ref(null);

async function loadPackingHouses() {
    loadingPackings.value = true;
    try {
        const res = await axios.get(route('api.packing-houses'));
        packingHouses.value = res.data;
    } finally {
        loadingPackings.value = false;
    }
}

function savePackingHouse() {
    const wasEditing = editingPackingHouseId.value !== null;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            packingForm.reset();
            editingPackingHouseId.value = null;
            loadPackingHouses();
            Swal.fire({ icon: 'success', title: wasEditing ? 'Packing actualizado' : 'Packing creado', timer: 1000, showConfirmButton: false });
        },
        onError: () => Swal.fire('Error', 'No se pudo guardar', 'error'),
    };

    if (editingPackingHouseId.value !== null) {
        packingForm.put(route('packing-houses.update', editingPackingHouseId.value), options);
    } else {
        packingForm.post(route('packing-houses.store'), options);
    }
}

function editPackingHouse(packingHouse) {
    editingPackingHouseId.value = packingHouse.value;
    packingForm.name = packingHouse.name;
    packingForm.address = packingHouse.address || '';
}

function cancelEditPackingHouse() {
    editingPackingHouseId.value = null;
    packingForm.reset();
    packingForm.clearErrors();
}

function deletePackingHouse(id) {
    Swal.fire({
        title: '¿Eliminar packing?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('packing-houses.delete', id)).then(() => {
                loadPackingHouses();
                Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar. Puede tener despachos asociados.', 'error'));
        }
    });
}

// === CLASIFICACIONES ===
const classifications = ref([]);
const loadingClassifications = ref(false);
const selectedFruitId = ref('');
const classForm = ref({ fruit_id: '', type: '', value: '', sort_order: 0 });
const typeLabels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };

async function loadClassifications() {
    if (!selectedFruitId.value) { classifications.value = []; return; }
    loadingClassifications.value = true;
    try {
        const res = await axios.get(route('api.fruit-classifications.index'), { params: { fruit_id: selectedFruitId.value } });
        classifications.value = res.data;
    } finally {
        loadingClassifications.value = false;
    }
}

watch(selectedFruitId, () => {
    classForm.value.fruit_id = selectedFruitId.value;
    loadClassifications();
});

async function saveClassification() {
    if (!classForm.value.fruit_id || !classForm.value.type || !classForm.value.value) {
        Swal.fire('Error', 'Complete todos los campos', 'error');
        return;
    }
    try {
        classForm.value.sort_order = classifications.value.filter(c => c.type === classForm.value.type).length;
        await axios.post(route('api.fruit-classifications.store'), classForm.value);
        classForm.value.value = '';
        loadClassifications();
        Swal.fire({ icon: 'success', title: 'Clasificación creada', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

// Posición dentro de su tipo, para mostrar y habilitar los botones de subir/bajar
const classificationRows = computed(() => {
    const totals = {};
    const counters = {};
    classifications.value.forEach(c => { totals[c.type] = (totals[c.type] || 0) + 1; });
    return classifications.value.map(c => {
        counters[c.type] = (counters[c.type] || 0) + 1;
        return { ...c, pos: counters[c.type], total: totals[c.type] };
    });
});

async function moveClassification(c, direction) {
    const ids = classifications.value.filter(x => x.type === c.type).map(x => x.id);
    const from = ids.indexOf(c.id);
    const to = from + direction;
    if (to < 0 || to >= ids.length) return;
    [ids[from], ids[to]] = [ids[to], ids[from]];
    try {
        await axios.post(route('api.fruit-classifications.reorder'), { fruit_id: selectedFruitId.value, type: c.type, ids });
        await loadClassifications();
    } catch {
        Swal.fire('Error', 'No se pudo cambiar el orden', 'error');
    }
}

function deleteClassification(id) {
    Swal.fire({
        title: '¿Eliminar clasificación?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('api.fruit-classifications.delete', id)).then(() => {
                loadClassifications();
                Swal.fire({ icon: 'success', title: 'Eliminada', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar', 'error'));
        }
    });
}

// Cargar datos al mostrar
watch(() => props.show, (val) => {
    if (val) {
        loadExporters();
        loadPackingHouses();
        loadBinTypes();
        loadBoxTypes();
        loadCarriers();
    }
});

// === BIN TYPES ===
const binTypes = ref([]);
const loadingBinTypes = ref(false);
const binTypeForm = ref({ name: '' });
const editingBinTypeId = ref(null);
const editingBinTypeIsActive = ref(true);

async function loadBinTypes() {
    loadingBinTypes.value = true;
    try {
        const res = await axios.get(route('api.bin-types'));
        binTypes.value = res.data;
    } finally {
        loadingBinTypes.value = false;
    }
}

async function saveBinType() {
    if (!binTypeForm.value.name) return;
    const wasEditing = editingBinTypeId.value !== null;
    try {
        if (editingBinTypeId.value !== null) {
            await axios.put(route('bin-types.update', editingBinTypeId.value), {
                name: binTypeForm.value.name,
                is_active: editingBinTypeIsActive.value,
            });
        } else {
            await axios.post(route('bin-types.store'), binTypeForm.value);
        }
        binTypeForm.value.name = '';
        editingBinTypeId.value = null;
        loadBinTypes();
        Swal.fire({ icon: 'success', title: wasEditing ? 'Tipo de bin actualizado' : 'Tipo de bin creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function editBinType(item) {
    editingBinTypeId.value = item.value;
    editingBinTypeIsActive.value = item.is_active !== false;
    binTypeForm.value.name = item.name || item.label;
}

function cancelEditBinType() {
    editingBinTypeId.value = null;
    binTypeForm.value.name = '';
}

function toggleBinType(item) {
    axios.put(route('bin-types.update', item.value), { name: item.name || item.label, is_active: !item.is_active }).then(() => {
        loadBinTypes();
    }).catch(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
}

function deleteBinType(id) {
    Swal.fire({
        title: '¿Eliminar tipo de bin?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('bin-types.delete', id)).then(() => {
                loadBinTypes();
                Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar. Puede tener despachos asociados.', 'error'));
        }
    });
}

// === BOX TYPES ===
const boxTypes = ref([]);
const loadingBoxTypes = ref(false);
const boxTypeForm = ref({ name: '' });
const editingBoxTypeId = ref(null);
const editingBoxTypeIsActive = ref(true);

async function loadBoxTypes() {
    loadingBoxTypes.value = true;
    try {
        const res = await axios.get(route('api.box-types'));
        boxTypes.value = res.data;
    } finally {
        loadingBoxTypes.value = false;
    }
}

async function saveBoxType() {
    if (!boxTypeForm.value.name) return;
    const wasEditing = editingBoxTypeId.value !== null;
    try {
        if (editingBoxTypeId.value !== null) {
            await axios.put(route('box-types.update', editingBoxTypeId.value), {
                name: boxTypeForm.value.name,
                is_active: editingBoxTypeIsActive.value,
            });
        } else {
            await axios.post(route('box-types.store'), boxTypeForm.value);
        }
        boxTypeForm.value.name = '';
        editingBoxTypeId.value = null;
        loadBoxTypes();
        Swal.fire({ icon: 'success', title: wasEditing ? 'Tipo de caja actualizado' : 'Tipo de caja creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function editBoxType(item) {
    editingBoxTypeId.value = item.value;
    editingBoxTypeIsActive.value = item.is_active !== false;
    boxTypeForm.value.name = item.name || item.label;
}

function cancelEditBoxType() {
    editingBoxTypeId.value = null;
    boxTypeForm.value.name = '';
}

function toggleBoxType(item) {
    axios.put(route('box-types.update', item.value), { name: item.name || item.label, is_active: !item.is_active }).then(() => {
        loadBoxTypes();
    }).catch(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
}

function deleteBoxType(id) {
    Swal.fire({
        title: '¿Eliminar tipo de caja?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('box-types.delete', id)).then(() => {
                loadBoxTypes();
                Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar. Puede tener despachos asociados.', 'error'));
        }
    });
}

// === CARRIERS ===
const carriers = ref([]);
const loadingCarriers = ref(false);
const carrierForm = ref({ name: '' });
const editingCarrierId = ref(null);
const editingCarrierIsActive = ref(true);

async function loadCarriers() {
    loadingCarriers.value = true;
    try {
        const res = await axios.get(route('api.carriers'));
        carriers.value = res.data;
    } finally {
        loadingCarriers.value = false;
    }
}

async function saveCarrier() {
    if (!carrierForm.value.name) return;
    const wasEditing = editingCarrierId.value !== null;
    try {
        if (editingCarrierId.value !== null) {
            await axios.put(route('carriers.update', editingCarrierId.value), {
                name: carrierForm.value.name,
                is_active: editingCarrierIsActive.value,
            });
        } else {
            await axios.post(route('carriers.store'), carrierForm.value);
        }
        carrierForm.value.name = '';
        editingCarrierId.value = null;
        loadCarriers();
        Swal.fire({ icon: 'success', title: wasEditing ? 'Transportista actualizado' : 'Transportista creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function editCarrier(item) {
    editingCarrierId.value = item.value;
    editingCarrierIsActive.value = item.is_active !== false;
    carrierForm.value.name = item.name || item.label;
}

function cancelEditCarrier() {
    editingCarrierId.value = null;
    carrierForm.value.name = '';
}

function toggleCarrier(item) {
    axios.put(route('carriers.update', item.value), { name: item.name || item.label, is_active: !item.is_active }).then(() => {
        loadCarriers();
    }).catch(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
}

function deleteCarrier(id) {
    Swal.fire({
        title: '¿Eliminar transportista?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('carriers.delete', id)).then(() => {
                loadCarriers();
                Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false });
            }).catch(() => Swal.fire('Error', 'No se pudo eliminar. Puede tener despachos asociados.', 'error'));
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
                        <i class="fas fa-cogs text-primary me-2 fs-8"></i>
                        Gestionar Catálogos de Producción
                    </h5>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3 flex-nowrap" style="overflow-x: auto;">
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'exporters' }" href="#" @click.prevent="activeTab = 'exporters'">
                                <i class="fas fa-building me-1"></i>Exportadoras
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'packings' }" href="#" @click.prevent="activeTab = 'packings'">
                                <i class="fas fa-warehouse me-1"></i>Packings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'binTypes' }" href="#" @click.prevent="activeTab = 'binTypes'">
                                <i class="fas fa-pallet me-1"></i>Tipos Bins
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'boxTypes' }" href="#" @click.prevent="activeTab = 'boxTypes'">
                                <i class="fas fa-box me-1"></i>Tipos Cajas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'carriers' }" href="#" @click.prevent="activeTab = 'carriers'">
                                <i class="fas fa-truck me-1"></i>Transportistas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{ active: activeTab === 'classifications' }" href="#" @click.prevent="activeTab = 'classifications'">
                                <i class="fas fa-tags me-1"></i>Clasificaciones
                            </a>
                        </li>
                    </ul>

                    <!-- TAB: EXPORTADORAS -->
                    <div v-show="activeTab === 'exporters'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <input v-model="exporterForm.name" type="text" class="form-control form-control-sm" placeholder="Nombre exportadora" />
                            </div>
                            <div class="col-md-3">
                                <input v-model="exporterForm.rut" type="text" class="form-control form-control-sm" placeholder="RUT (opcional)" />
                            </div>
                            <div class="col-md-3">
                                <input v-model="exporterForm.contact" type="text" class="form-control form-control-sm" placeholder="Contacto (opcional)" />
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" @click="saveExporter" :disabled="!exporterForm.name || exporterForm.processing">
                                        <i :class="editingExporterId !== null ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ editingExporterId !== null ? 'Guardar' : 'Agregar' }}
                                    </button>
                                    <button v-if="editingExporterId !== null" class="btn btn-sm btn-light" @click="cancelEditExporter" title="Cancelar edición">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th>RUT</th><th>Contacto</th><th style="width:80px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="e in exporters" :key="e.value" :class="{ 'table-warning': String(editingExporterId) === String(e.value) }">
                                        <td>{{ e.name }}</td>
                                        <td>{{ e.rut || '-' }}</td>
                                        <td>{{ e.contact || '-' }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="editExporter(e)" title="Editar">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm p-1" @click="deleteExporter(e.value)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="exporters.length === 0">
                                        <td colspan="4" class="text-center text-muted">Sin exportadoras.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: PACKINGS -->
                    <div v-show="activeTab === 'packings'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <input v-model="packingForm.name" type="text" class="form-control form-control-sm" placeholder="Nombre del packing" />
                            </div>
                            <div class="col-md-5">
                                <input v-model="packingForm.address" type="text" class="form-control form-control-sm" placeholder="Dirección (opcional)" />
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" @click="savePackingHouse" :disabled="!packingForm.name || packingForm.processing">
                                        <i :class="editingPackingHouseId !== null ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ editingPackingHouseId !== null ? 'Guardar' : 'Agregar' }}
                                    </button>
                                    <button v-if="editingPackingHouseId !== null" class="btn btn-sm btn-light" @click="cancelEditPackingHouse" title="Cancelar edición">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th>Dirección</th><th style="width:80px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in packingHouses" :key="p.value" :class="{ 'table-warning': String(editingPackingHouseId) === String(p.value) }">
                                        <td>{{ p.label }}</td>
                                        <td>{{ p.address || '-' }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="editPackingHouse(p)" title="Editar">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm p-1" @click="deletePackingHouse(p.value)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="packingHouses.length === 0">
                                        <td colspan="3" class="text-center text-muted">Sin packings.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: TIPOS DE BINS -->
                    <div v-show="activeTab === 'binTypes'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <input v-model="binTypeForm.name" type="text" class="form-control form-control-sm" placeholder="Nombre del tipo de bin" />
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" @click="saveBinType" :disabled="!binTypeForm.name">
                                        <i :class="editingBinTypeId !== null ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ editingBinTypeId !== null ? 'Guardar' : 'Agregar' }}
                                    </button>
                                    <button v-if="editingBinTypeId !== null" class="btn btn-sm btn-light" @click="cancelEditBinType" title="Cancelar edición">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:80px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in binTypes" :key="b.value" :class="{ 'table-warning': String(editingBinTypeId) === String(b.value) }">
                                        <td>{{ b.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="b.is_active !== false" :disabled="String(editingBinTypeId) === String(b.value)" @change="toggleBinType(b)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="editBinType(b)" title="Editar">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm p-1" @click="deleteBinType(b.value)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="binTypes.length === 0">
                                        <td colspan="3" class="text-center text-muted">Sin tipos de bins.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: TIPOS DE CAJAS -->
                    <div v-show="activeTab === 'boxTypes'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <input v-model="boxTypeForm.name" type="text" class="form-control form-control-sm" placeholder="Nombre del tipo de caja" />
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" @click="saveBoxType" :disabled="!boxTypeForm.name">
                                        <i :class="editingBoxTypeId !== null ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ editingBoxTypeId !== null ? 'Guardar' : 'Agregar' }}
                                    </button>
                                    <button v-if="editingBoxTypeId !== null" class="btn btn-sm btn-light" @click="cancelEditBoxType" title="Cancelar edición">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:80px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in boxTypes" :key="b.value" :class="{ 'table-warning': String(editingBoxTypeId) === String(b.value) }">
                                        <td>{{ b.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="b.is_active !== false" :disabled="String(editingBoxTypeId) === String(b.value)" @change="toggleBoxType(b)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="editBoxType(b)" title="Editar">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm p-1" @click="deleteBoxType(b.value)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="boxTypes.length === 0">
                                        <td colspan="3" class="text-center text-muted">Sin tipos de cajas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: TRANSPORTISTAS -->
                    <div v-show="activeTab === 'carriers'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <input v-model="carrierForm.name" type="text" class="form-control form-control-sm" placeholder="Nombre del transportista" />
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" @click="saveCarrier" :disabled="!carrierForm.name">
                                        <i :class="editingCarrierId !== null ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ editingCarrierId !== null ? 'Guardar' : 'Agregar' }}
                                    </button>
                                    <button v-if="editingCarrierId !== null" class="btn btn-sm btn-light" @click="cancelEditCarrier" title="Cancelar edición">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:80px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in carriers" :key="c.value" :class="{ 'table-warning': String(editingCarrierId) === String(c.value) }">
                                        <td>{{ c.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="c.is_active !== false" :disabled="String(editingCarrierId) === String(c.value)" @change="toggleCarrier(c)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="editCarrier(c)" title="Editar">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button class="btn btn-sm p-1" @click="deleteCarrier(c.value)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="carriers.length === 0">
                                        <td colspan="3" class="text-center text-muted">Sin transportistas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: CLASIFICACIONES -->
                    <div v-show="activeTab === 'classifications'">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select v-model="selectedFruitId" class="form-select form-select-sm">
                                    <option :value="''" disabled>Frutal...</option>
                                    <option v-for="f in fruits" :key="f.id" :value="f.id">{{ f.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select v-model="classForm.type" class="form-select form-select-sm">
                                    <option value="">Tipo...</option>
                                    <option value="caliber">Calibre</option>
                                    <option value="color">Color</option>
                                    <option value="quality">Calidad</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input v-model="classForm.value" type="text" class="form-control form-control-sm" placeholder="Valor (ej: XL, Light)" />
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-primary w-100" @click="saveClassification" :disabled="!selectedFruitId || !classForm.type || !classForm.value">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedFruitId" class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Tipo</th><th>Valor</th><th style="width:110px;">Orden</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in classificationRows" :key="c.id">
                                        <td>
                                            <span class="badge" :class="{
                                                'bg-primary': c.type === 'caliber',
                                                'bg-info': c.type === 'color',
                                                'bg-success': c.type === 'quality',
                                            }">{{ typeLabels[c.type] || c.type }}</span>
                                        </td>
                                        <td>{{ c.value }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <button class="btn btn-sm p-1" :disabled="c.pos === 1" @click="moveClassification(c, -1)" title="Subir">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <button class="btn btn-sm p-1" :disabled="c.pos === c.total" @click="moveClassification(c, 1)" title="Bajar">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                                <span class="text-muted">{{ c.pos }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm p-1" @click="deleteClassification(c.id)" title="Eliminar">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="classifications.length === 0">
                                        <td colspan="4" class="text-center text-muted">Sin clasificaciones para este frutal.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>Seleccione un frutal para ver y gestionar sus clasificaciones.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="emit('close')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>
