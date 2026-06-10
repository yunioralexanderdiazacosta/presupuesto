<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Components/InputError.vue";

const props = defineProps({ form: Object });
const page = usePage();

// === CC & AGRUPACION ===
const selectedGrouping = ref("");
const expandedCC = ref(false);

watch(selectedGrouping, (newId) => {
    if (!newId) return;
    const grouping = page.props.groupings?.find(g => g.id == newId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        props.form.cc = grouping.cost_centers.map(cc => cc.id);
    }
});

// === UNIDADES ===
const allowedPriceUnits = { 1: [1,2,8], 2: [1,2,8], 3: [3,4], 4: [3,4], 5: [5], 8: [1,2,8] };
const disallowedDoseUnitIds = [6, 7];
const getDoseUnitOptions = () => (page.props.units || []).filter(u => !disallowedDoseUnitIds.includes(u.value));
const getPriceUnitOptions = () => {
    const allowed = allowedPriceUnits[props.form.unit_id];
    return allowed ? (page.props.units || []).filter(u => allowed.includes(u.value)) : (page.props.units || []);
};

watch(() => props.form.unit_id, () => {
    const allowed = getPriceUnitOptions().map(u => u.value);
    if (props.form.unit_id_price && !allowed.includes(props.form.unit_id_price)) {
        props.form.unit_id_price = null;
    }
});

// === MESES ===
const toggleMonth = (monthValue) => {
    const idx = props.form.months.findIndex(m => String(m) === String(monthValue));
    if (idx >= 0) props.form.months.splice(idx, 1);
    else props.form.months.push(parseInt(monthValue));
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
        <div class="col-sm-3">
            <label class="form-label small mb-1">Nivel 3 <span class="text-danger">*</span></label>
            <Multiselect v-model="form.subfamily_id" :options="$page.props.subfamilies"
                placeholder="Seleccione nivel 3" :searchable="true" :close-on-select="true"
                :class="{ 'is-invalid': form.errors.subfamily_id }" class="multiselect-sm" />
            <InputError :message="form.errors.subfamily_id" />
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Agrupacion CC</label>
            <Multiselect v-model="selectedGrouping"
                :options="($page.props.groupings || []).map(g => ({ value: g.id, label: g.name }))"
                placeholder="Seleccione agrupacion" :searchable="true" :close-on-select="true" class="multiselect-sm" />
        </div>
        <div class="col-sm-6">
            <label class="form-label small mb-1">
                Centros de Costo <span class="text-danger">*</span>
                <span v-if="form.cc?.length" class="badge bg-primary ms-1" style="font-size:0.65rem;">{{ form.cc.length }} sel.</span>
            </label>
            <Multiselect mode="tags" v-model="form.cc" :options="$page.props.costCenters"
                placeholder="Seleccione CC" :searchable="true" :close-on-select="false"
                :class="{ 'is-invalid': form.errors.cc }" class="multiselect-sm" />
            <InputError :message="form.errors.cc" />
        </div>
    </div>

    <!-- Tabla producto (fila unica) -->
    <div class="border rounded" style="overflow:hidden;">
        <table class="table table-sm table-bordered align-middle mb-0 w-100" style="font-size:0.78rem;">
            <colgroup>
                <col style="width:22%;">
                <col style="width:9%;">
                <col style="width:6%;">
                <col style="width:7%;">
                <col style="width:6%;">
                <col style="width:8%;">
                <col style="width:7%;">
                <col>
                <col style="width:9%;">
            </colgroup>
            <thead style="background: linear-gradient(135deg, #1a6b3c 0%, #2d9e5f 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white">Producto</th>
                    <th class="text-white">Tipo dosis</th>
                    <th class="text-end text-white">Dosis</th>
                    <th class="text-white">U. dosis</th>
                    <th class="text-end text-white">Mojam.</th>
                    <th class="text-end text-white">Precio $</th>
                    <th class="text-white">U. precio</th>
                    <th class="text-white">Meses</th>
                    <th class="text-white">Obs.</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color:#f0faf5; border-left:3px solid #2d9e5f;">
                    <!-- Producto -->
                    <td class="p-1">
                        <input v-model="form.product_name" type="text"
                            class="form-control form-control-sm agro-input"
                            :class="{ 'is-invalid': form.errors.product_name }"
                            placeholder="Nombre del producto..." />
                        <InputError :message="form.errors.product_name" />
                    </td>

                    <!-- Tipo dosis -->
                    <td class="p-1">
                        <div class="d-flex flex-column gap-0">
                            <div v-for="dt in ($page.props.doseTypes || [])" :key="dt.value" class="form-check mb-0">
                                <input type="radio" v-model="form.dose_type_id" :value="dt.value"
                                    :id="'edit_dt_' + dt.value" class="form-check-input"
                                    style="width:11px; height:11px; margin-top:2px;" />
                                <label :for="'edit_dt_' + dt.value" class="form-check-label ms-1"
                                    style="font-size:0.71rem; cursor:pointer;">{{ dt.label }}</label>
                            </div>
                        </div>
                    </td>

                    <!-- Dosis -->
                    <td class="p-1">
                        <input v-model="form.dose" type="number" step="0.01"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors.dose }" />
                    </td>

                    <!-- Unidad dosis -->
                    <td class="p-1">
                        <select v-model="form.unit_id" class="form-select form-select-sm agro-input"
                            :class="{ 'is-invalid': form.errors.unit_id }">
                            <option value="">—</option>
                            <option v-for="u in getDoseUnitOptions()" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </td>

                    <!-- Mojamiento -->
                    <td class="p-1">
                        <input v-model="form.mojamiento" type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors.mojamiento, 'bg-light': form.dose_type_id != 2 }"
                            :placeholder="form.dose_type_id == 2 ? 'L/ha' : ''"
                            :title="form.dose_type_id != 2 ? 'Solo para tipo mojamiento' : 'Litros agua/ha'" />
                    </td>

                    <!-- Precio -->
                    <td class="p-1">
                        <input :value="form.price ? parseInt(form.price) : ''"
                            @input="form.price = $event.target.value" type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors.price }" />
                    </td>

                    <!-- Unidad precio -->
                    <td class="p-1">
                        <select v-model="form.unit_id_price" class="form-select form-select-sm agro-input"
                            :class="{ 'is-invalid': form.errors.unit_id_price }">
                            <option value="">—</option>
                            <option v-for="u in getPriceUnitOptions()" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
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
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.agro-no-arrows::-webkit-inner-spin-button,
.agro-no-arrows::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.agro-no-arrows { -moz-appearance: textfield; }

.agro-input {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.75rem !important;
    padding: 2px 5px !important;
    width: 100%;
}
.form-label.small { font-size: 0.78rem; }
</style>

<style>
.multiselect-sm {
    font-size: 0.75rem;
    min-height: 0;
    --ms-py: 0.15rem;
    --ms-px: 0.4rem;
    --ms-tag-py: 0rem;
    --ms-tag-px: 0.3rem;
    --ms-tag-font-size: 0.7rem;
    --ms-option-py: 0.2rem;
    --ms-option-px: 0.5rem;
    --ms-option-font-size: 0.75rem;
}
.multiselect-sm .multiselect-option { font-size: 0.8rem; padding: 3px 8px; line-height: 1.9; }
.multiselect-sm .multiselect-tag { font-size: 0.75rem; padding: 1px 4px; }
.multiselect-sm .multiselect-search input { font-size: 0.7rem; }
</style>
