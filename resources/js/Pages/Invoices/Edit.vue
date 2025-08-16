<script setup>
import Swal from 'sweetalert2';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInvoice from '@/Components/Invoices/Form.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const title = 'Editar Factura';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Facturas', link: 'invoices.index' }, { title: title, active: true }];

const props = defineProps({
	invoice: Object,
	invoiceProducts: Array
})

const form = useForm({
	date: props.invoice.date,
	due_date: props.invoice.due_date,
	payment_term: props.invoice.payment_term,
	payment_type: props.invoice.payment_type,
	petty_cash: props.invoice.petty_cash,
	supplier_id: props.invoice.supplier_id,
	company_reason_id: props.invoice.company_reason_id,
	type_document_id: props.invoice.type_document_id,
	number_document: props.invoice.number_document,
	products: props.invoiceProducts
});

// Asegura que cada producto tenga el unit_id correcto al cargar
form.products.forEach((p) => {
    if (!p.unit_id && p.product_id) {
        const prod = $page.props.products.find(prod => prod.value === p.product_id || prod.id === p.product_id);
        if (prod && prod.unit_id) {
            p.unit_id = prod.unit_id;
        }
    }
});

const update = () => {
	 form.post(route('invoices.update', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            msgSuccess('Actualizado correctamente');
            router.get(route('invoices.index'));
        }
    });
}

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
    });
};
</script>
<template>
	<Head :title="title" />
	<AppLayout>
		<Head :title="title" />
		<!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->  

        <div class="card my-3">
            <div class="card-header">
              <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                  <h5 class="mb-0" data-anchor="data-anchor" :id="title">{{title}}</h5>
                </div>
              </div>
            </div>
            <div class="card-body bg-body-tertiary">
				<form id="kt_invoice_form" @submit.prevent="update()">
					<!--begin::Form-->
					<FormInvoice :form="form" />
					<!--end::Form-->
					<div class="mb-0 text-end">
						<button type="submit" class="btn btn-success" id="kt_invoice_submit_button">
						<!--begin::Svg Icon | path: icons/duotune/general/gen016.svg-->
						<span class="svg-icon svg-icon-3">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M15.43 8.56949L10.744 15.1395C10.6422 15.282 10.5804 15.4492 10.5651 15.6236C10.5498 15.7981 10.5815 15.9734 10.657 16.1315L13.194 21.4425C13.2737 21.6097 13.3991 21.751 13.5557 21.8499C13.7123 21.9488 13.8938 22.0014 14.079 22.0015H14.117C14.3087 21.9941 14.4941 21.9307 14.6502 21.8191C14.8062 21.7075 14.9261 21.5526 14.995 21.3735L21.933 3.33649C22.0011 3.15918 22.0164 2.96594 21.977 2.78013C21.9376 2.59432 21.8452 2.4239 21.711 2.28949L15.43 8.56949Z" fill="currentColor" />
								<path opacity="0.3" d="M20.664 2.06648L2.62602 9.00148C2.44768 9.07085 2.29348 9.19082 2.1824 9.34663C2.07131 9.50244 2.00818 9.68731 2.00074 9.87853C1.99331 10.0697 2.04189 10.259 2.14054 10.4229C2.23919 10.5869 2.38359 10.7185 2.55601 10.8015L7.86601 13.3365C8.02383 13.4126 8.19925 13.4448 8.37382 13.4297C8.54839 13.4145 8.71565 13.3526 8.85801 13.2505L15.43 8.56548L21.711 2.28448C21.5762 2.15096 21.4055 2.05932 21.2198 2.02064C21.034 1.98196 20.8409 1.99788 20.664 2.06648Z" fill="currentColor" />
							</svg>
						</span>
						<!--end::Svg Icon-->Actualizar</button>
					</div>
				</form>
			</div>
		</div>
						
	</AppLayout>
</template>