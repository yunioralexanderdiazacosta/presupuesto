<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import AplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
	invoice: Object,
	supplier: Object,
	companyReason: Object,
	typeDocument: Object,
	invoiceProducts: Array,
	grant_total: String
});

const title = 'Ver Factura';

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Facturas', link: 'invoices.index' }, { title: title, active: true }];

const printInvoice = () => {
	window.print();
};

const downloadPDF = () => {
	// Opción 1: Usar print to PDF del navegador
	window.print();
	// El usuario puede seleccionar "Guardar como PDF" en el diálogo de impresión
};

</script>
<template>
<Head :title="title" />
<AppLayout>
	<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
		<!--begin::Content wrapper-->
		<div class="d-flex flex-column flex-column-fluid">
			<!--begin::Toolbar-->
			<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 d-print-none">
				<!--begin::Toolbar container-->
				<div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
					<!--begin::Page title-->
					<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
						<!--begin::Title-->
						<h1 class="page-heading d-flex text-dark fw-bold fs-5 flex-column justify-content-center my-0">{{title}}</h1>
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
					<!-- begin::Invoice 3-->
					<div class="card shadow-sm">
						<!-- begin::Body-->
						<div class="card-body p-6">
							<!-- begin::Wrapper-->
							<div class="mw-lg-950px mx-auto w-100">
								<!-- begin::Header-->
								<div class="d-flex justify-content-between align-items-start mb-2">
									<div>
										<h5 class="fw-bold text-gray-800 mb-1">Factura #{{ invoice.id }}</h5>
										<span class="badge badge-light-primary fs-8">{{ typeDocument.name }}</span>
									</div>
									<!--begin::Logo-->
									<div class="text-end">
										<AplicationLogo />
									</div>
									<!--end::Logo-->
								</div>
								<!--end::Header-->

								<!--begin::Separator-->
								<div class="separator separator-dashed mb-2"></div>
								<!--end::Separator-->

								<!--begin::Body-->
								<div class="pb-4">
									<!--begin::Wrapper-->
									<div class="d-flex flex-column gap-4">
										<!--begin::Info Grid-->
										<div class="row g-3">
											<!-- Columna 1 -->
											<div class="col-md-6">
												<div class="card bg-light-primary border-0 h-100">
													<div class="card-body p-1">
														<h6 class="text-primary fw-bold mb-2 fs-7">
															<i class="fas fa-building me-1"></i>Proveedor
														</h6>
														<div class="mb-2">
															<span class="text-muted d-block fs-8 mb-0">Nombre</span>
															<span class="fw-semibold fs-7">{{ supplier.name }}</span>
														</div>
														<div>
															<span class="text-muted d-block fs-8 mb-0">Razón Social</span>
															<span class="fw-semibold fs-7">{{ companyReason.name }}</span>
														</div>
													</div>
												</div>
											</div>

											<!-- Columna 2 -->
											<div class="col-md-6">
												<div class="card bg-light-info border-0 h-100">
													<div class="card-body p-3">
														<h6 class="text-info fw-bold mb-2 fs-7">
															<i class="fas fa-file-invoice me-1"></i>Documento
														</h6>
														<div class="row">
															<div class="col-6 mb-2">
																<span class="text-muted d-block fs-8 mb-0">Número</span>
																<span class="fw-semibold fs-7">{{ invoice.number_document }}</span>
															</div>
															<div class="col-6 mb-2">
																<span class="text-muted d-block fs-8 mb-0">Emisión</span>
																<span class="fw-semibold fs-7">{{ invoice.date }}</span>
															</div>
															<div class="col-6">
																<span class="text-muted d-block fs-8 mb-0">Vencimiento</span>
																<span class="fw-semibold fs-7">{{ invoice.due_date }}</span>
															</div>
															<div class="col-6" v-if="invoice.month">
																<span class="text-muted d-block fs-8 mb-0">Mes Contable</span>
																<span class="fw-semibold fs-7">{{ invoice.month }}</span>
															</div>
														</div>
													</div>
												</div>
											</div>

											<!-- Columna 3 -->
											<div class="col-md-6 col-lg-4">
												<div class="card bg-light-success border-0 h-100">
													<div class="card-body p-3">
														<h6 class="text-success fw-bold mb-2 fs-7">
															<i class="fas fa-credit-card me-1"></i>Pago
														</h6>
														<div class="mb-2">
															<span class="text-muted d-block fs-8 mb-0">Tipo</span>
															<span class="badge badge-success badge-sm">{{ invoice.payment_type == 1 ? 'Crédito' : 'Contado' }}</span>
														</div>
														<div>
															<span class="text-muted d-block fs-8 mb-0">Plazo</span>
															<span class="fw-semibold fs-7">{{ invoice.payment_term }}</span>
														</div>
													</div>
												</div>
											</div>

											<!-- Columna 4 -->
											<div class="col-md-6 col-lg-4">
												<div class="card bg-light-secondary border-0 h-100">
													<div class="card-body p-3">
														<h6 class="text-secondary fw-bold mb-2 fs-7">
															<i class="fas fa-wallet me-1"></i>Caja Chica
														</h6>
														<div>
															<span class="text-muted d-block fs-8 mb-0">Estado</span>
															<span :class="invoice.petty_cash ? 'badge badge-success badge-sm' : 'badge badge-secondary badge-sm'">
																{{ invoice.petty_cash ? 'Sí' : 'No' }}
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>
										<!--end::Info Grid-->

										<!--begin::Separator-->
										<div class="separator separator-dashed my-1"></div>
										<!--end::Separator-->

										<!--begin:Order summary-->
										<div class="d-flex justify-content-between flex-column">
											<h5 class="fw-bold text-gray-800 mb-3 fs-6">
												<i class="fas fa-box me-2 text-primary"></i>Productos
											</h5>
											<!--begin::Table-->
											<div class="table-responsive border rounded mb-4">
												<table class="table table-row-bordered table-hover align-middle gs-3 gy-2 mb-0">
													<thead class="bg-light">
														<tr class="fw-bold text-gray-700 fs-7">
															<th class="ps-4 min-w-200px">Producto</th>
															<th class="text-center min-w-80px">Cantidad</th>
															<th class="text-end min-w-100px">Precio Unit.</th>
															<th class="text-end pe-4 min-w-100px">Total</th>
														</tr>
													</thead>
													<tbody class="fw-semibold text-gray-600 fs-7">
														<!--begin::Products-->
														<tr v-for="(product, index) in invoiceProducts" :key="index">
															<td class="ps-4 py-2">
																<div class="fw-bold text-gray-800">{{ product.product_name }}</div>
															</td>
															<td class="text-center py-2">{{ product.amount }}</td>
															<td class="text-end py-2">{{ product.unit_price }}</td>
															<td class="text-end pe-4 py-2 fw-bold text-gray-800">{{ product.unit_price * product.amount }}</td>
														</tr>
														<!--end::Products-->
													</tbody>
													<tfoot class="bg-light-primary">
														<tr>
															<td colspan="3" class="ps-4 fs-5 text-primary fw-bold py-3">TOTAL</td>
															<td class="text-primary fs-4 fw-bolder text-end pe-4 py-3">{{ grant_total }}</td>
														</tr>
													</tfoot>
												</table>
											</div>
											<!--end::Table-->
										</div>
										<!--end:Order summary-->
									</div>
									<!--end::Wrapper-->
								</div>
								<!--end::Body-->

								<!-- begin::Footer-->
								<div class="d-flex justify-content-between align-items-center d-print-none pt-4 border-top">
									<button type="button" class="btn btn-sm btn-light-primary" onclick="window.history.back();">
										<i class="fas fa-arrow-left me-1"></i>Volver
									</button>
									<div class="d-flex gap-2">
										<button type="button" class="btn btn-sm btn-light-success" @click="downloadPDF">
											<i class="fas fa-file-pdf me-1"></i>Guardar PDF
										</button>
										<button type="button" class="btn btn-sm btn-primary" @click="printInvoice">
											<i class="fas fa-print me-1"></i>Imprimir
										</button>
									</div>
								</div>
								<!-- end::Footer-->
							</div>
							<!-- end::Wrapper-->
						</div>
						<!-- end::Body-->
					</div>
					<!-- end::Invoice 1-->
				</div>
				<!--end::Content container-->
			</div>
			<!--end::Content-->
		</div>
		<!--end::Content wrapper-->
	</div>
</AppLayout>	
</template>

<style scoped>
@media print {
	/* Ocultar elementos innecesarios */
	.d-print-none {
		display: none !important;
	}

	/* Ajustar márgenes de la página */
	@page {
		margin: 1.5cm;
		size: A4;
	}

	/* Asegurar que todo el contenido se vea bien */
	body {
		print-color-adjust: exact;
		-webkit-print-color-adjust: exact;
	}

	/* Mantener colores de fondo en las cards */
	.card,
	.bg-light-primary,
	.bg-light-info,
	.bg-light-success,
	.bg-light-secondary,
	.bg-light,
	.badge {
		print-color-adjust: exact;
		-webkit-print-color-adjust: exact;
	}

	/* Evitar saltos de página dentro de elementos */
	.card-body,
	.card,
	tr {
		page-break-inside: avoid;
	}

	/* Ajustar tamaños para impresión */
	.card {
		box-shadow: none !important;
		border: 1px solid #e0e0e0 !important;
	}

	/* Reducir padding para aprovechar mejor el espacio */
	.card-body {
		padding: 1rem !important;
	}

	/* Asegurar que la tabla se vea bien */
	table {
		page-break-inside: auto;
	}

	thead {
		display: table-header-group;
	}

	tfoot {
		display: table-footer-group;
	}
}
</style>