<script setup>
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});
</script>
<template>
    <div class="row">
        <div class="col-lg-8">
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
        </div>
        <div class="col-lg-4">
            <div class="fv-row">
                <label class="col-form-label">Estado</label>
                <select v-model="form.is_active" class="form-select form-select-solid">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                </select>
            </div>
        </div>
    </div>
</template>
