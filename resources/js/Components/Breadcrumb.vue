<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    links: Array
})
</script>
<template>
 <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
    <template v-for="(value, index) in links" :key="index">
        <!--begin::Item-->
        <li class="breadcrumb-item" v-if="index > 0">
            <span class="bullet bg-gray-400 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <span v-if="value.active">{{value.title}}</span>
            <Link v-else :href="route(value.link, value.params)" class="text-muted text-hover-primary">{{value.title}}</Link>
        </li>
        <!--end::Item-->
    </template>
</ul>
</template>