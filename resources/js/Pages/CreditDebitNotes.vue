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
              <div class="btn-group">
                <!-- Ver -->
                <Link :href="route('credit_debit_notes.show', note.id)"
                  v-tooltip="'Ver'"
                  class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                  <span class="svg-icon svg-icon-3">
                    <i class="fas fa-eye"></i>
                  </span>
                </Link>
                <!-- Editar -->
                <Link :href="route('credit_debit_notes.edit', note.id)"
                  v-tooltip="'Editar'"
                  class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                  <span class="svg-icon svg-icon-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                      <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                    </svg>
                  </span>
                </Link>
                <!-- Eliminar -->
                <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(note.id)" class="btn btn-icon btn-active-light-primary w-30px h-30px">
                  <span class="svg-icon svg-icon-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
                      <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
                      <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
                    </svg>
                  </span>
                </button>
              </div>
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

