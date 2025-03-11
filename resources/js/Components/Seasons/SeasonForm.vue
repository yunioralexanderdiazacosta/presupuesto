<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});
</script>
<template>
    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Nombre</label>
        <TextInput
            id="name"
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.name}"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>

    <div class="fv-row mb-8">
        <label for="observations" class="form-label fs-6 fw-bold mb-3">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }"></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>

       
    <div class="fv-row mb-3">
        <label for="month" class="form-label required fs-6 fw-bold mb-3">Mes de inicio</label>
        <Multiselect
            :placeholder="'Seleccione mes de inicio'"
            v-model="form.month_id"
            :close-on-select="false"
            :options="$page.props.months"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.month_id}"
            :searchable="true"
        />
        <InputError class="mt-2" :message="form.errors.month_id" />
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