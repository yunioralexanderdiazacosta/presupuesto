<script setup>
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
// ...existing imports...
import axios from 'axios';

const props = defineProps({
    form: Object,
    costCenters: Array,
});



// Inicializar el array de subfamilias dinámicas
if (!props.form.level3s) {
    props.form.level3s = [];
}

// Función para obtener subfamilias (level3s) según el nivel2 seleccionado
const getLevel3s = (event) => {
    if (event && event !== '') {
        axios.get(route('levels3.get', event))
            .then(response => {
                props.form.level3s = response.data;
                props.form.subfamily_id = '';
            }).catch(error => console.log(error));
    } else {
        props.form.level3s = [];
        props.form.subfamily_id = '';
    }
};
const addItem = () => {
    props.form.products.push({
        product_name: "",
        quantity: "",
        price: "",
        unit_id: "",
        unit_id_price: "",
        observations: "",
        months: [],
    });
};

const removeItem = (index) => {
    props.form.products.splice(index, 1);
};

const selectAllMonths = (index, months) => {
    const allMonths = months.map((m) => m.value);
    const current = props.form.products[index].months || [];
    if (
        current.length === allMonths.length &&
        allMonths.every((m) => current.includes(m))
    ) {
        // Si ya están todos seleccionados, deselecciona todos
        props.form.products[index].months = [];
    } else {
        // Si no, selecciona todos
        props.form.products[index].months = allMonths;
    }
};
</script>
<script setup></script>
<template>
  <div class="row mb-3">
    <div class="col-lg-6">
      <label for="grouping_name" class="col-form-label">Nombre del grupo</label>
      <input
        id="grouping_name"
        v-model="form.name"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': form.errors.name }"
        placeholder="Ingrese el nombre del grupo"
      />
      <InputError class="mt-2" :message="form.errors.name" />
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <label class="col-form-label">Seleccione los centros de costo que pertenecerán al grupo</label>
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
          <thead>
            <tr>
              <th style="width:40px"></th>
              <th>Nombre</th>
              <th>Superficie</th>
              <th>Fruta</th>
              <th>Variedad</th>
              <th>Parcela</th>
              <th>Año</th>
              <th>Estado desarrollo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cc in props.costCenters.filter(cc => String(cc.season_id) === String(form.season_id))" :key="cc.id">
              <td>
                <input
                  type="checkbox"
                  :value="cc.id"
                  v-model="form.cost_center_ids"
                />
              </td>
              <td>{{ cc.name }}</td>
              <td>{{ cc.surface }}</td>
              <td>{{ cc.fruit?.name }}</td>
              <td>{{ cc.variety?.name }}</td>
              <td>{{ cc.parcel?.name }}</td>
              <td>{{ cc.year_plantation }}</td>
              <td>{{ cc.development_state?.name }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <InputError class="mt-2" :message="form.errors.cost_center_ids" />
    </div>
  </div>

</template>
<!-- <style src="@vueform/multiselect/themes/default.css"></style>-->
<style>
select,
select.form-control {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.95rem;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
}

/* Agrandar la casilla de verificación (checkbox) */
.form-check-input[type="checkbox"] {
    width: 0.8em !important;
    height: 0.8em !important;
    min-width: 0.85em !important;
    min-height: 0.85em !important;
    max-width: 0.85em !important;
    max-height: 0.85em !important;
    vertical-align: middle;
}

/* Ajustar el alto de todos los vueform/multiselect (no solo los blue) */

/* Forzar el alto de los vueform/multiselect en este archivo */
.multiselect,
.multiselect.form-control,
.multiselect__tags {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.8
    rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Forzar el alto de los inputs (TextInput y nativos), excepto textarea */
.form-control:not(textarea),
.form-control-lg:not(textarea),
.form-control-sm:not(textarea) {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    font-size: 0.8rem !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    line-height: 22px !important;
}

/* Ajustar el alto y alineación de los contenedores de iconos, inputs y selects */
.input-group {
    min-height: 26px !important;
    height: 26px !important;
    align-items: center !important;
}

.input-group-text {
    min-height: 26px !important;
    height: 26px !important;
    max-height: 26px !important;
    padding-top: 2px !important;
    padding-bottom: 2px !important;
    font-size: 0.8rem !important;
    display: flex;
    align-items: center;
}

.col-form-label,
label {
    font-size: 0.8rem !important;
}

/* Reducir el tamaño del texto de los labels de los meses (checkboxes) */
.form-check-label.ps-1,
.form-check-label.ps-2,
.form-check-label {
    font-size: 0.8rem !important;
    line-height: 1.1 !important;
    padding-left: 0.01rem !important;
    margin-bottom: 0 !important;
    display: inline-block;
    vertical-align: middle;
}

.custom-hr {
    height: 2px;
    background: #b1b1b1;
    border: none;
    margin: 0.5rem 0;
}
/* Achicar el botón 'Seleccionar todos' */
.btn.btn-outline-primary.btn-sm.ms-2 {
    padding: 0.1rem 0.35rem !important;
    font-size: 0.75rem !important;
    line-height: 1 !important;
    height: 20px !important;
    min-height: 20px !important;
    border-radius: 0.15rem !important;
}
/* Asegura que el dropdown de autocomplete esté justo debajo del input */
.autocomplete-list {
    z-index: 1050 !important;
    background: #fff;
    top: 100%;
    left: 0;
}

.multiselect .multiselect-options,
.multiselect .multiselect-option,
.multiselect__option {
    font-size: 0.7rem !important;
}

::placeholder {
    font-size: 0.7rem !important;
    color: #888 !important; /* Opcional: cambia el color si lo deseas */
    opacity: 1; /* Para asegurar que el color se aplique en todos los navegadores */
}

.input-group .form-control {
    border-radius: 0.25rem !important;
}

</style>
