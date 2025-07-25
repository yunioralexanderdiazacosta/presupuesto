<script setup>
	import Multiselect from '@vueform/multiselect';
	import InputError from '@/Components/InputError.vue';
	
	const props = defineProps({
		form: Object
	})

	const add = () => {
	props.form.products.push({
			product_id: '',
			unit_price: 0.00,
			amount: 1,
			observations: ''
		});
	}

	const onDeleted = (index) => {
	    Swal.fire({
	        title: '¿Estás seguro de que quieres eliminar el registro?',
	        icon: 'warning',
	        showCancelButton: true,
	        confirmButtonColor: 'rgb(0, 158, 247)',
	        cancelButtonColor: '#6e6e6e',
	        cancelButtonText: 'Cancelar',
	        confirmButtonText: 'Confirmar',
	    }).then((result) => {
	        if (result.isConfirmed) {
	           props.form.products.splice(index, 1);
	        }
	    });
	}

	const calculateTotal = () => {
		var total = 0;
		props.form.products.filter(element => {
			total = total + (element.unit_price * element.amount)
		});

		return total;
	}

</script>
<template>
	<!--begin::Table wrapper-->
	<div class="table-responsive mb-10">
		<!--begin::Table-->
		<table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
			<!--begin::Table head-->
			<thead>
				<tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
					<th class="min-w-300px w-475px">Producto</th>
					<th class="min-w-100px w-100px">Cantidad</th>
					<th class="min-w-150px w-150px">Precio</th>
					<th class="min-w-100px w-150px text-end">Total</th>
					<th class="min-w-75px w-75px text-end">Acción</th>
				</tr>
			</thead>
			<!--end::Table head-->
			<!--begin::Table body-->
			<tbody>
				<tr class="border-bottom border-bottom-dashed" v-for="(product, index) in form.products" data-kt-element="item">
					<td class="pe-7">
						<Multiselect
		                    :placeholder="'Seleccione producto'"
		                    v-model="product.product_id"
		                    :close-on-select="true"
		                    :options="$page.props.products"
		                    class="multiselect-blue form-control"
		                    :class="{'is-invalid': form.errors['products.'+index+'.product_id']}"
		                    :searchable="true"
		                    :hide-selected="false"
		                />
						<input type="text" class="form-control form-control-solid mt-3" v-model="product.observations" placeholder="Observaciones..." />
					</td>
					<td class="ps-0">
						<input class="form-control form-control-solid" type="number" min="1" v-model="product.amount" value="1" data-kt-element="quantity" />
					</td>
					<td>
						<input type="number" class="form-control form-control-solid text-end unit_price" v-model="product.unit_price" value="0.00" step="0.00" />
					</td>
					<td class="text-end text-nowrap">$
					<span data-kt-element="total">{{product.unit_price * product.amount}}</span></td>
					<td class="text-end">
						<button type="button" @click="onDeleted(index)" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
							<!--begin::Svg Icon | path: icons/duotune/general/gen027.svg-->
							<span class="svg-icon svg-icon-3">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
									<path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
									<path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
								</svg>
							</span>
							<!--end::Svg Icon-->
						</button>
					</td>
				</tr>
			</tbody>
			<!--end::Table body-->
			<!--begin::Table foot-->
			<tfoot>
				<tr class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
					<th class="text-primary">
						<button type="button" @click="add()" class="btn btn-link py-1" data-kt-element="add-item">Agregar</button>
					</th>
					<th colspan="2"></th>
					<th colspan="2"></th>
				</tr>
				<tr class="align-top fw-bold text-gray-700">
					<th></th>
					<th colspan="2" class="fs-4 ps-0">Total</th>
					<th colspan="2" class="text-end fs-4 text-nowrap">$
					<span data-kt-element="grand-total">{{calculateTotal()}}</span></th>
				</tr>
			</tfoot>
			<!--end::Table foot-->
		</table>
	</div>
	<!--end::Table-->
	<!--begin::Item template-->
	<table class="table d-none" data-kt-element="item-template">
		<tr class="border-bottom border-bottom-dashed" data-kt-element="item">
			<td class="pe-7">
				<input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name" />
				<input type="text" class="form-control form-control-solid" name="description[]" placeholder="Description" />
			</td>
			<td class="ps-0">
				<input class="form-control form-control-solid" type="number" min="1" name="quantity[]" placeholder="1" data-kt-element="quantity" />
			</td>
			<td>
				<input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" data-kt-element="price" />
			</td>
			<td class="text-end">$
			<span data-kt-element="total">0.00</span></td>
			<td class="pt-5 text-end">
				<button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
					<!--begin::Svg Icon | path: icons/duotune/general/gen027.svg-->
					<span class="svg-icon svg-icon-3">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
							<path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
							<path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
						</svg>
					</span>
					<!--end::Svg Icon-->
				</button>
			</td>
		</tr>
	</table>
	<table class="table d-none" data-kt-element="empty-template">
		<tr data-kt-element="empty">
			<th colspan="5" class="text-muted text-center py-10">No items</th>
		</tr>
	</table>
	<!--end::Item template-->
</template>