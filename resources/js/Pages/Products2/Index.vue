<template>
  <AppLayout>
    <Head title="Productos2" />
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos2</h5>
        <Link class="btn btn-primary btn-sm" :href="route('products2.create')">
          <i class="fas fa-plus"></i> Nuevo
        </Link>
      </div>
      <div class="card-body">
        <Table :id="'products2-table'" :total="products2.data.length" :links="products2.links">
          <template #header>
            <th>Nombre</th>
            <th>Nivel 3</th>
            <th>Ingrediente Activo</th>
            <th>Precio</th>
            <th>Unidad</th>
            <th>Forma</th>
            <th class="text-end">Acciones</th>
          </template>
          <template #body>
            <template v-if="products2.total === 0">
              <Empty colspan="7" />
            </template>
            <template v-else>
              <tr v-for="item in products2.data" :key="item.id">
                <td>{{ item.name }}</td>
                <td>{{ item.level3 }}</td>
                <td>{{ item.active_ingredient }}</td>
                <td>{{ item.price }}</td>
                <td>{{ item.price_unit?.name || '' }}</td>
                <td>{{ item.form }}</td>
                <td class="text-end">
                  <Link :href="route('products2.edit', item.id)" class="btn btn-sm btn-light-primary me-2">
                    <i class="fas fa-edit"></i>
                  </Link>
                  <button class="btn btn-sm btn-light-danger" @click="onDelete(item.id)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  products2: Object
});

const onDelete = (id) => {
  Swal.fire({
    title: '¿Eliminar producto?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('products2.destroy', id), {
        onSuccess: () => {
          Swal.fire('Eliminado', 'Producto eliminado correctamente', 'success');
        }
      });
    }
  });
};
</script>
