<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	const props = defineProps({
		form: Object
	});

    const addItem = () => {
        props.form.products.push({
            product_name: '',
            price: '',
            observations: '',
            months: []
        });
    }

    const removeItem = (index) => {
        props.form.products.splice(index, 1);
    }
</script>
<script setup></script>
<template>
    <div class="row">
        <div class="col-md-6">
            <div class="fv-row mb-2">
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
        <div class="col-md-6">
            <div class="fv-row mb-2">
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
    <template v-for="(product,index) in form.products" :key="index">
        <hr>
        <div class="row">
            <div class="col-md-4">
                <div class="fv-row mb-8">
                    <label class="required fs-6 fw-semibold mb-2">Nombre del producto</label>
                    <TextInput
                        id="product_name"
                        v-model="product.product_name"
                        class="form-control form-control-solid"
                        type="text"
                         :class="{'is-invalid': form.errors['products.'+index+'.product_name']}"
                    />
                    <InputError class="mt-2" :message="form.errors.product_name" />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="fv-row mb-8">
                    <label class="required fs-6 fw-semibold mb-2">Jornadas</label>
                    <TextInput
                        id="workday"
                        v-model="product.workday"
                        class="form-control form-control-solid"
                        type="number"
                        step="0.00"
                        :class="{'is-invalid': form.errors['products.'+index+'.workday']}"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.workday']" />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="fv-row mb-8">
                    <label class="required fs-6 fw-semibold mb-2">Precio</label>
                    <TextInput
                        id="price"
                        v-model="product.price"
                        class="form-control form-control-solid"
                        type="number"
                        :class="{'is-invalid': form.errors['products.'+index+'.price']}"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.price']" />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="fv-row mb-3">
                    <label for="months" class="form-label required fs-6 fw-bold mb-3">Meses</label><br>
                    <template v-for="value in $page.props.months" :key="value.id">
                        <div style="margin-right: 0.5rem;" class="form-check form-check-solid form-check-inline mb-3">
                            <input class="form-check-input" type="checkbox" v-model="product.months" :id="'kt_month_'+value.id" :value="value.value">
                            <label class="form-check-label ps-2" :for="'kt_month_'+value.id">{{value.label}}</label>
                        </div>
                    </template>
                    <small class="text-danger" v-if="form.errors['products.'+index+'.months']"><br>{{form.errors['products.'+index+'.months']}}</small> 
                </div>
            </div>
            <div class="col-lg-6">
                <div class="fv-row mb-3">
                    <label for="observations" class="form-label fs-6 fw-bold mb-3">Observaciones</label>
                    <textarea v-model="product.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
                    <InputError class="mt-2" :message="form.errors.observations" />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 text-end">
                <button type="button" @click="removeItem(index)" class="btn btn-sm btn-danger me-2" v-if="form.products.length > 1">
                    <i class="fa fa-minus"></i>
                </button>
                <button type="button" @click="addItem()" v-if="form.products.length == (index + 1)" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
    </template>
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