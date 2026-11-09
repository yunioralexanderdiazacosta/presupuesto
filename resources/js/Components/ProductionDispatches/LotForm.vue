<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    dispatch: { type: Object, required: true },
});

function n(val) {
    const num = parseFloat(val);
    return isNaN(num) ? 0 : num;
}

function fmt(val) {
    return (Math.round(n(val) * 100) / 100).toLocaleString('es-CL');
}

const kgDispatched = computed(() => n(props.dispatch.kg_dispatched));

// Otros lotes de la misma guía, para comparar el total recepcionado contra la guía
const otherLotsKg = computed(() =>
    (props.dispatch.lots || [])
        .filter(l => String(l.id) !== String(props.form.id))
        .reduce((sum, l) => sum + n(l.kg_received), 0)
);

const totalReceived = computed(() => otherLotsKg.value + n(props.form.kg_received));
const difference = computed(() => Math.round((totalReceived.value - kgDispatched.value) * 100) / 100);
</script>

<template>
    <form @submit.prevent>
        <div class="card border mb-3">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-soft-info text-info px-2 py-1">
                        <i class="fas fa-warehouse me-1"></i>{{ dispatch.packing_house?.name || '-' }}
                    </span>
                    <span class="badge bg-soft-secondary text-secondary px-2 py-1">
                        <i class="fas fa-file-alt me-1"></i>Guía: {{ dispatch.guide_number }}
                    </span>
                    <span class="badge bg-soft-warning text-warning px-2 py-1 ms-auto">
                        <i class="fas fa-weight-hanging me-1"></i>{{ fmt(dispatch.kg_dispatched) }} kg en guía
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small mb-1">N° Lote (asignado por el packing)</label>
                <input type="text" v-model="form.lot_number" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.lot_number }" required />
                <div v-if="form.errors.lot_number" class="invalid-feedback">{{ form.errors.lot_number }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Fecha Recepción</label>
                <input type="date" v-model="form.reception_date" class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.reception_date }" required />
                <div v-if="form.errors.reception_date" class="invalid-feedback">{{ form.errors.reception_date }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Kg Recepcionados</label>
                <input type="number" v-model.number="form.kg_received" class="form-control form-control-sm" step="0.01" min="0" :class="{ 'is-invalid': form.errors.kg_received }" required />
                <div v-if="form.errors.kg_received" class="invalid-feedback">{{ form.errors.kg_received }}</div>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Observaciones</label>
                <textarea v-model="form.observations" class="form-control form-control-sm" rows="2"></textarea>
            </div>

            <div class="col-12" v-if="n(form.kg_received) > 0">
                <div class="alert py-1 px-2 mb-0 fs-10" :class="Math.abs(difference) > 0.001 ? 'alert-warning' : 'alert-light'">
                    <i class="fas fa-balance-scale me-1"></i>
                    <strong>Recepcionado en total:</strong> {{ fmt(totalReceived) }} kg
                    <span v-if="otherLotsKg > 0">(incluye {{ fmt(otherLotsKg) }} kg de otros lotes de esta guía)</span>
                    —
                    <span v-if="Math.abs(difference) <= 0.001">coincide con la guía</span>
                    <span v-else-if="difference > 0">{{ fmt(difference) }} kg más que la guía</span>
                    <span v-else>{{ fmt(-difference) }} kg menos que la guía</span>
                </div>
            </div>
        </div>
    </form>
</template>
