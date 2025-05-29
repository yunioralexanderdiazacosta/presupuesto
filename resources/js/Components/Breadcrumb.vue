<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    links: Array
})
</script>
<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <template v-for="(value, index) in links" :key="index">
                <!--begin::Item-->
                <li class="breadcrumb-item" :class="{'active': value.active}">
                    <span v-if="value.active">{{value.title}}</span>
                    <Link v-else :href="route(value.link, value.params)">{{value.title}}</Link>
                </li>
                <!--end::Item-->
            </template>
        </ol>
    </nav>
</template>