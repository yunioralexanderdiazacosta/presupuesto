<script setup>
import Multiselect from '@vueform/multiselect';
const props = defineProps({
    form: Object,
    subfamilies: Array,   // [{id, name}]
    units: Array,         // [{id, name}]
    months: Array,        // [{value, label}]
    doseTypes: Array,     // [{value, label}]
    costCenters: Array    // [{id, name}]
});
</script>

<template>
  <div>
    <h6 class="mb-2">Formulario en tabla</h6>
    <div class="row mb-2">
      <div class="col-6">
        <label for="cc" class="form-label small">CC</label>
        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text"><i class="fas fa-sitemap"></i></span>
          <Multiselect
            mode="tags"
            :placeholder="'Seleccione CC'"
            v-model="form.cc"
            :close-on-select="false"
            :options="costCenters"
            class="form-control form-control-sm"
            :class="{'is-invalid': form.errors.cc}"
            :searchable="true"
            :hide-selected="false"
            :track-by="'id'"
            :label="'name'"
          />
        </div>
      </div>
      <div class="col-6">
        <label for="families" class="form-label small">Familia</label>
        <div class="input-group input-group-sm mb-1">
          <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
          <Multiselect
            :placeholder="'Seleccione familia'"
            v-model="form.subfamily_id"
            :close-on-select="true"
            :options="subfamilies"
            class="form-control form-control-sm"
            :searchable="true"
            :hide-selected="false"
            :track-by="'id'"
            :label="'name'"
          />
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.78rem;">
        <thead class="table-light">
          <tr class="align-middle text-center">
            <th class="small p-1">Nombre</th>
            <th class="small p-1">Tipo</th>
            <th class="small p-1">Dosis</th>
            <th class="small p-1">U.</th>
            <th class="small p-1">Moj.</th>
            <th class="small p-1">Precio</th>
            <th class="small p-1">U. $</th>
            <th class="small p-1">Meses</th>
            <th class="small p-1">Obs.</th>
            <th class="small p-1"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(product, idx) in form.products" :key="idx">
            <td class="p-1"><input v-model="product.product_name" class="form-control form-control-sm py-0 px-1" type="text" style="min-width:6em;max-width:10em;" /></td>
            <td class="p-1">
              <div class="d-flex flex-wrap gap-1 justify-content-center">
                <div v-for="type in doseTypes" :key="type.value" class="form-check form-check-inline mb-0">
                  <input class="form-check-input" type="radio" :id="`dose_type_${idx}_${type.value}`" :value="type.value" v-model="product.dose_type_id" style="width:0.9em;height:0.9em;">
                </div>
              </div>
            </td>
            <td class="p-1"><input v-model="product.dose" type="number" class="form-control form-control-sm py-0 px-1" style="width:4em;" /></td>
            <td class="p-1">
              <Multiselect
                v-model="product.unit_id"
                :options="units"
                :searchable="true"
                :close-on-select="true"
                class="form-control form-control-sm"
                :track-by="'id'"
                :label="'name'"
                :placeholder="'Unidad'"
              />
            </td>
            <td class="p-1"><input v-model="product.mojamiento" type="number" class="form-control form-control-sm py-0 px-1" style="width:4em;" /></td>
            <td class="p-1"><input v-model="product.price" type="number" class="form-control form-control-sm py-0 px-1" style="width:5em;" /></td>
            <td class="p-1">
              <Multiselect
                v-model="product.unit_id_price"
                :options="units"
                :searchable="true"
                :close-on-select="true"
                class="form-control form-control-sm"
                :track-by="'id'"
                :label="'name'"
                :placeholder="'Unidad $'"
              />
            </td>
            <td class="p-1">
              <div class="d-flex flex-wrap gap-1 justify-content-center">
                <div v-for="month in months" :key="month.value" class="form-check form-check-inline mb-0">
                  <input class="form-check-input" type="checkbox" :id="`month_${idx}_${month.value}`" :value="month.value" v-model="product.months" style="width:0.9em;height:0.9em;">
                  <label class="form-check-label small px-1" :for="`month_${idx}_${month.value}`">{{ month.label.slice(0,2) }}</label>
                </div>
              </div>
            </td>
            <td class="p-1"><input v-model="product.observations" class="form-control form-control-sm py-0 px-1" type="text" style="min-width:6em;max-width:10em;" /></td>
            <td class="p-1 text-center align-middle">
              <button type="button" class="btn btn-sm btn-danger me-1" @click="form.products.splice(idx,1)" v-if="form.products.length > 1">
                <i class="fa fa-minus"></i>
              </button>
              <button type="button" class="btn btn-sm btn-primary" @click="form.products.push({product_name:'',dose:'',price:'',mojamiento:'',unit_id:'',unit_id_price:'',dose_type_id:'',observations:'',months:[]})" v-if="form.products.length === idx+1">
                <i class="fa fa-plus"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.table-sm th, .table-sm td { padding: 0.15rem 0.2rem; }
.form-control-sm, .form-select-sm { font-size: 0.78rem; padding: 0.08rem 0.3rem; }
input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { height: 0.8em; }

/* Forzar color de texto oscuro en los selects y opciones del multiselect */
.multiselect,
.multiselect__input,
.multiselect__single {
  color: #212529 !important;
}
.multiselect__option {
  color: #212529 !important;
}
</style>
