<script setup>
import { Link } from '@inertiajs/vue3';
import Paginate from './Pagination.vue';

defineProps({
    id: String,
    links: Array,
    total: {
        type: Number,
        default: 0
    }
})
</script>
<template>
    <div class="table-responsive scrollbar">
        <!--begin::Table-->
        <table class="table table-bordered table-striped table-hover table-sm custom-striped fs-10 mb-0">
            <!--begin::Table head-->
            <thead>
                <tr>
                    <slot name="header"></slot>
                </tr>
            </thead>
            <!--end::Table head-->
            <!--begin::Table body-->
            <tbody>
                 
                <slot name="body"></slot>
                
            </tbody>
            <!--end::Table body-->
        </table>
        <!--end::Table-->
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