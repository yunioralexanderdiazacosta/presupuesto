<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  product: Object,
  kardex: Array
});

const title = `Kardex de ${props.product.name}`;
const links = [
  { title: 'Inventario', link: 'inventory' },
  { title: title, active: true }
];
</script>

<template>
  <Head :title="title" />
  <AppLayout>
    <Breadcrumb :links="links" />
    <div class="card my-3">
      <div class="card-header">
        <h5 class="mb-0">{{ title }}</h5>
      </div>
      <div class="card-body bg-body-tertiary">
        <div class="table-responsive mb-4" style="max-height:500px;overflow-y:auto;">
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
              <tr v-for="(item, idx) in props.kardex" :key="idx">
                <td>{{ item.fecha }}</td>
                <td>{{ item.tipo }}</td>
                <td>{{ item.documento }}</td>
                <td>{{ item.entrada || '' }}</td>
                <td>{{ item.salida || '' }}</td>
                <td>{{ item.saldo }}</td>
                <td>{{ item.precio ?? '' }}</td>
                <td>{{ item.observaciones || '' }}</td>
              </tr>
              <tr v-if="!props.kardex || !props.kardex.length">
                <td colspan="8" class="text-center text-muted">No hay movimientos de Kardex.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
