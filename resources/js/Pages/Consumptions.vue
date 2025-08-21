<script setup>
import { computed, ref, onMounted } from 'vue';
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
const consumptions = computed(() => page.props.consumptions);
const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const page = usePage();

onMounted(() => {
  if (page.props.success) {
    Swal.fire({
      position: 'center',
      icon: 'success',
      title: page.props.success,
      showConfirmButton: false,
      timer: 1500
    });
  }
});

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
      router.delete(route('consumptions.delete', id), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'Registro eliminado correctamente',
            showConfirmButton: false,
            timer: 1000
          });
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
                    <th class="text-center">Acciones</th>
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
                    <td class="text-end">
                      <div class="btn-group">
                        <Link :href="route('consumptions.show', consumption.id)" v-tooltip="'Ver'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-2">
                          <span class="svg-icon svg-icon-3">
                            <i class="fas fa-eye" style="color:#1976d2"></i>
                          </span>
                        </Link>
                          <Link :href="route('consumptions.edit', consumption.id)" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-success w-30px h-30px me-2">
                            <span class="svg-icon svg-icon-3">
                              <i class="fas fa-pen" style="color:#00695c"></i>
                            </span>
                          </Link>
                          <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(consumption.id)" class="btn btn-icon btn-active-light-danger w-30px h-30px">
                            <span class="svg-icon svg-icon-3">
                              <i class="fas fa-trash" style="color:#858585"></i>
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