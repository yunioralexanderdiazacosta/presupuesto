
<script setup>
import { watch } from 'vue';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';
import { usePage } from '@inertiajs/vue3';
const $page = usePage().props;

const props = defineProps({
    form: Object
})

// Función para crear un nuevo producto (taggable): devuelve objeto con id y name
const newTag = (input) => ({ id: input, name: input });

const add = () => {
    props.form.products.push({
        product_id: '',
        unit_id: '',                // Unidad seleccionada o nueva
        unit_price: 0.00,
        amount: 1,
        observations: ''
    });
}

const onDeleted = (index) => {
	props.form.products.splice(index, 1);
}


const calculateTotal = () => {
	var total = 0;
	props.form.products.filter(element => {
		total = total + (element.unit_price * element.amount)
	});
	return total;
}

// Elimina solo la última línea vacía (sin producto, unidad, cantidad, precio ni observaciones)
const removeLastEmptyLine = () => {
	for (let i = props.form.products.length - 1; i >= 0; i--) {
		const p = props.form.products[i];
		if (!p.product_id && !p.unit_id && (!p.amount || p.amount <= 1) && (!p.unit_price || p.unit_price <= 0) && (!p.observations || p.observations.trim() === '')) {
			props.form.products.splice(i, 1);
			break;
		}
	}
}

// Crear diccionario de productos para búsqueda instantánea por id
const productDict = {};
($page.products || []).forEach(p => {
	productDict[p.value ?? p.id] = p;
});

// Watch optimizado para asignar automáticamente la unidad al seleccionar producto
watch(
	() => props.form.products.map(p => p.product_id),
	(newProductIds, oldProductIds) => {
		newProductIds.forEach((productId, idx) => {
			if (productId && productId !== oldProductIds[idx]) {
       const producto = productDict[productId];
       console.log('Watcher onProductSelect', { idx, productId, producto });
				if (producto && producto.unit_id) {
					props.form.products[idx].unit_id = producto.unit_id;
				}
			}
		});
	},
	{ deep: true }
);
</script>
<template>
	<div class="elegant-divider my-2"></div>
	<!--begin::Table wrapper-->
	<div class="table-responsive mb-1" style="max-width:100vw; margin-left:0; margin-right:0;">

		<!--begin::Table-->
	<table class="table g-2 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items" style="font-size:0.85rem;">
			<!--begin::Table head-->
			<thead>
				<tr class="border-bottom fs-10 fw-bold text-gray-700 text-uppercase">
					   <th class="min-w-250px w-250px">Producto</th>
					   <th class="min-w-150px w-150px">Unidad</th>
					   <th class="min-w-60px w-60px">Cantidad</th>
					   <th class="min-w-90px w-90px">Precio</th>
					   <th class="min-w-200px w-200px">Observaciones</th>
					<th class="min-w-100px w-150px text-end">Total</th>
					   <th class="min-w-40px w-40px text-end" style="font-size:0.70em;">Acción</th>
				</tr>
			</thead>
			<!--end::Table head-->
			<!--begin::Table body-->
			<tbody>
				   <tr class="border-bottom border-bottom-dashed align-top" v-for="(product, index) in form.products" :key="index" data-kt-element="item" style="vertical-align: top;">
						<td class="ps-0 text-start pe-0" style="width:250px; min-width:250px; max-width:250px;">
								<Multiselect
									:taggable="true"
									:create-tag="newTag"
									placeholder="Seleccione o escriba producto"
									v-model="product.product_id"
									:options="$page.products"
									option-label="label"
									option-value="value"
							:searchable="true"
							:close-on-select="true"
							:hide-selected="false"
							class="multiselect-blue form-control"
							:class="{'is-invalid': form.errors['products.'+index+'.product_id']}"
						/>
					   </td>
                     
					   <!-- Columna Unidad -->
					   <td class="pe-1" style="width:120px; min-width:120px; max-width:120px;">
						   <Multiselect
							   :taggable="true"
							   :create-tag="newTag"
							   placeholder="Unidad"
							   v-model="product.unit_id"
							   :options="$page.units"
							   option-label="label"
							   option-value="value"
							   :searchable="true"
							   :close-on-select="true"
							   :hide-selected="false"
							   class="multiselect-blue form-control"
							   :class="{'is-invalid': form.errors['products.'+index+'.unit_id']}"
						   />
						   <InputError class="mt-1" :message="form.errors['products.'+index+'.unit_id']" />
					   </td>
					<td class="ps-0 pe-1" style="width:120px; min-width:100px; max-width:100px;">
						<input class="form-control form-control-solid" style="width:55px; min-width:120px; max-width:100px; font-size:0.93em;" type="number" min="1" v-model="product.amount" value="1" data-kt-element="quantity" />
					</td>
					<td class="ps-0 pe-0" style="width:120px; min-width:100px; max-width:100px;">
						<input type="number" class="form-control form-control-solid unit_price" style="width:120px; min-width:120px; max-width:100px; font-size:0.93em;" v-model="product.unit_price" value="0" step="0.0" />
					</td>
					  <td class="ps-0 pe-0" style="width:150px; min-width:150px; max-width:150px;">
                           <input type="text" class="form-control form-control-solid" v-model="product.observations" placeholder="Observaciones..." />
                       </td>
					<td class="text-end text-nowrap align-middle" style="width:100px; min-width:100px; max-width:100px; margin:0; padding-right:2px;">
	$<span data-kt-element="total">{{ (product.unit_price * product.amount).toLocaleString('es-ES') }}</span>
</td>
<td class="text-end align-middle" style="width:40px; min-width:40px; max-width:50px; margin:0; padding:0;">
    <button type="button" @click="onDeleted(index)" class="btn btn-sm btn-icon btn-active-color-primary m-0 p-0" style="margin:0; padding:0;" data-kt-element="remove-item">
        <!--begin::Svg Icon | path: icons/duotune/general/gen027.svg-->
        <span class="svg-icon svg-icon-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
			<tfoot style="font-size:0.70em;">
				<tr class="border-top border-top-dashed align-top fw-bold text-gray-700">
									<th class="text-primary d-flex gap-2 align-items-center">
										<button type="button" @click="add()" class="btn btn-link py-1" data-kt-element="add-item" title="Agregar línea">
											<i class="fa fa-plus"></i>
										</button>
										<button type="button" @click="removeLastEmptyLine()" class="btn btn-link py-1 text-danger" data-kt-element="remove-empty-items" title="Quitar última línea vacía">
											<i class="fa fa-minus"></i>
										</button>
									</th>
					<th colspan="7" class="p-0"></th>
				</tr>
				<tr class="align-top fw-bold text-gray-700">
					<th colspan="4"></th>
					<th class="fs-6 ps-0 text-end">Total</th>
					<th class="text-end fs-6 text-nowrap">$
						<span data-kt-element="grand-total" style="font-size:0.75em;">{{ calculateTotal().toLocaleString('es-ES') }}</span>
					</th>
					<th></th>
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
			<th colspan="2" class="text-muted text-center py-10">No items</th>
		</tr>
	</table>
	<!--end::Item template-->
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.75rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajuste de placeholder dentro de multiselect-blue */
.multiselect-blue .multiselect__placeholder {
    font-size: 0.85rem !important;
    opacity: 0.7 !important;
	 white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Ajustes para inputs nativos */
input.form-control:not([role="combobox"]),
select.form-control {
    height: 26px;
    min-height: 26px;
    font-size: 0.75rem;
    padding-top: 2px;
    padding-bottom: 2px;
}

/* Ajuste de tamaño de placeholder en inputs nativos */
input.form-control::placeholder {
    font-size: 0.75rem !important;
    opacity: 0.7 !important;
}

/* Checkboxes */
.form-check-input[type="checkbox"] {
    width: 0.8em;
    height: 0.8em;
    vertical-align: middle;
}
/* Group icon alignment */
.input-group-text {
    font-size: 0.8rem;
    display: flex;
    align-items: center;
}
/* Labels */
.col-form-label,
label {
    font-size: 0.8rem;
}
/* Opciones del multiselect */
.multiselect__option {
    font-size: 0.7rem;
}
/* Asegura z-index adecuado para dropdown */
.multiselect__content {
    z-index: 2050;
}


input::placeholder,
textarea::placeholder {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
textarea::placeholder {
  text-transform: none !important;
}


.elegant-divider {
	width: 100%;
	height: 3px;
	border: none;
	border-radius: 2px;
	background: linear-gradient(90deg, rgba(44,123,229,0.18) 0%, rgba(44,123,229,0.45) 50%, rgba(44,123,229,0.18) 100%);
	box-shadow: 0 2px 8px 0 rgba(44,123,229,0.10);
}
</style>