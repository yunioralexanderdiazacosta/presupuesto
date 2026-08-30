<script setup>
import { ref, watch } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Components/InputError.vue";
import CalculateWorkDayModal from "@/Components/ManPowers/CalculateWorkDayModal.vue";
import { useInversionOperation } from "@/Composables/useInversionOperation";

const props = defineProps({ form: Object });
const page = usePage();
const { isInversionOp } = useInversionOperation();

watch(() => props.form.operation_id, (newVal) => {
    if (!isInversionOp(newVal, page.props.operations)) {
        props.form.investment_id = null;
    }
});

// === CC & AGRUPACION ===
const selectedGrouping = ref("");
const expandedCC = ref(false);

// Watch para autocompletar cost centers al seleccionar agrupaciÃ³n
watch(selectedGrouping, (newGroupingId) => {
  if (!newGroupingId) return;
  // Buscar la agrupaciÃ³n seleccionada en los datos del backend
  const grouping = page.props.groupings?.find(g => g.id == newGroupingId);
  if (grouping && Array.isArray(grouping.cost_centers)) {
    // IDs de los cost centers de la agrupaciÃ³n
    const groupCCs = grouping.cost_centers.map(cc => cc.id);
    // Siempre seleccionar todos los de la agrupaciÃ³n
    props.form.cc = groupCCs;
  }
});

const formWorkDay = useForm({
    performance: "",
    floors: "",
    index: "",
});

const valid = ref(false);

const addItem = () => {
    props.form.products.push({
        product_name: "",
        price: "",
        workday: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};

const storeWorkDay = () => {
    onValidated();
    if (valid.value === true) {
        props.form.products[formWorkDay.index].workday = (
            formWorkDay.floors / formWorkDay.performance
        ).toFixed(2).replace(/\.00$/, "");
        $("#calculateWorkDay").modal("hide");
        formWorkDay.reset();
    }
};

const onCalculated = (index) => {
    formWorkDay.reset();
    formWorkDay.index = index;
    $("#calculateWorkDay").modal("show");
};

const onValidated = () => {
    formWorkDay.errors = {};
    valid.value = true;
    if (formWorkDay.performance == "") {
        formWorkDay.errors.performance = "Este campo es obligatorio";
        valid.value = false;
    }
    if (formWorkDay.floors == "") {
        formWorkDay.errors.floors = "Este campo es obligatorio";
        valid.value = false;
    }
};

// === MESES ===
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
                <col style="width:11%;">
                <col style="width:10%;">
                <col>
                <col style="width:10%;">
                <col style="width:52px;">
            </colgroup>
            <thead style="background: linear-gradient(135deg, #6b1a2a 0%, #a0304a 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white px-2 py-1">Nombre del producto</th>
                    <th class="text-white px-2 py-1">Jornadas/ha</th>
                    <th class="text-white px-2 py-1">Precio $</th>
                    <th class="text-white px-2 py-1">Meses</th>
                    <th class="text-white px-2 py-1">Obs.</th>
                    <th class="text-white px-1 py-1 text-center"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(product, index) in props.form.products"
                    :key="index"
                    :style="{ backgroundColor: index % 2 === 0 ? '#faf0f2' : '#ffffff', borderLeft: '3px solid #a0304a' }"
                >
                    <!-- Nombre -->
                    <td class="p-1">
                        <input
                            v-model="product.product_name"
                            type="text"
                            class="form-control form-control-sm agro-input"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.product_name'] }"
                            placeholder="Nombre del producto..."
                            autocomplete="off"
                        />
                    </td>

                    <!-- Jornadas -->
                    <td class="p-1">
                        <div class="input-group input-group-sm">
                            <input
                                type="number"
                                v-model="product.workday"
                                step="0.01"
                                class="form-control form-control-sm agro-input agro-no-arrows"
                                :class="{ 'is-invalid': form.errors['products.' + index + '.workday'] }"
                            />
                            <button
                                type="button"
                                @click="onCalculated(index)"
                                class="btn btn-outline-secondary px-1"
                                title="Calcular jornadas"
                                style="height:26px; line-height:1; padding:0 4px;"
                            ><i class="fas fa-calculator" style="font-size:0.62rem;"></i></button>
                        </div>
                        <small v-if="form.errors['products.' + index + '.workday']" class="text-danger" style="font-size:0.65rem;">
                            {{ form.errors['products.' + index + '.workday'] }}
                        </small>
                    </td>

                    <!-- Precio -->
                    <td class="p-1">
                        <input
                            v-model="product.price"
                            type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.price'] }"
                        />
                    </td>

                    <!-- Meses -->
                    <td class="p-1">
                        <div class="d-grid gap-1" style="grid-template-columns: 22px repeat(6, 1fr); grid-template-rows: auto auto;">
                            <button type="button" @click="selectAllMonths(product)"
                                class="btn px-0 py-0"
                                :class="product.months.length === ($page.props.months||[]).length ? 'btn-primary' : 'btn-outline-secondary'"
                                style="font-size:0.58rem; height:100%; border-radius:3px; grid-row: 1 / 3;" title="Todos">&#10003;&#10003;</button>
                            <button v-for="m in ($page.props.months || [])" :key="m.value"
                                type="button" @click="toggleMonth(product, m.value)"
                                :class="product.months.includes(m.value) ? 'btn-primary' : 'btn-outline-secondary'"
                                class="btn px-0 py-0"
                                style="font-size:0.62rem; height:18px; border-radius:3px; min-width:0;">{{ monthAbbr(m.label) }}</button>
                        </div>
                        <small v-if="form.errors['products.' + index + '.months']" class="text-danger d-block" style="font-size:0.65rem;">
                            {{ form.errors['products.' + index + '.months'] }}
                        </small>
                    </td>

                    <!-- Observaciones -->
                    <td class="p-1">
                        <input v-model="product.observations" type="text"
                            class="form-control form-control-sm agro-input" placeholder="..." />
                    </td>

                    <!-- Acciones -->
                    <td class="p-1 text-center" style="white-space:nowrap;">
                        <button v-if="props.form.products.length > 1"
                            type="button" @click="removeItem(index)"
                            class="btn btn-sm btn-outline-danger px-1 py-0"
                            style="height:22px; width:22px; line-height:1; padding:0 !important;"
                            title="Eliminar fila">
                            <i class="fas fa-times" style="font-size:0.62rem;"></i>
                        </button>
                        <button v-if="props.form.products.length === index + 1"
                            type="button" @click="addItem()"
                            class="btn btn-sm btn-outline-success px-1 py-0 ms-1"
                            style="height:22px; width:22px; line-height:1; padding:0 !important;"
                            title="Agregar fila">
                            <i class="fas fa-plus" style="font-size:0.62rem;"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal calculadora jornadas (Ãºnico, fuera de la tabla) -->
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
