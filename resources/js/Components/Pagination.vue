<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
})
</script>
<template>
    <nav aria-label="Pagination" class="mt-3 d-flex justify-content-end">
       <ul class="pagination">
            <template v-for="(link, k) in links" :key="k">
                <li class="page-item" :class="{'active': link.active }">
                    <Link aria-controls="kt_subscriptions_table" tabindex="0" :href="link.url ?? '#'" class="page-link" v-html="link.label"></Link>
                </li>
            </template>
        </ul>
    </nav>
</template>
