<script setup>
    import Multiselect from '@vueform/multiselect';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';

    defineProps({
        form: Object
    });
</script>
<script setup></script>
<template>
    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="cc" class="col-form-label">CC</label>
                <Multiselect
                    mode="tags"
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="$page.props.costCenters"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.cc}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.cc" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="families" class="col-form-label">Nivel 3</label>
                <Multiselect
                    :placeholder="'Seleccione nivel 3'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="$page.props.subfamilies"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.subfamily_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
                <InputError class="mt-2" :message="form.errors.subfamily_id" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label">Nombre del Producto</label>
                <TextInput
                    id="product_name"
                    v-model="form.product_name"
                    class="form-control form-control-solid"
                    type="text"
                />
                <InputError class="mt-2" :message="form.errors.product_name" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fv-row">
                <label for="unit" class="col-form-label">Unidad</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                    <Multiselect
                        :placeholder="'Seleccione unidad'"
                        v-model="form.unit_id"
                        @update:modelValue="val => form.unit_id_price = val"
                        :close-on-select="true"
                        :options="$page.props.units"
                        class="multiselect-blue form-control"
                        :class="{'is-invalid': form.errors.unit_id}"
                        :searchable="false"
                        :hide-selected="false"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.unit_id" />
            </div>
        </div>
    </div>

   

    <div class="row">
        <div class="col-lg-6">
            <div class="fv-row">
                <label class="col-form-label">Cantidad</label>
                <TextInput
                    id="quantity"
                    v-model="form.quantity"
                    class="form-control form-control-solid"
                    type="number"
                    step="0.00"
                    :class="{'is-invalid': form.errors.quantity}"
                />
                <InputError class="mt-2" :message="form.errors.quantity" />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-6">
                    <div class="fv-row">
                        <label class="col-form-label">Precio</label>
                        <TextInput
                            id="price"
                            v-model="form.price"
                            class="form-control form-control-solid"
                            type="number"
                            :class="{'is-invalid': form.errors.price}"
                        />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="fv-row">
                        <label for="unit" class="col-form-label">Unidad</label>
                         <Multiselect
                            :placeholder="''"
                            v-model="form.unit_id_price"
                            @update:modelValue="val => form.unit_id = val"
                            :close-on-select="true"
                            :options="$page.props.units"
                            class="multiselect-blue form-control"
                            :class="{'is-invalid': form.errors.unit_id_price}"
                            :searchable="true"
                            :hide-selected="false"
                        />
                        <InputError class="mt-2" :message="form.errors.unit_id_price" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="fv-row">
        <label for="months" class="col-form-label">Meses</label><br>
        <template v-for="value in $page.props.months">
            <div class="form-check form-check-solid form-check-inline mb-1">
                <input class="form-check-input" type="checkbox" v-model="form.months" :id="'kt_month_'+value.id" :value="value.value">
                <label class="form-check-label ps-2" :for="'kt_month_'+value.id">{{value.label}}</label>
            </div>
        </template>
        <small class="text-danger">{{form.errors.months}}</small> 
    </div>

    <div class="fv-row">
        <label for="observations" class="col-form-label">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect,
.multiselect__input,
.multiselect__single {
  min-height: 32px !important;
  height: 32px !important;
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
  font-size: 1rem;
}

/* Agrandar la casilla de verificación (checkbox) */
.form-check-input[type="checkbox"] {
  width: 1.1em;
  height: 1.1em;
}

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