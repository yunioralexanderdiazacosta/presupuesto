<script setup>
import { Link } from '@inertiajs/vue3';
import Paginate from './Pagination.vue';

defineProps({
    id: String,
    links: Array,
    total: {
        type: Number,
        default: 0
    },
    stickyHeader: {
        type: Boolean,
        default: false
    }
})
</script>
<template>
        <!--begin::Table-->
        <table class="table table-bordered table-striped table-hover table-sm custom-striped fs-10 mb-0">
            <!--begin::Table head-->
            <thead :class="{ 'sticky-header': stickyHeader }">
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
            <!--begin::Table foot-->
            <tfoot v-if="$slots.footer">
                <slot name="footer"></slot>
            </tfoot>
            <!--end::Table foot-->
        </table>
        <!--end::Table-->
 
</template>

<style scoped>
.table-striped > tbody > tr:nth-of-type(odd) {
  background-color: #ffffff !important;
}
.table th, .table td {
  vertical-align: middle;
}

/* Sticky header styles */
thead.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
}

thead.sticky-header th {
  background-color: #f8f9fa !important;
  border-bottom: 2px solid #dee2e6;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

</style>