<script setup>

import { ref, watch, getCurrentInstance } from "vue";
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import CalculateWorkDayModal from "@/Components/ManPowers/CalculateWorkDayModal.vue";

// Inicializar contexto Inertia Page
const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page || { props: {} };

const valid = ref(false);

const props = defineProps({
    form: Object,
});

//Estado para agrupación seleccionada
// (Importación única ya presente arriba)
const selectedGrouping = ref("");

// Watch para autocompletar cost centers al seleccionar agrupación
watch(selectedGrouping, (newGroupingId) => {
  if (!newGroupingId) return;
  // Buscar la agrupación seleccionada en los datos del backend
  const grouping = page.props.groupings?.find(g => g.id == newGroupingId);
  if (grouping && Array.isArray(grouping.cost_centers)) {
    // IDs de los cost centers de la agrupación
    const groupCCs = grouping.cost_centers.map(cc => cc.id);
    // Siempre seleccionar todos los de la agrupación
    props.form.cc = groupCCs;
  }
});

const formWorkDay = useForm({
    performance: "",
    floors: "",
    index: "",
});

const addItem = () => {
    props.form.products.push({
        product_name: "",
        price: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};

const storeWorkDay = () => {
    onValidated();
    if (valid.value == true) {
        props.form.products[formWorkDay.index].workday = (
            formWorkDay.floors / formWorkDay.performance
        )
            .toFixed(2)
            .replace(/\.00$/, "");
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
<template>
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="fv-row mb-2">
                <label for="families" class="col-form-label">Familia</label>
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-tags"></i
                    ></span>
                    <Multiselect
                        :placeholder="'Seleccione familia'"
                        v-model="form.subfamily_id"
                        :close-on-select="true"
                        :options="$page.props.subfamilies"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.subfamily_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.subfamily_id" />
            </div>
        </div>
         <div class="col-md-8">
            <div class="fv-row">
                <label for="cc" class="col-form-label">CC</label>
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-sitemap"></i
                    ></span>
                    <Multiselect
                        mode="tags"
                        :placeholder="'Seleccione CC'"
                        v-model="form.cc"
                        :close-on-select="false"
                        :options="$page.props.costCenters"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.cc }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.cc" />
            </div>
        </div>


        <!-- Selector de agrupación con Multiselect -->
        <div class="col-sm-4">
            <label for="grouping" class="col-form-label mb-0">Agrupación</label>
            <div class="input-group mb-2 ">
                <span class="input-group-text"><i class="fas fa-object-group"></i></span>
                <Multiselect
                    id="grouping"
                    v-model="selectedGrouping"
                    :options="page.props.groupings.map(g => ({ value: g.id, label: g.name }))"
                    :placeholder="'Seleccione agrupación'"
                    :searchable="true"
                    :close-on-select="true"
                    :hide-selected="false"
                    class="form-control"
                />
            </div>
        </div>


    </div>
    <template v-for="(product, index) in form.products" :key="index">
        <hr class="custom-hr" />
        <div class="row mt-3 mb-3">
            <div class="col-md-4">
                <div class="fv-row">
                    <label class="col-form-label">Nombre del producto</label>
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="fas fa-flask"></i
                        ></span>
                        <TextInput
                            id="product_name"
                            v-model="product.product_name"
                            class="form-control form-control-solid"
                            type="text"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        'products.' + index + '.product_name'
                                    ],
                            }"
                        />
                    </div>
                    <InputError
                        class="mt-2"
                        :message="form.errors.product_name"
                    />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Jornadas/hectarea</label>
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="fas fa-user-clock"></i
                        ></span>
                        <input
                            type="number"
                            id="workday"
                            v-model="product.workday"
                            class="form-control form-control-solid"
                            aria-describedby="jornadas"
                            step="0.00"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        'products.' + index + '.workday'
                                    ],
                            }"
                        />
                        <button
                            type="button"
                            @click="onCalculated(index)"
                            id="jornadas"
                            class="btn btn-light text-primary"
                        >
                            <i
                                class="fas fa-question-circle"
                                style="font-size: 20px"
                            ></i>
                        </button>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="form.errors['products.' + index + '.workday']"
                    />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text"
                            ><i class="fas fa-dollar-sign"></i
                        ></span>
                        <TextInput
                            id="price"
                            v-model="product.price"
                            class="form-control form-control-solid"
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
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="fv-row">
                    <div class="d-flex align-items-center mb-1">
                        <label for="months" class="col-form-label mb-0 me-2">Meses</label>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm text-small"
                            @click="selectAllMonths(index, $page.props.months)"
                        >
                            {{
                                product.months &&
                                product.months.length ===
                                    $page.props.months.length &&
                                $page.props.months.every((m) =>
                                    product.months.includes(m.value)
                                )
                                    ? "Deseleccionar todos"
                                    : "Seleccionar todos"
                            }}
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <template v-for="value in $page.props.months" :key="value.id">
                            <div class="form-check form-check-solid form-check-inline mb-1">
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
                                >{{ value.label }}</label>
                            </div>
                        </template>
                    </div>
                    <small
                        class="text-danger"
                        v-if="form.errors['products.' + index + '.months']"
                        ><br />{{
                            form.errors["products." + index + ".months"]
                        }}</small
                    >
                </div>
            </div>
           <div class="col-lg-5 align-self-start ps-0">
                <label for="observations" class="col-form-label">Observaciones</label>
                    <textarea
                        v-model="product.observations"
                        rows="10"
                         class="form-control mb-3 mb-lg-0"
                        :class="{ 'is-invalid': form.errors.observations }"
                        style="resize: vertical; min-height: 60px;"
                    ></textarea>
            
                <InputError class="mt-2" :message="form.errors.observations" />
            </div>
        </div>
     

        <div class="row">
            <div class="col-lg-12 text-end">
                <button
                    type="button"
                    @click="removeItem(index)"
                    class="btn btn-sm btn-danger me-2"
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
        <CalculateWorkDayModal @store="storeWorkDay" :form="formWorkDay" />
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
</style>
