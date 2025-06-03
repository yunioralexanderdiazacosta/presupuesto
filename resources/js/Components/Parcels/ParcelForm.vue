<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});
</script>
<template>
    <div class="fv-row">
        <label class="col-form-label">Nombre</label>
        <TextInput
            id="name"
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.name}"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>

    <!-- <div class="fv-row mb-8">
        <label for="companyReason" class="form-label required fs-6 fw-bold mb-3">Razon Social</label>
        <Multiselect
            :placeholder="'Seleccione razon social'"
            v-model="form.company_reason_id"
            :close-on-select="true"
            :options="$page.props.companyReasons"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.company_reason_id}"
            :searchable="true"
            :hide-selected="false"
        />
        <InputError class="mt-2" :message="form.errors.company_reason_id" />
    </div> -->


    <div class="fv-row">
        <label for="season" class="col-form-label">Temporada</label>
        <Multiselect
            :placeholder="'Seleccione temporada'"
            v-model="form.season_id"
            :close-on-select="true"
            :options="$page.props.seasons"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.season_id}"
            :searchable="true"
            :hide-selected="false"
        />
        <InputError class="mt-2" :message="form.errors.season_id" />
    </div>

     <div class="fv-row">
        <label for="observations" class="col-form-label">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
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
</style>