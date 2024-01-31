<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const path = computed(() =>usePage().props.public_path);
</script>
<template>
    <img :src="path + '/assets/images/logo-dark.png'" style="height: 60px !important;" alt="logo">
</template>
