<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import InputError from "@/Components/InputError.vue";
import Products2Modal from '@/Components/Products2/Products2Modal.vue';
import axios from 'axios';
import { useInversionOperation } from '@/Composables/useInversionOperation';

const props = defineProps({ form: Object });

const page = usePage();
const sessionPrice = computed(() => page.props.price ?? 1);
const { isInversionOp } = useInversionOperation();

watch(() => props.form.operation_id, (newVal) => {
    if (!isInversionOp(newVal, page.props.operations)) {
        props.form.investment_id = null;
    }
});

// === CC & AGRUPACION ===
const selectedGrouping = ref("");
const expandedCC = ref(false);

watch(selectedGrouping, (newGroupingId) => {
    if (!newGroupingId) return;
    const grouping = page.props.groupings?.find(g => g.id == newGroupingId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        props.form.cc = grouping.cost_centers.map(cc => cc.id);
    }
});

// === LISTA DE PRODUCTOS ===
const productsList = computed(() => {
    const raw = page.props.products || [];
    if (raw.length && typeof raw[0] === 'object' && raw[0].name) return raw;
    return [];
});

// === MODAL PRODUCTOS2 ===
const products2Data = ref({ data: [], links: [] });
const searchProducts2 = ref('');
const currentProductIndex = ref(null);

const selectedLevel3Label = computed(() => {
    const sel = page.props.subfamilies?.find(f => f.value === props.form.subfamily_id);
    return sel ? sel.label : '';
});

const fetchProducts2 = () => {
    axios.get(route('products2.index'), {
        params: { term: searchProducts2.value, level3: selectedLevel3Label.value, form: 'agrochemicals' },
        headers: { Accept: 'application/json' }
    }).then(res => { products2Data.value = res.data; });
};

const openProducts2Modal = (index) => {
    currentProductIndex.value = index;
    props.form.products[index].product_name = '';
    searchProducts2.value = '';
    fetchProducts2();
    $(`#products2Modal`).modal('show');
};

const onFilterProducts2 = (term) => { searchProducts2.value = term; fetchProducts2(); };

const onProduct2Select = (item) => {
    const cleanName = item.name.replace(/\s*[xX]\s*\d+[\.,]?\d*\s*\w+$/i, '').trim();
    const p = props.form.products[currentProductIndex.value];
    p.product_name      = cleanName;
    p.price             = item.price || '';
    p.unit_id_price     = item.unit_price_id || '';
    p.active_ingredient = item.active_ingredient || '';
    $(`#products2Modal`).modal('hide');
};

// === FILAS ===
const addItem = () => {
    props.form.products.push({
        product_name: '', dose: '', price: '', mojamiento: '',
        unit_id: '', unit_id_price: '', dose_type_id: '', observations: '', months: [],
    });
};
const removeItem = (index) => props.form.products.splice(index, 1);

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

// === UNIDADES ===
const allowedPriceUnits = { 1: [1,2,8], 2: [1,2,8], 3: [3,4], 4: [3,4], 5: [5], 8: [1,2,8] };
const disallowedDoseUnitIds = [6, 7];
const getDoseUnitOptions = () => (page.props.units || []).filter(u => !disallowedDoseUnitIds.includes(u.value));
const getPriceUnitOptions = (product) => {
    const allowed = allowedPriceUnits[product.unit_id];
    return allowed ? (page.props.units || []).filter(u => allowed.includes(u.value)) : (page.props.units || []);
};

watch(
    () => props.form.products.map(p => p.unit_id),
    () => {
        props.form.products.forEach((p, idx) => {
            const allowed = getPriceUnitOptions(p).map(u => u.value);
            if (p.unit_id_price && !allowed.includes(p.unit_id_price)) {
                props.form.products[idx].unit_id_price = null;
            }
        });
    },
    { deep: true }
);

watch(
    () => props.form.products.map(p => p.product_name),
    (newNames, oldNames) => {
        newNames.forEach((name, idx) => {
            if (name !== oldNames?.[idx]) {
                const found = productsList.value.find(p => p.name === name);
                if (found?.price !== undefined)
                    props.form.products[idx].price = parseInt(Number(found.price) * Number(sessionPrice.value));
                if (found?.unit_price_id !== undefined)
                    props.form.products[idx].unit_id_price = found.unit_price_id;
            }
        });
    },
    { deep: true }
);
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
        <!-- Agrupación (va antes de CC) -->
        <div class="col-sm-3">
            <label class="form-label small mb-1">Agrupación CC</label>
            <Multiselect
                v-model="selectedGrouping"
                :options="($page.props.groupings || []).map(g => ({ value: g.id, label: g.name }))"
                placeholder="Seleccione agrupación"
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
        <!-- Operación -->
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
        <!-- Inversión (solo si Operación = Inversión) -->
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

    <!-- Tabla de productos -->
    <div class="border rounded" style="overflow:hidden;">
        <table class="table table-sm table-bordered align-middle mb-0 agro-table w-100">
            <colgroup>
                <col style="width:22%;">   <!-- Producto -->
                <col style="width:9%;">    <!-- Tipo dosis -->
                <col style="width:6%;">    <!-- Dosis -->
                <col style="width:7%;">    <!-- U. dosis -->
                <col style="width:6%;">    <!-- Mojamiento -->
                <col style="width:8%;">    <!-- Precio -->
                <col style="width:7%;">    <!-- U. precio -->
                <col>                       <!-- Meses (ocupa resto) -->
                <col style="width:9%;">    <!-- Obs. -->
                <col style="width:52px;">  <!-- Acciones -->
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(product, index) in form.products" :key="index"
                    :style="{ backgroundColor: index % 2 === 0 ? '#f0faf5' : '#ffffff', borderLeft: '3px solid #2d9e5f' }">

                    <!-- Producto -->
                    <td class="p-1">
                        <div class="d-flex gap-1 align-items-center">
                            <input
                                v-model="product.product_name"
                                type="text"
                                class="form-control form-control-sm agro-input flex-grow-1"
                                :class="{ 'is-invalid': form.errors['products.' + index + '.product_name'] }"
                                placeholder="Nombre del producto..."
                                autocomplete="off"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm px-1 flex-shrink-0"
                                @click="openProducts2Modal(index)"
                                title="Buscar en catalogo"
                                style="height:26px; width:26px; line-height:1; padding:0;"
                            ><i class="fas fa-search" style="font-size:0.62rem;"></i></button>
                        </div>
                    </td>

                    <!-- Tipo dosis -->
                    <td class="p-1">
                        <div class="d-flex flex-column gap-0">
                            <div v-for="dt in ($page.props.doseTypes || [])" :key="dt.value" class="form-check mb-0">
                                <input
                                    type="radio"
                                    v-model="product.dose_type_id"
                                    :value="dt.value"
                                    :id="'dt_' + index + '_' + dt.value"
                                    class="form-check-input"
                                    style="width:11px; height:11px; margin-top:2px;"
                                />
                                <label :for="'dt_' + index + '_' + dt.value" class="form-check-label ms-1" style="font-size:0.71rem; cursor:pointer;">{{ dt.label }}</label>
                            </div>
                        </div>
                    </td>

                    <!-- Dosis -->
                    <td class="p-1">
                        <input v-model="product.dose" type="number" step="0.01"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.dose'] }" />
                    </td>

                    <!-- Unidad dosis -->
                    <td class="p-1">
                        <select v-model="product.unit_id"
                            class="form-select form-select-sm agro-input"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.unit_id'] }">
                            <option value="">—</option>
                            <option v-for="u in getDoseUnitOptions()" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </td>

                    <!-- Mojamiento -->
                    <td class="p-1">
                        <input v-model="product.mojamiento" type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.mojamiento'], 'bg-light': product.dose_type_id != 2 }"
                            :placeholder="product.dose_type_id == 2 ? 'L/ha' : ''"
                            :title="product.dose_type_id != 2 ? 'Solo para tipo mojamiento' : 'Litros agua/ha'" />
                    </td>

                    <!-- Precio -->
                    <td class="p-1">
                        <input
                            :value="product.price ? parseInt(product.price) : ''"
                            @input="product.price = $event.target.value"
                            type="number"
                            class="form-control form-control-sm agro-input text-end agro-no-arrows"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.price'] }" />
                    </td>

                    <!-- Unidad precio -->
                    <td class="p-1">
                        <select v-model="product.unit_id_price"
                            class="form-select form-select-sm agro-input"
                            :class="{ 'is-invalid': form.errors['products.' + index + '.unit_id_price'] }">
                            <option value="">—</option>
                            <option v-for="u in getPriceUnitOptions(product)" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                    </td>

                    <!-- Meses -->
                    <td class="p-1">
                        <div class="d-grid gap-1" style="grid-template-columns: 22px repeat(6, 1fr); grid-template-rows: auto auto;">
                            <!-- Botón "todos" ocupa 2 filas -->
                            <button type="button" @click="selectAllMonths(product)"
                                class="btn px-0 py-0"
                                :class="product.months.length === ($page.props.months||[]).length ? 'btn-primary' : 'btn-outline-secondary'"
                                style="font-size:0.58rem; height:100%; border-radius:3px; grid-row: 1 / 3;" title="Todos">✓✓</button>
                            <!-- 12 meses en 2 filas de 6 -->
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
                        <button v-if="form.products.length > 1" type="button" @click="removeItem(index)"
                            class="btn btn-sm btn-outline-danger px-1 py-0" style="font-size:0.68rem; height:22px;" title="Eliminar">
                            <i class="fas fa-times"></i>
                        </button>
                        <button v-if="index === form.products.length - 1" type="button" @click="addItem"
                            class="btn btn-sm btn-outline-primary px-1 py-0 ms-1" style="font-size:0.68rem; height:22px;" title="Agregar fila">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Products2Modal
        :products2="products2Data"
        :term="searchProducts2"
        :level3="selectedLevel3Label"
        @filter="onFilterProducts2"
        @select="onProduct2Select"
    />
</template>

<style scoped>
.agro-table { font-size: 0.78rem; }

/* Ocultar flechas de inputs numéricos */
.agro-no-arrows::-webkit-inner-spin-button,
.agro-no-arrows::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.agro-no-arrows { -moz-appearance: textfield; }

/* Inputs y selects de la tabla */
.agro-input {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.75rem !important;
    padding: 2px 5px !important;
    width: 100%;
}

/* Labels del encabezado */
.form-label.small { font-size: 0.78rem; }
</style>

<style>
/* Multiselect estilo remuneraciones — aplica globalmente dentro del modal */
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
.multiselect-sm .multiselect-option {
    font-size: 0.8rem;
    padding: 3px 8px;
    line-height: 1.9;
}
.multiselect-sm .multiselect-tag {
    font-size: 0.75rem;
    padding: 1px 4px;
}
.multiselect-sm .multiselect-search input {
    font-size: 0.7rem;
}
</style>
