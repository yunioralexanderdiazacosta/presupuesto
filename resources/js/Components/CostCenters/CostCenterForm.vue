<script setup>
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    form: Object,
});

const getVarieties = (event) => {
    if (event && event != "") {
        axios
            .get(route("varieties.get", event))
            .then((response) => {
                props.form.varieties = response.data;
                props.form.variety_id = "";
            })
            .catch((error) => console.log(error));
    }
};
</script>
<template>
    <div class="row">
        <div class="col-lg-4">
            <label class="col-form-label">Nombre del centro de costo</label>
            <TextInput
                id="name"
                v-model="form.name"
                class="form-control form-control-solid"
                type="text"
                :class="{ 'is-invalid': form.errors.name }"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div class="col-lg-2">
            <label class="col-form-label">Superficie</label>
            <TextInput
                id="surface"
                v-model="form.surface"
                class="form-control form-control-solid"
                type="number"
                step="0.00"
                :class="{ 'is-invalid': form.errors.surface }"
            />
            <InputError class="mt-2" :message="form.errors.surface" />
        </div>

        <div class="col-lg-3">
            <label for="fruit" class="col-form-label">Frutal</label>
            <div class="input-group">
                <span class="input-group-text"
                    ><i class="fas fa-apple-alt"></i
                ></span>
                <Multiselect
                    :placeholder="'Seleccione el frutal'"
                    v-model="form.fruit_id"
                    :close-on-select="true"
                    :options="$page.props.fruits"
                    class="multiselect-blue form-control"
                    :class="{ 'is-invalid': form.errors.fruit_id }"
                    :searchable="true"
                    @change="getVarieties"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.fruit_id" />
        </div>
        <div class="col-lg-3">
                <label for="variety" class="col-form-label">Variedad</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-flask"></i></span>
                <Multiselect
                    :placeholder="'Seleccione variedad'"
                    v-model="form.variety_id"
                    :close-on-select="true"
                    :options="form.varieties"
                    class="multiselect-blue form-control"
                    :class="{ 'is-invalid': form.errors.variety_id }"
                    :searchable="true"
                />
                </div>
                <InputError class="mt-2" :message="form.errors.variety_id" />
            </div>
       
      </div>

    <div class="row">
        <div class="col-lg-4">
            <label for="fruit" class="col-form-label">Parcela</label>
            <div class="input-group">
                <span class="input-group-text"
                    ><i class="fas fa-layer-group"></i
                ></span>
                <Multiselect
                    :placeholder="'parcela'"
                    v-model="form.parcel_id"
                    :close-on-select="true"
                    :options="$page.props.parcels"
                    class="multiselect-blue form-control"
                    :class="{ 'is-invalid': form.errors.parcel_id }"
                    :searchable="true"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.parcel_id" />
        </div>
   
    <div class="col-lg-4">
            <label for="fruit" class="col-form-label"
                >Estado de desarrollo</label>
             <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
            <Multiselect
                :placeholder="'estado de desarrollo'"
                v-model="form.development_state_id"
                :close-on-select="true"
                :options="$page.props.developmentStates"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.development_state_id }"
                :searchable="true"
            />
            <InputError
                class="mt-2"
                :message="form.errors.development_state_id"
            />
        </div>
    </div>
    <div class="col-lg-4">
        <div class="fv-row">
            <label class="col-form-label">Año plantación</label>
            <TextInput
                id="ano_plantacion"
                v-model="form.year_plantation"
                class="form-control form-control-solid"
                type="number"
                min="1900"
                max="3000"
                step="1"
                :class="{ 'is-invalid': form.errors.year_plantacion }"
            />
            <InputError class="mt-2" :message="form.errors.year_plantacion" />
        </div>
    </div>

     </div>

    <div class="row">
        <div class="col-lg-4">
            <label for="companyreason" class="col-form-label"
                >Razon social</label
            >
            <Multiselect
                :placeholder="'Seleccione la razon social'"
                v-model="form.company_reason_id"
                :close-on-select="true"
                :options="$page.props.companyReasons"
                class="multiselect-blue form-control"
                :class="{ 'is-invalid': form.errors.company_reason_id }"
                :searchable="true"
            />
            <InputError class="mt-2" :message="form.errors.company_reason_id" />
        </div>

        <div class="col-lg-8">
            <label for="observations" class="col-form-label"
                >Observaciones</label
            >
            <textarea
                v-model="form.observations"
                rows="3"
                class="form-control form-control-lg form-control-solid mb-3 mb-lg-0"
                :class="{ 'is-invalid': form.errors.observations }"
            ></textarea>
            <InputError class="mt-2" :message="form.errors.observations" />
        </div>
    </div>

    <div class="fv-row">
        <label class="form-check form-check-inline">
            <Checkbox
                class="form-check-input"
                v-model:checked="form.status"
                name="status"
            />
            <span
                class="form-check-label fw-semibold text-gray-700 fs-base ms-1"
                >Activo</span
            >
        </label>
    </div>
</template> 
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
/* Forzar el alto de los vueform/multiselect en este archivo */
.multiselect-blue {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.7
    rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
    placeholder {
        font-size: 0.1rem !important;
        opacity: 0.7 !important;
    }
}
/* Ajustes para inputs nativos */
input.form-control:not([role="combobox"]),
select.form-control {
    height: 26px;
    min-height: 26px;
    font-size: 0.95rem;
    padding-top: 2px;
    padding-bottom: 2px;
}
/* Checkboxes */
.form-check-input[type="checkbox"] {
    width: 0.8em;
    height: 0.8em;
    vertical-align: middle;
}
/* Group icon alignment */
.input-group-text {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}
/* Labels */
.col-form-label,
label {
    font-size: 0.8rem;
}
/* Placeholder del multiselect */
.multiselect__placeholder {
    font-size: 0.5rem !important;
    opacity: 0.7 !important;
}
/* Opciones del multiselect */
.multiselect__option {
    font-size: 0.7rem;
}
/* Asegura z-index adecuado para dropdown */
.multiselect__content {
    z-index: 2050;
}
</style>
