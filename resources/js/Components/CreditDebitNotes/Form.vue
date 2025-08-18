
<script setup>
import FormItems from './FormItems.vue';
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
const props = defineProps({
  form: Object,
  suppliers: Array,
  invoices: Array,
  products: Array,
  units: Array
});
</script>



<template>
 
    <!-- Campos principales de nota de crédito/débito -->
    <div class="row">
        <div class="col-lg-2">
            <div class="fv-row">
          <label for="" class="col-form-label">Tipo</label>
          <Multiselect
            v-model="form.type"
            :options="[
              { value: 'credito', label: 'Crédito' },
              { value: 'debito', label: 'Débito' }
            ]"
            placeholder="Seleccione tipo"
            :searchable="false"
            :close-on-select="true"
            :hide-selected="false"
            :open="false"
            class="multiselect-blue form-control"
          />
            <InputError
                class="mt-2"
                :message="form.errors.type"
            />
          </div>
        </div>
        <div class="col-lg-3">
                <div class="fv-row">
                    <label for="" class="col-form-label">Proveedor</label>
            <Multiselect
              v-model="form.supplier_id"
              :options="suppliers"
              placeholder="Proveedor"
              :searchable="true"
               :max-height="440" 
              :close-on-select="true"
              :hide-selected="false"
              :open="false"
              class="multiselect-blue form-control"
            />
                    <InputError
                        class="mt-2"
                        :message="form.errors.supplier_id"
                    />
                    
                </div>
        </div>

        <div class="col-lg-2">
           <div class="fv-row">
                    <label for="" class="col-form-label">Factura Numero</label>
          <Multiselect
            v-model="form.invoice_id"
            :options="invoices"
            placeholder="Factura"
            :searchable="true"
            :close-on-select="true"
            :hide-selected="false"
            :open="false"
            class="multiselect-blue form-control"
          />
           <InputError
                        class="mt-2"
                        :message="form.errors.invoice_id"
                    />
           </div>
        </div>
         <div class="col-lg-3">
                <div class="fv-row">
                    <label for="" class="col-form-label">Fecha</label>
                    <TextInput
                        id="date"
                        v-model="form.date"
                        class="form-control form-control-solid"
                        type="date"
                        :class="{ 'is-invalid': form.errors.date }"
                    />
                    <InputError class="mt-2" :message="form.errors.date" />
                </div>
            </div>
        <div class="col-lg-2">
           <div class="fv-row">
                    <label for="" class="col-form-label">Número</label>
          <TextInput
            id="number"
            v-model="form.number"
            class="form-control form-control-solid"
            type="text"
           
            :class="{ 'is-invalid': form.errors && form.errors.number }"
          />
          <div v-if="form.errors && form.errors.number" class="text-danger small">{{ form.errors.number }}</div>
        </div>
    </div>

  <div class="mb-3 mt-4">
        <label>Motivo</label>
        <textarea v-model="form.reason" class="form-control"></textarea>
      </div>
     
      <FormItems
        v-model:items="form.items"
        :products="products"
        :units="units"
      />
  </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
/* Mostrar más opciones en el dropdown de Multiselect */
.multiselect-blue .multiselect__content-wrapper {
  max-height: 450px !important; /* Aproximadamente 10 opciones */
  overflow-y: auto !important;
}

.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search,
.multiselect-search {
    background: var(--kt-input-solid-bg) !important;
}
.textinput {
    
  height: 36px !important;
  font-size: 1.1em;
}

.multiselect-blue .multiselect__content-wrapper {
  max-height: 450px !important; /* o el valor que desees */
  min-height: 100px !important; /* opcional, para forzar altura mínima */
  overflow-y: auto !important;
}
</style>



