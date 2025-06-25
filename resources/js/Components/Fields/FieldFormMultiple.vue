<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';
    import { usePage } from '@inertiajs/vue3';
const page = usePage();

	const props = defineProps({
		form: Object
	});

    const addItem = () => {
        props.form.products.push({
            product_name: '',
            price: '',
            unit_id: '',
            quantity: '',
            observations: '',
            months: []
        });
    }

    const removeItem = (index) => {
        props.form.products.splice(index, 1);
    }

    const getLevel3s = (event) => {
        if(event && event != ""){
            axios.get(route('levels3.get', event))
            .then(response => {
                props.form.level3s = response.data;
                props.form.subfamily_id = '';
            }).catch(error => console.log(error));
        }
    }

//seleccionar todos los meses
    const selectAllMonths = (index) => {
      const allMonths = page.props.months.map(m => String(m.value));
      const current = props.form.products[index].months || [];
      if (current.length === allMonths.length && allMonths.every(m => current.includes(m))) {
        props.form.products[index].months = [];
      } else {
        props.form.products[index].months = allMonths;
      }
    };




</script>
<script setup></script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="level2_id" class="col-form-label">Level 2</label>
                <Multiselect
                    :placeholder="'Seleccione Level 2'"
                    v-model="form.level2_id"
                    :close-on-select="true"
                    :options="$page.props.level2s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.level2_id}"
                    :searchable="true"
                    :hide-selected="false"
                    @select="getLevel3s($event)"
                />
                <InputError class="mt-2" :message="form.errors.level2_id" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="families" class="col-form-label">Familia</label>
                <Multiselect
                    :placeholder="'Seleccione familia'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="form.level3s"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.subfamily_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.subfamily_id" />
            </div>
        </div>
    </div>

    <template v-for="(product,index) in form.products">
        <hr>
        <div class="row">
            <div class="col-lg-4">
                <div class="fv-row">
                    <label class="col-form-label">Nombre del producto</label>
                    <TextInput
                        id="product_name"
                        v-model="product.product_name"
                        class="form-control form-control-solid"
                        type="text"
                         :class="{'is-invalid': form.errors['products.'+index+'.product_name']}"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.product_name']" />
                </div>
            </div>
            <div class="col-lg-3">
                <div class="fv-row">
                    <label for="unit" class="col-form-label">Unidad de dosis</label>
                     <Multiselect
                        :placeholder="''"
                        v-model="product.unit_id"
                        :close-on-select="true"
                        :options="$page.props.units"
                        class="multiselect-blue form-control"
                        :class="{'is-invalid': form.errors['products.'+index+'.unit_id']}"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.unit_id']" />
                </div>
            </div>

            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Cantidad</label>
                    <TextInput
                        id="quantity"
                        v-model="product.quantity"
                        class="form-control form-control-solid"
                        type="number"
                        step="0.00"
                        :class="{'is-invalid': form.errors['products.'+index+'.quantity']}"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.quantity']" />
                </div>
            </div>
            <div class="col-lg-3">
                <div class="fv-row">
                    <label class="col-form-label">Precio</label>
                    <TextInput
                        id="price"
                        v-model="product.price"
                        class="form-control form-control-solid"
                        type="number"
                        :class="{'is-invalid': form.errors['products.'+index+'.price']}"
                    />
                    <InputError class="mt-2" :message="form.errors['products.'+index+'.price']" />
                </div>
            </div>
        </div>
  

        <div class="row">
            <div class="col-lg-8">
                <div class="fv-row">
                    <label for="months" class="col-form-label">Meses</label>
                     <button type="button" class="btn btn-sm btn-secondary mb-1 ms-1" :class="{'btn-success': product.months && product.months.length === page.props.months.length}" @click="selectAllMonths(index)">
                      {{ (product.months && product.months.length === page.props.months.length) ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                    </button><br>
                    <template v-for="value in $page.props.months">
                        <div style="margin-right: 0.5rem;" class="form-check form-check-solid form-check-inline mb-1">
                            <input class="form-check-input" type="checkbox" v-model="product.months" :id="'kt_month_'+value.id" :value="value.value">
                            <label class="form-check-label ps-2" :for="'kt_month_'+value.id">{{value.label}}</label>
                        </div>
                    </template>
                    <small class="text-danger" v-if="form.errors['products.'+index+'.months']"><br>{{form.errors['products.'+index+'.months']}}</small> 
                </div>
            </div>
            <div class="col-lg-4">
                <div class="fv-row">
                    <label for="observations" class="col-form-label">Observaciones</label>
                    <textarea v-model="product.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
                    <InputError class="mt-2" :message="form.errors.observations" />
                </div>
            </div>
        </div>
   

        <div class="row">
            <div class="col-lg-12 text-end">
                <button type="button" v-if="form.products.length == (index + 1)" @click="addItem()" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" @click="removeItem(index)" class="btn btn-sm btn-danger" v-if="form.products.length > 1">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
    </template>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}
</style>