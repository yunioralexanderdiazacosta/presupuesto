<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});

    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const cc = [];

    const families = [];

    const units = [];
</script>
<script setup></script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label for="cc" class="form-label required fs-6 fw-bold mb-3">CC</label>
                <Multiselect
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="cc"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.cc}"
                    :searchable="true"
                />
                <InputError class="mt-2" :message="form.errors.cc" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label for="families" class="form-label required fs-6 fw-bold mb-3">Familia</label>
                <Multiselect
                    :placeholder="'Seleccione familia'"
                    v-model="form.family"
                    :close-on-select="false"
                    :options="families"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.family}"
                    :searchable="true"
                />
                <InputError class="mt-2" :message="form.errors.family" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Nombre del producto</label>
                <TextInput
                    id="product_name"
                    v-model="form.product_name"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.product_name" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label for="familia" class="form-label required fs-6 fw-bold mb-3">Unidad</label>
                <Multiselect
                    :placeholder="'Seleccione unidad'"
                    v-model="form.unit"
                    :close-on-select="false"
                    :options="units"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.unit}"
                    :searchable="true"
                />
                <InputError class="mt-2" :message="form.errors.unit" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Tipo de dosis</label>
                <TextInput
                    id="dose_type"
                    v-model="form.dose_type"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.dose_type" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Dosis</label>
                <TextInput
                    id="dose"
                    v-model="form.dose"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.dose" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Mojamiento</label>
                <TextInput
                    id="product_name"
                    v-model="form.product_name"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.product_name" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Precio</label>
                <TextInput
                    id="price"
                    v-model="form.price"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.price" />
            </div>
        </div>
    </div>

    <div class="fv-row mb-3">
        <label for="months" class="form-label required fs-6 fw-bold mb-3">Meses</label>
        <Multiselect
            mode="tags"
            :placeholder="'Seleccione meses'"
            v-model="form.months"
            :close-on-select="false"
            :options="months"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.months}"
            :searchable="true"
        />
        <InputError class="mt-2" :message="form.errors.months" />
    </div>

    <div class="fv-row mb-3">
        <label for="observations" class="form-label fs-6 fw-bold mb-3">Observaciones</label>
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
    --ms-tag-bg: #eee;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}
</style>