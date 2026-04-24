<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    templates:       Array,
    terminations:    Array,
    availableFields: Array,
});

const title = 'Plantillas de Finiquito';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

// --- Upload plantilla ---
const uploadForm = useForm({
    name: '',
    file: null,
});

const fileInput = ref(null);

function onFileChange(e) {
    uploadForm.file = e.target.files[0];
}

function submitTemplate() {
    if (!uploadForm.name || !uploadForm.file) {
        Swal.fire('Atención', 'Debes ingresar un nombre y seleccionar un archivo .docx', 'warning');
        return;
    }
    uploadForm.post(route('termination-templates.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Plantilla guardada', showConfirmButton: false, timer: 1500 });
            uploadForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Error', msg, 'error');
        },
    });
}

// --- Generar finiquitos ---
const selectedTemplate   = ref(null);
const selectedTerminations = ref([]);
const isGenerating       = ref(false);

// Filtros
const filterSearch    = ref('');
const filterDateFrom  = ref('');
const filterDateTo    = ref('');

const filteredTerminations = computed(() => {
    if (!props.terminations) return [];
    let rows = props.terminations;

    if (filterSearch.value) {
        const q = filterSearch.value.toLowerCase();
        rows = rows.filter(t => t.label?.toLowerCase().includes(q) || t.causal?.toLowerCase().includes(q));
    }
    if (filterDateFrom.value) {
        rows = rows.filter(t => t.fecha_termino && t.fecha_termino >= filterDateFrom.value);
    }
    if (filterDateTo.value) {
        rows = rows.filter(t => t.fecha_termino && t.fecha_termino <= filterDateTo.value);
    }

    return rows;
});

// Seleccionar / deseleccionar todos
const allSelected = computed(() => {
    if (filteredTerminations.value.length === 0) return false;
    return filteredTerminations.value.every(t => selectedTerminations.value.includes(t.value));
});

function toggleSelectAll() {
    if (allSelected.value) {
        const filteredIds = filteredTerminations.value.map(t => t.value);
        selectedTerminations.value = selectedTerminations.value.filter(id => !filteredIds.includes(id));
    } else {
        const currentIds = new Set(selectedTerminations.value);
        filteredTerminations.value.forEach(t => currentIds.add(t.value));
        selectedTerminations.value = [...currentIds];
    }
}

function toggleTermination(id) {
    const idx = selectedTerminations.value.indexOf(id);
    if (idx >= 0) {
        selectedTerminations.value.splice(idx, 1);
    } else {
        selectedTerminations.value.push(id);
    }
}

function generateDocuments() {
    if (!selectedTemplate.value) {
        Swal.fire('Atención', 'Selecciona una plantilla', 'warning');
        return;
    }
    if (selectedTerminations.value.length === 0) {
        Swal.fire('Atención', 'Selecciona al menos un término', 'warning');
        return;
    }

    isGenerating.value = true;

    const url = route('termination-templates.generate', selectedTemplate.value);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/octet-stream',
        },
        body: JSON.stringify({ termination_ids: selectedTerminations.value }),
    })
    .then(response => {
        if (!response.ok) throw new Error('Error al generar');
        const contentDisposition = response.headers.get('Content-Disposition');
        let fileName = 'Finiquito.docx';
        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?(.+?)"?$/);
            if (match) fileName = match[1];
        }
        return response.blob().then(blob => ({ blob, fileName }));
    })
    .then(({ blob, fileName }) => {
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(blobUrl);
        a.remove();
        Swal.fire({ icon: 'success', title: 'Finiquito(s) generado(s)', showConfirmButton: false, timer: 1500 });
    })
    .catch(() => {
        Swal.fire('Error', 'No se pudieron generar los documentos', 'error');
    })
    .finally(() => {
        isGenerating.value = false;
    });
}

// --- Eliminar plantilla ---
function deleteTemplate(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se eliminará la plantilla permanentemente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('termination-templates.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Plantilla eliminada', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
}

const templateOptions = computed(() => {
    if (!props.templates) return [];
    return props.templates.filter(t => t.is_active).map(t => ({
        value: t.id,
        label: t.name,
    }));
});

// Tab activo
const activeTab = ref('generate');
</script>

<template>
    <Head :title="title" />
    <AppLayout :title="title">
        <Breadcrumb :links="links" />

        <div class="card my-3">
            <div class="card-header" style="background: linear-gradient(135deg, #f5ece8 0%, #faf3f1 100%); border-bottom: 2px solid #c0614a;">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0" style="color: #8b2e1a;">
                            <i class="fas fa-file-signature me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist" style="border-bottom-color: #c0614a;">
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'generate' }" @click="activeTab = 'generate'"
                            :style="activeTab === 'generate' ? 'color: #8b2e1a; border-color: #c0614a #c0614a #fff; font-weight:600;' : ''">
                            <i class="fas fa-file-download me-1"></i> Generar Finiquitos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'templates' }" @click="activeTab = 'templates'"
                            :style="activeTab === 'templates' ? 'color: #8b2e1a; border-color: #c0614a #c0614a #fff; font-weight:600;' : ''">
                            <i class="fas fa-folder-open me-1"></i> Mis Plantillas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'fields' }" @click="activeTab = 'fields'"
                            :style="activeTab === 'fields' ? 'color: #8b2e1a; border-color: #c0614a #c0614a #fff; font-weight:600;' : ''">
                            <i class="fas fa-tags me-1"></i> Campos Disponibles
                        </button>
                    </li>
                </ul>

                <!-- Tab: Generar Finiquitos -->
                <div v-if="activeTab === 'generate'">
                    <div class="card" style="border: 1px solid #e0b5aa;">
                        <div class="card-body">
                            <!-- Paso 1: Plantilla -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">1. Selecciona la plantilla</label>
                                    <select v-model="selectedTemplate" class="form-select form-select-sm">
                                        <option :value="null" disabled selected>Seleccione una plantilla...</option>
                                        <option v-for="tpl in templateOptions" :key="tpl.value" :value="tpl.value">
                                            {{ tpl.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Paso 2: Filtros + Selección -->
                            <label class="form-label small fw-bold">2. Selecciona los términos</label>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <input
                                        v-model="filterSearch"
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Buscar colaborador o causal..."
                                    />
                                </div>
                                <div class="col-md-4">
                                    <input type="date" v-model="filterDateFrom" class="form-control form-control-sm" title="Fecha término desde">
                                </div>
                                <div class="col-md-4">
                                    <input type="date" v-model="filterDateTo" class="form-control form-control-sm" title="Fecha término hasta">
                                </div>
                            </div>

                            <!-- Tabla de términos con checkbox -->
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm table-hover fs-10 mb-0">
                                    <thead class="bg-200 sticky-top">
                                        <tr>
                                            <th style="width: 40px;">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" :checked="allSelected" @change="toggleSelectAll">
                                                </div>
                                            </th>
                                            <th>Colaborador</th>
                                            <th>Causal</th>
                                            <th>Fecha Término</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="t in filteredTerminations"
                                            :key="t.value"
                                            @click="toggleTermination(t.value)"
                                            style="cursor: pointer;"
                                            :class="{ 'bg-soft-primary': selectedTerminations.includes(t.value) }"
                                        >
                                            <td>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" :checked="selectedTerminations.includes(t.value)" @click.stop="toggleTermination(t.value)">
                                                </div>
                                            </td>
                                            <td>{{ t.label }}</td>
                                            <td>{{ t.causal }}</td>
                                            <td>{{ t.fecha_termino ? new Date(t.fecha_termino + 'T12:00:00').toLocaleDateString('es-CL') : '-' }}</td>
                                        </tr>
                                        <tr v-if="filteredTerminations.length === 0">
                                            <td colspan="4" class="text-center text-muted py-3">No hay términos que coincidan con los filtros.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <button
                                    class="btn btn-sm"
                                    style="background-color: #c0614a; color: #fff; border-color: #c0614a;"
                                    @click="generateDocuments"
                                    :disabled="isGenerating || selectedTerminations.length === 0"
                                >
                                    <i class="fas fa-file-word me-1" :class="{ 'fa-spin': isGenerating }"></i>
                                    {{ isGenerating ? 'Generando...' : 'Generar y Descargar' }}
                                </button>
                                <small class="text-muted">
                                    {{ selectedTerminations.length }} término{{ selectedTerminations.length !== 1 ? 's' : '' }} seleccionado{{ selectedTerminations.length !== 1 ? 's' : '' }}
                                    <span v-if="selectedTerminations.length > 1"> — todos en un solo archivo</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Mis Plantillas -->
                <div v-if="activeTab === 'templates'">
                    <!-- Upload -->
                    <div class="card mb-3" style="border: 1px solid #e0b5aa;">
                        <div class="card-body">
                            <h6 class="mb-3" style="color: #8b2e1a;"><i class="fas fa-upload me-1"></i> Subir nueva plantilla</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small">Nombre de la plantilla</label>
                                    <input v-model="uploadForm.name" type="text" class="form-control form-control-sm" placeholder="Ej: Finiquito Art. 159">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small">Archivo Word (.docx)</label>
                                    <input ref="fileInput" type="file" class="form-control form-control-sm" accept=".docx" @change="onFileChange">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-falcon-default btn-sm w-100" @click="submitTemplate" :disabled="uploadForm.processing">
                                        <i class="fas fa-save me-1"></i>
                                        {{ uploadForm.processing ? 'Guardando...' : 'Guardar Plantilla' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de plantillas -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover fs-10 mb-0">
                            <thead style="background-color: #f5ece8;">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(tpl, index) in templates" :key="tpl.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <i class="fas fa-file-word text-primary me-1"></i>
                                        {{ tpl.name }}
                                    </td>
                                    <td>{{ new Date(tpl.created_at).toLocaleDateString('es-CL') }}</td>
                                    <td>
                                        <span class="badge" :class="tpl.is_active ? 'bg-success' : 'bg-secondary'">
                                            {{ tpl.is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-falcon-default" @click="deleteTemplate(tpl.id)" title="Eliminar">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!templates || templates.length === 0">
                                    <td colspan="5" class="text-center text-muted py-3">
                                        No hay plantillas. Sube tu primer archivo .docx con los campos marcados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Campos Disponibles -->
                <div v-if="activeTab === 'fields'">
                    <div class="card" style="border: 1px solid #e0b5aa;">
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Estos son los campos que puedes usar en tu plantilla Word. Escríbelos exactamente así en tu documento .docx y serán reemplazados automáticamente con los datos del colaborador y su término de contrato.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm fs-10 mb-0">
                                    <thead style="background-color: #f5ece8;">
                                        <tr>
                                            <th style="color: #8b2e1a;">Campo</th>
                                            <th style="color: #8b2e1a;">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="field in availableFields" :key="field.field">
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded">{{ field.field }}</code>
                                            </td>
                                            <td>{{ field.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
