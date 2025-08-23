<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Salidas de productos</h1>
    <!-- Aquí irá el listado de outflows -->
    <Head :title="title" />
    <AppLayout>
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0">Salidas de productos</h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <Link class="btn btn-falcon-default btn-sm" :href="route('outflows.create')">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                        </Link>
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
                        <th class="text-end">Acciones</th>
                    </template>
                    <template #body>
                        <tr v-for="outflow in outflows.data" :key="outflow.id">
                            <td>{{ outflow.invoice?.number_document || '-' }}</td>
                            <td>{{ outflow.invoice?.supplier?.name || '-' }}</td>
                            <td>{{ outflow.product?.name || '-' }}</td>
                            <td>{{ outflow.quantity }}</td>
                            <td class="text-end">
                                <Link :href="route('outflows.show', outflow.id)" class="btn btn-sm btn-info me-1">Ver</Link>
                                <Link :href="route('outflows.edit', outflow.id)" class="btn btn-sm btn-warning me-1">Editar</Link>
                                <!-- Aquí podrías agregar botón de eliminar si lo deseas -->
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

<script setup>
import { ref } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Empty from '@/Components/Empty.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';

const props = defineProps({
    outflows: Object,
    term: String
});

const title = 'Salidas de productos';
const term  = ref(props.term);
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title: title, active: true }
];

const onFilter = () => {
  router.get(route('outflows.index', {term: term.value}), { preserveState: true });  
}
</script>

<style scoped>
</style>
