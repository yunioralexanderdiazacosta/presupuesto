<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div class="invalid-feedback d-block" v-show="message">
        {{ message }}
    </div>
</template>
