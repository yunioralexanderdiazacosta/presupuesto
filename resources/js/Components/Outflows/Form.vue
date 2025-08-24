<script setup>
import { watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import FormOutflows from "./FormOutflows.vue";
const props = defineProps({
        form: Object,
});
const page = usePage();



// Puedes agregar aquí lógica específica para outflows si es necesario
</script>
<template>
    <div class="mb-0">
        <div class="row">
            <div class="col-lg-4">
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
            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Proyecto</label>
                    <Multiselect
                        :placeholder="'Seleccione proyecto'"
                        v-model="form.project_id"
                        :close-on-select="true"
                        :options="page.props.projects"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.project_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors.project_id" />
                </div>
            </div>
            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Operación</label>
                    <Multiselect
                        :placeholder="'Seleccione operación'"
                        v-model="form.operation_id"
                        :close-on-select="true"
                        :options="page.props.operations"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.operation_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors.operation_id" />
                </div>
            </div>
            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Maquinaria</label>
                    <Multiselect
                        :placeholder="'Seleccione maquinaria'"
                        v-model="form.machinery_id"
                        :close-on-select="true"
                        :options="page.props.machineries"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.machinery_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors.machinery_id" />
                </div>
            </div>
        </div>
        <FormOutflows :form="form" />
    </div>
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
