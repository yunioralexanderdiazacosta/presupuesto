<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    teams: Array,
});

const selectedTeam = ref('');
const preview = ref(null);
const loadingPreview = ref(false);
const copying = ref(false);

watch(selectedTeam, async (val) => {
    preview.value = null;
    if (!val) return;

    loadingPreview.value = true;
    try {
        const response = await axios.get(route('products.copy.preview'), {
            params: { source_team_id: val },
        });
        preview.value = response.data;
    } catch (error) {
        console.error('Error al obtener preview:', error);
    } finally {
        loadingPreview.value = false;
    }
});

const submit = () => {
    if (!selectedTeam.value) {
        Swal.fire('Atención', 'Seleccione un equipo origen', 'warning');
        return;
    }

    if (preview.value && preview.value.new_products === 0) {
        Swal.fire('Sin productos nuevos', 'Todos los productos del equipo origen ya existen en su equipo.', 'info');
        return;
    }

    Swal.fire({
        title: '¿Confirmar copia de productos?',
        html: `Se copiarán <strong>${preview.value?.new_products || '?'}</strong> productos nuevos desde el equipo seleccionado.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Copiar',
    }).then((result) => {
        if (result.isConfirmed) {
            copying.value = true;
            router.post(route('products.copy'), {
                source_team_id: selectedTeam.value,
            }, {
                preserveScroll: true,
                onSuccess: (page) => {
                    $('#copyProductsModal').modal('hide');
                    const flash = page.props.flash || {};
                    const warnings = flash.copy_warnings || [];

                    let html = flash.success || 'Copia completada.';
                    if (warnings.length > 0) {
                        html += '<br><br><strong>Advertencias:</strong><ul class="text-start small mt-2">';
                        warnings.forEach(w => { html += `<li>${w}</li>`; });
                        html += '</ul>';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Copia completada',
                        html: html,
                    });

                    selectedTeam.value = '';
                    preview.value = null;
                },
                onError: (errors) => {
                    Swal.fire('Error', errors.error || 'Ocurrió un error al copiar productos.', 'error');
                },
                onFinish: () => {
                    copying.value = false;
                },
            });
        }
    });
};

const reset = () => {
    selectedTeam.value = '';
    preview.value = null;
};

defineExpose({ reset });
</script>

<template>
    <div class="modal fade" id="copyProductsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-copy me-2"></i>Copiar productos desde otro equipo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Equipo origen</label>
                        <Multiselect
                            v-model="selectedTeam"
                            :options="teams"
                            placeholder="Seleccione un equipo..."
                            :searchable="true"
                            :can-clear="true"
                            value-prop="value"
                            label="label"
                        />
                    </div>

                    <!-- Preview -->
                    <div v-if="loadingPreview" class="text-center py-3">
                        <i class="fas fa-spinner fa-spin me-1"></i> Analizando...
                    </div>

                    <div v-if="preview && !loadingPreview" class="border rounded p-3 bg-light">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="text-muted small">Total origen</div>
                                <div class="fw-bold fs-7">{{ preview.total_source }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Ya existentes</div>
                                <div class="fw-bold fs-7 text-warning">{{ preview.duplicates }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Nuevos a copiar</div>
                                <div class="fw-bold fs-7 text-success">{{ preview.new_products }}</div>
                            </div>
                        </div>
                        <div v-if="preview.new_products === 0" class="text-center mt-2">
                            <small class="text-muted">No hay productos nuevos para copiar.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button 
                        type="button" 
                        class="btn btn-sm btn-falcon-default"
                        :disabled="!selectedTeam || copying || (preview && preview.new_products === 0)"
                        @click="submit"
                    >
                        <i class="fas fa-copy me-1" :class="{ 'fa-spin': copying }"></i>
                        {{ copying ? 'Copiando...' : 'Copiar productos' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
