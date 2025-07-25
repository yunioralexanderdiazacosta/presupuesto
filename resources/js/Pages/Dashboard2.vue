<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const links = [{ title: 'Tablero', link: 'dashboard', active: true }];
</script>
<template>
  <Head :title="title" />
  <AppLayout>
  </AppLayout>
</template>