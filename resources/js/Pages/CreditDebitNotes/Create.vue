
<script setup>
import Swal from 'sweetalert2';
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
  is_annulment: false
});

const save = () => {
  form.post(route('credit_debit_notes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      msgSuccess('Guardado correctamente');
      router.get(route('credit_debit_notes.index'));
    }
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
        <div class="row flex-between-end">
          <div class="col-auto align-self-center">
            <h5 class="mb-0 d-flex align-items-center gap-2" data-anchor="data-anchor" :id="title">
              <i class="fas fa-file-invoice text-primary"></i>
              {{title}}
            </h5>
          </div>
        </div>
      </div>
      <div class="card-body bg-body-tertiary">
        <form @submit.prevent="save()">
          <FormCreditDebitNote :form="form" :suppliers="suppliers" :invoices="invoices" :products="products" :units="units" />
          <div class="mb-0 text-end">
            <button type="submit" class="btn btn-primary mt-3">
              <span class="fas fa-save"></span> Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
