<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    seasons: Array,   // otras temporadas del equipo (opciones para origen)
});

const emit = defineEmits(['done']);

const sourceSeasonId = ref('');
const loading = ref(false);
const result = ref(null);

const reset = () => {
    sourceSeasonId.value = '';
    loading.value = false;
    result.value = null;
};

const submit = async () => {
    if (!sourceSeasonId.value) {
        Swal.fire({ icon: 'warning', title: 'Selecciona una temporada de origen', showConfirmButton: false, timer: 1500 });
        return;
    }

    const confirm = await Swal.fire({
        icon: 'question',
        title: '¿Copiar cuarteles?',
        html: 'Se copiarán todos los cuarteles y sus variedades desde la temporada seleccionada a la <strong>temporada activa actual</strong>.',
        showCancelButton: true,
        confirmButtonText: 'Sí, copiar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
    });

    if (!confirm.isConfirmed) return;

    loading.value = true;
    result.value = null;

    try {
        const response = await axios.post(route('cost.centers.copy'), {
            source_season_id: sourceSeasonId.value,
        });
        result.value = response.data;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error al copiar',
            text: error.response?.data?.message ?? 'Ocurrió un error inesperado.',
        });
    } finally {
        loading.value = false;
    }
};

const close = () => {
    $('#copyCostCentersModal').modal('hide');
    if (result.value?.success) {
        emit('done');
    }
    reset();
};

defineExpose({ reset });
</script>

<template>
    <div class="modal fade" id="copyCostCentersModal" tabindex="-1" aria-labelledby="copyCostCentersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title" id="copyCostCentersModalLabel">
                        <i class="fas fa-copy me-2 text-primary"></i>
                        Copiar cuarteles desde otra temporada
                    </h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- Resultado exitoso -->
                    <div v-if="result && result.success">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-check-circle text-success fs-4"></i>
                            <span class="fw-semibold">Copia completada</span>
                        </div>
                        <table class="table table-sm table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <td>Cuarteles copiados</td>
                                    <td class="text-center fw-semibold">{{ result.copied_cost_centers }}</td>
                                </tr>
                                <tr>
                                    <td>Variedades copiadas</td>
                                    <td class="text-center fw-semibold">{{ result.copied_varieties }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Formulario -->
                    <div v-else>
                        <div class="alert alert-info d-flex align-items-start py-2 px-3 mb-3 small" role="alert">
                            <i class="fas fa-info-circle me-2 mt-1 flex-shrink-0"></i>
                            <span>
                                Se copiarán todos los cuarteles y sus variedades a la <strong>temporada activa actual</strong>.
                                Los datos de fruta, variedad, parcela y demás catálogos se mantienen.
                            </span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Temporada origen</label>
                            <select v-model="sourceSeasonId" class="form-select form-select-sm">
                                <option value="">Selecciona la temporada de origen...</option>
                                <option v-for="s in seasons" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="close">
                        {{ result?.success ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button
                        v-if="!result?.success"
                        type="button"
                        class="btn btn-primary btn-sm"
                        :disabled="loading || !sourceSeasonId"
                        @click="submit"
                    >
                        <span v-if="loading">
                            <i class="fas fa-spinner fa-spin me-1"></i>Copiando...
                        </span>
                        <span v-else>
                            <i class="fas fa-copy me-1"></i>Copiar cuarteles
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>
