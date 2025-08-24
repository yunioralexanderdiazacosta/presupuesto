<script setup>
// Basado en FormProducts.vue, ajusta aquí la lógica específica de salidas (outflows)
import { watch } from 'vue';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';
import { usePage } from '@inertiajs/vue3';
const page = usePage();

const props = defineProps({
    form: Object
})

// Aquí puedes adaptar la lógica para salidas, por ejemplo, selección de productos, centros de costo, etc.
const newTag = (input) => ({ id: input, name: input });

const add = () => {
    props.form.outflows.push({
        product_id: '',
        unit_id: '',
        quantity: 1,
        cost_center_id: '',
        observations: ''
    });
}

const onDeleted = (index) => {
    props.form.outflows.splice(index, 1);
}

const productDict = {};
(page.props.products || []).forEach(p => {
    productDict[p.value ?? p.id] = p;
});

watch(
    () => props.form.outflows.map(p => p.product_id),
    (newProductIds, oldProductIds) => {
        newProductIds.forEach((productId, idx) => {
            if (productId && productId !== oldProductIds[idx]) {
                const producto = productDict[productId];
                if (producto && producto.unit_id) {
                    props.form.outflows[idx].unit_id = producto.unit_id;
                }
            }
        });
    },
    { deep: true }
);
</script>
<template>
    <div class="elegant-divider my-2"></div>
    <div class="table-responsive mb-1" style="max-width:100vw; margin-left:0; margin-right:0;">
        <table class="table g-2 gs-0 mb-0 fw-bold text-gray-700" style="font-size:0.85rem;">
            <thead>
                <tr class="border-bottom fs-10 fw-bold text-gray-700 text-uppercase">
                    <th style="width:250px; min-width:200px; max-width:300px;">Producto</th>
                    <th style="width:120px; min-width:100px; max-width:150px;">Unidad</th>
                    <th style="width:100px; min-width:80px; max-width:120px;">Cantidad</th>
                    <th>Centro de Costo</th>
                    <th style="width:160px; min-width:120px; max-width:200px;">Observaciones</th>
                    <th class="text-end" style="width:40px; min-width:40px; max-width:50px;"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(outflow, index) in form.outflows" :key="index">
                    <!-- Producto: si ya tiene nombre asignado, mostrar campo estático -->
                    <td v-if="outflow.product_name" style="width:150px; min-width:150px; max-width:150px;">
                        <input type="hidden" v-model="outflow.product_id" />
                        <input type="text" class="form-control form-control-solid" v-model="outflow.product_name" disabled />
                    </td>
                    <td v-else style="width:150px; min-width:150px; max-width:150px;">
                        <Multiselect
                            :taggable="true"
                            :create-tag="newTag"
                            placeholder="Producto"
                            v-model="outflow.product_id"
                            :options="$page.products"
                            option-label="label"
                            option-value="value"
                            :searchable="true"
                            class="multiselect-blue form-control"
                        />
                    </td>
                        <!-- Unidad (solo visual) -->
                        <td style="width:150px; min-width:100px; max-width:150px;">
                            <input type="hidden" v-model="outflow.unit_id" />
                            <input type="text" class="form-control form-control-solid" :value="page.props.invoiceProduct?.product?.unit?.name || ''" disabled />
                        </td>
                    <td style="width:150px; min-width:90px; max-width:150px;">
                        <input class="form-control form-control-solid" style="width:100%; min-width:130px; max-width:130px; font-size:0.93em;" type="number" min="1" v-model="outflow.quantity" />
                    </td>
                    <td>
                        <Multiselect
                            placeholder="Centro de Costo"
                            v-model="outflow.cost_center_id"
                            :options="$page.cost_centers"
                            option-label="label"
                            option-value="value"
                            :searchable="true"
                            class="multiselect-blue form-control"
                        />
                    </td>
                    <td style="width:160px; min-width:120px; max-width:200px;">
                        <input type="text" class="form-control form-control-solid" style="width:100%; min-width:120px; max-width:200px; font-size:0.93em;" v-model="outflow.observations" placeholder="Observaciones..." />
                    </td>
                    <td class="text-end">
                        <button type="button" @click="onDeleted(index)" class="btn btn-sm btn-icon btn-active-color-primary m-0 p-0">
                            <span class="svg-icon svg-icon-3">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
                                    <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
                                    <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
                                </svg>
                            </span>
                        </button>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>
                        <button type="button" @click="add()" class="btn btn-link py-1" title="Agregar línea">
                            <i class="fa fa-plus"></i>
                        </button>
                    </th>
                    <th colspan="5"></th>
                </tr>
            </tfoot>
        </table>
    </div>
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