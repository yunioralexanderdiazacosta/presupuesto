<script setup>
	import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';
	import FormProducts from './FormProducts.vue';
	const props = defineProps({
		form: Object
	});

	const paymentTypes = [{id: 1, label: 'Credito'}, {id: 2, label: 'Contado'}];
</script>
<template>
	
<!--begin::Wrapper-->
<div class="d-flex flex-column align-items-start flex-xxl-row">
	<!--begin::Input group-->
	<div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Enter invoice number">
		<span class="fs-2x fw-bold text-gray-800">Factura #</span>
		<input type="text" v-model="form.number" class="form-control form-control-solid fw-bold fs-3 w-200px" placehoder="" />
	</div>
	<!--end::Input group-->
</div>
<!--end::Top-->
<!--begin::Separator-->
<div class="separator separator-dashed my-10"></div>
<!--end::Separator-->
<!--begin::Wrapper-->
<div class="mb-0">
	<div class="row gx-10 mb-5">
		<div class="col-lg-6">
			<div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Fecha</label>
                <TextInput
                    id="date"
                    v-model="form.date"
                    class="form-control form-control-solid"
                    type="date"
                    :class="{'is-invalid': form.errors.date}"
                />
                <InputError class="mt-2" :message="form.errors.date" />
            </div>
		</div>
		<div class="col-lg-6">
			<div class="fv-row mb-8">
                <label class="required fs-6 fw-semibold mb-2">Fecha de vencimiento</label>
                <TextInput
                    id="due_date"
                    v-model="form.due_date"
                    class="form-control form-control-solid"
                    type="date"
                    :class="{'is-invalid': form.errors.due_date}"
                />
                <InputError class="mt-2" :message="form.errors.due_date" />
            </div>
		</div>
	</div>
	<!--begin::Row-->
	<div class="row gx-10 mb-5">
		<!--begin::Col-->
		<div class="col-lg-6">
			<div class="fv-row mb-8">
                <label for="" class="form-label required fs-6 fw-bold mb-3">Proveedor</label>
                <Multiselect
                    :placeholder="'Seleccione proveedor'"
                    v-model="form.supplier_id"
                    :close-on-select="false"
                    :options="$page.props.suppliers"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.supplier_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.supplier_id" />
            </div>
		</div>
		<!--end::Col-->
		<!--begin::Col-->
		<div class="col-lg-6">
			<div class="fv-row mb-8">
                <label for="" class="form-label required fs-6 fw-bold mb-3">Razón social</label>
                <Multiselect
                    :placeholder="'Seleccione razón social'"
                    v-model="form.company_reason_id"
                    :close-on-select="false"
                    :options="$page.props.companyReasons"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.company_reason_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.company_reason_id" />
            </div>
		</div>
		<!--end::Col-->
	</div>
	<!--end::Row-->
	<!--begin::Row-->
	<div class="row gx-10 mb-5">
		<div class="col-lg-6">
            <div class="row">
                <div class="col-lg-6">
                    <div class="fv-row mb-8">
                        <label for="typeDocument" class="form-label required fs-6 fw-bold mb-3">Tipo de documento</label>
                        <Multiselect
                            :placeholder="'Seleccione tipo de documento'"
                            v-model="form.type_document_id"
                            :close-on-select="false"
                            :options="$page.props.typeDocuments"
                            class="multiselect-blue form-control"
                            :class="{'is-invalid': form.errors.type_document_id}"
                            :searchable="true"
                            :hide-selected="false"
                        />
                        <InputError class="mt-2" :message="form.errors.type_document_id" />
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="fv-row mb-8">
                        <label class="required fs-6 fw-semibold mb-2">Número de documento</label>
                        <TextInput
                            id="number_document"
                            v-model="form.number_document"
                            class="form-control form-control-solid"
                            type="text"
                            :class="{'is-invalid': form.errors.number_document}"
                        />
                        <InputError class="mt-2" :message="form.errors.number_document" />
                    </div>
                </div>
            </div>
		</div>
		  <div class="col-lg-3">
            	<label for="paymentTerm" class="form-label required fs-6 fw-bold mb-3">Plazo de pago</label>
                <Multiselect
                    :placeholder="'Seleccione plazo de pago'"
                    v-model="form.payment_term"
                    :close-on-select="false"
                    :options="[0, 30, 60, 90, 120]"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.payment_term}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.payment_term" />
            </div>
            <div class="col-lg-3">
            	 <div class="fv-row mb-8">
	                <label for="unit" class="form-label required fs-6 fw-bold mb-3">Tipo de pago</label><br>
	                <template v-for="value in paymentTypes">
	                    <div class="form-check form-check-solid form-check-inline mb-3 mt-3">
	                        <input class="form-check-input" type="radio" v-model="form.payment_type" :id="'payment_type_'+value.id" :value="value.id">
	                        <label class="form-check-label ps-1" :for="'payment_type_'+value.id">{{value.label}}</label>
	                    </div>
	                </template>
	                <small class="text-danger mt-2" :v-if="form.errors.unit_id">{{form.errors.unit_id}}</small>
	            </div>
            </div>
	</div>
	<!--end::Row-->

	<div class="row gx-10 mb-5">
		<div class="col-lg-12">
            <div class="form-check form-check-solid form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" v-model="form.petty_cash" id="petty" value=true>
                <label class="form-check-label fw-bold ps-2">Caja chica</label>
            </div>
        </div>
	</div>
	<FormProducts :form="form" />
</div>
<!--end::Wrapper-->
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
