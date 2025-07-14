<script setup>
import { onMounted } from "vue";
onMounted(() => {
    console.log('page.props.products:', page.props.products);
    console.log('productsList.value:', productsList.value);
});

import { ref, computed, getCurrentInstance } from "vue";
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    form: Object,
});

// Preparar acceso a productos para sugerencias (cuando estén disponibles)
const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page || { props: {} };

// Normalizar productsList para que siempre sea [{id, name}]
const productsList = computed(() => {
    const raw = page.props.products || [];
    // Si ya es array de objetos con name, devolver tal cual
    if (raw.length && typeof raw[0] === 'object' && raw[0].name) return raw;
    // Si es array de strings, convertir a objetos
    if (raw.length && typeof raw[0] === 'string') {
        return raw.map((name, idx) => ({ id: idx, name }));
    }
    return [];
});

// Controlar sugerencias por producto
const productSearch = ref([]); // Un array de v-models para cada producto
const showSuggestions = ref([]); // Un array de flags para mostrar sugerencias

// Inicializar arrays según la cantidad de productos
const ensureProductSearchArrays = () => {
    while (productSearch.value.length < props.form.products.length) {
        productSearch.value.push("");
        showSuggestions.value.push(false);
    }
    while (productSearch.value.length > props.form.products.length) {
        productSearch.value.pop();
        showSuggestions.value.pop();
    }
};

// Llamar en el template antes de renderizar productos
ensureProductSearchArrays();

const filteredProducts = (search) => {
    if (!search) return [];
    return productsList.value.filter(p =>
        p.name && p.name.toLowerCase().includes(search.toLowerCase())
    ).slice(0, 8); // máximo 8 sugerencias
};

const selectSuggestion = (index, product) => {
    props.form.products[index].product_name = product.name;
    productSearch.value[index] = product.name;
    showSuggestions.value[index] = false;
};

const onInput = (index) => {
    showSuggestions.value[index] = true;
};

const onBlur = (index) => {
    setTimeout(() => (showSuggestions.value[index] = false), 150);
};

const addItem = () => {
    props.form.products.push({
        product_name: "",
        dose: "",
        price: "",
        mojamiento: "",
        unit_id: "",
        unit_id_price: "",
        dose_type_id: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};


const selectAllMonths = (index, months) => {
    const allMonths = months.map((m) => m.value);
    const current = props.form.products[index].months || [];
    if (
        current.length === allMonths.length &&
        allMonths.every((m) => current.includes(m))
    ) {
        // Si ya están todos seleccionados, deselecciona todos
        props.form.products[index].months = [];
    } else {
        // Si no, selecciona todos
        props.form.products[index].months = allMonths;
    }
};
</script>
<script setup></script>
<template>
    <div class="row gy-1">
        <div class="col-sm-4">
            <label for="families" class="col-form-label">Familia</label>
            <div class="input-group mb-2">
                <span class="input-group-text"
                    ><i class="fas fa-layer-group"></i
                ></span>
                <Multiselect
                    :placeholder="'Seleccione familia'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="$page.props.subfamilies"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.subfamily_id }"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.subfamily_id" />
        </div>
        <div class="col-sm-8">
            <label for="cc" class="col-form-label">CC</label>
            <div class="input-group mb-2">
                <span class="input-group-text"
                    ><i class="fas fa-sitemap"></i
                ></span>
                <Multiselect
                    mode="tags"
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="$page.props.costCenters"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.cc }"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.cc" />
        </div>
    </div>
    <template v-for="(product, index) in form.products">
        <hr class="custom-hr" />
        <div class="row mt-0">
            <div class="col-sm-5 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Nombre del producto</label>
                <div class="input-group position-relative">
                    <span class="input-group-text"
                        ><i class="fas fa-flask"></i
                    ></span>
                    <input
                        :id="'product_name_' + index"
                        v-model="product.product_name"
                        class="form-control"
                        type="text"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.product_name'],
                        }"
                        autocomplete="off"
                        @input="onInput(index)"
                        @focus="onInput(index)"
                        @blur="onBlur(index)"
                        placeholder="Escriba o seleccione un producto..."
                    />
                    <!-- Sugerencias -->
                    <ul v-if="showSuggestions[index] && filteredProducts(product.product_name).length" class="autocomplete-list bg-white border rounded shadow-sm position-absolute w-100 z-3" style="max-height: 180px; overflow-y: auto;">
                        <li
                            v-for="suggestion in filteredProducts(product.product_name)"
                            :key="suggestion.id"
                            class="px-2 py-1 cursor-pointer hover-bg-primary"
                            @mousedown.prevent="selectSuggestion(index, suggestion)"
                        >
                            {{ suggestion.name }}
                        </li>
                    </ul>
                </div>
                <InputError class="mt-2" :message="form.errors.product_name" />
            </div>
            </div>
            <div class="col-sm-3 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Tipo de dosis</label>
                <div class="d-flex flex-wrap gap-1">
                    <template v-for="value in $page.props.doseTypes">
                        <div
                            class="form-check form-check-solid form-check-inline mb-0 mt-0 mr-1"
                        >
                            <input
                                class="form-check-input"
                                type="radio"
                                v-model="product.dose_type_id"
                                :id="'kt_unit_' + value.id"
                                :value="value.value"
                            />
                            <label
                                class="form-check-label ps-1"
                                :for="'kt_unit_' + value.id"
                                >{{ value.label }}</label
                            >
                        </div>
                    </template>
                </div>
                <small
                    class="text-danger mt-2"
                    :v-if="form.errors['products.' + index + '.dose_type_id']"
                    ><br />{{
                        form.errors["products." + index + ".dose_type_id"]
                    }}</small
                >
                </div>
            </div>
            <div class="col-sm-3 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Dosis</label>
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-vial"></i
                    ></span>
                    <TextInput
                        id="dose"
                        v-model="product.dose"
                        class="form-control"
                        type="number"
                        step="0.00"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.dose'],
                        }"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.dose']"
                />
            </div>
            </div>
        </div>
        <div class="row mt-0">
            <div class="col-sm-3">
                <label for="unit" class="col-form-label"
                    >Unidad de la dosis</label
                >
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-balance-scale"></i
                    ></span>
                    <Multiselect
                        :placeholder="''"
                        v-model="product.unit_id"
                        :close-on-select="true"
                        :options="$page.props.units"
                        class="form-control"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.unit_id'],
                        }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.unit_id']"
                />
            </div>
            <div class="col-sm-3">
                <label class="col-form-label">Mojamiento</label>
                <div class="input-group mb-0">
                    <span class="input-group-text"
                        ><i class="fas fa-tint"></i
                    ></span>
                    <TextInput
                        id="product_name"
                        v-model="product.mojamiento"
                        class="form-control"
                        type="number"
                        :class="{
                            'is-invalid':
                                form.errors[
                                    'products.' + index + '.mojamiento'
                                ],
                        }"
                    />
                </div>
                <InputError
                    class="mt-0"
                    :message="form.errors['products.' + index + '.mojamiento']"
                />
            </div>

            <div class="col-sm-3">
                <label for="price" class="col-form-label">Precio</label>
                <div class="input-group mb-1">
                    <span class="input-group-text"
                        ><i class="fas fa-dollar-sign"></i
                    ></span>
                    <TextInput
                        id="price"
                        v-model="product.price"
                        class="form-control"
                        type="number"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.price'],
                        }"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.price']"
                />
            </div>
            <div class="col-sm-3">
                <label for="unit" class="col-form-label"
                    >Unidad del precio</label
                >
                <div class="input-group mb-1">
                    <span class="input-group-text"
                        ><i class="fas fa-balance-scale"></i
                    ></span>
                    <Multiselect
                        :placeholder="''"
                        v-model="product.unit_id_price"
                        :close-on-select="true"
                        :options="$page.props.units"
                        class="form-control"
                        :class="{
                            'is-invalid':
                                form.errors[
                                    'products.' + index + '.unit_id_price'
                                ],
                        }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="
                        form.errors['products.' + index + '.unit_id_price']
                    "
                />
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-1">
                    <label for="months" class="col-form-label mb-1">Meses</label>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm ms-2"
                        @click="selectAllMonths(index, $page.props.months)"
                    >
                        {{
                            product.months &&
                            product.months.length === $page.props.months.length &&
                            $page.props.months.every((m) => product.months.includes(m.value))
                                ? "Deseleccionar todos"
                                : "Seleccionar todos"
                        }}
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <template v-for="value in $page.props.months">
                        <div
                            style="margin-right: 0.5rem"
                            class="form-check form-check-solid form-check-inline mb-1"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                v-model="product.months"
                                :id="'kt_month_' + value.id"
                                :value="value.value"
                            />
                            <label
                                class="form-check-label ps-1"
                                :for="'kt_month_' + value.id"
                                >{{ value.label }}</label
                            >
                        </div>
                    </template>
                </div>
                <small
                    class="text-danger"
                    v-if="form.errors['products.' + index + '.months']"
                >
                    <br />{{ form.errors["products." + index + ".months"] }}
                </small>
            </div>


            
            <div class="col-lg-4 align-self-start ps-0">
                <label for="observations" class="col-form-label">Observaciones</label>
                    <textarea
                        v-model="product.observations"
                        rows="10"
                         class="form-control mb-3 mb-lg-0"
                        :class="{ 'is-invalid': form.errors.observations }"
                        style="resize: vertical; min-height: 80px;"
                    ></textarea>
            
                <InputError class="mt-2" :message="form.errors.observations" />
            </div>
        </div>

         <div class="row">
            <div class="col-lg-12 text-end">
                <button
                    type="button"
                    @click="removeItem(index)"
                    class="btn btn-sm btn-danger me-1"
                    v-if="form.products.length > 1"
                >
                    <i class="fa fa-minus"></i>
                </button>
                <button
                    type="button"
                    @click="addItem()"
                    v-if="form.products.length == index + 1"
                    class="btn btn-sm btn-primary"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
    </template>
</template>
<!-- <style src="@vueform/multiselect/themes/default.css"></style>-->
<style>
select,
select.form-control {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.95rem;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
}

/* Agrandar la casilla de verificación (checkbox) */
.form-check-input[type="checkbox"] {
    width: 0.8em !important;
    height: 0.8em !important;
    min-width: 0.95em !important;
    min-height: 0.95em !important;
    max-width: 0.95em !important;
    max-height: 0.95em !important;
    vertical-align: middle;
}

/* Ajustar el alto de todos los vueform/multiselect (no solo los blue) */

/* Forzar el alto de los vueform/multiselect en este archivo */
.multiselect,
.multiselect.form-control,
.multiselect__tags {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.95rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Forzar el alto de los inputs (TextInput y nativos), excepto textarea */
.form-control:not(textarea),
.form-control-lg:not(textarea),
.form-control-sm:not(textarea) {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.95rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajustar el alto y alineación de los contenedores de iconos, inputs y selects */
.input-group {
    min-height: 26px !important;
    height: 26px !important;
    align-items: center !important;
}

.input-group-text {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    font-size: 1rem !important;
    display: flex;
    align-items: center;
}

.col-form-label,
label {
    font-size: 0.8rem !important;
}

/* Reducir el tamaño del texto de los labels de los meses (checkboxes) */
.form-check-label.ps-1,
.form-check-label.ps-2,
.form-check-label {
    font-size: 0.8rem !important;
    line-height: 1.1 !important;
    padding-left: 0.25rem !important;
    margin-bottom: 0 !important;
    display: inline-block;
    vertical-align: middle;
}

.custom-hr {
    height: 1px;
    background: #888;
    border: none;
    margin: 0.5rem 0;
}
/* Achicar el botón 'Seleccionar todos' */
.btn.btn-outline-primary.btn-sm.ms-2 {
    padding: 0.1rem 0.35rem !important;
    font-size: 0.75rem !important;
    line-height: 1 !important;
    height: 20px !important;
    min-height: 20px !important;
    border-radius: 0.15rem !important;
}
/* Asegura que el dropdown de autocomplete esté justo debajo del input */
.autocomplete-list {
    z-index: 1050 !important;
    background: #fff;
    top: 100%;
    left: 0;
}
</style>
