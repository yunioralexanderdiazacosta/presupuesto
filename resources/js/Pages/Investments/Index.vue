<script setup>
// Card de monto total (estilo Services)
const totalAmount = computed(() => {
  if (!filteredInvestments.value.length) return 0;
  return filteredInvestments.value.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
});
const totalAmountCLP = computed(() => {
  return totalAmount.value.toLocaleString('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
});
import { computed, ref, nextTick } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateInvestmentModal from '@/Components/Investments/CreateInvestmentModal.vue';
import EditInvestmentModal from '@/Components/Investments/EditInvestmentModal.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
  investments: Object,
  costCenters: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  seasons: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] }
});

console.log('Investments props:', props.investments);

const search = ref('');
const filteredInvestments = computed(() => {
  if (!props.investments || !props.investments.data) return [];
  if (!search.value) return props.investments.data;
  const term = search.value.toLowerCase();
  return props.investments.data.filter(item => {
    const name = item.name ? item.name.toLowerCase() : '';
    const month = item.month ? item.month.toLowerCase() : '';
    return (
      name.includes(term) ||
      month.includes(term)
    );
  });
});

import { onMounted } from 'vue';
const selectedInvestment = ref(null);

// Formulario reactivo para crear inversión
const createForm = useForm({
  name: '',
  month: '',
  amount: '',
  cost_centers: [],
  season_id: '',
  responsable: ''
});
const editForm = useForm({
  name: '',
  month_execute: '',
  amount: '',
  cost_centers: [],
  season_id: '',
  responsable: '',
  estado: '',
  observations: ''
});

function openCreateModal() {
  $('#createInvestmentModal').modal('show');
}
function closeCreateModal() {
  $('#createInvestmentModal').modal('hide');
  createForm.reset();
}

function storeInvestment() {
  createForm.post(route('investments.store'), {
    onSuccess: () => {
      $('#createInvestmentModal').modal('hide');
      createForm.reset();
      Swal.fire({
        icon: 'success',
        title: '¡Inversión creada!',
        text: 'La inversión se registró correctamente.',
        timer: 1800,
        showConfirmButton: false
      });
    }
  });
}

function openEditModal(investment) {
  selectedInvestment.value = investment;
  // Cargar datos en formulario de edición
  editForm.reset();
  editForm.name = investment.name;
  editForm.month_execute = Number(investment.month_execute);
  editForm.amount = investment.amount;
  editForm.cost_centers = investment.cost_centers.map(cc => cc.id);
  editForm.season_id = investment.season?.id || '';
  editForm.responsable = investment.responsable;
  editForm.observations = investment.observations || '';
  editForm.estado = investment.estado || 'pendiente';
  nextTick(() => {
    $('#editInvestmentModal').modal('show');
  });
}
function closeEditModal() {
  $('#editInvestmentModal').modal('hide');
  selectedInvestment.value = null;
  editForm.reset();
}
// Enviar edición al servidor
function updateInvestment() {
  editForm.post(route('investments.update', selectedInvestment.value.id), {
    onSuccess: () => {
      $('#editInvestmentModal').modal('hide');
      selectedInvestment.value = null;
      editForm.reset();
      Swal.fire({
        icon: 'success',
        title: '¡Inversión actualizada!',
        text: 'Los cambios se guardaron correctamente.',
        timer: 1800,
        showConfirmButton: false
      });
    }
  });
}
</script>

<template>
  <Head :title="title" />
  <AppLayout >
    <Breadcrumb />
   
    <div class="card my-3">
      <div class="card-header">
        <div class="row flex-between-center">
          <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
            <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
              <i class="fas fa-piggy-bank text-success me-2"></i>Inversiones
            </h5>
          </div>
          <div class="col-6 col-sm-auto ms-auto text-end ps-0">
            <div id="table-investments-replace-element">
              <button class="btn btn-falcon-default btn-sm" type="button" @click="openCreateModal">
                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Nueva</span>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body bg-body-tertiary">
        <ul class="nav nav-pills" id="pill-myTab" role="tablist">
          <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab" href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
          <!-- Puedes agregar más tabs aquí si lo necesitas -->
        </ul>
        <div class="tab-content border p-3 mt-3" id="pill-myTabContent">


 <div class="row mb-3">
      <div class="col-md-4 col-lg-2 col-xl-2 col-xxl-2">
        <div class="card h-100 p-1 small-card">
          <div class="card-header pb-0 pt-1 px-2">
            <h6 class="mb-0 mt-1 fs-8 d-flex align-items-center small-card-title">Monto Total</h6>
          </div>
          <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
            <div class="row">
              <div class="col">
                <p class="font-sans-serif lh-1 mb-1 fs-8 small-card-number">{{ totalAmountCLP }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>






          <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="edicion-tab">
            <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
              <SearchInput v-model="search" placeholder="Buscar inversión..." class="me-2" />
            </div>
            <Table :items="filteredInvestments" :fields="['name', 'month', 'amount', 'cost_centers', 'responsable', 'observations', 'actions']">
              <template #header>
                <th>Nombre</th>
                <th>Mes</th>
                <th>Monto</th>
                <th>Centros de Costo</th>
                <th>Responsable</th>
                <th>Observaciones</th>
                <th>Acciones</th>
              </template>
              <template #body>
                <tr v-for="item in filteredInvestments" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>{{ item.month }}</td>
                  <td>{{ item.amount != null ? Number(item.amount).toLocaleString('es-ES', { maximumFractionDigits: 0 }) : '' }}</td>
                  <td>
                    <span v-for="cc in item.cost_centers" :key="cc.id" class="badge bg-info me-1">{{ cc.name }}</span>
                  </td>
                  <td>{{ item.responsable }}</td>
                  <td><span class="text-muted small">{{ item.observations }}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(item)"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger" @click="$emit('delete', item)"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </template>
            </Table>
            <Empty v-if="filteredInvestments.length === 0" message="No hay inversiones registradas." />
          </div>
        </div>
      </div>
    </div>
    <CreateInvestmentModal
      :costCenters="props.costCenters"
      :months="props.months"
      :seasons="props.seasons"
      :form="createForm"
      @close="closeCreateModal"
      @store="storeInvestment"
      @update:form="f => Object.assign(createForm, f)"
    />
    <EditInvestmentModal
      v-if="selectedInvestment"
      :form="editForm"
      :cost-centers="props.costCenters"
      :months="props.months"
      :seasons="props.seasons"
      @update="updateInvestment"
      @update:form="f => Object.assign(editForm, f)"
      @close="closeEditModal"
    />
  </AppLayout>
</template>

<style scoped>
</style>
