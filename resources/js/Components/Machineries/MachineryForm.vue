<script setup>
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';
    import Multiselect from '@vueform/multiselect';

	defineProps({
		form: Object
	});
</script>
<template>
    <div class="fv-row mb-8">
        <label for="type_machinery_id" class="form-label required fs-6 fw-bold mb-3">Tipo Maquinaria</label>
        <Multiselect
            :placeholder="'Seleccione tipo de maquinaria'"
            v-model="form.type_machinery_id"
            :close-on-select="true"
            :options="$page.props.typeMachineries"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.type_machinery_id}"
            :searchable="true"
            :hide-selected="false"
        />
        <InputError class="mt-2" :message="form.errors.type_machinery_id" />
    </div>

    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Código Maquinaria</label>
        <TextInput
            id="cod_machinery"
            v-model="form.cod_machinery"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.cod_machinery}"
        />
        <InputError class="mt-2" :message="form.errors.cod_machinery" />
    </div>

    <div class="fv-row mb-8">
        <label for="company_reason_id" class="form-label required fs-6 fw-bold mb-3">Razón social</label>
        <Multiselect
            :placeholder="'Seleccione razón social'"
            v-model="form.company_reason_id"
            :close-on-select="true"
            :options="$page.props.companyReasons"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.company_reason_id}"
            :searchable="true"
            :hide-selected="false"
        />
        <InputError class="mt-2" :message="form.errors.company_reason_id" />
    </div>


     <div class="fv-row mb-8">
        <label for="volume" class="form-label required fs-6 fw-bold mb-3">Volumen</label>
        <TextInput
            id="volume"
            v-model="form.volume"
            class="form-control form-control-solid"
            type="number"
            :class="{'is-invalid': form.errors.volume}"
        />
        <InputError class="mt-2" :message="form.errors.volume" />
    </div>

    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Marca</label>
            <TextInput
                id="brand"
                v-model="form.brand"
                class="form-control form-control-solid"
                type="text"
                :class="{'is-invalid': form.errors.brand}"
            />
        <InputError class="mt-2" :message="form.errors.brand" />
    </div>

    <div class="fv-row mb-3">
        <label for="observations" class="form-label fs-6 fw-bold mb-3">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>

    <div class="row gx-10 mb-5">
        <div class="col-lg-12">
            <div class="form-check form-check-solid form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="is_active" value=true>
                <label class="form-check-label fw-bold ps-2">Activo</label>
            </div>
        </div>
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #eee;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}
</style>