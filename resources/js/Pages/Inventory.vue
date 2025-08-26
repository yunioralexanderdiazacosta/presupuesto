<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CardHeader from '@/Components/CardHeader.vue';



const props = defineProps({
  inventory: Array
});
</script>

<template>
     <Head :title="title" />
    <AppLayout>
    <!--begin::Breadcrumb-->
    <Breadcrumb :links="links" />
    <!--end::Breadcrumb-->

    <div class="card my-3">
     <CardHeader title="Inventario" />

         <div class="card-body bg-body-tertiary">
            <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-detalles" data-bs-toggle="tab" href="#pill-tab-detalles" role="tab" aria-controls="pill-tab-detalles" aria-selected="false">Detalles</a></li>
                <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab" aria-controls="pill-tab-gastos" aria-selected="false">Gastos por Hectarea</a></li>
                 <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab" href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra" aria-selected="false">Detalle de compra</a></li>
            </ul>
            <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
       
  <div class="container py-4">
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm small">
        <thead class="table-primary text-white">
          <tr>
            <th>Nivel 2</th>
            <th>Nivel 3</th>
            <th>Producto</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in props.inventory" :key="item.level2_name + '-' + item.level3_name + '-' + item.product_name">
            <td>{{ item.level2_name }}</td>
            <td>{{ item.level3_name }}</td>
            <td>{{ item.product_name }}</td>
            <td>{{ item.cantidad }}</td>
          </tr>
          <tr v-if="!props.inventory || !props.inventory.length">
            <td colspan="4" class="text-center text-muted">No hay datos de inventario.</td>
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
