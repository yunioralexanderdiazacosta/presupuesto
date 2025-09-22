<template>
  <Teleport to="body">
    <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Seleccionar línea de stock disponible</h5>
            <button type="button" class="btn-close" @click="$emit('close')"></button>
          </div>
          <div class="modal-body">
            <div v-if="loading" class="text-center my-4">
              <span class="spinner-border"></span>
            </div>
            <div v-else>
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th>Tipo</th>
                    <th>Documento</th>
                    <th>Proveedor</th>
                    <th>Cantidad original</th>
                    <th>Stock disponible</th>
                    <th>Seleccionar</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in stockLines" :key="line.tipo + '-' + line.line_id">
                    <td>{{ line.tipo }}</td>
                    <td>{{ line.documento }}</td>
                    <td>{{ line.proveedor }}</td>
                    <td>{{ line.cantidad_original }}</td>
                    <td>{{ line.stock_disponible }}</td>
                    <td>
                      <input type="radio" name="stockLine" :value="line" v-model="selectedLine" />
                    </td>
                  </tr>
                  <tr v-if="!stockLines.length">
                    <td colspan="6" class="text-center text-muted">No hay stock disponible.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancelar</button>
            <button type="button" class="btn btn-primary" :disabled="!selectedLine" @click="confirmSelection">Seleccionar</button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  show: Boolean,
  productId: [Number, String],
});
const emit = defineEmits(['close', 'selected']);

const stockLines = ref([]);
const loading = ref(false);
const selectedLine = ref(null);

watch(() => props.show, (val) => {
  if (val) fetchStockLines();
});

async function fetchStockLines() {
  if (!props.productId) return;
  loading.value = true;
  selectedLine.value = null;
  try {
    const res = await fetch(`/product-stock-lines?product_id=${props.productId}`);
    const data = await res.json();
    stockLines.value = data.lines || [];
  } catch (e) {
    stockLines.value = [];
  }
  loading.value = false;
}

function confirmSelection() {
  if (selectedLine.value) {
    emit('selected', selectedLine.value);
    emit('close');
  }
}
</script>

<style scoped>
.modal-backdrop {
  z-index: 1050;
}
</style>
