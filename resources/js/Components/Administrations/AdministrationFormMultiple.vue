<script setup>
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { usePage } from "@inertiajs/vue3";
const page = usePage();

const props = defineProps({
    form: Object,
});

const addItem = () => {
    props.form.products.push({
        product_name: "",
        quantity: "",
        price: "",
        unit_id: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};

//obtengo valores para llenar select familia
const getLevel3s = (event) => {
    if (event && event != "") {
        axios
            .get(route("levels3.get", event))
            .then((response) => {
                props.form.level3s = response.data;
                props.form.subfamily_id = "";
            })
            .catch((error) => console.log(error));
    }
};

//seleccionar todos los meses
const selectAllMonths = (index) => {
    const allMonths = page.props.months.map((m) => String(m.value));
    const current = props.form.products[index].months || [];
    if (
        current.length === allMonths.length &&
        allMonths.every((m) => current.includes(m))
    ) {
        props.form.products[index].months = [];
    } else {
        props.form.products[index].months = allMonths;
    }
};
</script>
<script setup></script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="level2_id" class="col-form-label"
                    >Nivel 2<span
                        style="cursor: pointer"
                        title="Seleccione el grupo principal para filtrar las familias disponibles."
                    >
                        <i class="fas fa-question-circle text-info ms-1"></i>
                    </span>
                </label>
                <Multiselect
                    :placeholder="'Seleccione Level 2'"
                    v-model="form.level2_id"
                    :close-on-select="true"
                    :options="$page.props.level2s"
                    class="multiselect-blue form-control"
                    :class="{ 'is-invalid': form.errors.level2_id }"
                    :searchable="true"
                    :hide-selected="false"
                    @select="getLevel3s($event)"
                />
                <InputError class="mt-2" :message="form.errors.level2_id" />
            </div>
        </div>

        <div class="col-lg-6">
            <label for="families" class="col-form-label">Nivel 3</label>
            <Multiselect
                :placeholder="'Seleccione nivel 3'"
                v-model="form.subfamily_id"
                :close-on-select="true"
                :options="form.level3s"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.subfamily_id }"
                :searchable="true"
                :hide-selected="false"
            />
            <InputError class="mt-2" :message="form.errors.subfamily_id" />
        </div>
    </div>

    <template v-for="(product, index) in form.products">
        <hr />
        <div class="row">
            <div class="col-lg-4">
                <label class="col-form-label">Nombre del producto</label>
                <TextInput
                    id="product_name"
                    v-model="product.product_name"
                    class="form-control"
                    type="text"
                    :class="{
                        'is-invalid':
                            form.errors['products.' + index + '.product_name'],
                    }"
                />
                <InputError
                    class="mt-2"
                    :message="
                        form.errors['products.' + index + '.product_name']
                    "
                />
            </div>
            <div class="col-lg-2">
                <label for="unit" class="col-form-label">Unidad</label>
                <Multiselect
                    :placeholder="''"
                    v-model="product.unit_id"
                    :close-on-select="true"
                    :options="$page.props.units"
                    class="multiselect-blue form-control"
                    :class="{
                        'is-invalid':
                            form.errors['products.' + index + '.unit_id'],
                    }"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.unit_id']"
                />
            </div>

            <div class="col-lg-2">
                <label class="col-form-label">Cantidad</label>
                <TextInput
                    id="quantity"
                    v-model="product.quantity"
                    class="form-control"
                    type="number"
                    step="0.00"
                    :class="{
                        'is-invalid':
                            form.errors['products.' + index + '.quantity'],
                    }"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.quantity']"
                />
            </div>

            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Precio</label>
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
                    <InputError
                        class="mt-2"
                        :message="form.errors['products.' + index + '.price']"
                    />
                </div>
            </div>
        </div>

         <div class="row">
            <div class="col-lg-6">
                <div class="row align-items-start">
                        <div class="d-flex align-items-center mb-1">
                    <label for="months" class="col-form-label mb-1">Meses</label>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm ms-2"
                         @click="selectAllMonths(index, $page.props.months)"
                    >
                        <i class="fas fa-tasks me-1"></i>
                        {{
                            product.months &&
                            product.months.length === page.props.months.length &&
                              $page.props.months.every((m) => product.months.includes(m.value))
                                ? "Quitar todos"
                                : "todos"
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
                                class="form-check-label ps-2"
                                :for="'kt_month_' + value.id"
                                >{{ value.label }}</label
                            >
                        </div>
                    </template>
                    </div>
                    <small
                        class="text-danger  ms-2 align-self-start"
                        v-if="form.errors['products.' + index + '.months']"
                        ><br />{{
                            form.errors["products." + index + ".months"]
                        }}</small
                    >
                </div>
            </div>
            <div class="col-lg-4">
                <div class="fv-row">
                    <label for="observations" class="col-form-label"
                        >Observaciones</label
                    >
                    <textarea
                        v-model="product.observations"
                        rows="5"
                        class="form-control mb-3 mb-lg-0"
                        :class="{ 'is-invalid': form.errors.observations }"
                    ></textarea>
                    <InputError
                        class="mt-2"
                        :message="form.errors.observations"
                    />
                </div>
            </div>
          </div>
        <div class="row">
            <div class="col-lg-12 text-end">
                <button
                    type="button"
                    v-if="form.products.length == index + 1"
                    @click="addItem()"
                    class="btn btn-sm btn-primary me-1"
                >
                    <i class="fas fa-plus"></i>
                </button>
                <button
                    type="button"
                    @click="removeItem(index)"
                    class="btn btn-sm btn-danger"
                    v-if="form.products.length > 1"
                >
                    <i class="fas fa-minus"></i>
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
    min-width: 0.85em !important;
    min-height: 0.85em !important;
    max-width: 0.85em !important;
    max-height: 0.85em !important;
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
    font-size: 0.8 rem !important;
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
    font-size: 0.85rem !important;
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
    padding-left: 0.01rem !important;
    margin-bottom: 0 !important;
    display: inline-block;
    vertical-align: middle;
}

.custom-hr {
    height: 2px;
    background: #b1b1b1;
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

.multiselect .multiselect-options,
.multiselect .multiselect-option,
.multiselect__option {
    font-size: 0.7rem !important;
}

::placeholder {
    font-size: 0.7rem !important;
    color: #888 !important; /* Opcional: cambia el color si lo deseas */
    opacity: 1; /* Para asegurar que el color se aplique en todos los navegadores */
}

.input-group .form-control {
    border-radius: 0.25rem !important;
}
</style>
