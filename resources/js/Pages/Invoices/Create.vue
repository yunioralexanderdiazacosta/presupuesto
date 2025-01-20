<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInvoice from '@/Components/Invoices/Form.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const title = 'Crear Factura';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Facturas', link: 'invoices.index' }, { title: title, active: true }];

const form = useForm({
	number: '',
	date: '',
	due_date: '',
	payment_term: '',
	payment_type: '',
	petty_cash: '',
	supplier_id: '',
	company_reason_id: '',
	type_document_id: '',
	products: [
		{
			product_id: '',
			unit_price: 0.00,
			amount: 1,
			observations: ''
		}
	]
});
</script>
 <Head :title="title" />
<template>
	<AppLayout>
		<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
			<!--begin::Content wrapper-->
			<div class="d-flex flex-column flex-column-fluid">
				<!--begin::Toolbar-->
				<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
					<!--begin::Toolbar container-->
					<div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
						<!--begin::Page title-->
						<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
							<!--begin::Title-->
                            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{title}}</h1>
                            <!--end::Title-->
                            <!--begin::Breadcrumb-->
                            <Breadcrumb :links="links" />
                            <!--end::Breadcrumb-->
						</div>
						<!--end::Page title-->
					</div>
					<!--end::Toolbar container-->
				</div>
				<!--end::Toolbar-->
				<!--begin::Content-->
				<div id="kt_app_content" class="app-content flex-column-fluid">
					<!--begin::Content container-->
					<div id="kt_app_content_container" class="app-container container-xxl">
						<!--begin::Form-->
						<FormInvoice :form="form" />
						<!--end::Form-->
					</div>
					<!--end::Content container-->
				</div>
				<!--end::Content-->
			</div>
			<!--end::Content wrapper-->
		</div>
	</AppLayout>
</template>