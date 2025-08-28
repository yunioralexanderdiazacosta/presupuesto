<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CardHeader from '@/Components/CardHeader.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  inventory: Array
});

const kardex = ref([]);
const kardexProduct = ref(null);

function verKardex(item) {
  router.get(route('kardex.show', item.product_id), {
    onSuccess: (page) => {
      kardex.value = page.props.kardex;
      kardexProduct.value = page.props.product;
      // Cambiar a la pestaña Kardex
      setTimeout(() => {
        const pill = document.getElementById('pill-kardex');
        if (pill) pill.click();
      }, 100);
    }
  });
}
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
                <li class="nav-item"><a class="nav-link" id="pill-kardex" data-bs-toggle="tab" href="#pill-tab-kardex" role="tab" aria-controls="pill-tab-kardex" aria-selected="false">Kardex</a></li>
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
            <td @click="verKardex(item)" style="cursor:pointer; color:#2c7be5; text-decoration:underline;">{{ item.product_name }}</td>
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
  <div class="tab-pane fade" id="pill-tab-kardex" role="tabpanel" aria-labelledby="pill-kardex">
  <div v-if="kardexProduct">
    <h5>Kardex de {{ kardexProduct.name }}</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm small">
        <thead class="table-primary text-white">
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Documento</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Saldo</th>
            <th>Precio</th>
            <th>Observaciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mov in kardex" :key="mov.fecha + mov.tipo + mov.documento">
            <td>{{ mov.fecha }}</td>
            <td>{{ mov.tipo }}</td>
            <td>{{ mov.documento }}</td>
            <td>{{ mov.entrada }}</td>
            <td>{{ mov.salida }}</td>
            <td>{{ mov.saldo }}</td>
            <td>{{ mov.precio ?? '-' }}</td>
            <td>{{ mov.observaciones ?? '-' }}</td>
          </tr>
          <tr v-if="!kardex.length">
            <td colspan="8" class="text-center text-muted">No hay movimientos para este producto.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div v-else class="text-muted">Selecciona un producto para ver su historial.</div>
</div>
   </div>
   </div>
  </AppLayout>
</template>
