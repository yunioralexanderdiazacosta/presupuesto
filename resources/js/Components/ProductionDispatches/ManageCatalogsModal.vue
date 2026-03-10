<script setup>
import { ref, watch } from 'vue';
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
    exporterForm.post(route('exporters.store'), {
        preserveScroll: true,
        onSuccess: () => {
            exporterForm.reset();
            loadExporters();
            Swal.fire({ icon: 'success', title: 'Exportadora creada', timer: 1000, showConfirmButton: false });
        },
        onError: () => Swal.fire('Error', 'No se pudo guardar', 'error'),
    });
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
    packingForm.post(route('packing-houses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            packingForm.reset();
            loadPackingHouses();
            Swal.fire({ icon: 'success', title: 'Packing creado', timer: 1000, showConfirmButton: false });
        },
        onError: () => Swal.fire('Error', 'No se pudo guardar', 'error'),
    });
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
        await axios.post(route('api.fruit-classifications.store'), classForm.value);
        classForm.value.value = '';
        classForm.value.sort_order = classifications.value.length;
        loadClassifications();
        Swal.fire({ icon: 'success', title: 'Clasificación creada', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
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
    try {
        await axios.post(route('bin-types.store'), binTypeForm.value);
        binTypeForm.value.name = '';
        loadBinTypes();
        Swal.fire({ icon: 'success', title: 'Tipo de bin creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function toggleBinType(item) {
    axios.put(route('bin-types.update', item.value), { name: item.label, is_active: !item.is_active }).then(() => {
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
    try {
        await axios.post(route('box-types.store'), boxTypeForm.value);
        boxTypeForm.value.name = '';
        loadBoxTypes();
        Swal.fire({ icon: 'success', title: 'Tipo de caja creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function toggleBoxType(item) {
    axios.put(route('box-types.update', item.value), { name: item.label, is_active: !item.is_active }).then(() => {
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
    try {
        await axios.post(route('carriers.store'), carrierForm.value);
        carrierForm.value.name = '';
        loadCarriers();
        Swal.fire({ icon: 'success', title: 'Transportista creado', timer: 1000, showConfirmButton: false });
    } catch {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
}

function toggleCarrier(item) {
    axios.put(route('carriers.update', item.value), { name: item.label, is_active: !item.is_active }).then(() => {
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
                                <button class="btn btn-sm btn-primary w-100" @click="saveExporter" :disabled="!exporterForm.name || exporterForm.processing">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th>RUT</th><th>Contacto</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="e in exporters" :key="e.value">
                                        <td>{{ e.label }}</td>
                                        <td>{{ e.rut || '-' }}</td>
                                        <td>{{ e.contact || '-' }}</td>
                                        <td class="text-center">
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
                                <button class="btn btn-sm btn-primary w-100" @click="savePackingHouse" :disabled="!packingForm.name || packingForm.processing">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th>Dirección</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in packingHouses" :key="p.value">
                                        <td>{{ p.label }}</td>
                                        <td>{{ p.address || '-' }}</td>
                                        <td class="text-center">
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
                                <button class="btn btn-sm btn-primary w-100" @click="saveBinType" :disabled="!binTypeForm.name">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in binTypes" :key="b.value">
                                        <td>{{ b.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="b.is_active !== false" @change="toggleBinType(b)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
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
                                <button class="btn btn-sm btn-primary w-100" @click="saveBoxType" :disabled="!boxTypeForm.name">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in boxTypes" :key="b.value">
                                        <td>{{ b.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="b.is_active !== false" @change="toggleBoxType(b)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
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
                                <button class="btn btn-sm btn-primary w-100" @click="saveCarrier" :disabled="!carrierForm.name">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Nombre</th><th style="width:80px;">Estado</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in carriers" :key="c.value">
                                        <td>{{ c.label }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input class="form-check-input" type="checkbox" :checked="c.is_active !== false" @change="toggleCarrier(c)" />
                                            </div>
                                        </td>
                                        <td class="text-center">
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
                            <div class="col-md-3">
                                <input v-model="classForm.value" type="text" class="form-control form-control-sm" placeholder="Valor (ej: XL, Light)" />
                            </div>
                            <div class="col-md-2">
                                <input v-model="classForm.sort_order" type="number" class="form-control form-control-sm" placeholder="Orden" min="0" />
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-primary w-100" @click="saveClassification" :disabled="!selectedFruitId || !classForm.type || !classForm.value">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedFruitId" class="table-responsive">
                            <table class="table table-sm table-bordered fs-10 mb-0">
                                <thead class="table-light">
                                    <tr><th>Tipo</th><th>Valor</th><th>Orden</th><th style="width:60px;"></th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in classifications" :key="c.id">
                                        <td>
                                            <span class="badge" :class="{
                                                'bg-primary': c.type === 'caliber',
                                                'bg-info': c.type === 'color',
                                                'bg-success': c.type === 'quality',
                                            }">{{ typeLabels[c.type] || c.type }}</span>
                                        </td>
                                        <td>{{ c.value }}</td>
                                        <td>{{ c.sort_order }}</td>
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
