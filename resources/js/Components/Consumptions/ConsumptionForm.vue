<script setup>


import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import FormItems from "./FormItems.vue";

const props = defineProps({
    form: Object,
    costCenters: Array,
    operations: Array,
    machineries: Array,
    projects: Array,
    products: Array,
    invoiceLinesByProduct: Object
});

const emit = defineEmits(['submit']);



</script>
<template>
  
    <div class="mb-0">
        <div class="row">
            <div class="col-lg-3">
                <div class="fv-row">

                    <label class="col-form-label">Fecha</label>
                    <TextInput
                        id="date"
                        v-model="form.date"
                        class="form-control form-control-solid"
                        type="date"
                        :class="{ 'is-invalid': form.errors.date }"
                    />
                    <InputError class="mt-2" :message="form.errors.date" />
                </div>
            </div>
            

            <div class="col-lg-6">
                <div class="fv-row">
                    <label for="" class="col-form-label">Centros de costos</label>
                    <Multiselect
                        :placeholder="'Seleccione centro de costos'"
                        v-model="form.cost_center_id"
                        :close-on-select="true"
                        :options="costCenters"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.cost_center_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.cost_center_id"
                    />
                </div>
            </div>
        </div>
        <!--begin::Row-->
        <div class="row">
            <div class="col-lg-6">
                <div class="fv-row">
                    <label for="" class="col-form-label">tipo de operacion</label>
                    <Multiselect
                        :placeholder="'Seleccione operacion'"
                        v-model="form.operation_id"
                        :close-on-select="true"
                        :options="operations"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.operation_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.operation_id"
                    />
                </div>
            </div>
            <div class="col-lg-3">
                <div class="fv-row">
                    <label for="typeDocument" class="col-form-label">Proyecto</label
                    >
                    <Multiselect
                        :placeholder="'Seleccione proyecto'"
                        v-model="form.project_id"
                        :close-on-select="true"
                        :options="projects"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.project_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.project_id"
                    />
                </div>
            </div>
           
 <div class="col-lg-3">
                <div class="fv-row">
                    <label for="typeDocument" class="col-form-label">Maquinarias</label
                    >
                    <Multiselect
                        :placeholder="'Seleccione maquinarias'"
                        v-model="form.machinery_id"
                        :close-on-select="true"
                        :options="machineries"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.machinery_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.machinery_id"
                    />
                </div>
            </div>

 <div class="col-lg-6">
                <div class="fv-row">
                    <label class="col-form-label">Observaciones</label>
                    <TextInput
                        id="observations"
                        v-model="form.observations"
                        class="form-control form-control-solid"
                        type="text"
                        :class="{ 'is-invalid': form.errors.observations }"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.observations"
                    />
                </div>
            </div>
       
               
        </div>
        <!--end::Row-->

                <FormItems
                    :items="form.items"
                    :products="products"
                    :invoice-lines-by-product="invoiceLinesByProduct"
                    @update:items="form.items = $event"
                />

                <div class="mt-4 text-end">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="$emit('submit')"
                    >
                        Guardar
                    </button>
                </div>
    </div>
    <!--end::Wrapper-->
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search,
.multiselect-search {
    background: var(--kt-input-solid-bg) !important;
}
</style>
