<script setup>
    import { ref, onMounted, nextTick } from 'vue';
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	const props = defineProps({
		form: Object
	});

    const getLevel2s = (event) => {
        if(event && event != ""){
            axios.get(route('levels2.get', event))
            .then(response => {
                props.form.level2s = response.data;
                props.form.level3s = [];
                props.form.level4s = [];
                props.form.level2_id = '';
                props.form.level3_id = ''; 
                props.form.level4_id = '';
            }).catch(error => console.log(error));
        }
    }

    const getLevel3s = (event) => {
        if(event && event != ""){
            axios.get(route('levels3.get', event))
            .then(response => {
                props.form.level3s = response.data;
                props.form.level4s = [];
                props.form.level3_id = '';
                props.form.level4_id = '';
            }).catch(error => console.log(error));
        }
    }

    const getLevel4s = (event) => {
        if(event && event != ""){
            axios.get(route('levels4.get', event))
            .then(response => {
                props.form.level4s = response.data;
                props.form.level4_id = '';
            }).catch(error => console.log(error));
        }
    }
</script>
<template>
    <div class="row">
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
    <div class="col-lg-6">
            <div class="fv-row">
                <label for="unit" class="col-form-label">Unidad</label>
                <Multiselect
                    :placeholder="'Seleccione la unidad'"
                    v-model="form.unit_id"
                    :close-on-select="true"
                    :options="$page.props.units"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.unit_id}"
                    :searchable="true"
                />
                <InputError class="mt-2" :message="form.errors.unit_id" />
            </div>
        </div>
    </div>
    <div class="row mb-3">
    <div class="col-lg-6">
            <div class="fv-row">
                <label for="level1" class="col-form-label">Nivel 1</label>
                <Multiselect
                    :placeholder="'Seleccione nivel 1'"
                    v-model="form.level1_id"
                    :close-on-select="true"
                    :options="$page.props.level1s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.unit_id}"
                    :searchable="true"
                    @select="getLevel2s($event)"
                />
                <InputError class="mt-2" :message="form.errors.level1_id" />
            </div>
        </div>
    <div class="col-lg-6">
            <div class="fv-row">
                <label for="level2" class="col-form-label">Nivel 2</label>
                <Multiselect
                    :placeholder="'Seleccione nivel 2'"
                    v-model="form.level2_id"
                    :close-on-select="true"
                    :options="form.level2s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.unit_id}"
                    :searchable="true"
                    @select="getLevel3s($event)"
                />
                <InputError class="mt-2" :message="form.errors.level2_id" />
            </div>
        </div>
    </div>
    <div class="row">
    <div class="col-lg-6">
            <div class="fv-row">
                <label for="level3" class="col-form-label">Nivel 3</label>
                <Multiselect
                    :placeholder="'Seleccione nivel 3'"
                    v-model="form.level3_id"
                    :close-on-select="true"
                    :options="form.level3s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.level3_id}"
                    :searchable="true"
                    @select="getLevel4s($event)"
                />
                <InputError class="mt-2" :message="form.errors.level3_id" />
            </div>
        </div>
    <div class="col-lg-6">
            <div class="fv-row">
                <label for="level4" class="col-form-label">Level 4</label>
                <Multiselect
                    :placeholder="'Seleccione nivel 4'"
                    v-model="form.level4_id"
                    :close-on-select="true"
                    :options="form.level4s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.level4_id}"
                    :searchable="true"
                />
                <InputError class="mt-2" :message="form.errors.level4_id" />
            </div>  
        </div>
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect,
.multiselect__input,
.multiselect__single {
    min-height: 32px !important;
    height: 32px !important;
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
    font-size: 1rem;
}

/* Agrandar la casilla de verificación (checkbox) */
.form-check-input[type="checkbox"] {
    width: 1.1em;
    height: 1.1em;
}

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
