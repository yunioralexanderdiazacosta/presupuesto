<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  notes: Object,
  term: String
});

const title = 'Notas de Crédito/Débito';
const term  = ref(props.term);
const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const msgSuccess = (msg) => {
  Swal.fire({
    position: 'center',
    icon: 'success',
    title: msg,
    showConfirmButton: false,
    timer: 1000
  });
};

const onDeleted = (id) => {
  Swal.fire({
    title: '¿Estás seguro de que quieres eliminar el registro?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: 'rgb(0, 158, 247)',
    cancelButtonColor: '#6e6e6e',
    cancelButtonText: 'Cancelar',
    confirmButtonText: 'Confirmar',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('credit_debit_notes.delete', id), {
        preserveScroll: true,
        onSuccess: () => {
          msgSuccess('Registro eliminado correctamente');
        }
      });
    }
  });
}

const onFilter = () => {
  router.get(route('credit_debit_notes.index', {term: term.value}), { preserveState: true});  
}
</script>

<template>
   <Head :title="title" />
  <AppLayout title="Notas de Crédito/Débito">
     <!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->
    <!-- Aquí irá el listado de notas -->
     <div class="card my-3">
            <div class="card-header">
    <div class="mb-4 d-flex justify-content-between align-items-center">
      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-chess text-primary me-2"></i>Notas de Credito/Debito</h5>
      <Link class="btn btn-primary" :href="route('credit_debit_notes.create')">Nueva Nota</Link>
    </div>
    <div class="card-body bg-body-tertiary">
      <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
    <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
      <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Tipo</th>
            <th>Proveedor</th>
            <th>Factura</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="note in notes.data" :key="note.id">
            <td>{{ note.number }}</td>
            <td>{{ note.type }}</td>
            <td>{{ note.supplier?.name }}</td>
            <td>{{ note.invoice?.number_document }}</td>
            <td>{{ note.date }}</td>
            <td>
              <Link :href="route('credit_debit_notes.show', note.id)" class="btn btn-sm btn-info me-1">Ver</Link>
              <Link :href="route('credit_debit_notes.edit', note.id)" class="btn btn-sm btn-warning me-1">Editar</Link>
              <!-- Botón de eliminar aquí -->
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
     </div>
      </div>
      </div>
  </AppLayout>
</template>

