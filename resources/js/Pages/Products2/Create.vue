<template>
  <AppLayout>
    <Head title="Crear Producto2" />
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Nuevo Producto2</h5>
      </div>
      <div class="card-body">
        <Products2Form :units="units" :form-options="formOptions" @submit="onSubmit" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Products2Form from '@/Components/Products2/Products2Form.vue';

const props = defineProps({
  units: Array
});

const formOptions = [
  { value: 'agroquimicos', label: 'Agroquímicos' },
  { value: 'fertilizantes', label: 'Fertilizantes' }
];

const level3Options = [
  { value: 'fungicidas', label: 'fungicidas' },
  { value: 'insecticidas', label: 'insecticidas' },
  { value: 'acaricidas', label: 'acaricidas o misceláneos' },
  { value: 'herbicidas', label: 'herbicidas' },
  { value: 'foliares', label: 'foliares' }
];

const onSubmit = (data) => {
  router.post(route('products2.store'), data, {
    onSuccess: () => {
      window.Swal.fire('Éxito', 'Producto creado correctamente', 'success');
      router.visit(route('products2.index'));
    }
  });
};
</script>
