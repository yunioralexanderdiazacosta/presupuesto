<script setup>
import { computed, ref } from 'vue';
import { Link, router, Head, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import moment from 'moment';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import TitleBudget from '@/Components/Budgets/TitleBudget.vue';
import CreateCostCenterModal from '@/Components/CostCenters/CreateCostCenterModal.vue';
import EditCostCenterModal from '@/Components/CostCenters/EditCostCenterModal.vue';
import SearchInput from '@/Components/SearchInput.vue';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';
import ExportPdfButton from '@/Components/ExportPdfButton.vue';
import axios from 'axios';

const props = defineProps({
    costCenters: Object,
    season: Object,
    term: String
});

const form = useForm({
    id: null,
    name: '',
    observations: '',
    surface: null,
    fruit_id: '',
    variety_id: '',
    parcel_id: '',
    varieties: [],
    status: true,
    year_plantation: '',
    development_state_id: '',
    company_reason_id: ''   
});

const path = computed(() =>usePage().props.public_path);

const title = 'Centros de costo';

const term  = ref(props.term);

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: title, active: true }];

const openAdd = () => {
    form.reset();
    $('#createCostCenterModal').modal('show');
}

const openEdit = (costCenter) => {
    form.reset();
    form.id = costCenter.id; 
    form.name = costCenter.name;
    form.surface = costCenter.surface; 
    form.observations = costCenter.observations; 
    form.fruit_id = costCenter.fruit_id;
    getVarieties(form.fruit_id);
    form.variety_id = costCenter.variety_id;
    form.parcel_id = costCenter.parcel_id;
    form.development_state_id = costCenter.development_state_id;
    form.year_plantation = costCenter.year_plantation;
     form.company_reason_id = costCenter.company_reason_id;
    $('#editCostCenterModal').modal('show');
}

const storeCostCenter = () => {
    form.post(route('cost.centers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#createCostCenterModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
    });
}

 const getVarieties = (frutal_id) => {
        if(frutal_id && frutal_id != ""){
            axios.get(route('varieties.get', frutal_id))
            .then(response => {
                form.varieties = response.data;
            }).catch(error => console.log(error));
        }
    }

const updateCostCenter = () => {
   form.post(route('cost.centers.update', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#editCostCenterModal').modal('hide');
            msgSuccess('Guardado correctamente');
        }
   }); 
}

const msgSuccess = (msg) => {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1000
    });
}

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
            router.delete(route('cost.centers.delete', id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess('Registro eliminado correctamente');
                }
            });
        }
    });
}

const onFilter = () => {
  router.get(route('cost.centers.index', {term: term.value}), { preserveState: true});  
}

const importFile = ref(null);
const fileName = ref('');

const onFileSelected = () => {
  fileName.value = importFile.value.files[0]?.name || '';
};

const importExcel = async () => {
  if (!importFile.value || !importFile.value.files.length) {
    Swal.fire('Selecciona un archivo Excel primero', '', 'warning');
    return;
  }
  const formData = new FormData();
  formData.append('file', importFile.value.files[0]);
  try {
    await axios.post(route('cost.centers.import'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    Swal.fire('Importación exitosa', '', 'success');
    window.location.reload();
  } catch (e) {
    if (e.response?.status === 422 && e.response.data.failures) {
      const detalles = e.response.data.failures
        .map(f => `Fila ${f.row}: ${f.errors.join(', ')}`)
        .join('\n');
      Swal.fire('Errores en el archivo', detalles, 'error');
    } else {
      Swal.fire('Error al importar', e.response?.data?.message || 'Revisa el archivo', 'error');
    }
  }
};
</script>
<template>
    <Head :title="title" />
    <AppLayout>
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-12 d-flex align-items-center justify-content-between pe-0 gap-2">
                      <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Centros de costo
                      </h5>
                       <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                      <div id="table-purchases-replace-element">
                        
                        <!-- Input y botones de importación y plantilla -->
                        <!-- Selección de archivo personalizada -->
<label for="import-file" class="btn btn-falcon-default btn-sm mb-0 ms-2">
  <span class="fas fa-file-upload me-1"></span>
  Seleccionar archivo
</label>
<input id="import-file" type="file" ref="importFile" accept=".xlsx,.xls,.csv" class="d-none" @change="onFileSelected" />
<span v-if="fileName" class="ms-2 text-truncate" style="max-width:150px;display:inline-block;vertical-align:middle;">
  {{ fileName }}
</span>
<button class="btn btn-falcon-default btn-sm ms-1" @click="importExcel">
  <span class="fas fa-file-import me-1"></span>
  Importar Excel
</button>
<a :href="route('cost.centers.template')" class="btn btn-falcon-default btn-sm ms-1 me-1">
  <span class="fas fa-file-download me-1"></span>
  Ejemplo plantilla
</a>
<button class="btn btn-falcon-default btn-sm ms-auto" type="button" @click="openAdd()" style="margin-right: 0.8rem;"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Nuevo</span></button>
                      </div>
                    </div>
                    </div>
                </div>
            </div>


             <div class="card-body  card-body bg-body-tertiary pt-2"> 
                 <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
               <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                          <SearchInput
                            v-model="search"
                            placeholder="Buscar por nombre..."
                          />
                          <div class="d-flex align-items-center gap-1">
                            <ExportExcelButton
                              :data="costCenters.data"
                              :headers="[
                                { label: 'Nombre', key: 'name' },
                                { label: 'Frutal', key: 'fruit.name' },
                                { label: 'Variedad', key: 'variety.name' },
                                { label: 'Superficie', key: 'surface', type: 'number' },
                                { label: 'Año plantación', key: 'year_plantation' },
                                { label: 'Estado productivo', key: 'development_state.name' },
                                { label: 'Razón social', key: 'company_reason.name' },
                                { label: 'Observaciones', key: 'observations' }
                              ]"
                              class="btn btn-success btn-md d-flex align-items-center p-0"
                              filename="CentrosDeCosto.xlsx"
                            />
                           
                            <ExportPdfButton
                              :data="costCenters.data"
                              :headers="[
                                { label: 'Nombre', key: 'name' },
                                { label: 'Frutal', key: 'fruit.name' },
                                { label: 'Variedad', key: 'variety.name' },
                                { label: 'Superficie', key: 'surface', type: 'number' },
                                { label: 'Año plantación', key: 'year_plantation' },
                                { label: 'Estado productivo', key: 'development_state.name' },
                                { label: 'Razón social', key: 'company_reason.name' },
                                { label: 'Observaciones', key: 'observations' }
                              ]"
                              class="btn btn-danger btn-md d-flex align-items-center p-0"
                              filename="CentrosDeCosto.pdf"
                            />
                          </div>
                     </div>     







                <!-- Contenedor con scroll vertical para tabla -->
                <div class="table-responsive" style="max-height:600px; overflow-y:auto;">
                <Table :id="'costCenters'" :total="costCenters.data.length" :links="costCenters.links">
                    <!--begin::Table head-->
                    <template #header>
                        <!--begin::Table row-->
                        <th width="min-w-150px">Nombre</th>
                        <th width="min-w-150px">Frutal</th>
                        <th width="min-w-150px">Variedad</th>
                        <th width="min-w-150px">Superficie</th>
                        <th width="min-w-150px">Año plantacion</th>
                        <th width="min-w-150px">Estado productivo</th>
                        <th width="min-w-150px">Razon social</th>
                        <th width="min-w-150px">Observaciones</th>
                        <th width="min-w-150px" class="text-end">Acciones</th>
                        <!--end::Table row-->
                    </template>
                    <!--end::Table head-->
                    <!--begin::Table body-->
                    <template #body>
                        <template v-if="costCenters.total == 0">
                            <Empty colspan="8" />
                        </template>
                        <template v-else>
                            <tr v-for="(costCenter, index) in costCenters.data" :key="index">
                                <td><span class="text-dark text-hover-primary fw-bold mb-1">{{costCenter.name}}</span></td>
                                <td>{{costCenter.fruit.name}}</td>
                                <td>{{costCenter.variety.name}}</td>
                                <td>{{costCenter.surface}}</td>
                                <td>{{costCenter.year_plantation}}</td>
                                <td>{{costCenter.development_state.name}}</td>
                                 <td>{{costCenter.company_reason ? costCenter.company_reason.name : ''}}</td>
                                <td>{{costCenter.observations}}</td>
                                <td class="text-end">
                                    <!--begin::Update-->
                                    <button type="button" v-tooltip="'Editar'" class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" @click="openEdit(costCenter)">
                                        <span class="svg-icon svg-icon-3">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path opacity="0.3" d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z" fill="currentColor"></path>
                                            <path d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z" fill="currentColor"></path>
                                        </svg>
                                        </span>
                                    </button>
                                    <!--end::Update-->
                                    <!--begin::Delete-->
                                    <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(costCenter.id)" class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor" />
                                                <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor" />
                                                <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                    </button>
                                    <!--end::Delete-->
                                </td>
                            </tr>
                        </template>
                    </template>
                    <!--end::Table body-->
                </Table>
                </div>
            </div>
                </div>
        </div>
        <CreateCostCenterModal @store="storeCostCenter" :form="form" />
        <EditCostCenterModal @update="updateCostCenter" :form="form" />
    </AppLayout>
</template>