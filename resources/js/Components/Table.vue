<script setup>
import { Link } from '@inertiajs/vue3';
import Paginate from './Pagination.vue';

const props = defineProps({
    id: String,
    items: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },
    links: { type: Array, default: () => [] },
    total: { type: Number, default: 0 }
});
</script>
<template>
    <div class="table-responsive scrollbar">
        <table class="table table-bordered table-striped table-hover table-sm custom-striped fs-10 mb-0">
            <thead>
                <tr>
                    <th v-for="field in fields" :key="field">{{ field }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.id">
                    <td v-for="field in fields" :key="field">
                        <slot :name="`cell(${field})`" :item="item">
                            {{ item[field] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- Paginación -->
        <Paginate v-if="links.length" :links="links" :total="total" />
    </div>
</template>
<style>
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #ffffff !important;
}
.table th, .table td {
    vertical-align: middle;
}
</style>