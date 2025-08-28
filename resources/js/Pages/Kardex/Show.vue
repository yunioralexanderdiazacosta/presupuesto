<script setup>
const props = defineProps({
  product: Object,
  kardex: Array
});
</script>

<template>
  <div>
    <h5>Kardex de {{ product.name }}</h5>
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
</template>
