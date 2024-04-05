<script setup>
    import { ref } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';
    import CalculateWorkDayModal from '@/Components/ManPowers/CalculateWorkDayModal2.vue';

	const valid = ref(false);

    const props = defineProps({
		form: Object
	});

    const formWorkDay = useForm({
        performance: '',
        floors: ''
    });

    const storeWorkDay = () => {
        onValidated();
        if(valid.value == true){
            props.form.workday = (formWorkDay.floors / formWorkDay.performance).toFixed(2).replace(/\.00$/, '');
            $('#calculateWorkDay2').modal('hide');
            formWorkDay.reset();
        }
    }

    const onCalculated = () => {
        formWorkDay.reset();
        $('#calculateWorkDay2').modal('show');
    }

    const onValidated = () => {
        formWorkDay.errors = {};
        valid.value = true;
        if(formWorkDay.performance == ""){
            formWorkDay.errors.performance = 'Este campo es obligatorio';
            valid.value = false;
        } if(formWorkDay.floors == ""){
            formWorkDay.errors.floors = 'Este campo es obligatorio';
            valid.value = false;
        }
    }
</script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label for="cc" class="form-label required fs-6 fw-bold mb-3">CC</label>
                <Multiselect
                    mode="tags"
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="$page.props.costCenters"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.cc}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.cc" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row mb-8">
                <label for="families" class="form-label required fs-6 fw-bold mb-3">Familia</label>
                <Multiselect
                    :placeholder="'Seleccione familia'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="$page.props.subfamilies"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.subfamily_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.subfamily_id" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Nombre del producto</label>
                <TextInput
                    id="product_name"
                    v-model="form.product_name"
                    class="form-control form-control-solid"
                    type="text"
                    :class="{'is-invalid': form.errors.product_name}"
                />
                <InputError class="mt-2" :message="form.errors.product_name" />
            </div>
        </div>
    
        <div class="col-lg-4">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Jornadas/hectarea</label>
                <div class="input-group">
                    <input type="number" id="workday" v-model="form.workday" class="form-control form-control-solid" aria-describedby="jornadas" step="0.00" :class="{'is-invalid': form.errors.workday}">
                    <button type="button" @click="onCalculated()" id="jornadas" class="btn btn-light text-primary"><i class="fas fa-question-circle" style="font-size: 20px"></i></button>
                </div>
                <InputError class="mt-2" :message="form.errors.workday" />
            </div>
        </div>

        <div class="col-lg-4">
            <div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Precio</label>
                <TextInput
                    id="price"
                    v-model="form.price"
                    class="form-control form-control-solid"
                    type="number"
                    :class="{'is-invalid': form.errors.price}"
                />
                <InputError class="mt-2" :message="form.errors.price" />
            </div>
        </div>

    <CalculateWorkDayModal @store="storeWorkDay" :form="formWorkDay" />
    </div>

    <div class="fv-row mb-3">
        <label for="months" class="form-label required fs-6 fw-bold mb-3">Meses</label><br>
        <template v-for="value in $page.props.months" :key="value.id">
            <div class="form-check form-check-solid form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" v-model="form.months" :id="'kt_month_'+value.id" :value="value.value">
                <label class="form-check-label ps-2" :for="'kt_month_'+value.id">{{value.label}}</label>
            </div>
        </template>
        <small class="text-danger">{{form.errors.months}}</small>
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