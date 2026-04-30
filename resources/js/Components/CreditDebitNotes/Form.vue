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
    branches: {
        type: Array,
        default: () => []
    },
});

// Asegurar branch_id en el form
if (props.form && props.form.branch_id === undefined) {
    props.form.branch_id = '';
}

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
// Resetear is_annulment si cambia a débito
watch(
    [() => props.form.type, () => props.form.is_annulment],
    ([type, isAnnulment]) => {
        if (type === 'debito') {
            props.form.is_annulment = false;
        }
        if (type === 'credito') {
            props.form.branch_id = '';
            if (isAnnulment) {
                props.form.affects_inventory = true;
            }
        }
    }
);

// Al cambiar la sucursal, actualizar branch_id en TODOS los ítems existentes
watch(
    () => props.form.branch_id,
    (newBranchId) => {
        if (props.form.type !== 'debito') return;
        if (props.form.items && props.form.items.length > 0) {
            props.form.items.forEach(item => {
                item.branch_id = newBranchId || '';
            });
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

    <!-- Sucursal (solo para tipo débito) -->
    <div v-if="form.type === 'debito' && branches.length > 0" class="row mb-2">
        <div class="col-lg-4">
            <div class="fv-row">
                <label class="col-form-label fw-bold">
                    <i class="fas fa-building text-primary me-1"></i>Sucursal
                </label>
                <select v-model="form.branch_id" class="form-select form-control">
                    <option value="">Sin sucursal</option>
                    <option v-for="b in branches" :key="b.value" :value="b.value">{{ b.label }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Ambos checkboxes en cards, en la misma fila -->
    <div class="row mb-3 mt-3">
      <div class="col-lg-6">
        <div class="card h-100" :class="form.is_annulment ? 'border-danger' : ''">
          <div class="card-body p-3">
            <div class="form-check">
              <input
                class="form-check-input mt-2"
                type="checkbox"
                v-model="form.is_annulment"
                id="is_annulment"
                :disabled="form.type !== 'credito'"
              />
              <label class="form-check-label mt-1 mb-1" for="is_annulment">
                <strong>Anula factura completa</strong>
              </label>
            </div>
            <div v-if="form.is_annulment && form.type === 'credito'" class="alert alert-danger py-1 px-2 mb-0 mt-2" style="font-size: 0.75rem;">
              <i class="fas fa-undo me-1"></i>
              Se revertirá TODO el stock y montos de la factura. Los items se autocompletarán.
            </div>
            <div v-else-if="form.type !== 'credito'" class="mt-2">
              <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Solo disponible para notas de crédito</small>
            </div>
            <div v-else class="mt-2">
              <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Marcar si la NC anula la factura por completo</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100" :class="!form.affects_inventory ? 'border-warning' : ''">
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
              >
                <strong>Afecta inventario</strong>
              </label>
            </div>
            <div v-if="form.affects_inventory" class="mt-2">
              <small class="text-success"><i class="fas fa-boxes me-1"></i>Se moverá stock (entrada o salida según tipo)</small>
            </div>
            <div v-else class="alert alert-warning py-1 px-2 mb-0 mt-2" style="font-size: 0.75rem;">
              <i class="fas fa-exclamation-triangle me-1"></i>
              <strong>NC Financiera:</strong> No moverá stock. El descuento se aplicará directamente al precio unitario de la factura.
              <br><small class="text-muted">Los items se autocompletarán con las líneas de la factura.</small>
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

    <!-- Resumen de acción -->
    <div v-if="form.type && form.invoice_id && form.items.length > 0" class="alert d-flex align-items-start py-2 px-3 mb-3" 
      :class="form.type === 'credito' ? (form.affects_inventory ? 'alert-info' : 'alert-warning') : 'alert-info'" 
      style="font-size: 0.8rem;" role="alert">
      <i class="fas fa-clipboard-check me-2 mt-1"></i>
      <div>
        <strong>Resumen de lo que ocurrirá al guardar:</strong>
        <ul class="mb-0 mt-1 ps-3">
          <li v-if="form.type === 'credito' && form.is_annulment">
            <i class="fas fa-undo text-danger me-1"></i>Se <strong>anulará completamente</strong> la factura: se revertirá el stock de {{ form.items.length }} producto{{ form.items.length > 1 ? 's' : '' }}.
          </li>
          <li v-else-if="form.type === 'credito' && form.affects_inventory">
            <i class="fas fa-arrow-left text-primary me-1"></i>Se <strong>devolverá stock</strong> de {{ form.items.length }} producto{{ form.items.length > 1 ? 's' : '' }} al inventario.
          </li>
          <li v-else-if="form.type === 'credito' && !form.affects_inventory">
            <i class="fas fa-tag text-warning me-1"></i>Se <strong>ajustará el precio unitario</strong> en la factura de {{ form.items.length }} producto{{ form.items.length > 1 ? 's' : '' }}. <strong>No se moverá stock.</strong>
          </li>
          <li v-else-if="form.type === 'debito' && form.affects_inventory">
            <i class="fas fa-arrow-right text-success me-1"></i>Se <strong>agregará stock</strong> de {{ form.items.length }} producto{{ form.items.length > 1 ? 's' : '' }}.
          </li>
          <li v-else-if="form.type === 'debito' && !form.affects_inventory">
            <i class="fas fa-tag text-warning me-1"></i>Se <strong>ajustará el precio</strong> de {{ form.items.length }} producto{{ form.items.length > 1 ? 's' : '' }}. <strong>No se moverá stock.</strong>
          </li>
        </ul>
      </div>
    </div>

    <!-- Items -->
    <FormItems
        v-model:items="form.items"
        :products="form.type === 'credito' ? filteredInvoiceLines : products"
        :units="units"
        :branch-id="form.branch_id"
        :is_annulment="form.is_annulment"
        :type="form.type"
        :affects_inventory="form.affects_inventory"
    />
</template>
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
