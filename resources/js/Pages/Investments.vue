<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

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

const title = 'Inversiones';
const links = [
  { title: 'Tablero', link: 'dashboard' },
  { title: 'Inversiones', active: true }
];

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

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedInvestment = ref(null);

function openEditModal(investment) {
  selectedInvestment.value = investment;
  showEditModal.value = true;
}
function closeEditModal() {
  selectedInvestment.value = null;
  showEditModal.value = false;
}
function closeCreateModal() {
  showCreateModal.value = false;
}

const handleDelete = (investment) => {
  Swal.fire({
    title: '¿Estás seguro de que quieres eliminar esta inversión?',
    text: `"${investment.name}"`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: 'rgb(0, 158, 247)',
    cancelButtonColor: '#6e6e6e',
    cancelButtonText: 'Cancelar',
    confirmButtonText: 'Confirmar',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('investments.delete', investment.id), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            icon: 'success',
            title: 'Registro eliminado correctamente',
            showConfirmButton: false,
            timer: 1000
          });
        },
        onError: (errors) => {
          console.error('Error al eliminar:', errors);
          Swal.fire({
            icon: 'error',
            title: 'Error al eliminar',
            text: 'Ocurrió un error al intentar eliminar la inversión',
            showConfirmButton: true
          });
        }
      });
    }
  });
}
</script>

<template>

  <Head :title="title" />
  <AppLayout>
    <Breadcrumb :links="links" />

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
              <button class="btn btn-falcon-default btn-sm" type="button" @click="showCreateModal = true">
                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                <span class="d-none d-sm-inline-block ms-1">Nueva</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card-body bg-body-tertiary">
        <ul class="nav nav-pills" id="pill-myTab" role="tablist">
          <li class="nav-item"><a class="nav-link active" id="pill-edicion" data-bs-toggle="tab"
              href="#pill-tab-edicion" role="tab" aria-controls="pill-tab-edicion" aria-selected="true">Edición</a></li>
          <li class="nav-item"><a class="nav-link" id="pill-salidas" data-bs-toggle="tab" href="#pill-tab-salidas"
              role="tab" aria-controls="pill-tab-salidas" aria-selected="false">Salidas</a></li>
          <li class="nav-item"><a class="nav-link" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos"
              role="tab" aria-controls="pill-tab-gastos" aria-selected="false">kjhyuass</a></li>
          <li class="nav-item"><a class="nav-link" id="pill-detalles-compra" data-bs-toggle="tab"
              href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra"
              aria-selected="false">kjuh</a></li>
        </ul>
        <div class="tab-content border p-3 mt-3" id="pill-myTabContent">


          <Table :items="filteredInvestments" :fields="['name', 'cost_centers', 'actions']">
            <template #cell(name)="{ item }">
              {{ item.name }}
            </template>
            <template #cell(cost_centers)="{ item }">
              <span v-for="cc in item.cost_centers" :key="cc.id" class="badge bg-info me-1">{{ cc.name }}</span>
            </template>
            <template #cell(actions)="{ item }">
              <button class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(item)"><i
                  class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-outline-danger" @click="handleDelete(item)"><i
                  class="fas fa-trash"></i></button>
            </template>
          </Table>
        </div>
      </div>
    </div>
    <CreateInvestmentModal v-if="showCreateModal" @close="closeCreateModal" :cost-centers="props.costCenters"
      :months="props.months" :seasons="props.seasons" :users="props.users" />
    <EditInvestmentModal v-if="showEditModal" :investment="selectedInvestment" @close="closeEditModal"
      :cost-centers="props.costCenters" :months="props.months" :seasons="props.seasons" :users="props.users" />
  </AppLayout>
</template>

