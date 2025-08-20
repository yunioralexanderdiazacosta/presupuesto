<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
  consumptions: Object,
  notes: Object,
  term: String
});

const title = 'Consumos Grles';
const term  = ref(props.term);
const consumptions = props.consumptions;
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
  <AppLayout :title="title">
    <div class="card my-3">
      <div class="card-header">
        <div class="mb-4 d-flex justify-content-between align-items-center">
          <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0"><i class="fas fa-cubes text-primary me-2"></i>Consumos grles.</h5>
          <Link class="btn btn-primary" :href="route('consumptions.create')">Nuevo Consumo</Link>
        </div>
        <div class="card-body bg-body-tertiary">
          <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
            <div class="table-responsive mt-1" style="max-height: 450px; overflow-y: auto;">
              <table class="table table-bordered table-hover table-sm custom-striped fs-10 mb-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Centro de costo</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="consumption in consumptions.data" :key="consumption.id">
                    <td>{{ consumption.id }}</td>
                    <td>
                      {{ consumption.items && consumption.items.length > 0 && consumption.items[0].product ? consumption.items[0].product.name : '' }}
                    </td>
                    <td>
                      {{ consumption.items && consumption.items.length > 0 ? consumption.items[0].quantity : '' }}
                    </td>
                    <td>{{ consumption.cost_center ? consumption.cost_center.name : '' }}</td>
                    <td>{{ consumption.date }}</td>
                    <td>{{ consumption.user ? consumption.user.name : '' }}</td>
                    <td>
                      <Link :href="route('consumptions.show', consumption.id)" class="btn btn-sm btn-info me-1">Ver</Link>
                      <Link :href="route('consumptions.edit', consumption.id)" class="btn btn-sm btn-warning me-1">Editar</Link>
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