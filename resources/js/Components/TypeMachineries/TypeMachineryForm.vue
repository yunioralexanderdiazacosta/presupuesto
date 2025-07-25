<script setup>
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';
    import Multiselect from '@vueform/multiselect';

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
</template>