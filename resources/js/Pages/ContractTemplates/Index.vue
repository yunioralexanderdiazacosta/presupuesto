<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    templates: Array,
    employees: Array,
    availableFields: Array,
});

const title = 'Plantillas de Contrato';
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
    uploadForm.post(route('contract-templates.store'), {
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

// --- Generar contratos ---
const selectedTemplate = ref(null);
const selectedEmployees = ref([]);
const isGenerating = ref(false);

// Filtros de colaboradores
const filterLabor = ref('');
const filterContractType = ref('');
const filterDateFrom = ref('');
const filterDateTo = ref('');

// Labores únicas para el filtro
const uniqueLabors = computed(() => {
    const labors = props.employees.map(e => e.labor).filter(l => l);
    return [...new Set(labors)].sort();
});

// Tipos de contrato únicos
const uniqueContractTypes = computed(() => {
    const types = props.employees.map(e => e.contract_type).filter(t => t);
    return [...new Set(types)].sort();
});

// Empleados filtrados
const filteredEmployees = computed(() => {
    if (!props.employees) return [];
    let rows = props.employees;

    if (filterLabor.value) {
        rows = rows.filter(e => e.labor === filterLabor.value);
    }
    if (filterContractType.value) {
        rows = rows.filter(e => e.contract_type === filterContractType.value);
    }
    if (filterDateFrom.value) {
        rows = rows.filter(e => e.contract_date && e.contract_date >= filterDateFrom.value);
    }
    if (filterDateTo.value) {
        rows = rows.filter(e => e.contract_date && e.contract_date <= filterDateTo.value);
    }

    return rows;
});

// Seleccionar / deseleccionar todos
const allSelected = computed(() => {
    if (filteredEmployees.value.length === 0) return false;
    return filteredEmployees.value.every(e => selectedEmployees.value.includes(e.value));
});

function toggleSelectAll() {
    if (allSelected.value) {
        // Deseleccionar los filtrados
        const filteredIds = filteredEmployees.value.map(e => e.value);
        selectedEmployees.value = selectedEmployees.value.filter(id => !filteredIds.includes(id));
    } else {
        // Agregar los filtrados que faltan
        const currentIds = new Set(selectedEmployees.value);
        filteredEmployees.value.forEach(e => currentIds.add(e.value));
        selectedEmployees.value = [...currentIds];
    }
}

function toggleEmployee(empId) {
    const idx = selectedEmployees.value.indexOf(empId);
    if (idx >= 0) {
        selectedEmployees.value.splice(idx, 1);
    } else {
        selectedEmployees.value.push(empId);
    }
}

function generateContracts() {
    if (!selectedTemplate.value) {
        Swal.fire('Atención', 'Selecciona una plantilla', 'warning');
        return;
    }
    if (selectedEmployees.value.length === 0) {
        Swal.fire('Atención', 'Selecciona al menos un colaborador', 'warning');
        return;
    }

    isGenerating.value = true;

    const url = route('contract-templates.generate', selectedTemplate.value);

    // Usar fetch para descargar el archivo
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/octet-stream',
        },
        body: JSON.stringify({ employee_ids: selectedEmployees.value }),
    })
    .then(response => {
        if (!response.ok) throw new Error('Error al generar');
        const contentDisposition = response.headers.get('Content-Disposition');
        let fileName = 'Contrato.docx';
        if (contentDisposition) {
            const match = contentDisposition.match(/filename="?(.+?)"?$/);
            if (match) fileName = match[1];
        }
        return response.blob().then(blob => ({ blob, fileName }));
    })
    .then(({ blob, fileName }) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
        Swal.fire({ icon: 'success', title: 'Contratos generados', showConfirmButton: false, timer: 1500 });
    })
    .catch(() => {
        Swal.fire('Error', 'No se pudieron generar los contratos', 'error');
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
            router.delete(route('contract-templates.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Plantilla eliminada', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
}

// Opciones de plantillas para el select
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
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-file-contract me-2"></i>Plantillas de Contrato
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'generate' }" @click="activeTab = 'generate'">
                            <i class="fas fa-file-download me-1"></i> Generar Contratos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'templates' }" @click="activeTab = 'templates'">
                            <i class="fas fa-folder-open me-1"></i> Mis Plantillas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" :class="{ active: activeTab === 'fields' }" @click="activeTab = 'fields'">
                            <i class="fas fa-tags me-1"></i> Campos Disponibles
                        </button>
                    </li>
                </ul>

                <!-- Tab: Generar Contratos -->
                <div v-if="activeTab === 'generate'">
                    <div class="card">
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
                            <label class="form-label small fw-bold">2. Selecciona colaboradores</label>
                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <select v-model="filterContractType" class="form-select form-select-sm">
                                        <option value="">Todos los tipos</option>
                                        <option v-for="t in uniqueContractTypes" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select v-model="filterLabor" class="form-select form-select-sm">
                                        <option value="">Todas las labores</option>
                                        <option v-for="l in uniqueLabors" :key="l" :value="l">{{ l }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" v-model="filterDateFrom" class="form-control form-control-sm" placeholder="Desde" title="Fecha contrato desde">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" v-model="filterDateTo" class="form-control form-control-sm" placeholder="Hasta" title="Fecha contrato hasta">
                                </div>
                            </div>

                            <!-- Tabla de colaboradores con checkbox -->
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
                                            <th>Tipo</th>
                                            <th>Labor</th>
                                            <th>Fecha Contrato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="emp in filteredEmployees" :key="emp.value" 
                                            @click="toggleEmployee(emp.value)" 
                                            style="cursor: pointer;"
                                            :class="{ 'bg-soft-primary': selectedEmployees.includes(emp.value) }">
                                            <td>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" :checked="selectedEmployees.includes(emp.value)" @click.stop="toggleEmployee(emp.value)">
                                                </div>
                                            </td>
                                            <td>{{ emp.label }}</td>
                                            <td>{{ emp.contract_type || '-' }}</td>
                                            <td>{{ emp.labor || '-' }}</td>
                                            <td>{{ emp.contract_date ? new Date(emp.contract_date).toLocaleDateString('es-CL') : '-' }}</td>
                                        </tr>
                                        <tr v-if="filteredEmployees.length === 0">
                                            <td colspan="5" class="text-center text-muted py-3">No hay colaboradores que coincidan con los filtros</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <button 
                                    class="btn btn-falcon-default btn-sm"
                                    @click="generateContracts"
                                    :disabled="isGenerating || selectedEmployees.length === 0"
                                >
                                    <i class="fas fa-file-word me-1" :class="{ 'fa-spin': isGenerating }"></i>
                                    {{ isGenerating ? 'Generando...' : 'Generar y Descargar' }}
                                </button>
                                <small class="text-muted">
                                    {{ selectedEmployees.length }} colaborador{{ selectedEmployees.length !== 1 ? 'es' : '' }} seleccionado{{ selectedEmployees.length !== 1 ? 's' : '' }}
                                    <span v-if="selectedEmployees.length > 1"> — todos en un solo archivo</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Mis Plantillas -->
                <div v-if="activeTab === 'templates'">
                    <!-- Upload -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-upload me-1"></i> Subir nueva plantilla</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small">Nombre de la plantilla</label>
                                    <input v-model="uploadForm.name" type="text" class="form-control form-control-sm" placeholder="Ej: Contrato Plazo Fijo">
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
                            <thead class="bg-200">
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
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Estos son los campos que puedes usar en tu plantilla Word. Escríbelos exactamente así en tu documento .docx y serán reemplazados automáticamente con los datos del colaborador.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm fs-10 mb-0">
                                    <thead class="bg-200">
                                        <tr>
                                            <th>Campo</th>
                                            <th>Descripción</th>
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
