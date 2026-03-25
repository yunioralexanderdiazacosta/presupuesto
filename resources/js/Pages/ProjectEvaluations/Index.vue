<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    evaluations: Array,
    varieties: Array,
});

// ── Formulario Crear ──────────────────────────────────────────────────────
const showCreateModal = ref(false);
const createForm = ref({ name: '', description: '', target_margin: 15 });

const openCreateModal = () => {
    createForm.value = { name: '', description: '', target_margin: 15 };
    showCreateModal.value = true;
    setTimeout(() => $('#createEvaluationModal').modal('show'), 50);
};

const submitCreate = () => {
    $('#createEvaluationModal').modal('hide');
    router.post(route('project-evaluations.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            Swal.fire({ icon: 'success', title: 'Evaluación creada', showConfirmButton: false, timer: 1200 });
        },
    });
};

// ── Formulario Editar ─────────────────────────────────────────────────────
const editingId = ref(null);
const editForm = ref({ name: '', description: '', target_margin: 15 });

const openEditModal = (ev) => {
    editingId.value = ev.id;
    editForm.value = { name: ev.name, description: ev.description || '', target_margin: ev.target_margin };
    setTimeout(() => $('#editEvaluationModal').modal('show'), 50);
};

const submitEdit = () => {
    $('#editEvaluationModal').modal('hide');
    router.put(route('project-evaluations.update', editingId.value), editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Evaluación actualizada', showConfirmButton: false, timer: 1200 });
        },
    });
};

// ── Eliminar ──────────────────────────────────────────────────────────────
const onDelete = (id) => {
    Swal.fire({
        title: '¿Eliminar evaluación?',
        text: 'Se eliminarán también todas sus filas de composición.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0,158,247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('project-evaluations.delete', id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Eliminada', showConfirmButton: false, timer: 1000 }),
            });
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('es-CL');
};
</script>

<template>
    <AppLayout title="Evaluación de Proyectos Agrícolas">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-seedling me-2"></i>Evaluación de Proyectos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <button class="btn btn-falcon-default btn-sm" @click="openCreateModal">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nueva evaluación</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Sin datos -->
                <div v-if="evaluations.length === 0" class="text-center py-5 text-muted">
                    <i class="fas fa-seedling fa-3x mb-3 text-secondary"></i>
                    <p class="mb-1">No hay evaluaciones creadas aún.</p>
                    <button class="btn btn-sm btn-primary mt-2" @click="openCreateModal">
                        <i class="fas fa-plus me-1"></i>Crear primera evaluación
                    </button>
                </div>

                <!-- Grid de cards -->
                <div v-else class="row g-3">
                    <div
                        v-for="ev in evaluations"
                        :key="ev.id"
                        class="col-12 col-md-6 col-xl-4"
                    >
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <i class="fas fa-file-alt me-1 text-muted"></i>{{ ev.name }}
                                    </h6>
                                    <div class="d-flex gap-1">
                                        <button
                                            class="btn btn-icon btn-active-light-primary w-25px h-25px p-1"
                                            v-tooltip="'Editar'"
                                            @click.stop="openEditModal(ev)"
                                        >
                                            <i class="fas fa-edit" style="font-size:0.65rem;"></i>
                                        </button>
                                        <button
                                            class="btn btn-icon btn-active-light-danger w-25px h-25px p-1"
                                            v-tooltip="'Eliminar'"
                                            @click.stop="onDelete(ev.id)"
                                        >
                                            <i class="fas fa-trash-alt" style="font-size:0.65rem;"></i>
                                        </button>
                                    </div>
                                </div>

                                <p v-if="ev.description" class="text-muted small mb-2">{{ ev.description }}</p>

                                <div class="d-flex gap-3 mb-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-success" style="font-size:1.2rem;">{{ ev.rows_count }}</div>
                                        <small class="text-muted">filas</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold text-info" style="font-size:1.2rem;">{{ ev.target_margin }}%</div>
                                        <small class="text-muted">margen obj.</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-muted small">{{ formatDate(ev.updated_at) }}</div>
                                        <small class="text-muted">actualización</small>
                                    </div>
                                </div>

                                <a
                                    :href="route('project-evaluations.show', ev.id)"
                                    class="btn btn-sm btn-falcon-default w-100"
                                >
                                    <i class="fas fa-chart-bar me-1"></i>Ver evaluación
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear -->
        <div class="modal fade" id="createEvaluationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="fas fa-plus me-2 text-primary"></i>Nueva Evaluación
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input v-model="createForm.name" type="text" class="form-control form-control-sm" placeholder="Ej: Huerto Las Palmas 2026" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea v-model="createForm.description" class="form-control form-control-sm" rows="2" placeholder="Descripción opcional..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Margen objetivo (%)</label>
                            <input v-model.number="createForm.target_margin" type="number" min="0" max="100" step="0.5" class="form-control form-control-sm" />
                            <small class="text-muted">Se usará para calcular la oferta máxima conservando el margen.</small>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-sm btn-primary" @click="submitCreate" :disabled="!createForm.name">
                            <i class="fas fa-save me-1"></i>Crear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar -->
        <div class="modal fade" id="editEvaluationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title">
                            <i class="fas fa-edit me-2 text-primary"></i>Editar Evaluación
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input v-model="editForm.name" type="text" class="form-control form-control-sm" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea v-model="editForm.description" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Margen objetivo (%)</label>
                            <input v-model.number="editForm.target_margin" type="number" min="0" max="100" step="0.5" class="form-control form-control-sm" />
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-sm btn-primary" @click="submitEdit" :disabled="!editForm.name">
                            <i class="fas fa-save me-1"></i>Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
