<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    dispatch: Object,
    lot: Object,
    classifications: Object,
    processes: { type: Array, default: () => [] },
    costCenterVarieties: Array,
});

const emit = defineEmits(['close']);

const typeLabels = { caliber: 'Calibre', color: 'Color', quality: 'Calidad' };
const typeIcons = { caliber: 'fa-ruler', color: 'fa-palette', quality: 'fa-star' };

// Frutal de la guía: todos sus cuarteles son del mismo frutal
const selectedFruitId = computed(() => {
    const first = props.dispatch?.cost_centers?.[0]?.cost_center_variety;
    return first?.fruit_id ?? null;
});

// Clasificaciones filtradas por frutal
const currentClassifications = computed(() => {
    if (!selectedFruitId.value || !props.classifications?.[selectedFruitId.value]) return {};
    return props.classifications[selectedFruitId.value];
});

// Agrupar items del dispatch por tipo
const itemsByType = computed(() => {
    if (!props.lot?.items) return {};
    const grouped = {};
    props.lot.items.forEach(item => {
        if (!grouped[item.classification_type]) grouped[item.classification_type] = [];
        grouped[item.classification_type].push(item);
    });
    return grouped;
});

function getItemKg(type, value) {
    // Un lote puede tener ítems de varios procesos: se suman
    return (props.lot?.items || [])
        .filter(i => i.classification_type === type && i.classification_value === value)
        .reduce((sum, i) => sum + Number(i.kg || 0), 0);
}

// Procesos en los que participó el lote, con los kg que aportó a cada uno
const lotProcesses = computed(() => {
    if (!props.lot) return [];
    return (props.processes || [])
        .map(p => ({
            id: p.id,
            number: p.process_number,
            kg: (p.allocations || [])
                .filter(a => String(a.production_lot_id) === String(props.lot.id))
                .reduce((sum, a) => sum + Number(a.kg_to_process || 0), 0),
            matches: (p.allocations || []).some(a => String(a.production_lot_id) === String(props.lot.id)),
        }))
        .filter(p => p.matches);
});

function totalKgByType(type) {
    return (itemsByType.value[type] || []).reduce((sum, i) => sum + Number(i.kg || 0), 0);
}

function formatNumber(val) {
    return Number(val || 0).toLocaleString('es-CL', { maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('es-CL');
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show && dispatch && lot">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-eye text-primary me-2 fs-8"></i>
                        Detalle del Proceso — Lote {{ lot.lot_number }} — Guía {{ dispatch.guide_number }}
                    </h5>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <div class="modal-body">
                    <!-- Info del despacho -->
                    <div class="card border mb-3">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span v-for="c in dispatch.cost_centers" :key="c.id" class="badge bg-soft-primary text-primary px-2 py-1">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ c.cost_center_variety?.cost_center?.name || '-' }}
                                    <small>· {{ c.cost_center_variety?.variety?.name || '-' }}<template v-if="c.cost_center_variety?.fruit"> ({{ c.cost_center_variety.fruit.name }})</template></small>
                                </span>
                                <span class="badge bg-soft-info text-info px-2 py-1" v-if="dispatch.company_reason">
                                    <i class="fas fa-file-invoice me-1"></i>{{ dispatch.company_reason.name }}
                                </span>
                                <span class="badge bg-soft-info text-info px-2 py-1">
                                    <i class="fas fa-building me-1"></i>{{ dispatch.exporter?.name || '-' }}
                                </span>
                                <span v-for="p in lotProcesses" :key="p.id" class="badge bg-soft-success text-success px-2 py-1">
                                    <i class="fas fa-industry me-1"></i>Proceso {{ p.number }} · {{ formatNumber(p.kg) }} kg
                                </span>
                                <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                                    <i class="fas fa-dolly me-1"></i>{{ formatNumber(lot.kg_received) }} kg recepcionados
                                </span>
                                <span class="badge bg-soft-warning text-warning px-2 py-1 ms-auto">
                                    <i class="fas fa-weight-hanging me-1"></i>{{ formatNumber(dispatch.kg_dispatched) }} kg desp.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de kilos -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Fecha Proceso</small>
                                <span class="fw-bold small">{{ formatDate(lot.process_date) }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Kg Proceso</small>
                                <span class="fw-bold small">{{ formatNumber(lot.kg_to_process) }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Kg Export.</small>
                                <span class="fw-bold small text-success">{{ formatNumber(lot.kg_exported) }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Kg Nacional</small>
                                <span class="fw-bold small text-info">{{ formatNumber(lot.kg_national) }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Kg Industrial</small>
                                <span class="fw-bold small text-warning">{{ formatNumber(lot.kg_industrial) }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-2 border rounded bg-white">
                                <small class="text-muted d-block">Kg Descarte</small>
                                <span class="fw-bold small text-danger">{{ formatNumber(lot.kg_waste) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose por Clasificación -->
                    <template v-if="lot.items && lot.items.length > 0">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-chart-pie me-1"></i>Desglose por Clasificación
                        </h6>

                        <div v-for="(values, type) in currentClassifications" :key="type" class="card border mb-2">
                            <div class="card-header py-2 px-3">
                                <span class="fw-semibold">
                                    <i class="fas me-2 text-primary" :class="typeIcons[type] || 'fa-tag'"></i>{{ typeLabels[type] || type }}
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered fs-10 mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th v-for="cls in values" :key="cls.value" class="text-center" style="min-width: 80px;">
                                                {{ cls.value }}
                                            </th>
                                            <th class="text-center" style="min-width: 80px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td v-for="cls in values" :key="cls.value" class="text-center">
                                                {{ formatNumber(getItemKg(type, cls.value)) }}
                                            </td>
                                            <td class="text-center fw-bold">
                                                {{ formatNumber(totalKgByType(type)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <div v-else class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>Este lote no tiene desglose por clasificación.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="emit('close')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>
