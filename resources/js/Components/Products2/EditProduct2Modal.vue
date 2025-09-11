<template>
  <div class="modal fade" tabindex="-1" ref="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Producto2</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="update">
            <Products2Form v-model="form" :units="units" :form-options="formOptions" :level3-options="level3Options" />
            <div class="text-end mt-3">
              <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Products2Form from './Products2Form.vue';

const props = defineProps({
  product2: Object,
  units: Array,
  formOptions: Array,
  level3Options: Array
});
const emit = defineEmits(['updated']);
const modal = ref(null);
const form = useForm({
  name: '',
  level3: '',
  active_ingredient: '',
  price: '',
  unit_price_id: '',
  form: ''
});

watch(() => props.product2, (val) => {
  if (val) {
    form.name = val.name;
    form.level3 = typeof val.level3 === 'object' && val.level3?.value ? val.level3.value : val.level3 || '';
    form.active_ingredient = val.active_ingredient;
    form.price = val.price;
    form.unit_price_id = val.unit_price_id;
    form.form = typeof val.form === 'object' && val.form?.value ? val.form.value : val.form || '';
  }
});

const open = () => {
  $(modal.value).modal('show');
};
const close = () => {
  $(modal.value).modal('hide');
};
const update = () => {
  form.post(route('products2.update', props.product2.id), {
    onSuccess: () => {
      close();
      emit('updated');
    }
  });
};

defineExpose({ open });
</script>
