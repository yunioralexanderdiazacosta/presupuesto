<script setup>
import { computed } from 'vue';
import { Link,usePage } from '@inertiajs/vue3';

const props = defineProps({
    tam: {
        type: String,
        default: '340'
    }
})

const path = computed(() => usePage().props.public_path);
</script>
<template>
    <div class="d-flex justify-content-center mb-4">
        <a :href="'#'">
            <div class="my-account-logo p-0 text-center">
               <img :src="path + '/assets/images/logo-dark.png'" :width="tam" alt="logo">
            </div>
        </a>
    </div>
</template>
