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
         <div class="col-md-4">
            <label for="families" class="col-form-label">Familia</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                <Multiselect
                    :placeholder="'Seleccione familia'"
                    v-model="form.subfamily_id"
                    :close-on-select="true"
                    :options="$page.props.subfamilies"
                    class="form-control"
                    :class="{'is-invalid': form.errors.subfamily_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.subfamily_id" />
        </div>
        <div class="col-md-8">
            <label for="cc" class="col-form-label">CC</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-sitemap"></i></span>
                <Multiselect
                    mode="tags"
                    :placeholder="'Seleccione CC'"
                    v-model="form.cc"
                    :close-on-select="false"
                    :options="$page.props.costCenters"
                    class="form-control"
                    :class="{'is-invalid': form.errors.cc}"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.cc" />
        </div>
       
    </div>
<hr>
    <div class="row">
        <div class="col-md-4">
            <label class="col-form-label">Nombre del producto</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-flask"></i></span>
                <TextInput
                    id="product_name"
                    v-model="form.product_name"
                    class="form-control"
                    type="text"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.product_name" />
        </div>
        <div class="col-md-4">
            <label for="unit" class="col-form-label">Unidad de la dosis</label>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-balance-scale"></i></span>
                <Multiselect
                    :placeholder="''"
                    v-model="form.unit_id"
                    :close-on-select="true"
                    :options="$page.props.units"
                    class="multiselect-blue form-control"
                    :class="{'is-invalid': form.errors.unit_id}"
                    :searchable="true"
                    :hide-selected="false"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.unit_id" />
        </div>

         <div class="col-lg-4">
            <label class="col-form-label">Tipo de dosis</label><br>
            <template v-for="value in $page.props.doseTypes">
                <div class="form-check form-check-solid form-check-inline mb-3 mt-3">
                    <input class="form-check-input" type="radio" v-model="form.dose_type_id" :id="'kt_unit_'+value.id" :value="value.value">
                    <label class="form-check-label ps-1" :for="'kt_unit_'+value.id">{{value.label}}</label>
                </div>
            </template>
            <small class="text-danger mt-2" :v-if="form.errors.dose_type_id">{{form.errors.dose_type_id}}</small>
        </div>
    </div>

    <div class="row">
       
        <div class="col-lg-3">
            <div class="fv-row">
                <label class="col-form-label">Dosis</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="fas fa-vial"></i></span>
                    <TextInput
                        id="dose"
                        v-model="form.dose"
                        class="form-control"
                        type="number"
                        step="0.00"
                        :class="{'is-invalid': form.errors.dose}"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.dose" />
            </div>
        </div>
         <div class="col-lg-3">
            <div class="fv-row">
                <label class="col-form-label">Mojamiento</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="fas fa-tint"></i></span>
                    <TextInput
                        id="product_name"
                        v-model="form.mojamiento"
                        class="form-control"
                        type="number"
                        :class="{'is-invalid': form.errors.mojamiento}"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.mojamiento" />
            </div>
        </div>
          <div class="col-lg-3">
            <div class="fv-row">
                <label class="col-form-label">Precio</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                    <TextInput
                        id="price"
                        v-model="form.price"
                        class="form-control"
                        type="number"
                        :class="{'is-invalid': form.errors.price}"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.price" />
            </div>
        </div>
         <div class="col-lg-3">
            <div class="fv-row">
                <label for="unit" class="col-form-label">Unidad del precio</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="fas fa-balance-scale"></i></span>
                    <Multiselect
                        :placeholder="''"
                        v-model="form.unit_id_price"
                        :close-on-select="true"
                        :options="$page.props.units"
                        class="multiselect-blue form-control"
                        :class="{'is-invalid': form.errors.unit_id_price}"
                        :searchable="true"
                        :hide-selected="false"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.unit_id_price" />
            </div>
        </div>
    </div>

    <div class="row">
       
      
       
    </div>

    <div class="mb-3">
        <label for="months" class="col-form-label">Meses</label><br>
        <div class="d-flex flex-wrap gap-2">
            <template v-for="value in $page.props.months">
                <div class="form-check form-check-solid form-check-inline">
                    <input class="form-check-input" type="checkbox" v-model="form.months" :id="'kt_month_'+value.id" :value="value.value">
                    <label class="form-check-label ps-2" :for="'kt_month_'+value.id">{{value.label}}</label>
                </div>
            </template>
        </div>
        <small class="text-danger">{{form.errors.months}}</small> 
    </div>

    <div class="fv-row">
        <label for="observations" class="col-form-label">Observaciones</label>
        <div class="input-group mb-2">
            <span class="input-group-text"><i class="fas fa-comment-dots"></i></span>
            <textarea v-model="form.observations" rows="2" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }" ></textarea>
        </div>
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