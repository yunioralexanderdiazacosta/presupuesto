<template>
  <div class="modal fade" tabindex="-1" ref="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nuevo Producto2</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="store">
            <Products2Form v-model="form" :units="units" :form-options="formOptions" :level3-options="level3Options" />
            <div class="text-end mt-3">
              <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Products2Form from './Products2Form.vue';

const props = defineProps({
  units: Array,
  formOptions: Array,
  level3Options: Array
});

const emit = defineEmits(['saved']);
const modal = ref(null);
const form = useForm({
  name: '',
  level3: '',
  active_ingredient: '',
  price: '',
  unit_price_id: '',
  form: ''
});

const open = () => {
  form.reset();
  $(modal.value).modal('show');
};
const close = () => {
  $(modal.value).modal('hide');
};
const store = () => {
  form.post(route('products2.store'), {
    onSuccess: () => {
      close();
      emit('saved');
    }
  });
};

defineExpose({ open });
</script>
