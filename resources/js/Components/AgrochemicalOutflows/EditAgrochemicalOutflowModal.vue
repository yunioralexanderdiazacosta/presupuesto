<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    outflow: Object, // Fila agrupada con detalle
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    application_order_id: null,
    date: '',
    maquinadas: '',
    observations: '',
    detalle: [],
});

watch(() => props.show, (val) => {
    if (val && props.outflow) {
        form.application_order_id = props.outflow.application_order_id;
        form.date = props.outflow.date;
        form.maquinadas = props.outflow.maquinadas;
        form.observations = props.outflow.observations || '';
        form.detalle = props.outflow.detalle.map(d => ({
            id: d.id,
            cuartel: d.cuartel,
            producto: d.producto,
            cantidad: d.cantidad,
            unidad: d.unidad,
            factura: d.factura,
        }));
        $('#editAgrochemicalOutflowModal').modal('show');
    } else {
        $('#editAgrochemicalOutflowModal').modal('hide');
    }
});

onMounted(() => {
    $('#editAgrochemicalOutflowModal').on('hidden.bs.modal', () => {
        emit('close');
    });
});

function closeModal() {
    $('#editAgrochemicalOutflowModal').modal('hide');
    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '');
    }, 300);
}

const totalCantidad = computed(() => {
    return form.detalle.reduce((sum, d) => sum + parseFloat(d.cantidad || 0), 0);
});

function save() {
    if (!form.date || !form.maquinadas) {
        Swal.fire('Error', 'Fecha y maquinadas son obligatorios', 'error');
        return;
    }

    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán los datos de esta aplicación',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(route('agrochemical-outflows.update'), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: 'La aplicación fue actualizada correctamente',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    emit('saved');
                    closeModal();
                },
                onError: (errors) => {
                    let msg = '';
                    Object.values(errors).forEach(e => { msg += `• ${e}<br>`; });
                    Swal.fire({ icon: 'error', title: 'Error', html: msg });
                },
            });
        }
    });
}
</script>

<template>
    <div class="modal fade" id="editAgrochemicalOutflowModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Aplicación - Orden #{{ form.application_order_id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <!-- Campos generales -->
                    <div class="row mb-3 g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha *</label>
                            <input v-model="form.date" type="date" class="form-control form-control-sm" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Maquinadas *</label>
                            <input v-model.number="form.maquinadas" type="number" step="0.01" class="form-control form-control-sm" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Observaciones</label>
                            <input v-model="form.observations" type="text" class="form-control form-control-sm" placeholder="Opcional..." />
                        </div>
                    </div>

                    <!-- Detalle por cuartel -->
                    <h6 class="mb-2"><i class="fas fa-list me-1"></i>Detalle por Cuartel</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Cuartel</th>
                                    <th>Producto</th>
                                    <th class="text-end" style="width: 150px;">Cantidad</th>
                                    <th>Unidad</th>
                                    <th>Factura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(d, idx) in form.detalle" :key="d.id">
                                    <td>{{ d.cuartel }}</td>
                                    <td>{{ d.producto }}</td>
                                    <td>
                                        <input 
                                            v-model.number="form.detalle[idx].cantidad" 
                                            type="number" 
                                            step="0.01" 
                                            min="0"
                                            class="form-control form-control-sm text-end"
                                        />
                                    </td>
                                    <td>{{ d.unidad }}</td>
                                    <td><small class="text-muted">{{ d.factura }}</small></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">
                                        {{ totalCantidad.toLocaleString('es-CL', {minimumFractionDigits: 2}) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" @click="closeModal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" @click="save" :disabled="form.processing">
                        <i class="fas fa-save me-1"></i>
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
