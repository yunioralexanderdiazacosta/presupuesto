<script setup>
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});
</script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label">Rut</label>
                <TextInput
                    id="rut"
                    v-model="form.rut"
                    class="form-control form-control-solid"
                    type="text"
                    :class="{'is-invalid': form.errors.rut}"
                />
                <InputError class="mt-2" :message="form.errors.rut" />
            </div>
        </div>
        <div class="col-lg-6">
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
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label">Representante legal</label>
                <TextInput
                    id="rut"
                    v-model="form.legal_representative"
                    class="form-control form-control-solid"
                    type="text"
                    :class="{'is-invalid': form.errors.legal_representative}"
                />
                <InputError class="mt-2" :message="form.errors.legal_representative" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label">Rut Representante legal</label>
                <TextInput
                    id="rut"
                    v-model="form.rut_representative"
                    class="form-control form-control-solid"
                    type="text"
                    :class="{'is-invalid': form.errors.rut_representative}"
                />
                <InputError class="mt-2" :message="form.errors.rut_representative" />
            </div>
        </div>
    </div>
    
    <div class="fv-row">
        <label for="address" class="col-form-label">Dirección</label>
        <textarea v-model="form.address" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.address }"></textarea>
        <InputError class="mt-2" :message="form.errors.address" />
    </div>
</template>