<script setup>
import { ref, watch, getCurrentInstance } from "vue";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Components/InputError.vue";
import { useInversionOperation } from "@/Composables/useInversionOperation";

const props = defineProps({ form: Object });

const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page;
const { isInversionOp } = useInversionOperation();

watch(() => props.form.operation_id, (newVal) => {
    if (!isInversionOp(newVal, page.props.operations)) {
        props.form.investment_id = null;
    }
});

// Agrupación CC
const selectedGrouping = ref("");
watch(selectedGrouping, (newGroupingId) => {
    if (!newGroupingId) return;
    const grouping = page.props.groupings?.find(g => g.id == newGroupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        props.form.cc = grouping.cost_centers.map(cc => cc.id);
    }
});

// Meses
const toggleMonth = (product, monthValue) => {
    const idx = product.months.indexOf(monthValue);
    if (idx >= 0) product.months.splice(idx, 1);
    else product.months.push(monthValue);
};
const selectAllMonths = (product) => {
    const all = (page.props.months || []).map(m => m.value);
    product.months = (product.months.length === all.length) ? [] : [...all];
};
const monthAbbr = (label) => label ? label.substring(0, 3) : '';

// Filas
const addItem = () => {
    props.form.products.push({
        product_name: "",
        quantity: "",
        price: "",
        unit_id: "",
        unit_id_price: "",
        observations: "",
        months: [],
    });
};
const removeItem = (index) => props.form.products.splice(index, 1);
</script>
<template>
    <!-- Encabezado: Nivel 3 / Agrupación CC / CC -->
    <div class="row g-2 mb-3">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Nivel 3 <span class="text-danger">*</span></label>
            <Multiselect
                v-model="props.form.subfamily_id"
                :options="$page.props.subfamilies"
                placeholder="Seleccione nivel 3"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': props.form.errors.subfamily_id }"
                class="multiselect-sm"
            />
            <InputError :message="props.form.errors.subfamily_id" />
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Agrupación CC</label>
            <Multiselect
                v-model="selectedGrouping"
                :options="page.props.groupings.map(g => ({ value: g.id, label: g.name }))"
                placeholder="Seleccione agrupación"
                :searchable="true"
                :close-on-select="true"
                class="multiselect-sm"
            />
        </div>
        <div class="col-sm-4">
            <label class="form-label small mb-1">
                Centros de Costo <span class="text-danger">*</span>
                <span v-if="props.form.cc?.length" class="badge bg-primary ms-1">{{ props.form.cc.length }} sel.</span>
            </label>
            <Multiselect
                mode="tags"
                v-model="props.form.cc"
                :options="$page.props.costCenters"
                placeholder="Seleccione CC"
                :searchable="true"
                :close-on-select="false"
                :class="{ 'is-invalid': props.form.errors.cc }"
                class="multiselect-sm"
            />
            <InputError :message="props.form.errors.cc" />
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Operación <span class="text-danger">*</span></label>
            <Multiselect
                v-model="props.form.operation_id"
                :options="$page.props.operations"
                placeholder="Seleccione operación"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': props.form.errors.operation_id }"
                class="multiselect-sm"
            />
            <InputError :message="props.form.errors.operation_id" />
        </div>
        <div v-if="isInversionOp(props.form.operation_id, $page.props.operations)" class="col-sm-2">
            <label class="form-label small mb-1">Inversión <span class="text-danger">*</span></label>
            <Multiselect
                v-model="props.form.investment_id"
                :options="$page.props.investments"
                placeholder="Seleccione inversión"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': props.form.errors.investment_id }"
                class="multiselect-sm"
            />
            <InputError :message="props.form.errors.operation_id" />
        </div>
    </div>

    <!-- Tabla de servicios -->
    <div class="border rounded" style="overflow:hidden;">
        <table class="table table-sm table-bordered align-middle mb-0 w-100" style="font-size:0.78rem;">
            <colgroup>
                <col style="width:28%;">
                <col style="width:9%;">
                <col style="width:11%;">
                <col style="width:12%;">
                <col>
                <col style="width:9%;">
                <col style="width:52px;">
            </colgroup>
            <thead style="background: linear-gradient(135deg, #3d1a74 0%, #6a35b5 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white px-2 py-1">Servicio</th>
                    <th class="text-white px-2 py-1">Cantidad</th>
                    <th class="text-white px-2 py-1">Precio</th>
                    <th class="text-white px-2 py-1">Unidad</th>
                    <th class="text-white px-2 py-1">Meses</th>
                    <th class="text-white px-2 py-1">Obs.</th>
                    <th class="text-white px-1 py-1 text-center"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(product, index) in props.form.products"
                    :key="index"
                    :style="{ backgroundColor: index % 2 === 0 ? '#f5f0fb' : '#ffffff', borderLeft: '3px solid #6a35b5' }"
                >
                    <!-- Servicio -->
                    <td class="p-1">
                        <input
                            v-model="product.product_name"
                            type="text"
                            class="form-control form-control-sm agro-input"
                            placeholder="Nombre del servicio..."
                            autocomplete="off"
                        />
                        <small v-if="props.form.errors['products.' + index + '.product_name']" class="text-danger">
                            {{ props.form.errors['products.' + index + '.product_name'] }}
                        </small>
                    </td>

                    <!-- Cantidad -->
                    <td class="p-1">
                        <input type="number" v-model="product.quantity" step="0.01"
                            class="form-control form-control-sm agro-input agro-no-arrows" />
                    </td>

                    <!-- Precio -->
                    <td class="p-1">
                        <input type="number" v-model="product.price"
                            class="form-control form-control-sm agro-input agro-no-arrows" />
                    </td>

                    <!-- Unidad (sincroniza unit_id y unit_id_price) -->
                    <td class="p-1">
                        <select v-model="product.unit_id" class="form-select form-select-sm agro-input"
                            @change="product.unit_id_price = product.unit_id">
                            <option value="">--</option>
                            <option v-for="u in ($page.props.units || [])" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </td>

                    <!-- Meses -->
                    <td class="p-1">
                        <div class="d-grid gap-1"
                            style="grid-template-columns: 22px repeat(6, 1fr); grid-template-rows: auto auto;">
                            <button type="button" @click="selectAllMonths(product)"
                                class="btn px-0 py-0"
                                :class="product.months.length === ($page.props.months||[]).length ? 'btn-primary' : 'btn-outline-secondary'"
                                style="font-size:0.58rem; height:100%; border-radius:3px; grid-row: 1 / 3;">✓✓</button>
                            <button v-for="m in ($page.props.months || [])" :key="m.value"
                                type="button" @click="toggleMonth(product, m.value)"
                                :class="product.months.includes(m.value) ? 'btn-primary' : 'btn-outline-secondary'"
                                class="btn px-0 py-0"
                                style="font-size:0.62rem; height:18px; border-radius:3px; min-width:0;">{{ monthAbbr(m.label) }}</button>
                        </div>
                        <small v-if="props.form.errors['products.' + index + '.months']" class="text-danger">
                            {{ props.form.errors['products.' + index + '.months'] }}
                        </small>
                    </td>

                    <!-- Obs. -->
                    <td class="p-1">
                        <input type="text" v-model="product.observations"
                            class="form-control form-control-sm agro-input" placeholder="..." />
                    </td>

                    <!-- Acciones -->
                    <td class="p-1 text-center">
                        <button v-if="props.form.products.length > 1" type="button" @click="removeItem(index)"
                            class="btn btn-sm btn-outline-danger px-1 py-0" style="font-size:0.68rem; height:22px;">
                            <i class="fas fa-times"></i>
                        </button>
                        <button v-if="index === props.form.products.length - 1" type="button" @click="addItem"
                            class="btn btn-sm btn-outline-primary px-1 py-0 ms-1" style="font-size:0.68rem; height:22px;">
                            <i class="fas fa-plus"></i>
                        </button>
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
select.agro-input {
    font-size: 0.75rem !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
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

