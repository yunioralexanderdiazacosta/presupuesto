<script setup>
import { computed, ref, watch } from "vue";
import FormItems from "./FormItems.vue";
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
const props = defineProps({
    form: Object,
    suppliers: Array,
    invoices: Array,
    products: Array, // todos los productos del catálogo
    units: Array,
});

// Forzar valor inicial vacío en el select tipo solo si no hay valor previo (modo creación)
if (props.form && props.form.type === undefined) {
    props.form.type = null;
}

// Limpiar factura y productos al cambiar proveedor (solo si hay cambio real, no carga inicial)
watch(
    () => props.form.supplier_id,
    (nuevoProveedor, viejoProveedor) => {
        // Solo limpiar si hay un cambio real (no undefined -> valor en carga inicial)
        if (viejoProveedor !== undefined && nuevoProveedor !== viejoProveedor) {
            if (props.form.invoice_id) props.form.invoice_id = "";
            props.form.items = [];
        }
    }
);
// Limpiar productos al cambiar factura (solo si hay cambio real, no carga inicial)
watch(
    () => props.form.invoice_id,
    (nuevaFactura, viejaFactura) => {
        // Solo limpiar si hay un cambio real (no undefined -> valor en carga inicial)
        if (viejaFactura !== undefined && nuevaFactura !== viejaFactura) {
            props.form.items = [];
        }
    }
);

// Computed para líneas de factura (para nota de crédito o ajuste de precio)
const filteredInvoiceLines = computed(() => {
    if (props.form.invoice_id) {
        const factura = props.invoices.find(
            (inv) => inv.value === props.form.invoice_id
        );
        return factura && factura.products ? factura.products : [];
    }
    return [];
});

// Forzar affects_inventory a true si es crédito y anulación total
watch(
    [() => props.form.type, () => props.form.is_annulment],
    ([type, isAnnulment]) => {
        if (type === "credito" && isAnnulment) {
            props.form.affects_inventory = true;
        }
    }
);

// Computed para facturas filtradas por proveedor
const filteredInvoices = computed(() => {
    if (!props.form.supplier_id) return [];
    return props.invoices.filter(
        (inv) => String(inv.supplier_id) === String(props.form.supplier_id)
    );
});

// Autollenar items si es anulación total, o si se desmarca "Afecta inventario" y hay factura seleccionada
watch(
    [
        () => props.form.is_annulment,
        () => props.form.invoice_id,
        () => props.form.affects_inventory,
        () => props.form.type,
    ],
    (
        [isAnnulment, invoiceId, affectsInventory, type],
        [prevAnnulment, prevInvoiceId, prevAffectsInventory, prevType]
    ) => {
        // Solo autocompletar si hay un cambio REAL en alguno de los valores
        // No en la carga inicial (cuando todos los previos son undefined)
        const isInitialLoad = prevAnnulment === undefined && prevInvoiceId === undefined &&
                             prevAffectsInventory === undefined && prevType === undefined;
        if (isInitialLoad) return;

        const factura = props.invoices.find((inv) => inv.value === invoiceId);
        if (!factura || !factura.products) return;

        // Si es anulación total (crédito) o si se desmarca "Afecta inventario" (en crédito o débito)
        if (
            (type === "credito" && isAnnulment && invoiceId) ||
            (affectsInventory === false && invoiceId)
        ) {
            props.form.items = factura.products
                .filter(
                    (prod) => prod.value !== undefined && prod.value !== null
                )
                .map((prod) => ({
                    invoice_product_id: prod.value, // id de la línea, nunca null
                    product_id: prod.product_id ?? prod.value, // para backend y watcher
                    unit_id: prod.unit_id,
                    quantity: prod.amount ?? 1,
                    unit_price: prod.unit_price ?? 0,
                }));
        }
    }
);
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
                        { value: '', label: 'Seleccione tipo' },
                        { value: 'credito', label: 'Crédito' },
                        { value: 'debito', label: 'Débito' },
                    ]"
                    placeholder="Seleccione tipo"
                    :searchable="false"
                    :close-on-select="true"
                    :hide-selected="false"
                    :open="false"
                    class="multiselect-blue form-control"
                />
                <InputError class="mt-2" :message="form.errors.type" />
            </div>
        </div>
        <!-- Select Proveedor -->
        <div class="col-lg-3">
            <div class="fv-row">
                <label for="" class="col-form-label">Proveedor</label>
                <Multiselect
                    v-model="form.supplier_id"
                    :options="suppliers"
                    placeholder="Proveedor"
                    :searchable="true"
                    :close-on-select="true"
                    :hide-selected="false"
                    :open="false"
                    class="multiselect-blue form-control"
                />
                <InputError class="mt-2" :message="form.errors.supplier_id" />
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
                <InputError class="mt-2" :message="form.errors.invoice_id" />
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
                <div
                    v-if="form.errors && form.errors.number"
                    class="text-danger small"
                >
                    {{ form.errors.number }}
                </div>
            </div>
        </div>
    </div>

    <!-- Ambos checkboxes en cards, en la misma fila -->
    <div class="row mb-3 mt-3">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body p-3">
            <div class="form-check">
              <input
                class="form-check-input mt-2"
                type="checkbox"
                id="affects_inventory"
                v-model="form.affects_inventory"
                :disabled="form.type === 'credito' && form.is_annulment"
              />
                            <label
                                class="form-check-label mt-1 mb-1"
                                for="affects_inventory"
                                title="Al estar la casilla desmarcada, solo afectará el precio, no el stock"
                            >
                                Afecta inventario
                            </label>
            </div>
            <small class="text-muted mb-0 mt-0">
              Desmarcado → solo ajusta precio sin mover stock
            </small>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body p-3">
            <div class="form-check">
              <input
                class="form-check-input mt-3"
                type="checkbox"
                v-model="form.is_annulment"
                id="is_annulment"
                :disabled="form.type !== 'credito'"
              />
              <label class="form-check-label mt-3" for="is_annulment">
                Anula factura completa
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Motivo -->
    <div class="mb-3 mt-4">
        <label>Motivo</label>
        <textarea v-model="form.reason" class="form-control"></textarea>
    </div>

    <!-- Items -->
    <FormItems
        v-model:items="form.items"
        :products="form.type === 'credito' ? filteredInvoiceLines : products"
        :units="units"
        :is_annulment="form.is_annulment"
        :type="form.type"
        :affects_inventory="form.affects_inventory"
    />
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
/* Mostrar más opciones en el dropdown de Multiselect */


.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
    /* Aumentar alto máximo según viewport */
    --ms-max-height: 60vh !important;
}

/* Reducir tamaño de letra en opciones */
.multiselect-blue .multiselect-option {
    font-size: 0.75rem !important;
}

.multiselect-tags-search,
.multiselect-search {
    background: var(--kt-input-solid-bg) !important;
}
.textinput {
    height: 36px !important;
    font-size: 1.1em;
}



.form-check-input {
    transform: scale(
        1.3
    ); /* Cambia el valor para hacerlo más grande o más pequeño */
    margin-right: 8px; /* Opcional: separa el checkbox del label */
}
</style>
