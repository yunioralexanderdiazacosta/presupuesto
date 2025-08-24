<script setup>
import { ref } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';

const props = defineProps({
    outflows: Object,
    term: String
});

const title = 'Salidas de productos';
const term  = ref(props.term);

const onFilter = () => {
  router.get(route('outflows.index', {term: term.value}), { preserveState: true });  
}
</script>



<template>
  <div>
   
    <Head :title="title" />
    <AppLayout>
         <Breadcrumb :links="links" />
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0">Salidas de productos</h5>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row justify-content-end g-0">
                    <div class="col-auto col-sm-5 mb-3">
                        <div class="input-group">
                            <input class="form-control form-control-sm shadow-none search" type="text" placeholder="Buscar..." @keyup.enter="onFilter()" v-model="term" />
                            <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                        </div>
                    </div>
                </div>
                <Table :id="'outflows'" :total="outflows.data.length" :links="outflows.links">
                    <template #header>
                        <th>Factura</th>
                        <th>Proveedor</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                         <th>Unidad</th>
                        <th class="text-center">Acciones</th>
                    </template>
                    <template #body>
                        <tr v-for="outflow in outflows.data" :key="outflow.invoice_product_id">
                            <td>{{ outflow.number_document }}</td>
                            <td>{{ outflow.supplier }}</td>
                            <td>{{ outflow.product }}</td>
                            <td>{{ outflow.quantity }}</td>
                            <td>{{ outflow.unit }}</td>
                            <td class="text-center">
                                <Link :href="route('outflows.create', { invoice_product_id: outflow.invoice_product_id })" class="btn btn-sm btn-white me-1">
                                  <span class="fas fa-paper-plane text-secondary"></span>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="outflows.data.length === 0">
                            <td colspan="5"><Empty /></td>
                        </tr>
                    </template>
                </Table>
            </div>
        </div>
    </AppLayout>
  </div>
</template>


