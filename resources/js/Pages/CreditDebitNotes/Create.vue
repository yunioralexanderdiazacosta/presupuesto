
<script setup>
import Swal from 'sweetalert2';
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormCreditDebitNote from '@/Components/CreditDebitNotes/Form.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const title = 'Nueva Nota de Crédito/Débito';
const links = [
  { title: 'Tablero', link: 'dashboard' },
  { title: 'Notas de Crédito/Débito', link: 'credit_debit_notes.index' },
  { title: title, active: true }
];

const props = defineProps({ suppliers: Array, invoices: Array, products: Array, units: Array });

const form = useForm({
  type: 'credito',
  supplier_id: '',
  invoice_id: '',
  date: '',
  number: '',
  reason: '',
  items: [],
  is_annulment: false,
  affects_inventory: true
});


const hasInvalidDebitQty = computed(() => {
  if (form.type !== 'debito' || !!form.affects_inventory) return false;
  return form.items.some(item => !item.quantity || Number(item.quantity) <= 0);
});

// Validar si algún item excede la cantidad facturada
const hasExceedingQuantity = computed(() => {
  if (form.type !== 'credito') return false;
  const invoice = props.invoices.find(inv => inv.value === form.invoice_id);
  if (!invoice || !invoice.products) return false;
  return form.items.some(item => {
    if (!item.invoice_product_id) return false;
    const line = invoice.products.find(p => p.value === item.invoice_product_id);
    if (!line) return false;
    return Number(item.quantity) > Number(line.amount || line.quantity || 0);
  });
});

// Validar si el monto total excede la factura
const exceedsInvoiceTotal = computed(() => {
  if (form.type !== 'credito') return false;
  const invoice = props.invoices.find(inv => inv.value === form.invoice_id);
  if (!invoice || !invoice.products) return false;
  const invoiceTotal = invoice.products.reduce((sum, p) => sum + (parseFloat(p.unit_price) || 0) * (parseFloat(p.amount || p.quantity || 0)), 0);
  const noteTotal = form.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0), 0);
  return noteTotal > invoiceTotal && invoiceTotal > 0;
});

// Texto descriptivo de lo que hará la operación
const operationDescription = computed(() => {
  if (form.type === 'credito' && form.is_annulment) return 'ANULAR completamente la factura (se revertirá todo el stock)';
  if (form.type === 'credito' && form.affects_inventory) return 'devolver stock de ' + form.items.length + ' producto(s) al inventario';
  if (form.type === 'credito' && !form.affects_inventory) return 'ajustar el precio unitario en la factura (NC financiera, sin mover stock)';
  if (form.type === 'debito' && form.affects_inventory) return 'agregar stock de ' + form.items.length + ' producto(s)';
  if (form.type === 'debito' && !form.affects_inventory) return 'ajustar precio sin mover stock';
  return '';
});

const save = () => {
  // Validación débito sin inventario
  if (hasInvalidDebitQty.value) {
    Swal.fire({ icon: 'error', title: 'Cantidad requerida', text: 'En débito sin afectar inventario, la cantidad debe ser mayor a cero.' });
    return;
  }
  // Validación cantidad excedida
  if (hasExceedingQuantity.value) {
    Swal.fire({ icon: 'error', title: 'Cantidad excedida', text: 'Al menos un item tiene una cantidad mayor a la facturada. Corrija antes de guardar.' });
    return;
  }
  // Advertencia si excede total factura
  if (exceedsInvoiceTotal.value) {
    Swal.fire({ icon: 'error', title: 'Monto excedido', text: 'El monto total de la nota excede el total de la factura. Corrija antes de guardar.' });
    return;
  }
  // Sin items
  if (!form.items.length) {
    Swal.fire({ icon: 'error', title: 'Sin items', text: 'Debe agregar al menos un item a la nota.' });
    return;
  }

  // Confirmación con resumen
  const totalNota = form.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0), 0);
  Swal.fire({
    icon: 'question',
    title: '¿Confirmar nota de ' + (form.type === 'credito' ? 'Crédito' : 'Débito') + '?',
    html: `
      <div style="text-align:left; font-size:0.9rem;">
        <p><strong>Acción:</strong> ${operationDescription.value}</p>
        <p><strong>Items:</strong> ${form.items.length} producto(s)</p>
        <p><strong>Monto total:</strong> $${totalNota.toLocaleString('es-CL')}</p>
        ${!form.affects_inventory ? '<p class="text-warning"><i class="fas fa-exclamation-triangle"></i> <strong>NC Financiera:</strong> Se ajustará el precio unitario en la factura.</p>' : ''}
        ${form.is_annulment ? '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Anulación total:</strong> Se revertirá todo el stock.</p>' : ''}
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#2c7be5',
  }).then((result) => {
    if (!result.isConfirmed) return;
    form.post(route('credit_debit_notes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        msgSuccess('Guardado correctamente');
        router.reload({ only: [] });
        router.get(route('credit_debit_notes.index'));
      }
    });
  });
}

const msgSuccess = (msg) => {
  Swal.fire({
    position: 'top-end',
    icon: 'success',
    title: msg,
    showConfirmButton: false,
    timer: 1000
  });
};
</script>

<template>
  <AppLayout>
    <Head :title="title" />
    <Breadcrumb :links="links" />
    <div class="card my-1 mx-1 px-1">
      <div class="card-header">
        <div class="row flex-between-center">
          <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
              <i class="fas fa-file-invoice me-2"></i>{{title}}
            </h5>
          </div>
          <div class="col-6 col-sm-auto ms-auto text-end ps-0">
            <a :href="route('credit_debit_notes.index')" class="btn btn-falcon-default btn-sm">
              <span class="fas fa-arrow-left" data-fa-transform="shrink-3 down-2"></span>
              <span class="d-none d-sm-inline-block ms-1">Volver</span>
            </a>
          </div>
        </div>
      </div>
      <div class="card-body bg-body-tertiary">
        <form @submit.prevent="save()">
          <FormCreditDebitNote :form="form" :suppliers="suppliers" :invoices="invoices" :products="products" :units="units" />
          <div class="mb-0 text-end">
            <button type="submit" class="btn btn-primary mt-3" :disabled="hasInvalidDebitQty || hasExceedingQuantity || exceedsInvoiceTotal || form.items.length === 0">
              <span class="fas fa-save"></span> Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
