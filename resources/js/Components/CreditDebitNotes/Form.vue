<script setup>
import { computed, ref, watch } from 'vue';
import FormItems from './FormItems.vue';
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
const props = defineProps({
  form: Object,
  suppliers: Array,
  invoices: Array,
  products: Array, // todos los productos del catálogo
  units: Array
});

// Limpiar factura y productos al cambiar proveedor
watch(() => props.form.supplier_id, (nuevoProveedor) => {
  if (props.form.invoice_id) props.form.invoice_id = '';
  props.form.items = [];
});
// Limpiar productos al cambiar factura
watch(() => props.form.invoice_id, (nuevaFactura) => {
  props.form.items = [];
});

// Computed para productos filtrados según tipo y factura
const filteredProducts = computed(() => {
  if (props.form.type === 'debito' && props.form.invoice_id) {
    // Buscar la factura seleccionada
    const factura = props.invoices.find(inv => inv.value === props.form.invoice_id);
    // Si la factura tiene productos asociados, devolver solo esos
    if (factura && factura.products) {
      return factura.products;
    }
    // Si no hay productos asociados, devolver array vacío
    return [];
  }
  // Si es crédito, devolver todos los productos del catálogo
  return props.products;
});

// Computed para facturas filtradas por proveedor
const filteredInvoices = computed(() => {
  if (!props.form.supplier_id) return [];
  return props.invoices.filter(inv => inv.supplier_id === props.form.supplier_id);
});

// Autollenar items si es anulación total y hay factura seleccionada
watch([
  () => props.form.is_annulment,
  () => props.form.invoice_id
], ([isAnnulment, invoiceId]) => {
  if (isAnnulment && invoiceId) {
    // Buscar la factura seleccionada
    const factura = props.invoices.find(inv => inv.value === invoiceId);
    if (factura && factura.products) {
      // Crear los items con los datos de la factura
      props.form.items = factura.products.map(prod => ({
        product_id: prod.value,
        unit_id: prod.unit_id,
        quantity: prod.amount ?? 1, // usa amount si está disponible, si no 1
        unit_price: prod.unit_price ?? 0 // usa unit_price si está disponible, si no 0
      }));
    }
  }
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
            :options="filteredInvoices"
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

      <div class="row">
    <div class="col-12 mb-2 mt-4  ms-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" v-model="form.is_annulment" id="is_annulment" :disabled="form.type !== 'credito'">
        <label class="form-check-label" for="is_annulment">
          Anula factura completa
        </label>
      </div>
    </div>
  </div>

  <div class="mb-3 mt-4">
        <label>Motivo</label>
        <textarea v-model="form.reason" class="form-control"></textarea>
      </div>
     
      <FormItems
        v-model:items="form.items"
        :products="filteredProducts"
        :units="units"
        :is_annulment="form.is_annulment"
        :type="form.type"
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

.form-check-input {
  transform: scale(1.3); /* Cambia el valor para hacerlo más grande o más pequeño */
  margin-right: 8px;     /* Opcional: separa el checkbox del label */
}
</style>



