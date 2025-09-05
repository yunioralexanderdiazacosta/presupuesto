<script setup>
import { ref } from 'vue';
import { Link, router, Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateGroupingModal from '@/Components/Groupings/CreateGroupingModal.vue';
import EditGroupingModal from '@/Components/Groupings/EditGroupingModal.vue';

const props = defineProps({
  groupings: Object,
  term: String,
  costCenters: Array,
  currentSeasonId: Number,
});

const form = useForm({
    id: '',
    name: '',
    season_id: '',
    team_id: '',
    cost_center_ids: [],
});

const title = 'Grupos';
const term = ref(props.term);

const openAdd = () => {
  form.reset();
  // Asignar season_id usando el valor de la temporada activa pasada desde el servidor
  form.season_id = props.currentSeasonId;
  $('#createGroupingModal').modal('show');
};

const openEdit = (grouping) => {
  form.reset();
  form.id = grouping.id;
  form.name = grouping.name;
  form.season_id = grouping.season_id;
  form.team_id = grouping.team_id;
  form.cost_center_ids = grouping.cost_centers.map(c => c.id);
  $('#editGroupingModal').modal('show');
};

const storeGrouping = () => {
  console.log('storeGrouping invoked, form data:', form);
  console.log('POST URL:', route('groupings.store'));
  form.post(route('groupings.store'), {
    preserveScroll: true,
    onStart: () => console.log('Inertia: start sending grouping'),
    onSuccess: () => {
      console.log('Inertia: success');
      form.reset();
      $('#createGroupingModal').modal('hide');
      msgSuccess('Guardado correctamente');
    },
    onError: (errors) => {
      console.error('Inertia: error', errors);
    },
    onFinish: () => console.log('Inertia: finish'),
  });
};

const updateGrouping = () => {
  form.post(route('groupings.update', form.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      $('#editGroupingModal').modal('hide');
      msgSuccess('Actualizado correctamente');
    },
  });
};

const msgSuccess = (msg) => {
  Swal.fire({
    position: 'center',
    icon: 'success',
    title: msg,
    showConfirmButton: false,
    timer: 1000,
  });
};

const onDeleted = (id) => {
  Swal.fire({
    title: '¿Estás seguro de que quieres eliminar el registro?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: 'rgb(0, 158, 247)',
    cancelButtonColor: '#6e6e6e',
    cancelButtonText: 'Cancelar',
    confirmButtonText: 'Confirmar',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('groupings.delete', id), {
        preserveScroll: true,
        onSuccess: () => msgSuccess('Registro eliminado correctamente'),
      });
    }
  });
};

const onFilter = () => {
  router.get(route('groupings.index', { term: term.value }), { preserveState: true });
};
</script>

<template>
  <Head :title="title" />
  <AppLayout>
    <Breadcrumb :links="[{ title: 'Dashboard', link: 'dashboard' }, { title, active: true }]" />
    <div class="card mb-3">
      <div class="card-header">
        <div class="row flex-between-end">
          <div class="col-auto align-self-center">
            <h5 class="mb-0">{{ title }}</h5>
          </div>
          <div class="col-auto ms-auto">
            <button class="btn btn-falcon-default btn-sm" @click="openAdd()">
              <span class="fas fa-plus"></span>
              <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="row justify-content-end g-0 mb-3">
          <div class="col-auto col-sm-5">
            <div class="input-group">
              <input
                class="form-control form-control-sm shadow-none search"
                type="text"
                placeholder="Buscar..."
                @keyup.enter="onFilter()"
                v-model="term"
              />
              <div class="input-group-text bg-transparent">
                <span class="fa fa-search fs-10 text-600"></span>
              </div>
            </div>
          </div>
        </div>
        <Table :id="'groupings'" :total="groupings.data.length" :links="groupings.links">
          <template #header>
            <th width="min-w-150px">Nombre</th>
            <th width="min-w-150px">Temporada</th>
            <th width="min-w-150px">Equipo</th>
            <th width="min-w-150px">IDs Centros</th>
            <th class="text-end">Acciones</th>
          </template>
          <template #body>
            <template v-if="groupings.total === 0">
              <Empty :colspan="5" />
            </template>
            <template v-else>
              <tr v-for="(grouping, i) in groupings.data" :key="i">
                <td>{{ grouping.name }}</td>
                <td>{{ grouping.season?.name }}</td>
                <td>{{ grouping.team?.name }}</td>
                <td>
                  <span v-if="grouping.cost_centers && grouping.cost_centers.length">
                    {{ grouping.cost_centers.map(cc => cc.name).join(', ') }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="text-end">
                  <button
                    type="button"
                    v-tooltip="'Editar'"
                    class="btn btn-icon btn-active-light-primary w-30px h-30px me-2"
                    @click="openEdit(grouping)"
                  >
                    <span class="svg-icon svg-icon-3">
                      <i class="fas fa-edit"></i>
                    </span>
                  </button>
                  <button
                    type="button"
                    v-tooltip="'Eliminar'"
                    class="btn btn-icon btn-active-light-danger w-30px h-30px"
                    @click="onDeleted(grouping.id)"
                  >
                    <span class="svg-icon svg-icon-3">
                      <i class="fas fa-trash"></i>
                    </span>
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </Table>
      </div>
    </div>
    <CreateGroupingModal
      :form="form"
      :cost-centers="props.costCenters"
      @store="storeGrouping"
    />
    <EditGroupingModal
      :form="form"
      :cost-centers="props.costCenters"
      @update="updateGrouping"
    />
  </AppLayout>
</template>
