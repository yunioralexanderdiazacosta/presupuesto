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
					<!-- begin::Invoice 3-->
					<div class="card">
						<!-- begin::Body-->
						<div class="card-body py-20">
							<!-- begin::Wrapper-->
							<div class="mw-lg-950px mx-auto w-100">
								<!-- begin::Header-->
								<div class="d-flex justify-content-between flex-column flex-sm-row mb-19">
									<h4 class="fw-bolder text-gray-800 fs-2qx pe-5 pb-7">Factura</h4>
									<!--end::Logo-->
									<div class="text-sm-end">
										<!--begin::Logo-->
										<AplicationLogo />
										<!--end::Logo-->
										<!--begin::Text-->
										<!--
										<div class="text-sm-end fw-semibold fs-4 text-muted mt-7">
											<div>Cecilia Chapman, 711-2880 Nulla St, Mankato</div>
											<div>Mississippi 96522</div>
										</div>-->
										<!--end::Text-->
									</div>
								</div>
								<!--end::Header-->
								<!--begin::Body-->
								<div class="pb-12">
									<!--begin::Wrapper-->
									<div class="d-flex flex-column gap-7 gap-md-10">
										<!--begin::Message-->
										<!--
										<div class="fw-bold fs-2">Dear Olivia Wild
										<span class="fs-6">(olivia@corpmail.com)</span>,
										<br />
										<span class="text-muted fs-5">Here are your order details. We thank you for your purchase.</span></div>-->
										<!--begin::Message-->
										<!--begin::Separator-->
										<div class="separator"></div>
										<!--begin::Separator-->
										<!--begin::Detail 1-->
										<div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold">
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Factura #</span>
												<span class="fs-5">{{invoice.number}}</span>
											</div>
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Fecha de Emisión</span>
												<span class="fs-5">{{invoice.date}}</span>
											</div>
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Fecha de Vencimiento</span>
												<span class="fs-5">{{invoice.due_date}}</span>
											</div>
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Proveedor</span>
												<span class="fs-5">{{supplier.name}}</span>
											</div>
										</div>
										<!--end::Detail 1-->
										<!--begin::Detail 2-->
										<div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold">
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Razón Social</span>
												<span class="fs-5">{{companyReason.name}}</span>
											</div>
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Tipo de Documento</span>
												<span class="fs-5">{{typeDocument.name}}</span>
											</div>
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Número de Documento</span>
												<span class="fs-5">{{invoice.number_document}}</span>
											</div>	
										</div>
										<!--end::Detail 2-->
										<!--begin::Detail 3-->
										<div class="d-flex flex-column flex-sm-row gap-7 gap-md-10 fw-bold">
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Plazo de Pago</span>
												<span class="fs-5">{{invoice.payment_term}}</span>
											</div>	
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Tipo de Pago</span>
												<span class="fs-5">{{invoice.payment_type == 1 ? 'Credito' : 'Contado'}}</span>
											</div>	
											<div class="flex-root d-flex flex-column">
												<span class="text-muted">Caja chica</span>
												<span class="fs-5">{{invoice.petty_cash == true ? 'Si' : 'No'}}</span>
											</div>	
										</div>
										<!--end::Detail 3-->
										<!--begin:Order summary-->
										<div class="d-flex justify-content-between flex-column">
											<!--begin::Table-->
											<div class="table-responsive border-bottom mb-9">
												<table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
													<thead>
														<tr class="border-bottom fs-6 fw-bold text-muted">
															<th class="min-w-175px pb-2">Producto</th>
															<th class="min-w-70px text-end pb-2">Cantidad</th>
															<th class="min-w-80px text-end pb-2">Precio</th>
															<th class="min-w-100px text-end pb-2">Total</th>
														</tr>
													</thead>
													<tbody class="fw-semibold text-gray-600">
														<!--begin::Products-->
														<tr v-for="(product, index) in invoiceProducts">
															<!--begin::Product-->
															<td>
																<div class="fw-bold">{{product.product_name}}</div>
															</td>
															<!--end::Product-->
															<!--begin::SKU-->
															<td class="text-end">{{product.amount}}</td>
															<!--end::SKU-->
															<!--begin::Quantity-->
															<td class="text-end">{{product.unit_price}}</td>
															<!--end::Quantity-->
															<!--begin::Total-->
															<td class="text-end">{{product.unit_price * product.amount}}</td>
															<!--end::Total-->
														</tr>
														<!--end::Products-->
														<!--begin::Grand total-->
														<tr>
															<td colspan="3" class="fs-3 text-dark fw-bold text-end">Total</td>
															<td class="text-dark fs-3 fw-bolder text-end">{{grant_total}}</td>
														</tr>
														<!--end::Grand total-->
													</tbody>
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
								<div class="d-flex flex-center d-print-none flex-wrap mt-lg-20 pt-13">
									<!-- begin::Actions-->
									<div class="my-1 me-5">
										<!-- begin::Pint-->
										<button type="button" class="btn btn-success my-1 me-12" onclick="window.print();">Imprimir</button>
										<!-- end::Pint-->
									</div>
									<!-- end::Actions-->
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