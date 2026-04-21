<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    activeContracts: Array,
    causales: Array,
    terminations: Array,
});

const title = 'Términos de Faena';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: 'Remuneraciones', active: false },
    { title, active: true },
];

// Formulario
const form = ref({
    contract_ids: [],
    causal_termino_id: '',
    fecha_termino: '',
    notas: '',
});

const submitting = ref(false);

const handleSubmit = () => {
    if (!form.value.contract_ids.length) {
        Swal.fire('Atención', 'Debe seleccionar al menos un colaborador.', 'warning');
        return;
    }
    if (!form.value.causal_termino_id) {
        Swal.fire('Atención', 'Debe seleccionar la causal de término.', 'warning');
        return;
    }
    if (!form.value.fecha_termino) {
        Swal.fire('Atención', 'Debe ingresar la fecha de término.', 'warning');
        return;
    }

    const count = form.value.contract_ids.length;
    Swal.fire({
        title: '¿Confirmar Término de Faena?',
        html: `Se registrará el término de faena para <strong>${count}</strong> colaborador(es).<br>Esta acción los dejará <strong>inactivos</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, registrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            submitting.value = true;
            router.post(route('terminations.store'), form.value, {
                onSuccess: () => {
                    form.value = { contract_ids: [], causal_termino_id: '', fecha_termino: '', notas: '' };
                    Swal.fire({
                        icon: 'success',
                        title: 'Registrado',
                        text: 'Término(s) de faena registrado(s) correctamente.',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
                onFinish: () => { submitting.value = false; },
            });
        }
    });
};

// Historial filtro
const term = ref('');
const filteredTerminations = computed(() => {
    if (!props.terminations) return [];
    if (!term.value) return props.terminations;
    const q = term.value.toLowerCase();
    return props.terminations.filter(t =>
        t.employee?.toLowerCase().includes(q) ||
        t.rut?.toLowerCase().includes(q) ||
        t.causal?.toLowerCase().includes(q)
    );
});

const handleAnular = (t) => {
    Swal.fire({
        title: '¿Anular término de faena?',
        html: `Se eliminará el registro y <strong>${t.employee}</strong> quedará <strong>activo</strong> nuevamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('terminations.delete', t.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Anulado',
                        text: 'El término fue anulado y el colaborador reactivado.',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        <Breadcrumb :links="links" />

        <div class="card my-3">
            <div class="card-header" style="background: linear-gradient(135deg, #e4ece6 0%, #f2f6f3 100%); border-bottom: 1px solid #c3d4c7;">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0" style="color: #3d5c45;">
                            <i class="fas fa-user-slash me-2"></i>{{ title }}
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- Formulario de Registro -->
                <div class="card mb-4" style="border: 1px solid #c3d4c7;">
                    <div class="card-header" style="background-color: #f0f5f1; border-bottom: 1px solid #c3d4c7;">
                        <h6 class="mb-0" style="color: #3d5c45;"><i class="fas fa-plus-circle me-2"></i>Registrar Término de Faena</h6>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="handleSubmit">
                            <div class="row g-3">

                                <!-- Colaboradores -->
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        Colaboradores (contrato Faena activos)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <Multiselect
                                        v-model="form.contract_ids"
                                        :options="activeContracts"
                                        mode="tags"
                                        :searchable="true"
                                        :close-on-select="false"
                                        placeholder="Buscar y seleccionar colaborador(es)..."
                                        no-options-text="Sin resultados"
                                        no-results-text="Sin coincidencias"
                                    />
                                    <small v-if="form.contract_ids.length" class="text-muted">
                                        {{ form.contract_ids.length }} seleccionado(s)
                                    </small>
                                </div>

                                <!-- Causal -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">
                                        Causal de Término <span class="text-danger">*</span>
                                    </label>
                                    <select v-model="form.causal_termino_id" class="form-select form-select-sm">
                                        <option value="" disabled selected>Seleccione causal...</option>
                                        <option v-for="c in causales" :key="c.value" :value="c.value">{{ c.label }}</option>
                                    </select>
                                </div>

                                <!-- Fecha de Término -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">
                                        Fecha de Término <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        v-model="form.fecha_termino"
                                        type="date"
                                        class="form-control form-control-sm"
                                    />
                                </div>

                                <!-- Notas -->
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Notas (opcional)</label>
                                    <textarea
                                        v-model="form.notas"
                                        class="form-control form-control-sm"
                                        rows="2"
                                        maxlength="500"
                                        placeholder="Observaciones adicionales..."
                                    ></textarea>
                                </div>

                                <!-- Botón -->
                                <div class="col-12 text-end">
                                    <button
                                        type="submit"
                                        class="btn btn-sm"
                                        style="background-color: #4a7055; color: #fff; border-color: #4a7055;"
                                        :disabled="submitting"
                                    >
                                        <span class="fas fa-check" data-fa-transform="shrink-3 down-2"></span>
                                        <span class="ms-1">{{ submitting ? 'Procesando...' : 'Registrar Término' }}</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Historial -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark"><i class="fas fa-history me-2"></i>Historial de Términos</h6>
                        <input
                            v-model="term"
                            type="text"
                            class="form-control form-control-sm w-auto"
                            placeholder="Buscar..."
                            style="min-width: 200px;"
                        />
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 fs-10">
                                <thead class="table-light">
                                    <tr>
                                        <th>Colaborador</th>
                                        <th>RUT</th>
                                        <th>Causal</th>
                                        <th>Fecha Término</th>
                                        <th>Notas</th>
                                        <th>Registrado por</th>
                                        <th>Fecha Registro</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!filteredTerminations.length">
                                        <td colspan="8" class="text-center text-muted py-3">No hay registros.</td>
                                    </tr>
                                    <tr v-for="t in filteredTerminations" :key="t.id">
                                        <td>{{ t.employee }}</td>
                                        <td>{{ t.rut }}</td>
                                        <td>{{ t.causal }}</td>
                                        <td>{{ t.fecha_termino }}</td>
                                        <td>{{ t.notas ?? '—' }}</td>
                                        <td>{{ t.created_by }}</td>
                                        <td>{{ t.created_at }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-falcon-default btn-sm"
                                                @click="handleAnular(t)"
                                                title="Anular y reactivar colaborador"
                                            >
                                                <span class="fas fa-undo fa-xs"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
