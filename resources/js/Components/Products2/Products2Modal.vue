<script setup>
import Table from '@/Components/Table.vue';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
  products2: Object,
  term: String,
  level3: String
});
const emit = defineEmits(['filter', 'select']);

const search = ref(props.term || '');
const searchInput = ref(null);
watch(() => props.term, (val) => (search.value = val));
watch(search, (val) => emit('filter', val));
// Enfocar el input cuando el modal se muestre
onMounted(() => {
  const modal = document.getElementById('products2Modal');
  modal.addEventListener('shown.bs.modal', () => {
    searchInput.value && searchInput.value.focus();
  });
});
</script>

<template>
  <div class="modal fade" id="products2Modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Buscar Producto - {{ level3 }}</h5>
        </div>
        <div class="modal-body">
          <div class="input-group sticky-top bg-white mb-3">
              <input
              ref="searchInput"
              v-model="search"
              @keyup.enter="emit('filter', search)"
              type="text"
              class="form-control"
              placeholder="Buscar..."
            />
            <button class="btn btn-outline-secondary btn-sm px-2 py-1 fs-10" type="button" @click="emit('filter', search)">Buscar</button>
          </div>
          <Table :id="'products2'" :total="products2.data.length" :links="products2.links">
            <template #header>
              <th>Nombre</th>
              <th>Ingrediente Act.</th>
              <th>precio</th>
              <th>Unidad</th>
            </template>
            <template #body>
              <tr
                v-for="item in products2.data"
                :key="item.id"
                @click="emit('select', item)"
                style="cursor: pointer;"
              >
                <td>{{ item.name }}</td>
                <td>{{ item.active_ingredient }}</td>
                <td>{{ item.price | currency }}</td>
                <td>{{ item.unit }}</td>
              </tr>
            </template>
          </Table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

