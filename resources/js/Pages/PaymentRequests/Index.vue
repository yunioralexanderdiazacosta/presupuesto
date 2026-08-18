<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

const characterOptions = [
    { value: 'normal', label: 'Normal', icon: 'fas fa-circle-info', activeClass: 'btn-secondary' },
    { value: 'importante', label: 'Importante', icon: 'fas fa-exclamation-circle', activeClass: 'btn-warning' },
    { value: 'urgente', label: 'Urgente', icon: 'fas fa-exclamation-triangle', activeClass: 'btn-danger' },
];

const props = defineProps({
    requests: Array,
    costCenters: Array,
    groupings: Array,
    executors: Array,
    authUserId: Number,
});

// ─── Filtro de vista ─────────────────────────
const viewFilter = ref('todas'); // todas | mias | pendientes-para-mi
const search = ref('');

const filteredRequests = computed(() => {
    return props.requests.filter(r => {
        let matchView = true;
        if (viewFilter.value === 'mias') matchView = r.is_owner;
        if (viewFilter.value === 'pendientes-para-mi') matchView = r.is_recipient && r.status === 'pendiente';

        const matchSearch = !search.value ||
            r.number.toLowerCase().includes(search.value.toLowerCase()) ||
            r.user_name.toLowerCase().includes(search.value.toLowerCase()) ||
            (r.concept_observations && r.concept_observations.toLowerCase().includes(search.value.toLowerCase()));

        return matchView && matchSearch;
    });
});

const pendingForMeCount = computed(() => props.requests.filter(r => r.is_recipient && r.status === 'pendiente').length);

// ─── Crear solicitud ─────────────────────────
const createForm = useForm({
    date: new Date().toISOString().split('T')[0],
    character: 'normal',
    concept_observations: '',
    files: [],
    cost_center_ids: [],
    user_ids: [],
});

const fileInput = ref(null);
const selectedGrouping = ref(null);
const expandedCC = ref(false);

const selectedCostCenters = computed({
    get: () => createForm.cost_center_ids,
    set: (val) => { createForm.cost_center_ids = val; },
});

const selectedRecipients = computed({
    get: () => createForm.user_ids,
    set: (val) => { createForm.user_ids = val; },
});

const applyGrouping = () => {
    if (!selectedGrouping.value) return;
    const grouping = (props.groupings || []).find(g => g.id == selectedGrouping.value);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        selectedCostCenters.value = grouping.cost_centers.map(cc => cc.id);
    }
};

const onFileChange = (e) => {
    createForm.files = [...createForm.files, ...Array.from(e.target.files)];
    e.target.value = '';
};

const removeFile = (index) => {
    createForm.files.splice(index, 1);
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.date = new Date().toISOString().split('T')[0];
    createForm.character = 'normal';
    createForm.files = [];
    selectedGrouping.value = null;
    expandedCC.value = false;
    if (fileInput.value) fileInput.value.value = '';
    $('#createPaymentRequestModal').modal('show');
};

const submitCreate = () => {
    createForm.post(route('payment-requests.store'), {
        forceFormData: true,
        onSuccess: () => {
            $('#createPaymentRequestModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Solicitud enviada',
                text: 'Se notificó por correo a los destinatarios seleccionados.',
                showConfirmButton: false,
                timer: 2000,
            });
        },
        onError: () => {
            Swal.fire('Error', 'Revisa los campos marcados en el formulario.', 'error');
        },
    });
};

// ─── Eliminar solicitud ──────────────────────
const deleteRequest = (paymentRequest) => {
    Swal.fire({
        title: `¿Eliminar ${paymentRequest.number}?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).delete(route('payment-requests.delete', paymentRequest.id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminada', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Solicitudes de Pago">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center g-2">
                    <div class="col-12 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-money-check-alt me-2"></i>Solicitudes de Pago
                        </h5>
                    </div>
                    <div class="col-12 col-sm-auto ms-sm-auto text-start text-sm-end ps-0">
                        <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                            <i class="fas fa-plus"></i>
                            <span class="d-none d-sm-inline-block ms-1">Nueva Solicitud</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Filtros -->
                <div class="row mb-3 g-2">
                    <div class="col-12 col-md-5">
                        <input
                            type="text"
                            v-model="search"
                            class="form-control form-control-sm"
                            placeholder="Buscar por folio, solicitante o concepto..."
                        >
                    </div>
                    <div class="col-12 col-md-7 d-flex gap-2 flex-wrap">
                        <button
                            class="btn btn-sm"
                            :class="viewFilter === 'todas' ? 'btn-primary' : 'btn-falcon-default'"
                            @click="viewFilter = 'todas'"
                        >Todas</button>
                        <button
                            class="btn btn-sm"
                            :class="viewFilter === 'mias' ? 'btn-primary' : 'btn-falcon-default'"
                            @click="viewFilter = 'mias'"
                        >Creadas por mí</button>
                        <button
                            class="btn btn-sm"
                            :class="viewFilter === 'pendientes-para-mi' ? 'btn-primary' : 'btn-falcon-default'"
                            @click="viewFilter = 'pendientes-para-mi'"
                        >
                            Pendientes para mí
                            <span v-if="pendingForMeCount > 0" class="badge bg-danger ms-1">{{ pendingForMeCount }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tabla desktop -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-sm table-hover fs-10 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Carácter</th>
                                <th>Centro(s) de Costo</th>
                                <th>Solicitante</th>
                                <th>Destinatarios</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in filteredRequests" :key="r.id">
                                <td class="fw-semi-bold">
                                    <Link :href="route('payment-requests.show', r.id)" class="text-decoration-none">{{ r.number }}</Link>
                                </td>
                                <td>{{ r.date_formatted }}</td>
                                <td><span :class="'badge bg-' + r.character_color">{{ r.character_label }}</span></td>
                                <td class="text-truncate" style="max-width: 180px;">{{ r.cost_centers.join(', ') || '—' }}</td>
                                <td>{{ r.user_name }}</td>
                                <td class="text-truncate" style="max-width: 160px;">{{ r.recipients.map(u => u.name).join(', ') }}</td>
                                <td class="text-center">
                                    <span :class="'badge bg-' + r.status_color">{{ r.status_label }}</span>
                                    <div v-if="r.resolved_by_name" class="text-muted" style="font-size: 0.65rem;">
                                        {{ r.resolved_by_name }} · {{ r.resolved_at }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <Link :href="route('payment-requests.show', r.id)" class="btn btn-sm btn-falcon-default me-1" v-tooltip="'Ver detalle'">
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                    <a :href="route('payment-requests.pdf', r.id)" target="_blank" class="btn btn-sm btn-falcon-default me-1" v-tooltip="'Imprimir PDF'">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <div v-if="r.files && r.files.length" class="dropdown d-inline-block me-1">
                                        <button class="btn btn-sm btn-falcon-default dropdown-toggle" type="button" data-bs-toggle="dropdown" v-tooltip="'Ver adjuntos'">
                                            <i class="fas fa-paperclip"></i> {{ r.files.length }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li v-for="f in r.files" :key="f.id">
                                                <a class="dropdown-item" :href="'/storage/' + f.file_path" target="_blank">
                                                    <i class="fas fa-file me-1"></i>{{ f.original_name || 'Archivo' }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <button
                                        v-if="r.is_owner && r.status === 'pendiente'"
                                        class="btn btn-sm btn-falcon-default"
                                        @click="deleteRequest(r)"
                                        v-tooltip="'Eliminar'"
                                    >
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="8" class="text-center text-muted py-4">
                                    No hay solicitudes de pago registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cards mobile -->
                <div class="d-md-none">
                    <div v-for="r in filteredRequests" :key="r.id" class="card mb-2 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <Link :href="route('payment-requests.show', r.id)" class="fw-bold text-primary text-decoration-none">{{ r.number }}</Link>
                                    <div class="text-muted small">{{ r.date_formatted }} · {{ r.user_name }}</div>
                                </div>
                                <span :class="'badge bg-' + r.status_color">{{ r.status_label }}</span>
                            </div>
                            <div class="mb-1">
                                <span :class="'badge bg-' + r.character_color">{{ r.character_label }}</span>
                            </div>
                            <div v-if="r.cost_centers.length" class="small mb-1">
                                <strong>CC:</strong> {{ r.cost_centers.join(', ') }}
                            </div>
                            <div class="small mb-1">
                                <strong>Destinatarios:</strong> {{ r.recipients.map(u => u.name).join(', ') }}
                            </div>
                            <div v-if="r.concept_observations" class="small text-muted mb-2">{{ r.concept_observations }}</div>
                            <div class="d-flex gap-2">
                                <Link :href="route('payment-requests.show', r.id)" class="btn btn-sm btn-falcon-default flex-fill">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </Link>
                                <a :href="route('payment-requests.pdf', r.id)" target="_blank" class="btn btn-sm btn-falcon-default flex-fill">
                                    <i class="fas fa-print me-1"></i>PDF
                                </a>
                                <div v-if="r.files && r.files.length" class="dropdown flex-fill">
                                    <button class="btn btn-sm btn-falcon-default dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-paperclip me-1"></i>Adjuntos ({{ r.files.length }})
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li v-for="f in r.files" :key="f.id">
                                            <a class="dropdown-item" :href="'/storage/' + f.file_path" target="_blank">
                                                <i class="fas fa-file me-1"></i>{{ f.original_name || 'Archivo' }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <button
                                    v-if="r.is_owner && r.status === 'pendiente'"
                                    class="btn btn-sm btn-falcon-default"
                                    @click="deleteRequest(r)"
                                >
                                    <i class="fas fa-trash-alt text-danger"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="filteredRequests.length === 0" class="text-center text-muted py-4">
                        No hay solicitudes de pago registradas
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nueva Solicitud -->
        <Modal :id="'createPaymentRequestModal'" maxWidth="lg">
            <template #header>
                <div class="d-flex align-items-center gap-2 mb-3 text-start">
                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.3rem;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </span>
                    <span>
                        <span class="fw-bold" style="font-size: 1.15rem; color: #2d3748;">Nueva Solicitud de Pago</span>
                        <br>
                        <span class="text-muted" style="font-size: 0.82rem;">Se notificará por correo a los encargados de ejecutar el pago</span>
                    </span>
                </div>
            </template>

            <template #body>
                <form @submit.prevent="submitCreate" class="pt-3">
                    <!-- Datos Generales -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Datos Generales</h6>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small mb-1">Fecha <span class="text-danger">*</span></label>
                                    <input v-model="createForm.date" type="date" class="form-control form-control-sm" :class="{ 'is-invalid': createForm.errors.date }">
                                    <InputError :message="createForm.errors.date" />
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label small mb-1 d-block">Carácter <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <button
                                            v-for="opt in characterOptions"
                                            :key="opt.value"
                                            type="button"
                                            @click="createForm.character = opt.value"
                                            class="btn btn-sm flex-fill d-flex align-items-center justify-content-center gap-1"
                                            :class="createForm.character === opt.value ? opt.activeClass : 'btn-outline-secondary'"
                                        >
                                            <i :class="opt.icon"></i>{{ opt.label }}
                                        </button>
                                    </div>
                                    <InputError :message="createForm.errors.character" />
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small mb-1">Facturas / Comprobantes <span class="text-muted fw-normal">(PDF o imagen, opcional, puedes adjuntar varios)</span></label>
                                <input ref="fileInput" type="file" class="d-none" multiple accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange">
                                <div
                                    class="border rounded-3 p-3 text-center"
                                    :class="createForm.files.length ? 'border-success bg-success bg-opacity-10' : 'border-dashed bg-light'"
                                    style="cursor: pointer;"
                                    @click="fileInput.click()"
                                >
                                    <template v-if="!createForm.files.length">
                                        <i class="fas fa-cloud-upload-alt fa-lg text-muted me-2"></i>
                                        <span class="small text-muted">Haz clic para adjuntar uno o más archivos</span>
                                    </template>
                                    <template v-else>
                                        <div class="d-flex flex-column gap-1 text-start">
                                            <div v-for="(f, index) in createForm.files" :key="index" class="d-flex align-items-center justify-content-between">
                                                <span class="small fw-semibold text-truncate">
                                                    <i class="fas fa-file-circle-check text-success me-2"></i>{{ f.name }}
                                                </span>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" @click.stop="removeFile(index)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-2">
                                            <i class="fas fa-plus-circle me-1"></i>Haz clic para agregar más archivos
                                        </div>
                                    </template>
                                </div>
                                <InputError :message="createForm.errors.files" />
                            </div>
                        </div>
                    </div>

                    <!-- Centro(s) de Costo -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="text-primary mb-3"><i class="fas fa-layer-group me-2"></i>Centro(s) de Costo</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Agrupación</label>
                                    <select v-model="selectedGrouping" @change="applyGrouping" class="form-select form-select-sm">
                                        <option :value="null" disabled selected>Seleccione agrupación...</option>
                                        <option v-for="g in (groupings || [])" :key="g.id" :value="g.id">{{ g.name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                        <label class="form-label small mb-0">Centro(s) de Costo <span class="text-danger">*</span></label>
                                        <button
                                            v-if="selectedCostCenters.length > 5"
                                            type="button"
                                            @click="expandedCC = !expandedCC"
                                            class="btn btn-link btn-sm p-0 text-muted"
                                            style="font-size: 0.65rem; text-decoration: none;"
                                        >
                                            <i class="fas" :class="expandedCC ? 'fa-compress-alt' : 'fa-expand-alt'"></i>
                                            {{ expandedCC ? 'Colapsar' : 'Ver todos' }}
                                        </button>
                                    </div>
                                    <Multiselect
                                        v-model="selectedCostCenters"
                                        :options="costCenters"
                                        mode="tags"
                                        :searchable="true"
                                        :close-on-select="false"
                                        placeholder="Seleccione centros de costo..."
                                        :class="['multiselect-blue form-control-sm multiselect-tags-limited', { 'multiselect-tags-expanded': expandedCC }, { 'is-invalid': createForm.errors.cost_center_ids }]"
                                    />
                                    <InputError :message="createForm.errors.cost_center_ids" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Concepto -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="text-primary mb-3"><i class="fas fa-comment-alt me-2"></i>Concepto y Observaciones</h6>
                            <textarea v-model="createForm.concept_observations" class="form-control form-control-sm" rows="3" placeholder="Ej: Pago proveedor X, factura N°..." :class="{ 'is-invalid': createForm.errors.concept_observations }"></textarea>
                            <InputError :message="createForm.errors.concept_observations" />
                        </div>
                    </div>

                    <!-- Destinatarios -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-primary mb-3"><i class="fas fa-paper-plane me-2"></i>Enviar a <span class="text-danger">*</span></h6>
                            <Multiselect
                                v-model="selectedRecipients"
                                :options="executors"
                                mode="tags"
                                :searchable="true"
                                :close-on-select="false"
                                placeholder="Seleccione destinatarios (Ejecutor de Pagos)..."
                                :class="['multiselect-blue form-control-sm', { 'is-invalid': createForm.errors.user_ids }]"
                            />
                            <InputError :message="createForm.errors.user_ids" />
                            <small v-if="!executors || executors.length === 0" class="text-danger d-block mt-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                No hay usuarios con el rol "Ejecutor de Pagos" asignado en este equipo.
                            </small>
                        </div>
                    </div>
                </form>
            </template>

            <template #footer>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="submitCreate"
                    :disabled="createForm.processing"
                >
                    <i class="fas fa-paper-plane me-1"></i>
                    {{ createForm.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
    transition: background-color 0.15s ease;
}
.border-dashed:hover {
    background-color: #eef2f7 !important;
}
</style>
