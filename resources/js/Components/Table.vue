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
    <div class="table-responsive">
        <!--begin::Table-->
        <table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-4">
            <!--begin::Table head-->
            <thead>
                <tr class="fw-bold fs-6">
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
    <paginate v-if="total > 0" :links="links"></paginate>
</template>