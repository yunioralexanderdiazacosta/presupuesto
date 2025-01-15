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
        <label class="required fs-6 fw-semibold mb-2">Rut</label>
        <TextInput
            id="rut"
            v-model="form.rut"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.rut}"
        />
        <InputError class="mt-2" :message="form.errors.rut" />
    </div>

     <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Contacto</label>
        <TextInput
            id="contact"
            v-model="form.contact"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.contact}"
        />
        <InputError class="mt-2" :message="form.errors.contact" />
    </div>

     <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Email</label>
        <TextInput
            id="email"
            v-model="form.email"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.email}"
        />
        <InputError class="mt-2" :message="form.errors.email" />
    </div>

    <div class="fv-row mb-8">
        <label class="required fs-6 fw-semibold mb-2">Telefono</label>
        <TextInput
            id="phone"
            v-model="form.phone"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.phone}"
        />
        <InputError class="mt-2" :message="form.errors.phone" />
    </div>


    <!--
    <div class="fv-row mb-3">
        <label for="month" class="form-label required fs-6 fw-bold mb-3">Presupuesto</label>
        <Multiselect
            :placeholder="'Seleccione el presupuesto'"
            v-model="form.budget_id"
            :close-on-select="false"
            :options="$page.props.budgets"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.budget_id}"
            :searchable="true"
        />
        <InputError class="mt-2" :message="form.errors.budget_id" />
    </div>-->
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