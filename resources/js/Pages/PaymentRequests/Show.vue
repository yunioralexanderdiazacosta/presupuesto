<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    request: Object,
});

const deleteRequest = () => {
    Swal.fire({
        title: `¿Eliminar ${props.request.number}?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).delete(route('payment-requests.delete', props.request.id), {
                onSuccess: () => {
                    Swal.fire({ icon: 'success', title: 'Eliminada', showConfirmButton: false, timer: 1500 });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout :title="'Solicitud de Pago ' + request.number">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center g-2">
                    <div class="col-12 col-sm-auto d-flex align-items-center flex-wrap pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-money-check-alt me-2"></i>{{ request.number }}
                        </h5>
                        <span :class="'badge bg-' + request.status_color + ' ms-2'">{{ request.status_label }}</span>
                        <span :class="'badge bg-' + request.character_color + ' ms-1'">{{ request.character_label }}</span>
                    </div>
                    <div class="col-12 col-sm-auto ms-sm-auto text-start text-sm-end ps-0">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <Link :href="route('payment-requests.index')" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-arrow-left"></i>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </Link>
                            <a :href="route('payment-requests.pdf', request.id)" target="_blank" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-print"></i>
                                <span class="d-none d-sm-inline-block ms-1">Imprimir PDF</span>
                            </a>
                            <button
                                v-if="request.is_owner && request.status === 'pendiente'"
                                class="btn btn-falcon-default btn-sm"
                                @click="deleteRequest"
                            >
                                <i class="fas fa-trash-alt text-danger"></i>
                                <span class="d-none d-sm-inline-block ms-1">Eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Solicitante</small>
                        <strong>{{ request.user_name }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Fecha</small>
                        <strong>{{ request.date_formatted }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Creada el</small>
                        <strong>{{ request.created_at }}</strong>
                    </div>
                    <div class="col-6 col-md-3" v-if="request.status === 'gestionada'">
                        <small class="text-muted d-block">Gestionada por</small>
                        <strong>{{ request.resolved_by_name }}</strong>
                        <small class="text-muted d-block">{{ request.resolved_at }}</small>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <small class="text-muted d-block mb-1">Centro(s) de Costo</small>
                        <span v-if="request.cost_centers.length">
                            <span v-for="cc in request.cost_centers" :key="cc" class="badge bg-light text-dark border me-1 mb-1">{{ cc }}</span>
                        </span>
                        <span v-else class="text-muted">—</span>
                    </div>
                    <div class="col-12 col-md-6">
                        <small class="text-muted d-block mb-1">Destinatarios</small>
                        <span v-for="u in request.recipients" :key="u.id" class="badge bg-light text-dark border me-1 mb-1">{{ u.name }}</span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12" v-if="request.concept_observations">
                        <small class="text-muted d-block mb-1">Concepto y Observaciones</small>
                        <p class="mb-0">{{ request.concept_observations }}</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <small class="text-muted d-block mb-1">Facturas / Comprobantes</small>
                        <div v-if="request.files && request.files.length" class="d-flex flex-wrap gap-2">
                            <a
                                v-for="f in request.files"
                                :key="f.id"
                                :href="'/storage/' + f.file_path"
                                target="_blank"
                                class="btn btn-falcon-default btn-sm"
                            >
                                <i class="fas fa-paperclip me-1"></i>{{ f.original_name || 'Archivo' }}
                            </a>
                        </div>
                        <span v-else class="text-muted">Sin adjuntos</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
