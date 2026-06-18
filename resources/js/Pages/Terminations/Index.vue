<script setup>
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Multiselect from '@vueform/multiselect';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

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
    settlement: '',
    vacation_days: '',
    vacation_amount: '',
    indemnification: '',
    notice_month: '',
    years_of_service: '',
    afc_discount: '',
});

const submitting = ref(false);
const formCollapsed = ref(false);

// Auto-calcular Total finiquito como suma de los 4 campos monetarios
watch(
    () => [form.value.vacation_amount, form.value.indemnification, form.value.notice_month, form.value.afc_discount],
    ([vac, ind, mes, afc]) => {
        const sum = (Number(vac) || 0) + (Number(ind) || 0) + (Number(mes) || 0) - (Number(afc) || 0);
        form.value.settlement = sum > 0 ? sum : '';
    }
);

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
                    form.value = { contract_ids: [], causal_termino_id: '', fecha_termino: '', notas: '', settlement: '', vacation_days: '', vacation_amount: '', indemnification: '', notice_month: '', years_of_service: '', afc_discount: '' };
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
const monthFilter = ref('');

const filteredTerminations = computed(() => {
    if (!props.terminations) return [];
    let rows = props.terminations;

    if (monthFilter.value) {
        const [yyyy, mm] = monthFilter.value.split('-');
        rows = rows.filter(t => {
            if (!t.fecha_termino) return false;
            const parts = t.fecha_termino.split('/'); // dd/mm/yyyy
            return parts[1] === mm && parts[2] === yyyy;
        });
    }

    if (term.value) {
        const q = term.value.toLowerCase();
        rows = rows.filter(t =>
            t.employee?.toLowerCase().includes(q) ||
            t.rut?.toLowerCase().includes(q) ||
            t.causal?.toLowerCase().includes(q)
        );
    }

    return rows;
});

const excelHeaders = [
    { label: 'Colaborador',     key: 'employee' },
    { label: 'RUT',             key: 'rut' },
    { label: 'Causal',          key: 'causal' },
    { label: 'Fecha Término',   key: 'fecha_termino' },
    { label: 'Finiquito',       key: 'settlement',       type: 'number' },
    { label: 'Indemnización',   key: 'indemnification',  type: 'number' },
    { label: 'Mes de Aviso',    key: 'notice_month',     type: 'number' },
    { label: 'Días Vacaciones',  key: 'vacation_days',    type: 'number' },
    { label: '$ Monto Vacaciones', key: 'vacation_amount',  type: 'number' },
    { label: 'Años Servicio',    key: 'years_of_service', type: 'number' },
    { label: 'Descuento AFC',   key: 'afc_discount',     type: 'number' },
    { label: 'Notas',           key: 'notas' },
    { label: 'Registrado por',  key: 'created_by' },
    { label: 'Fecha Registro',  key: 'created_at' },
];

const excelData = computed(() =>
    filteredTerminations.value.map(t => ({
        employee:        t.employee || '',
        rut:             t.rut || '',
        causal:          t.causal || '',
        fecha_termino:   t.fecha_termino || '',
        settlement:      t.settlement != null ? Number(t.settlement) : '',
        indemnification: t.indemnification != null ? Number(t.indemnification) : '',
        notice_month:    t.notice_month != null ? Number(t.notice_month) : '',
        vacation_days:   t.vacation_days != null ? Number(t.vacation_days) : '',
        vacation_amount: t.vacation_amount != null ? Number(t.vacation_amount) : '',
        years_of_service: t.years_of_service != null ? Number(t.years_of_service) : '',
        afc_discount:    t.afc_discount != null ? Number(t.afc_discount) : '',
        notas:           t.notas || '',
        created_by:      t.created_by || '',
        created_at:      t.created_at || '',
    }))
);

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
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f0f5f1; border-bottom: 1px solid #c3d4c7; cursor: pointer;" @click="formCollapsed = !formCollapsed">
                        <h6 class="mb-0" style="color: #3d5c45;"><i class="fas fa-plus-circle me-2"></i>Registrar Término de Faena</h6>
                        <i class="fas text-muted" :class="formCollapsed ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                    </div>
                    <div v-show="!formCollapsed" class="card-body">
                        <form @submit.prevent="handleSubmit">
                            <div class="row g-3">

                                <!-- Colaboradores -->
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">
                                        Colaboradores con contrato activo
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
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <small class="text-muted fst-italic">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Actualmente se cargan todos los tipos de contrato activos. El tipo de contrato se indica entre paréntesis.
                                        </small>
                                        <small v-if="form.contract_ids.length" class="text-muted">
                                            &mdash; {{ form.contract_ids.length }} seleccionado(s)
                                        </small>
                                    </div>
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

                                <!-- Notas + Años de servicio + Días de vacaciones -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Notas (opcional)</label>
                                    <textarea
                                        v-model="form.notas"
                                        class="form-control form-control-sm"
                                        rows="1"
                                        maxlength="500"
                                        placeholder="Observaciones adicionales..."
                                    ></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Años de Servicio</label>
                                    <input
                                        v-model="form.years_of_service"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        placeholder="0.00"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Días de Vacaciones</label>
                                    <input
                                        v-model="form.vacation_days"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        placeholder="0.00"
                                    />
                                </div>

                                <!-- $ Monto Vacaciones + Indemnización + Mes de aviso + AFC + Finiquito -->
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">$ Monto Vacaciones</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input
                                            v-model="form.vacation_amount"
                                            type="number"
                                            min="0"
                                            :class="['form-control form-control-sm', form.vacation_amount !== '' && form.vacation_amount !== null ? 'input-filled' : '']"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Indemnización</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input
                                            v-model="form.indemnification"
                                            type="number"
                                            min="0"
                                            :class="['form-control form-control-sm', form.indemnification !== '' && form.indemnification !== null ? 'input-filled' : '']"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Mes de Aviso</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input
                                            v-model="form.notice_month"
                                            type="number"
                                            min="0"
                                            :class="['form-control form-control-sm', form.notice_month !== '' && form.notice_month !== null ? 'input-filled' : '']"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Descuento AFC</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input
                                            v-model="form.afc_discount"
                                            type="number"
                                            min="0"
                                            :class="['form-control form-control-sm', form.afc_discount !== '' && form.afc_discount !== null ? 'input-filled' : '']"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">$ Total finiquito
                                        <span class="text-muted fw-normal" style="font-size: 0.7rem;">(se calcula automáticamente)</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input
                                            v-model="form.settlement"
                                            type="number"
                                            min="0"
                                            :class="['form-control form-control-sm', form.settlement !== '' && form.settlement !== null ? 'input-filled' : '']"
                                            placeholder="0"
                                        />
                                    </div>
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
                    <div class="card-header bg-light d-flex justify-content-between align-items-center gap-2">
                        <h6 class="mb-0 text-dark text-nowrap"><i class="fas fa-history me-2"></i>Historial de Términos</h6>
                        <div class="d-flex align-items-center gap-2 ms-auto">
                            <input
                                v-model="monthFilter"
                                type="month"
                                class="form-control form-control-sm"
                                style="min-width: 150px;"
                                title="Filtrar por mes"
                            />
                            <input
                                v-model="term"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Buscar..."
                                style="min-width: 180px;"
                            />
                            <ExportExcelButton
                                :data="excelData"
                                :headers="excelHeaders"
                                filename="terminos_faena.xlsx"
                                class="btn btn-falcon-default btn-sm text-nowrap"
                            />
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                            <table class="table table-sm table-hover mb-0 fs-10" style="min-width: 1200px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Colaborador</th>
                                        <th>RUT</th>
                                        <th style="max-width: 180px;">Causal</th>
                                        <th>Fecha Término</th>
                                        <th class="text-end">Finiquito</th>
                                        <th class="text-end">Indemnización</th>
                                        <th class="text-end">Mes de Aviso</th>
                                        <th class="text-center">Días Vacaciones</th>
                                        <th class="text-end">$ Monto Vacaciones</th>
                                        <th class="text-center">Años Servicio</th>
                                        <th class="text-end">Descuento AFC</th>
                                        <th>Notas</th>
                                        <th>Registrado por</th>
                                        <th>Fecha Registro</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!filteredTerminations.length">
                                        <td colspan="15" class="text-center text-muted py-3">No hay registros.</td>
                                    </tr>
                                    <tr v-for="t in filteredTerminations" :key="t.id">
                                        <td>{{ t.employee }}</td>
                                        <td>{{ t.rut }}</td>
                                        <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="t.causal">{{ t.causal }}</td>
                                        <td>{{ t.fecha_termino }}</td>
                                        <td class="text-end">{{ t.settlement != null ? '$' + Number(t.settlement).toLocaleString('es-CL') : '—' }}</td>
                                        <td class="text-end">{{ t.indemnification != null ? '$' + Number(t.indemnification).toLocaleString('es-CL') : '—' }}</td>
                                        <td class="text-end">{{ t.notice_month != null ? '$' + Number(t.notice_month).toLocaleString('es-CL') : '—' }}</td>
                                        <td class="text-center">{{ t.vacation_days ?? '—' }}</td>
                                        <td class="text-end">{{ t.vacation_amount != null ? '$' + Number(t.vacation_amount).toLocaleString('es-CL') : '—' }}</td>
                                        <td class="text-center">{{ t.years_of_service ?? '—' }}</td>
                                        <td class="text-end">{{ t.afc_discount != null ? '$' + Number(t.afc_discount).toLocaleString('es-CL') : '—' }}</td>
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

<style scoped>
.input-filled {
    border-color: #4a7055 !important;
    box-shadow: 0 0 0 0.15rem rgba(74, 112, 85, 0.2) !important;
}
</style>
