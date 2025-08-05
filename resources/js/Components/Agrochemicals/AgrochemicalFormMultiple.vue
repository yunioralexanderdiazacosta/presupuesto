<script setup>
// Estado para agrupación seleccionada
// (Importación única ya presente arriba)
const selectedGrouping = ref("");

// Watch para autocompletar cost centers al seleccionar agrupación
watch(selectedGrouping, (newGroupingId) => {
  if (!newGroupingId) return;
  // Buscar la agrupación seleccionada en los datos del backend
  const grouping = page.props.groupings?.find(g => g.id == newGroupingId);
  if (grouping && Array.isArray(grouping.cost_centers)) {
    // IDs de los cost centers de la agrupación
    const groupCCs = grouping.cost_centers.map(cc => cc.id);
    // Siempre seleccionar todos los de la agrupación
    props.form.cc = groupCCs;
  }
});



import { ref, computed, getCurrentInstance, watch } from "vue";
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Products2Modal from '@/Components/Products2/Products2Modal.vue';
import axios from 'axios';

const props = defineProps({
    form: Object,
});




// Preparar acceso a productos para sugerencias (cuando estén disponibles)
const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page || { props: {} };


// Normalizar productsList para que siempre sea [{name, level3}]
const productsList = computed(() => {
    const raw = page.props.products || [];
    if (raw.length && typeof raw[0] === 'object' && raw[0].name) return raw;
    return [];
});

// Filtrar productos según la familia seleccionada (level3) y ordenar alfabéticamente por nombre
const filteredProductsByFamily = computed(() => {
    if (!props.form.subfamily_id) return [];
    // Buscar el label de la familia seleccionada
    const selectedFamily = page.props.subfamilies.find(f => f.value === props.form.subfamily_id);
    if (!selectedFamily) return [];
    // Filtrar productos cuyo level3 coincida con el label de la familia y ordenar por nombre
    return productsList.value
        .filter(p => p.level3 === selectedFamily.label)
        .sort((a, b) => a.name.localeCompare(b.name));
});

// Controlar sugerencias por producto
const productSearch = ref([]); // Un array de v-models para cada producto
const showSuggestions = ref([]); // Un array de flags para mostrar sugerencias

// Inicializar arrays según la cantidad de productos
const ensureProductSearchArrays = () => {
    while (productSearch.value.length < props.form.products.length) {
        productSearch.value.push("");
        showSuggestions.value.push(false);
    }
    while (productSearch.value.length > props.form.products.length) {
        productSearch.value.pop();
        showSuggestions.value.pop();
    }
};

// Llamar en el template antes de renderizar productos
ensureProductSearchArrays();

const filteredProducts = (search) => {
    if (!search) return [];
    return productsList.value.filter(p =>
        p.name && p.name.toLowerCase().includes(search.toLowerCase())
    ).slice(0, 8); // máximo 8 sugerencias
};


const selectSuggestion = (index, product) => {
    props.form.products[index].product_name = product.name;
    productSearch.value[index] = product.name;
    showSuggestions.value[index] = false;
    // Asignar el precio automáticamente si existe
    if (typeof product.price !== 'undefined') {
        props.form.products[index].price = product.price;
    } else {
        props.form.products[index].price = '';
    }
    // Asignar unidad del precio automáticamente si existe
    if (typeof product.unit_price_id !== 'undefined') {
        props.form.products[index].unit_id_price = product.unit_price_id;
    } else {
        props.form.products[index].unit_id_price = '';
    }
};

// Watch para actualizar el precio cuando el nombre del producto cambie manualmente
watch(
    () => props.form.products.map(p => p.product_name),
    (newNames, oldNames) => {
        newNames.forEach((name, idx) => {
            const found = productsList.value.find(p => p.name === name);
            if (found && typeof found.price !== 'undefined') {
                props.form.products[idx].price = found.price;
            } else {
                props.form.products[idx].price = '';
            }
            // Asignar unidad del precio automáticamente si existe
            if (found && typeof found.unit_price_id !== 'undefined') {
                props.form.products[idx].unit_id_price = found.unit_price_id;
            } else {
                props.form.products[idx].unit_id_price = '';
            }
        });
    },
    { deep: true }
);

const onInput = (index) => {
    showSuggestions.value[index] = true;
    // Si el nombre coincide exactamente con un producto, asignar el precio
    const name = props.form.products[index].product_name;
    const found = productsList.value.find(p => p.name === name);
    if (found && typeof found.price !== 'undefined') {
        props.form.products[index].price = found.price;
    }
};

const onBlur = (index) => {
    setTimeout(() => (showSuggestions.value[index] = false), 150);
};

const addItem = () => {
    props.form.products.push({
        product_name: "",
        dose: "",
        price: "",
        mojamiento: "",
        unit_id: "",
        unit_id_price: "",
        dose_type_id: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};


const selectAllMonths = (index, months) => {
    const allMonths = months.map((m) => m.value);
    const current = props.form.products[index].months || [];
    if (
        current.length === allMonths.length &&
        allMonths.every((m) => current.includes(m))
    ) {
        // Si ya están todos seleccionados, deselecciona todos
        props.form.products[index].months = [];
    } else {
        // Si no, selecciona todos
        props.form.products[index].months = allMonths;
    }
};

const formatPrice = (value) => {
  if (value === null || value === undefined || value === '') return '';
  return parseInt(value);
};

// Nuevas reglas para filtrar 'Unidad del precio' según la unidad de la dosis
const allowedPriceUnitIdsForDose1 = [1, 2, 8];
const allowedPriceUnitIdsForDose3 = [3, 4];
const allowedPriceUnitIdsForDose8 = [1, 2, 8];
const allowedPriceUnitIdsForDose2 = [1, 2, 8];
const allowedPriceUnitIdsForDose4 = [3, 4];
const allowedPriceUnitIdsForDose5 = [5];

// Excluir unidades de dosis con id 6 y 7
const disallowedDoseUnitIds = [6, 7];
const getDoseUnitOptions = () => page.props.units.filter(u => !disallowedDoseUnitIds.includes(u.value));

const getPriceUnitOptions = (product) => {
  if (product.unit_id === 1) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose1.includes(u.value));
  } else if (product.unit_id === 2) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose2.includes(u.value));
  } else if (product.unit_id === 3) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose3.includes(u.value));
  } else if (product.unit_id === 4) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose4.includes(u.value));
  } else if (product.unit_id === 5) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose5.includes(u.value));
  } else if (product.unit_id === 8) {
    return page.props.units.filter(u => allowedPriceUnitIdsForDose8.includes(u.value));
  }
  return page.props.units;
};

watch(
  () => props.form.products.map(p => p.unit_id),
  (newUnitIds) => {
    newUnitIds.forEach((id, idx) => {
      const currentPriceUnit = props.form.products[idx].unit_id_price;
      if (id === 1 && !allowedPriceUnitIdsForDose1.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
      if (id === 2 && !allowedPriceUnitIdsForDose2.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
      if (id === 3 && !allowedPriceUnitIdsForDose3.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
      if (id === 4 && !allowedPriceUnitIdsForDose4.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
      if (id === 5 && !allowedPriceUnitIdsForDose5.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
      if (id === 8 && !allowedPriceUnitIdsForDose8.includes(currentPriceUnit)) {
        props.form.products[idx].unit_id_price = null;
      }
    });
  },
  { deep: true }
);

// Estado para modal de productos2
const products2Data = ref({ data: [], links: [] });
const searchProducts2 = ref('');
const currentProductIndex = ref(null);

// Label nivel 3 para filtrar
const selectedLevel3Label = computed(() => {
  const sel = page.props.subfamilies.find(f => f.value === props.form.subfamily_id);
  return sel ? sel.label : '';
});

// Abrir modal: cargar datos iniciales y mostrar
const openProducts2Modal = (index) => {
  currentProductIndex.value = index;
  // Limpiar campo de producto actual antes de abrir modal
  props.form.products[index].product_name = '';
  productSearch.value[index] = '';
  // Reiniciar búsqueda en el modal
  searchProducts2.value = '';
  fetchProducts2();
  $('#products2Modal').modal('show');
};

// Petición AJAX JSON para modal
const fetchProducts2 = () => {
  axios.get(route('products2.index'), {
    params: { term: searchProducts2.value, level3: selectedLevel3Label.value },
    headers: { Accept: 'application/json' }
  }).then(res => {
    products2Data.value = res.data;
  });
};

// Manejar evento de filtro desde modal
const onFilterProducts2 = (term) => {
  searchProducts2.value = term;
  fetchProducts2();
};

// Selección de producto desde modal
const onProduct2Select = (item) => {
  props.form.products[currentProductIndex.value].product_name = item.name;
  // Asigna datos adicionales: price, unit_id_price, active_ingredient
  props.form.products[currentProductIndex.value].price = item.price || '';
  props.form.products[currentProductIndex.value].unit_id_price = item.unit_price_id || '';
  props.form.products[currentProductIndex.value].active_ingredient = item.active_ingredient || '';
  $('#products2Modal').modal('hide');
};


</script>
<script setup></script>
<template>
    <div class="row gy-1">
        <div class="col-sm-4">
            <label for="families" class="col-form-label mb-0">Nivel 3</label>
            <div class="input-group mb-2">
                <span class="input-group-text"
                    ><i class="fas fa-layer-group"></i
                ></span>
                <Multiselect
                    :placeholder="'Seleccione nivel 3'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="$page.props.subfamilies"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.subfamily_id }"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.subfamily_id" />
        </div>
        <div class="col-sm-8">

            <label for="cc" class="col-form-label mb-0">CC</label>
            <div class="input-group mb-2">
                <span class="input-group-text"
                    ><i class="fas fa-sitemap"></i
                ></span>
                <Multiselect
                    mode="tags"
                    tagClass="small-tag"
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="$page.props.costCenters"
                    class="form-control" style="height:52px;"
                    :class="{ 'is-invalid': form.errors.cc }"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.cc" />
        </div>






        <!-- Selector de agrupación con Multiselect -->
        <div class="col-sm-4">
            <label for="grouping" class="col-form-label mb-0">Agrupación</label>
            <div class="input-group mb-2 ">
                <span class="input-group-text"><i class="fas fa-object-group"></i></span>
                <Multiselect
                    id="grouping"
                    v-model="selectedGrouping"
                    :options="page.props.groupings.map(g => ({ value: g.id, label: g.name }))"
                    :placeholder="'Seleccione agrupación'"
                    :searchable="true"
                    :close-on-select="true"
                    :hide-selected="false"
                    class="form-control"
                />
            </div>
        </div>
    </div>
    <template v-for="(product, index) in form.products">
        <hr class="custom-hr" />
        <div class="row mt-0">
            <div class="col-sm-5 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Nombre del producto</label>
                <div class="input-group position-relative">
                    <span class="input-group-text"><i class="fas fa-flask"></i></span>
                    <input
                        :id="'product_name_' + index"
                        v-model="product.product_name"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors['products.' + index + '.product_name'] }"
                        placeholder="Escriba producto o use la lupa"
                        autocomplete="off"
                    />
                    <span
                      class="input-group-text btn btn-sm btn-outline-secondary p-1"
                      style="cursor: pointer;"
                      @click="openProducts2Modal(index)"
                    >
                      <i class="fas fa-search"></i>
                    </span>
                </div>
                <InputError class="mt-2" :message="form.errors['products.' + index + '.product_name']" />
            </div>
            </div>
            <div class="col-sm-3 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Tipo de dosis</label>
                <div class="d-flex flex-wrap gap-1">
                    <template v-for="value in $page.props.doseTypes">
                        <div
                            class="form-check form-check-solid form-check-inline mb-0 mt-0 mr-1"
                        >
                            <input
                                class="form-check-input"
                                type="radio"
                                v-model="product.dose_type_id"
                                :id="'kt_unit_' + value.id"
                                :value="value.value"
                            />
                            <label
                                class="form-check-label ps-1"
                                :for="'kt_unit_' + value.id"
                                >{{ value.label }}</label
                            >
                        </div>
                    </template>
                </div>
                <small
                    class="text-danger mt-2"
                    :v-if="form.errors['products.' + index + '.dose_type_id']"
                    ><br />{{
                        form.errors["products." + index + ".dose_type_id"]
                    }}</small
                >
                </div>
            </div>
            <div class="col-sm-3 pe-0">
                <div class="fv-row">
                <label class="col-form-label">Dosis</label>
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-vial"></i
                    ></span>
                    <TextInput
                        id="dose"
                        v-model="product.dose"
                        class="form-control"
                        type="number"
                        step="0.00"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.dose'],
                        }"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.dose']"
                />
            </div>
            </div>
        </div>
        <div class="row mt-0">
            <div class="col-sm-3">
                <label for="unit" class="col-form-label"
                    >Unidad de la dosis</label
                >
                <div class="input-group">
                    <span class="input-group-text"
                        ><i class="fas fa-balance-scale"></i
                    ></span>
                    <Multiselect
                        :placeholder="''"
                        v-model="product.unit_id"
                        :close-on-select="true"
                        :options="getDoseUnitOptions()"
                        class="form-control"
                        :class="{
                            'is-invalid':
                                form.errors['products.' + index + '.unit_id'],
                        }"
                        :searchable="false"
                        :hide-selected="false"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.unit_id']"
                />
            </div>
            <div class="col-sm-3">
                <label class="col-form-label">Mojamiento</label>
                <div class="input-group mb-0">
                    <span class="input-group-text"
                        ><i class="fas fa-tint"></i
                    ></span>
                    <TextInput
                        id="product_name"
                        v-model="product.mojamiento"
                        class="form-control"
                        type="number"
                        :class="{
                            'is-invalid':
                                form.errors[
                                    'products.' + index + '.mojamiento'
                                ],
                        }"
                    />
                </div>
                <InputError
                    class="mt-0"
                    :message="form.errors['products.' + index + '.mojamiento']"
                />
            </div>

            <div class="col-sm-3">
                <label for="price" class="col-form-label">Precio</label>
                <div class="input-group mb-1">
                    <span class="input-group-text"
                        ><i class="fas fa-dollar-sign"></i
                    ></span>
<TextInput
    id="price"
    :model-value="formatPrice(product.price)"
    @update:model-value="val => product.price = val"
    class="form-control"
    type="number"
    :class="{
        'is-invalid':
            form.errors['products.' + index + '.price'],
    }"
/> 

                </div>
                <InputError
                    class="mt-2"
                    :message="form.errors['products.' + index + '.price']"
                />
            </div>
            <div class="col-sm-3">
                <label for="unit" class="col-form-label"
                    >Unidad del precio</label
                >
                <div class="input-group mb-1">
                    <span class="input-group-text"
                        ><i class="fas fa-balance-scale"></i
                    ></span>
                    <Multiselect
                        :placeholder="''"
                        v-model="product.unit_id_price"
                        :close-on-select="true"
                        :options="getPriceUnitOptions(product)"
                        class="form-control"
                        :class="{
                            'is-invalid':
                                form.errors[
                                    'products.' + index + '.unit_id_price'
                                ],
                        }"
                        :searchable="false"
                        :hide-selected="false"
                    />
                </div>
                <InputError
                    class="mt-2"
                    :message="
                        form.errors['products.' + index + '.unit_id_price']
                    "
                />
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-1">
                    <label for="months" class="col-form-label mb-1">Meses</label>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm ms-2"
                        @click="selectAllMonths(index, $page.props.months)"
                    >
                        {{
                            product.months &&
                            product.months.length === $page.props.months.length &&
                            $page.props.months.every((m) => product.months.includes(m.value))
                                ? "Deseleccionar todos"
                                : "Seleccionar todos"
                        }}
                    </button><span
                    style="cursor: pointer;"
                    title="Por cada mes que selecciones, el producto se incluirá en el presupuesto para ese mes.">
                    <i class="fas fa-question-circle text-info ms-1"></i>
                  </span>
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <template v-for="value in $page.props.months">
                        <div
                            style="margin-right: 0.5rem"
                            class="form-check form-check-solid form-check-inline mb-1"
                        >
                            <input
                                class="form-check-input"
                                type="checkbox"
                                v-model="product.months"
                                :id="'kt_month_' + value.id"
                                :value="value.value"
                            />
                            <label
                                class="form-check-label ps-1"
                                :for="'kt_month_' + value.id"
                                >{{ value.label }}</label
                            >
                        </div>
                    </template>
                </div>
                <small
                    class="text-danger"
                    v-if="form.errors['products.' + index + '.months']"
                >
                    <br />{{ form.errors["products." + index + ".months"] }}
                </small>
            </div>


            
            <div class="col-lg-4 align-self-start ps-0">
                <label for="observations" class="col-form-label">Observaciones</label>
                    <textarea
                        v-model="product.observations"
                        rows="10"
                         class="form-control mb-3 mb-lg-0"
                        :class="{ 'is-invalid': form.errors.observations }"
                        style="resize: vertical; min-height: 80px;"
                    ></textarea>
            
                <InputError class="mt-2" :message="form.errors.observations" />
            </div>
        </div>

         <div class="row">
            <div class="col-lg-12 text-end">
                <button
                    type="button"
                    @click="removeItem(index)"
                    class="btn btn-sm btn-danger me-1"
                    v-if="form.products.length > 1"
                >
                    <i class="fa fa-minus"></i>
                </button>
                <button
                    type="button"
                    @click="addItem()"
                    v-if="form.products.length == index + 1"
                    class="btn btn-sm btn-primary"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
    <!-- Componente modal para selección de Product2 -->
    <Products2Modal
      :products2="products2Data"
      :term="searchProducts2"
      :level3="selectedLevel3Label"
      @filter="onFilterProducts2"
      @select="onProduct2Select"
    />
    </template>

    <!-- Modal para selección de productos2 -->
    <div
      class="modal fade"
      id="products2Modal"
      tabindex="-1"
      aria-labelledby="products2ModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="products2ModalLabel">
              Seleccionar Producto
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <!-- Buscador -->
            <div class="mb-3">
              <label for="searchProducts2" class="form-label"
                >Buscar producto:</label
              >
              <div class="input-group">
                <span class="input-group-text"
                  ><i class="fas fa-search"></i
                ></span>
                <input
                  type="text"
                  id="searchProducts2"
                  v-model="searchProducts2"
                  @input="fetchProducts2"
                  class="form-control"
                  placeholder="Escriba para buscar..."
                />
              </div>
            </div>

            <!-- Tabla de resultados -->
            <div v-if="products2Data.data.length" class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Unidad</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in products2Data.data"
                    :key="item.id"
                    @click="onProduct2Select(item)"
                    style="cursor: pointer;"
                  >
                    <td>{{ item.name }}</td>
                    <td>{{ item.price }}</td>
                    <td>{{ item.unit_price_id }}</td>
                    <td>
                      <button
                        class="btn btn-sm btn-primary"
                        @click.stop="onProduct2Select(item)"
                      >
                        <i class="fas fa-check"></i> Seleccionar
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center text-muted py-3">
              No se encontraron productos que coincidan con la búsqueda.
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
</template>
<!-- <style src="@vueform/multiselect/themes/default.css"></style>-->
<style>
select,
select.form-control {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.95rem;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
}

/* Agrandar la casilla de verificación (checkbox) */
.form-check-input[type="checkbox"] {
    width: 0.8em !important;
    height: 0.8em !important;
    min-width: 0.85em !important;
    min-height: 0.85em !important;
    max-width: 0.85em !important;
    max-height: 0.85em !important;
    vertical-align: middle;
}

/* Ajustar el alto de todos los vueform/multiselect (no solo los blue) */

/* Forzar el alto de los vueform/multiselect en este archivo */
.multiselect,
.multiselect.form-control,
.multiselect__tags {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.8
    rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Forzar el alto de los inputs (TextInput y nativos), excepto textarea */
.form-control:not(textarea),
.form-control-lg:not(textarea),
.form-control-sm:not(textarea) {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.85rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajustar el alto y alineación de los contenedores de iconos, inputs y selects */
.input-group {
    min-height: 26px !important;
    height: 26px !important;
    align-items: center !important;
}

.input-group-text {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    font-size: 1rem !important;
    display: flex;
    align-items: center;
}

.col-form-label,
label {
    font-size: 0.8rem !important;
}

/* Reducir el tamaño del texto de los labels de los meses (checkboxes) */
.form-check-label.ps-1,
.form-check-label.ps-2,
.form-check-label {
    font-size: 0.8rem !important;
    line-height: 1.1 !important;
    padding-left: 0.01rem !important;
    margin-bottom: 0 !important;
    display: inline-block;
    vertical-align: middle;
}

.custom-hr {
    height: 2px;
    background: #b1b1b1;
    border: none;
    margin: 0.5rem 0;
}
/* Achicar el botón 'Seleccionar todos' */
.btn.btn-outline-primary.btn-sm.ms-2 {
    padding: 0.1rem 0.35rem !important;
    font-size: 0.75rem !important;
    line-height: 1 !important;
    height: 20px !important;
    min-height: 20px !important;
    border-radius: 0.15rem !important;
}
/* Asegura que el dropdown de autocomplete esté justo debajo del input */
.autocomplete-list {
    z-index: 1050 !important;
    background: #fff;
    top: 100%;
    left: 0;
}

.multiselect .multiselect-options,
.multiselect .multiselect-option,
.multiselect__option {
    font-size: 0.7rem !important;
}

::placeholder {
    font-size: 0.7rem !important;
    color: #888 !important; /* Opcional: cambia el color si lo deseas */
    opacity: 1; /* Para asegurar que el color se aplique en todos los navegadores */
}

.input-group .form-control {
    border-radius: 0.25rem !important;
}




</style>

