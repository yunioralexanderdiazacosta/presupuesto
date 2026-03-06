<script setup>
import Swal from 'sweetalert2';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInvoice from '@/Components/Invoices/Form.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
	typeDocuments: Array,
	suppliers: Array,
	companyReasons: Array,
	products: Array,
	units: Array,
	months: Array,
	prefill: Object,
	purchaseOrders: Array,
});

const title = props.prefill?.is_duplicate
	? 'Duplicar Factura #' + props.prefill.original_id
	: props.prefill?.expense_report_number
		? 'Factura desde Rendición ' + props.prefill.expense_report_number
		: 'Ingresar Factura';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Facturas', link: 'invoices.index' }, { title: title, active: true }];

const form = useForm({
	date: props.prefill?.date || '',
	due_date: '',
	month_id: props.prefill?.month_id || null,
	payment_term: props.prefill?.payment_term || '',
	payment_type: props.prefill?.payment_type || '',
	supplier_id: props.prefill?.supplier_id || '',
	company_reason_id: props.prefill?.company_reason_id || '',
	type_document_id: props.prefill?.type_document_id || '',
	number_document: props.prefill?.number_document || '',
	expense_item_id: props.prefill?.expense_item_id || null,
	purchase_order_id: null,
	products: props.prefill?.products?.length
		? props.prefill.products
		: [
			{
				product_id: props.prefill?.product_id || '',
				unit_price: props.prefill?.amount || 0.00,
				amount: 1,
				observations: props.prefill?.description || ''
			}
		]
});

const save = () => {
	// Validación previa: todos los precios deben ser mayores a cero
	const invalid = form.products.some(p => !p.unit_price || p.unit_price <= 0);
	if (invalid) {
		Swal.fire({
			icon: 'error',
			title: 'Precio inválido',
			text: 'El precio de cada producto debe ser mayor a cero.',
			confirmButtonColor: '#3085d6',
		});
		return;
	}
	form.post(route('invoices.store'), {
		preserveScroll: true,
		onSuccess: () => {
			msgSuccess('Guardado correctamente');
			router.get(route('invoices.index'));
		}
	});
}

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
    });
};
</script>
 
<template>
	<AppLayout>
		<Head :title="title" />
		<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
		<!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->  

	    <div class="card my-1 mx-1 px-1">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
									<h5 class="mb-0 d-flex align-items-center gap-2" data-anchor="data-anchor" :id="title">
										<i class="fas fa-file-invoice text-primary"></i>
										{{title}}
									</h5>
                </div>
            
              </div>
            </div>
            <div class="card-body bg-body-tertiary">
				<!-- Banner cuando viene de rendición -->
                <div v-if="prefill?.is_duplicate" class="alert alert-warning d-flex align-items-center mb-3 py-2">
					<i class="fas fa-copy me-2"></i>
					<div>
						<strong>Copia de Factura #{{ prefill.original_id }}</strong> —
						Todos los datos fueron pre-cargados. Ingrese el nuevo N° de documento y ajuste lo que corresponda.
					</div>
				</div>
				<div v-else-if="prefill" class="alert alert-info d-flex align-items-center mb-3 py-2">
					<i class="fas fa-info-circle me-2"></i>
					<div>
						<strong>Importando desde {{ prefill.expense_report_number }}</strong> — 
						Proveedor y monto pre-cargados. Complete los campos faltantes y guarde.
					</div>
				</div>
            	<form id="kt_invoice_form" @submit.prevent="save()">
					<!--begin::Form-->
					<FormInvoice :form="form" />
					<!--end::Form-->
					<div class="mb-0 text-end">
						<button type="submit" class="btn btn-primary mt-3" id="kt_invoice_submit_button">
						<!--begin::Svg Icon | path: icons/duotune/general/gen016.svg-->
						<span class="svg-icon svg-icon-3">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.43 8.56949L10.744 15.1395C10.6422 15.282 10.5804 15.4492 10.5651 15.6236C10.5498 15.7981 10.5815 15.9734 10.657 16.1315L13.194 21.4425C13.2737 21.6097 13.3991 21.751 13.5557 21.8499C13.7123 21.9488 13.8938 22.0014 14.079 22.0015H14.117C14.3087 21.9941 14.4941 21.9307 14.6502 21.8191C14.8062 21.7075 14.9261 21.5526 14.995 21.3735L21.933 3.33649C22.0011 3.15918 22.0164 2.96594 21.977 2.78013C21.9376 2.59432 21.8452 2.4239 21.711 2.28949L15.43 8.56949Z" fill="currentColor" />
								<path opacity="0.3" d="M20.664 2.06648L2.62602 9.00148C2.44768 9.07085 2.29348 9.19082 2.1824 9.34663C2.07131 9.50244 2.00818 9.68731 2.00074 9.87853C1.99331 10.0697 2.04189 10.259 2.14054 10.4229C2.23919 10.5869 2.38359 10.7185 2.55601 10.8015L7.86601 13.3365C8.02383 13.4126 8.19925 13.4448 8.37382 13.4297C8.54839 13.4145 8.71565 13.3526 8.85801 13.2505L15.43 8.56548L21.711 2.28448C21.5762 2.15096 21.4055 2.05932 21.2198 2.02064C21.034 1.98196 20.8409 1.99788 20.664 2.06648Z" fill="currentColor" />
							</svg>
						</span>
						<!--end::Svg Icon-->Guardar</button>
					</div>
				</form>
            </div>
        </div>            
	</AppLayout>
</template>