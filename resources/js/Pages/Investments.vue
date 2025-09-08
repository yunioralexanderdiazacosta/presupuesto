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
</script>

<template>
   <Head :title="title" />
    <AppLayout>
        <Breadcrumb :links="links" />
   
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <SearchInput v-model="search" placeholder="Buscar inversión..." class="me-2" />
      <button class="btn btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus me-1"></i> Nueva Inversión
      </button>
    </div>
    <Table :items="filteredInvestments" :fields="['name', 'month', 'amount', 'cost_centers', 'actions']">
      <template #cell(name)="{ item }">
        {{ item.name }}
      </template>
      <template #cell(month)="{ item }">
        {{ item.month }}
      </template>
      <template #cell(amount)="{ item }">
        {{ item.amount | currency }}
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
    <CreateInvestmentModal v-if="showCreateModal" @close="closeCreateModal" :cost-centers="props.costCenters" :months="props.months" :seasons="props.seasons" :users="props.users" />
    <EditInvestmentModal v-if="showEditModal" :investment="selectedInvestment" @close="closeEditModal" :cost-centers="props.costCenters" :months="props.months" :seasons="props.seasons" :users="props.users" />
  </AppLayout>
</template>

<style scoped>
</style>
