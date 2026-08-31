<script setup>
import { ref, watch, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';
import CalculateWorkDayModal from '@/Components/ManPowers/CalculateWorkDayModal2.vue';
import { useInversionOperation } from '@/Composables/useInversionOperation';

const props = defineProps({ form: Object });
const page = usePage();
const { isInversionOp } = useInversionOperation();

watch(() => props.form.operation_id, (newVal) => {
    if (!isInversionOp(newVal, page.props.operations)) {
        props.form.investment_id = null;
    }
});

// === CC & AGRUPACION ===
const selectedGrouping = ref('');
watch(selectedGrouping, (newId) => {
    if (!newId) return;
    const grouping = page.props.groupings?.find(g => g.id == newId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        props.form.cc = grouping.cost_centers.map(cc => cc.id);
    }
});

const selectedCcSurface = computed(() => {
    const selected = (props.form.cc || []).map(String);
    return (page.props.costCenters || [])
        .filter(cc => selected.includes(String(cc.value)))
        .reduce((sum, cc) => sum + (Number(cc.surface) || 0), 0);
});

// === CALCULADORA JORNADAS ===
const valid = ref(false);
const formWorkDay = useForm({ performance: '', floors: '' });

const storeWorkDay = () => {
    onValidated();
    if (valid.value) {
        props.form.workday = (formWorkDay.floors / formWorkDay.performance).toFixed(2).replace(/\.00$/, '');
        $('#calculateWorkDay2').modal('hide');
        formWorkDay.reset();
    }
};

const onCalculated = () => {
    formWorkDay.reset();
    $('#calculateWorkDay2').modal('show');
};

const onValidated = () => {
    formWorkDay.errors = {};
    valid.value = true;
    if (formWorkDay.performance === '') { formWorkDay.errors.performance = 'Este campo es obligatorio'; valid.value = false; }
    if (formWorkDay.floors === '') { formWorkDay.errors.floors = 'Este campo es obligatorio'; valid.value = false; }
};

// === MESES ===
const toggleMonth = (monthValue) => {
    const idx = props.form.months.indexOf(monthValue);
    if (idx >= 0) props.form.months.splice(idx, 1);
    else props.form.months.push(monthValue);
};
const selectAllMonths = () => {
    const all = (page.props.months || []).map(m => m.value);
    props.form.months = (props.form.months.length === all.length) ? [] : [...all];
};
const monthAbbr = (label) => label ? label.substring(0, 3) : '';
</script>
<template>
    <!-- Encabezado -->
    <div class="row g-2 mb-3">
        <!-- Nivel 3 -->
        <div class="col-sm-3">
            <label class="form-label small mb-1">Nivel 3 <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.subfamily_id"
                :options="$page.props.subfamilies"
                placeholder="Seleccione nivel 3"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.subfamily_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.subfamily_id" />
        </div>
        <!-- AgrupaciÃ³n -->
        <div class="col-sm-3">
            <label class="form-label small mb-1">AgrupaciÃ³n CC</label>
            <Multiselect
                v-model="selectedGrouping"
                :options="($page.props.groupings || []).map(g => ({ value: g.id, label: g.name }))"
                placeholder="Seleccione agrupaciÃ³n"
                :searchable="true"
                :close-on-select="true"
                class="multiselect-sm"
            />
        </div>
        <!-- CC -->
        <div class="col-sm-4">
            <label class="form-label small mb-1">
                Centros de Costo <span class="text-danger">*</span>
                <span v-if="form.cc?.length" class="badge bg-primary ms-1" style="font-size:0.65rem;">{{ form.cc.length }} sel.</span>
                <span v-if="form.cc?.length" class="badge bg-info ms-1" style="font-size:0.65rem;"><i class="fas fa-ruler-combined me-1"></i>{{ selectedCcSurface.toLocaleString('es-CL', { maximumFractionDigits: 2 }) }} ha</span>
            </label>
            <Multiselect
                mode="tags"
                v-model="form.cc"
                :options="$page.props.costCenters"
                placeholder="Seleccione CC"
                :searchable="true"
                :close-on-select="false"
                :class="{ 'is-invalid': form.errors.cc }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.cc" />
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Operación <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.operation_id"
                :options="$page.props.operations"
                placeholder="Seleccione operación"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.operation_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.operation_id" />
        </div>
        <div v-if="isInversionOp(form.operation_id, $page.props.operations)" class="col-sm-2">
            <label class="form-label small mb-1">Inversión <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.investment_id"
                :options="$page.props.investments"
                placeholder="Seleccione inversión"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.investment_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.investment_id" />
        </div>
    </div>

    <!-- Tabla -->
    <div class="border rounded" style="overflow:hidden;">
        <table class="table table-sm table-bordered align-middle mb-0 agro-table w-100">
            <colgroup>
                <col style="width:22%;">
                <col style="width:12%;">
                <col style="width:10%;">
                <col>
                <col style="width:10%;">
            </colgroup>
            <thead style="background: linear-gradient(135deg, #6b1a2a 0%, #a0304a 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white px-2 py-1">Nombre del producto</th>
                    <th class="text-white px-2 py-1">Jornadas/ha</th>
                    <th class="text-white px-2 py-1">Precio $</th>
                    <th class="text-white px-2 py-1">Meses</th>
                    <th class="text-white px-2 py-1">Obs.</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color:#faf0f2; border-left:3px solid #a0304a;">
                    <!-- Nombre -->
                    <td class="p-1">
                        <input v-model="form.product_name" type="text"
                            class="form-control form-control-sm agro-input"
                            :class="{ 'is-invalid': form.errors.product_name }"
                            placeholder="Nombre del producto..." autocomplete="off" />
                        <InputError :message="form.errors.product_name" />
                    </td>

                    <!-- Jornadas -->
                    <td class="p-1">
                        <div class="input-group input-group-sm">
                            <input type="number" v-model="form.workday" step="0.01"
                                class="form-control form-control-sm agro-input agro-no-arrows"
                                :class="{ 'is-invalid': form.errors.workday }" />
                            <button type="button" @click="onCalculated()"
                                class="btn btn-outline-secondary px-1"
                                title="Calcular jornadas"
                                style="height:26px; line-height:1; padding:0 4px;">
                                <i class="fas fa-calculator" style="font-size:0.62rem;"></i>
                            </button>
                        </div>
                        <InputError :message="form.errors.workday" />
                    </td>

                    <!-- Precio -->
                    <td class="p-1">
                        <input v-model="form.price" type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors.price }" />
                        <InputError :message="form.errors.price" />
                    </td>

                    <!-- Meses -->
                    <td class="p-1">
                        <div class="d-grid gap-1" style="grid-template-columns: 22px repeat(6, 1fr); grid-template-rows: auto auto;">
                            <button type="button" @click="selectAllMonths()"
                                class="btn px-0 py-0"
                                :class="form.months.length === ($page.props.months||[]).length ? 'btn-primary' : 'btn-outline-secondary'"
                                style="font-size:0.58rem; height:100%; border-radius:3px; grid-row: 1 / 3;" title="Todos">&#10003;&#10003;</button>
                            <button v-for="m in ($page.props.months || [])" :key="m.value"
                                type="button" @click="toggleMonth(m.value)"
                                :class="form.months.some(x => String(x) === String(m.value)) ? 'btn-primary' : 'btn-outline-secondary'"
                                class="btn px-0 py-0"
                                style="font-size:0.62rem; height:18px; border-radius:3px; min-width:0;">{{ monthAbbr(m.label) }}</button>
                        </div>
                        <small v-if="form.errors.months" class="text-danger d-block" style="font-size:0.65rem;">{{ form.errors.months }}</small>
                    </td>

                    <!-- Observaciones -->
                    <td class="p-1">
                        <input v-model="form.observations" type="text"
                            class="form-control form-control-sm agro-input" placeholder="..." />
                        <InputError :message="form.errors.observations" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal calculadora jornadas -->
    <CalculateWorkDayModal @store="storeWorkDay" :form="formWorkDay" />
</template>

<style scoped>
.agro-no-arrows::-webkit-inner-spin-button,
.agro-no-arrows::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.agro-no-arrows { -moz-appearance: textfield; }
.agro-input {
    height: 26px !important;
    min-height: 26px !important;
    max-height: 26px !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    font-size: 0.82rem !important;
    line-height: 22px !important;
}
.agro-table td, .agro-table th {
    vertical-align: middle;
}
.multiselect-sm {
    --ms-py: 2px;
    --ms-px: 6px;
    --ms-font-size: 0.82rem;
    --ms-line-height: 1.4;
    --ms-min-height: 28px;
    --ms-tag-font-size: 0.75rem;
}
</style>
