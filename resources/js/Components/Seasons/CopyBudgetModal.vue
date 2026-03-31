<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    sourceSeason: Object,   // temporada origen (la fila desde donde se clickeó)
    seasons: Array,         // todas las temporadas del equipo (para elegir destino)
});

const emit = defineEmits(['done']);

// ---------------------------------------------------------------------------
// Estado del wizard
// ---------------------------------------------------------------------------
const step = ref(1); // 1 = elegir destino, 2 = elegir tipos
const loading = ref(false);

const targetSeasonId = ref('');

const budgetTypes = ref([
    { key: 'agrochemicals',    label: 'Agroquímicos',   icon: 'fas fa-flask' },
    { key: 'fertilizers',      label: 'Fertilizantes',  icon: 'fas fa-seedling' },
    { key: 'supplies',         label: 'Insumos',        icon: 'fas fa-boxes' },
    { key: 'services',         label: 'Servicios',      icon: 'fas fa-tools' },
    { key: 'harvests',         label: 'Cosecha',        icon: 'fas fa-tractor' },
    { key: 'manpowers',        label: 'Mano de obra',   icon: 'fas fa-users' },
    { key: 'administrations',  label: 'Administración', icon: 'fas fa-building' },
    { key: 'fields',           label: 'Campos',         icon: 'fas fa-map-marked-alt' },
]);

const selectedTypes = ref(budgetTypes.value.map(t => t.key)); // todos seleccionados por defecto

// Resultado final de la copia
const copyResult = ref(null);
const errorMessage = ref('');

// ---------------------------------------------------------------------------
// Opciones para el multiselect de temporada destino (excluir la origen)
// ---------------------------------------------------------------------------
const targetOptions = computed(() => {
    if (!props.seasons || !props.sourceSeason) return [];
    return props.seasons
        .filter(s => String(s.id) !== String(props.sourceSeason.id))
        .map(s => ({ value: s.id, label: s.name }));
});

// ---------------------------------------------------------------------------
// Etiquetas de traducción para el resumen
// ---------------------------------------------------------------------------
const typeLabels = {
    agrochemicals:   'Agroquímicos',
    fertilizers:     'Fertilizantes',
    supplies:        'Insumos',
    services:        'Servicios',
    harvests:        'Cosecha',
    manpowers:       'Mano de obra',
    administrations: 'Administración',
    fields:          'Campos',
};

// ---------------------------------------------------------------------------
// Métodos
// ---------------------------------------------------------------------------
const reset = () => {
    step.value = 1;
    targetSeasonId.value = '';
    selectedTypes.value = budgetTypes.value.map(t => t.key);
    copyResult.value = null;
    errorMessage.value = '';
    loading.value = false;
};

const goToStep2 = () => {
    if (!targetSeasonId.value) {
        Swal.fire({ icon: 'warning', title: 'Selecciona una temporada destino', showConfirmButton: false, timer: 1500 });
        return;
    }
    step.value = 2;
};

const toggleAll = () => {
    if (selectedTypes.value.length === budgetTypes.value.length) {
        selectedTypes.value = [];
    } else {
        selectedTypes.value = budgetTypes.value.map(t => t.key);
    }
};

const allSelected = computed(() =>
    selectedTypes.value.length === budgetTypes.value.length
);

const submitCopy = async () => {
    if (selectedTypes.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Selecciona al menos un tipo', showConfirmButton: false, timer: 1500 });
        return;
    }

    const confirm = await Swal.fire({
        icon: 'question',
        title: '¿Copiar presupuesto?',
        html: `Se copiarán los datos seleccionados desde <strong>${props.sourceSeason.name}</strong> a la temporada destino.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, copiar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: 'rgb(0, 158, 247)',
        cancelButtonColor: '#6e6e6e',
    });

    if (!confirm.isConfirmed) return;

    loading.value = true;
    try {
        const response = await axios.post(route('seasons.copy-budget', props.sourceSeason.id), {
            target_season_id: targetSeasonId.value,
            types: selectedTypes.value,
        });

        copyResult.value = response.data;
        step.value = 3;
    } catch (error) {
        console.error(error);
        errorMessage.value = error.response?.data?.message ?? error.response?.data?.error ?? JSON.stringify(error.response?.data) ?? 'Ocurrió un error inesperado.';
    } finally {
        loading.value = false;
    }
};

const close = () => {
    $('#copyBudgetModal').modal('hide');
    emit('done');
};

// Exponer reset para que el padre lo llame antes de abrir
defineExpose({ reset });
</script>

<template>
    <!-- Modal Bootstrap 5 -->
    <div class="modal fade" id="copyBudgetModal" tabindex="-1" aria-labelledby="copyBudgetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title" id="copyBudgetModalLabel">
                        <i class="fas fa-copy me-2 text-primary"></i>
                        Copiar presupuesto
                        <span v-if="sourceSeason" class="text-muted ms-1" style="font-size: 0.85rem;">
                            — {{ sourceSeason.name }}
                        </span>
                    </h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- Indicador de pasos -->
                    <div v-if="step < 3" class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <span class="badge rounded-pill me-2"
                                  :class="step >= 1 ? 'bg-primary' : 'bg-secondary'">1</span>
                            <span class="small" :class="step >= 1 ? 'fw-semibold' : 'text-muted'">Temporada destino</span>
                        </div>
                        <div class="flex-grow-1 border-top mx-3"></div>
                        <div class="d-flex align-items-center">
                            <span class="badge rounded-pill me-2"
                                  :class="step >= 2 ? 'bg-primary' : 'bg-secondary'">2</span>
                            <span class="small" :class="step >= 2 ? 'fw-semibold' : 'text-muted'">Qué copiar</span>
                        </div>
                    </div>

                    <!-- =========================================================
                         PASO 1: Temporada destino
                    ========================================================= -->
                    <div v-if="step === 1">
                        <p class="text-muted small mb-3">
                            Selecciona la temporada a la que se copiarán los presupuestos.
                            Los cuarteles y subfamilias se mapearán por nombre.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Temporada destino</label>
                            <Multiselect
                                v-model="targetSeasonId"
                                :options="targetOptions"
                                placeholder="Selecciona una temporada..."
                                :searchable="true"
                                noOptionsText="No hay temporadas disponibles"
                                noResultsText="Sin resultados"
                            />
                        </div>
                    </div>

                    <!-- =========================================================
                         PASO 2: Qué copiar
                    ========================================================= -->
                    <div v-if="step === 2">
                        <p class="text-muted small mb-3">
                            Selecciona los tipos de presupuesto que deseas copiar.
                        </p>

                        <!-- Seleccionar todos -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="checkAll"
                                    :checked="allSelected"
                                    @change="toggleAll"
                                />
                                <label class="form-check-label fw-semibold" for="checkAll">
                                    Seleccionar todos
                                </label>
                            </div>
                        </div>

                        <div class="row row-cols-2 g-2">
                            <div v-for="type in budgetTypes" :key="type.key" class="col">
                                <div
                                    class="border rounded p-2 d-flex align-items-center gap-2"
                                    :class="selectedTypes.includes(type.key) ? 'border-primary bg-light' : ''"
                                    style="cursor: pointer;"
                                    @click="selectedTypes.includes(type.key)
                                        ? selectedTypes.splice(selectedTypes.indexOf(type.key), 1)
                                        : selectedTypes.push(type.key)"
                                >
                                    <input
                                        class="form-check-input mt-0 flex-shrink-0"
                                        type="checkbox"
                                        :checked="selectedTypes.includes(type.key)"
                                        @click.stop
                                        @change="selectedTypes.includes(type.key)
                                            ? selectedTypes.splice(selectedTypes.indexOf(type.key), 1)
                                            : selectedTypes.push(type.key)"
                                    />
                                    <i :class="[type.icon, 'text-primary']" style="width: 16px;"></i>
                                    <span class="small">{{ type.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =========================================================
                         ERROR copiable
                    ========================================================= -->
                    <div v-if="errorMessage" class="mt-3">
                        <div class="alert alert-danger py-2 mb-2">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <strong>Error al copiar</strong> — puedes seleccionar y copiar el detalle:
                        </div>
                        <textarea
                            class="form-control form-control-sm font-monospace"
                            rows="4"
                            readonly
                            @click="$event.target.select()"
                        >{{ errorMessage }}</textarea>
                    </div>

                    <!-- =========================================================
                         PASO 3: Resultado
                    ========================================================= -->
                    <div v-if="step === 3 && copyResult">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-check-circle text-success fs-4"></i>
                            <span class="fw-semibold">Copia completada</span>
                        </div>

                        <!-- Tabla de resultados -->
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th class="text-center">Registros copiados</th>
                                    <th class="text-center">Items copiados</th>
                                    <th class="text-center">Items omitidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(data, key) in copyResult.results" :key="key">
                                    <td>{{ typeLabels[key] ?? key }}</td>
                                    <td class="text-center">{{ data.copied }}</td>
                                    <td class="text-center">{{ data.items_copied }}</td>
                                    <td class="text-center">
                                        <span v-if="data.items_skipped > 0" class="text-warning fw-semibold">
                                            {{ data.items_skipped }}
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Advertencias -->
                        <div v-if="copyResult.warnings && copyResult.warnings.length > 0"
                             class="alert alert-warning py-2 mb-0">
                            <p class="fw-semibold mb-1">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Advertencias ({{ copyResult.warnings.length }})
                            </p>
                            <ul class="mb-0 ps-3 small">
                                <li v-for="(w, i) in copyResult.warnings" :key="i">{{ w }}</li>
                            </ul>
                        </div>
                        <div v-else class="alert alert-success py-2 mb-0 small">
                            <i class="fas fa-check me-1"></i>
                            Todo se copió sin discrepancias.
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button v-if="step === 3" type="button" class="btn btn-falcon-default btn-sm" @click="close">
                        Cerrar
                    </button>
                    <template v-else>
                        <button type="button" class="btn btn-falcon-default btn-sm" @click="close">
                            Cancelar
                        </button>
                        <button v-if="step === 2" type="button" class="btn btn-falcon-default btn-sm" @click="step = 1">
                            <i class="fas fa-arrow-left me-1"></i>Atrás
                        </button>
                        <button
                            v-if="step === 1"
                            type="button"
                            class="btn btn-primary btn-sm"
                            @click="goToStep2"
                        >
                            Siguiente <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        <button
                            v-if="step === 2"
                            type="button"
                            class="btn btn-primary btn-sm"
                            :disabled="loading || selectedTypes.length === 0"
                            @click="submitCopy"
                        >
                            <span v-if="loading">
                                <i class="fas fa-spinner fa-spin me-1"></i>Copiando...
                            </span>
                            <span v-else>
                                <i class="fas fa-copy me-1"></i>Copiar presupuesto
                            </span>
                        </button>
                    </template>
                </div>

            </div>
        </div>
    </div>
</template>
