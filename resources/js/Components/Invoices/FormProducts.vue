<script setup>
import { watch, ref, computed } from 'vue';
import Swal from 'sweetalert2';
// Controla si se muestran opciones en el Multiselect de productos por cada línea
const showProductOptions = ref([]);
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';
import { usePage } from '@inertiajs/vue3';
const $page = usePage().props;

const props = defineProps({
    form: Object,
    protectedProductIds: {
        type: Array,
        default: () => []
    }
})

// Verifica si un producto está protegido (tiene salidas asociadas)
const isProtected = (productId) => {
    return props.protectedProductIds.includes(productId);
}
// Opciones de tipo documento desde Inertia (label, value)
const typeDocuments = $page.typeDocuments || [];
// Mostrar IVA solo si el documento es 'factura'
const showIVA = computed(() => {
  const doc = typeDocuments.find(td => td.value === props.form.type_document_id);
  return doc && doc.label.toLowerCase() === 'factura';
});

// Opciones reactivas para productos (permite agregar dinámicamente)
import { reactive, onMounted } from 'vue';
const productOptions = reactive([...(($page.products || []).map(p => ({ value: p.value ?? p.id, label: p.label ?? p.name })))]);

// Al montar, asegurar que todos los productos de la factura estén en las opciones
onMounted(() => {
	syncProductOptionsFromForm();
});

/**
 * Sincroniza los product_id del formulario con productOptions.
 * Si un product_id es un string (nombre nuevo del PDF o escrito a mano)
 * y no existe en las opciones, lo agrega como tag.
 */
function syncProductOptionsFromForm() {
	if (!props.form || !Array.isArray(props.form.products)) return;
	props.form.products.forEach(p => {
		if (
			p.product_id &&
			!productOptions.some(opt => opt.value === p.product_id)
		) {
			// Si es numérico, buscar el label en los productos de la página
			if (typeof p.product_id === 'number' || /^\d+$/.test(p.product_id)) {
				let label = p.product_name || p.label;
				if (!label) {
					const found = ($page.products || []).find(prod => (prod.id || prod.value) === p.product_id);
					label = found ? (found.label || found.name) : p.product_id;
				}
				productOptions.push({ value: p.product_id, label });
			} else {
				// Es un nombre de producto (string) del PDF → agregar como tag nuevo
				productOptions.push({ value: p.product_id, label: p.product_id });
			}
		}
	});
}

// Watcher: cuando se agregan productos al form (ej. desde PDF), sincronizar opciones
watch(
	() => props.form.products.length,
	() => {
		syncProductOptionsFromForm();
	}
);

// Bandera para mostrar validación solo tras submit
const showProductValidation = ref(false);
// Permite que el padre active la validación visual tras submit
function triggerProductValidation() {
	showProductValidation.value = true;
}
// Exponer variables/métodos al componente padre si es necesario
defineExpose({ showProductValidation, triggerProductValidation });


// Función para crear un nuevo producto (taggable): agrega a las opciones y retorna objeto compatible
const newTag = (input) => {
	// Normalizar el input (trim y lowercase)
	const normalizedInput = input.trim().toLowerCase();
	
	// Verificar si ya existe un producto con ese nombre (case-insensitive)
	const existingProduct = productOptions.find(p => 
		p.label.toLowerCase() === normalizedInput
	);
	
	if (existingProduct) {
		// Si existe, usar el producto existente en lugar de crear uno nuevo
		Swal.fire({
			icon: 'warning',
			title: 'Producto existente',
			text: `El producto "${existingProduct.label}" ya existe. Se seleccionará automáticamente.`,
			timer: 3000,
			showConfirmButton: false
		});
		return existingProduct;
	}
	
	// Si no existe, crear nuevo
	const newProduct = { value: input.trim(), label: input.trim() };
	productOptions.push(newProduct);
	return newProduct;
};

const add = () => {
	props.form.products.push({
		product_id: '',
		unit_id: '',                // Unidad seleccionada o nueva
		unit_price: 0.00,
		amount: 1,
		observations: ''
	});
	showProductOptions.value.push(true);
}

const onDeleted = (index) => {
	const product = props.form.products[index];
	if (isProtected(product.product_id)) {
		Swal.fire({
			icon: 'warning',
			title: 'Producto protegido',
			text: 'Este producto no se puede eliminar porque tiene salidas asociadas.',
			confirmButtonColor: '#3085d6',
		});
		return;
	}
	props.form.products.splice(index, 1);
	showProductOptions.value.splice(index, 1);
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
				   <tr class="border-bottom border-bottom-dashed align-top" v-for="(product, index) in form.products" :key="index" data-kt-element="item" style="vertical-align: top;" :class="{'bg-light': isProtected(product.product_id)}">
						<td class="ps-0 text-start pe-0" style="width:250px; min-width:250px; max-width:250px;">
							<div v-if="isProtected(product.product_id)" class="d-flex align-items-center gap-1">
								<i class="fas fa-lock text-warning" style="font-size:0.7rem;" v-tooltip="'Producto con salidas asociadas'"></i>
								<span class="form-control form-control-solid bg-light" style="font-size:0.75rem; height:26px; min-height:26px; cursor:not-allowed; opacity:0.8;">{{ productOptions.find(p => p.value === product.product_id)?.label || product.product_id }}</span>
							</div>
							<Multiselect
								v-if="!isProtected(product.product_id)"
									:taggable="true"
									:create-tag="newTag"
									placeholder="Seleccione o escriba producto"
									v-model="product.product_id"
									:options="productOptions"
									:searchable="true"
									:close-on-select="true"
									:hide-selected="false"
									:showOptions="showProductOptions[index] !== false"
									class="multiselect-blue form-control"
									required
									:class="{'is-invalid': showProductValidation && !product.product_id}"
								/>
							<span v-if="!isProtected(product.product_id) && showProductValidation && !product.product_id" class="text-danger" style="font-size:0.7em;">Campo obligatorio</span>
					   </td>
                     
					   <!-- Columna Unidad -->
					   <td class="ps-1 pe-1 " style="width:120px; min-width:120px; max-width:120px;">
					<Multiselect
							placeholder="Unidad"
							v-model="product.unit_id"
							:options="$page.units"
							option-label="label"
							option-value="value"
							:searchable="false"
							:close-on-select="true"
							:hide-selected="false"
							class="multiselect-blue form-control"
							required
							:disabled="isProtected(product.product_id)"
							:class="{'is-invalid': showProductValidation && !product.unit_id}"
						/>
						<span v-if="showProductValidation && !product.unit_id" class="text-danger" style="font-size:0.7em;">Campo obligatorio</span>
					   </td>
					<td class="ps-0 pe-1" style="width:120px; min-width:100px; max-width:100px;">
					<input class="form-control form-control-solid" :class="{'is-invalid': showProductValidation && (!product.amount || product.amount < 1), 'bg-light': isProtected(product.product_id)}" style="width:55px; min-width:120px; max-width:100px; font-size:0.93em;" type="number" min="1" v-model="product.amount" value="1" data-kt-element="quantity" required
							step="0.01"
							:disabled="isProtected(product.product_id)" />
						<span v-if="showProductValidation && (!product.amount || product.amount < 1)" class="text-danger" style="font-size:0.7em;">Campo obligatorio</span>
					</td>
					<td class="ps-0 pe-0" style="width:120px; min-width:100px; max-width:100px;">
					<input type="number" class="form-control form-control-solid unit_price" :class="{'is-invalid': showProductValidation && (!product.unit_price || product.unit_price <= 0), 'bg-light': isProtected(product.product_id)}" style="width:120px; min-width:120px; max-width:100px; font-size:0.93em;" v-model="product.unit_price" value="0" step="0.01" required
						:disabled="isProtected(product.product_id)" />
						<span v-if="showProductValidation && (!product.unit_price || product.unit_price <= 0)" class="text-danger" style="font-size:0.7em;">Campo obligatorio</span>
					</td>
					  <td class="ps-0 pe-0" style="width:150px; min-width:150px; max-width:150px;">
                           <input type="text" class="form-control form-control-solid" :class="{'bg-light': isProtected(product.product_id)}" v-model="product.observations" :disabled="isProtected(product.product_id)" placeholder="Observaciones..." />
                       </td>
					<td class="text-end text-nowrap align-middle" style="width:100px; min-width:100px; max-width:100px; margin:0; padding-right:2px;">
	$<span data-kt-element="total">{{ (product.unit_price * product.amount).toLocaleString('es-ES') }}</span>
</td>
<td class="text-end align-middle" style="width:40px; min-width:40px; max-width:50px; margin:0; padding:0;">
    <button v-if="!isProtected(product.product_id)" type="button" @click="onDeleted(index)" class="btn btn-sm btn-icon btn-active-color-primary m-0 p-0" style="margin:0; padding:0;" data-kt-element="remove-item">
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
    <span v-else class="text-warning" v-tooltip="'Producto protegido: tiene salidas asociadas'" style="font-size:0.75rem;">
        <i class="fas fa-lock"></i>
    </span>
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
						<span data-kt-element="grand-total" style="font-size:0.75em;">{{ calculateTotal().toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</span>
					</th>
					<th></th>
				</tr>
				<!-- Fila de Total con IVA -->
				<tr v-if="showIVA" class="align-top fw-bold text-gray-700">
					<th colspan="4"></th>
					<th class="fs-8 ps-0 text-end">Total con IVA</th>
					<th class="text-end fs-8 text-nowrap">
						$<span style="font-size:0.75em;">{{ (calculateTotal() * 1.19).toLocaleString('es-ES', { maximumFractionDigits: 0 }) }}</span>
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
	   --ms-max-height: 60vh !important;
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