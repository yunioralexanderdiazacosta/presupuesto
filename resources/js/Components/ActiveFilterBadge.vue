<script setup>
defineProps({
    label: { type: String, default: '' },
});
</script>

<template>
    <span
        v-if="label"
        class="badge badge-subtle-primary fw-normal d-inline-block text-truncate align-middle ms-2"
        style="font-size: 0.7rem; max-width: 420px;"
        :title="'Datos filtrados: ' + label"
    >
        <i class="fas fa-filter fa-xs me-1"></i>{{ label }}
    </span>
</template>
