<template>
  <AppLayout>
    <Head title="Productos2" />
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos2</h5>
        <button class="btn btn-primary btn-sm" @click="openAdd">
          <i class="fas fa-plus"></i> Nuevo
        </button>
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
                  <button class="btn btn-sm btn-light-primary me-2" @click="openEdit(item)">
                    <i class="fas fa-edit"></i>
                  </button>
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
    <CreateProduct2Modal ref="createModal" @saved="reload"
      :units="units" :form-options="formOptions" :level3-options="level3Options"
    />
    <EditProduct2Modal ref="editModal" :product2="selectedProduct2" @updated="reload"
      :units="units" :form-options="formOptions" :level3-options="level3Options"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import CreateProduct2Modal from '@/Components/Products2/CreateProduct2Modal.vue';
import EditProduct2Modal from '@/Components/Products2/EditProduct2Modal.vue';

const props = defineProps({
  products2: Object,
  units: Array,
  formOptions: Array,
  level3Options: Array
});
const { products2, units, formOptions, level3Options } = props;

const createModal = ref(null);
const editModal = ref(null);
const selectedProduct2 = ref(null);

const openAdd = () => {
  createModal.value.open();
};

const openEdit = (item) => {
  selectedProduct2.value = item;
  editModal.value.open();
};

const reload = () => {
  window.location.reload();
};

const onDelete = (id) => {
  if (confirm('¿Seguro que deseas eliminar este producto?')) {
    window.axios.post(route('products2.destroy', id)).then(reload);
  }
};
</script>
