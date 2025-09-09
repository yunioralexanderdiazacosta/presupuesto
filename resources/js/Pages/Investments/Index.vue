<script setup>
import { computed, ref } from 'vue';
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
const showEditModal = ref(false);
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
    }
  });
}

function openEditModal(investment) {
  selectedInvestment.value = investment;
  showEditModal.value = true;
}
function closeEditModal() {
  selectedInvestment.value = null;
  showEditModal.value = false;
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
          <div class="tab-pane fade show active" id="pill-tab-edicion" role="tabpanel" aria-labelledby="edicion-tab">
            <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
              <SearchInput v-model="search" placeholder="Buscar inversión..." class="me-2" />
            </div>
            <Table :items="filteredInvestments" :fields="['name', 'month', 'amount', 'cost_centers', 'actions']">
              <template #cell(name)="{ item }">
                {{ item.name }}
              </template>
              <template #cell(month)="{ item }">
                {{ item.month }}
              </template>
              <template #cell(amount)="{ item }">
                {{ item.amount != null ? Number(item.amount).toLocaleString('es-ES', { maximumFractionDigits: 0 }) : '' }}
              </template>
              <template #cell(cost_centers)="{ item }">
                <span v-for="cc in item.cost_centers" :key="cc.id" class="badge bg-info me-1">{{ cc.name }}</span>
              </template>
              <template #cell(actions)="{ item }">
                <button class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(item)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-danger" @click="$emit('delete', item)"><i class="fas fa-trash"></i></button>
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
    <EditInvestmentModal v-if="showEditModal" :investment="selectedInvestment" @close="closeEditModal" :cost-centers="props.costCenters" :months="props.months" :seasons="props.seasons" :users="props.users" />
  </AppLayout>
</template>

<style scoped>
</style>
