<script setup>
import Swal from 'sweetalert2';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInvoice from '@/Components/Invoices/Form.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { onMounted, watch } from 'vue';

const title = 'Editar Factura';
const page = usePage();

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Facturas', link: 'invoices.index' }, { title: title, active: true }];

const props = defineProps({
	invoice: Object,
	invoiceProducts: Array,
	protectedProductIds: {
		type: Array,
		default: () => []
	}
})

// Mostrar mensaje de error si existe
onMounted(() => {
	if (page.props.flash?.error) {
		Swal.fire({
			icon: 'error',
			title: 'No se puede editar',
			text: page.props.flash.error,
			confirmButtonColor: '#d33',
		});
	}
});

// Watch para errores flash que lleguen después
watch(() => page.props.flash?.error, (newError) => {
	if (newError) {
		Swal.fire({
			icon: 'error',
			title: 'No se puede editar',
			text: newError,
			confirmButtonColor: '#d33',
		});
	}
});

const form = useForm({
	date: props.invoice.date,
	due_date: props.invoice.due_date,
	payment_term: props.invoice.payment_term,
	payment_type: props.invoice.payment_type,
	supplier_id: props.invoice.supplier_id,
	month_id: props.invoice.month_id,
	company_reason_id: props.invoice.company_reason_id,
	type_document_id: props.invoice.type_document_id,
	number_document: props.invoice.number_document,
	purchase_order_id: props.invoice.purchase_order_id || null,
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
	form.post(route('invoices.update', props.invoice.id), {
		preserveScroll: true,
		onSuccess: () => {
			msgSuccess('Actualizado correctamente');
			router.get(route('invoices.index'));
		},
		onError: (errors) => {
			console.log('❌ Form errors:', errors);
		},
		onFinish: () => {
			// Verificar si hay mensaje de error flash después de la respuesta
			if (page.props.flash?.error) {
				Swal.fire({
					icon: 'error',
					title: 'No se puede editar',
					text: page.props.flash.error,
					confirmButtonColor: '#d33',
				});
			}
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
	<Head :title="title" />
	<AppLayout :title="title">
		<!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->  

        <div class="card my-3">
            <div class="card-header">
              <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                  <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                    <i class="fas fa-edit me-2"></i>{{ title }}
                  </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                  <button @click="router.get(route('invoices.index'))" type="button" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
                    <span class="d-none d-sm-inline-block ms-1">Volver</span>
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body bg-body-tertiary">
				<!-- Badge informativo de rendición -->
				<div v-if="invoice.expense_report" class="alert alert-info d-flex align-items-center py-2 px-3 mb-3" role="alert">
					<i class="fas fa-receipt me-2"></i>
					<small>Esta factura fue importada desde la rendición <strong>{{ invoice.expense_report.number }}</strong></small>
				</div>

				<form id="kt_invoice_form" @submit.prevent="update()">
					<!--begin::Form-->
					<FormInvoice :form="form" :protectedProductIds="props.protectedProductIds" />
					<!--end::Form-->
					<div class="mt-4 d-flex justify-content-end gap-2">
						<button @click="router.get(route('invoices.index'))" type="button" class="btn btn-secondary">
							<span class="fas fa-times me-1"></span>
							Cancelar
						</button>
						<button type="submit" class="btn btn-primary" id="kt_invoice_submit_button">
							<span class="fas fa-save me-1"></span>
							Guardar Cambios
						</button>
					</div>
				</form>
			</div>
		</div>
						
	</AppLayout>
</template>