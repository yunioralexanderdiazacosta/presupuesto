<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const path  = computed(() =>usePage().props.public_path);

defineProps({
    colspan: {
        type: String,
        default: 4
    }
})
</script>
<template>
    <tr class="odd">
        <td valign="top" :colspan="colspan" class="dataTables_empty">
            <div class="d-flex flex-column flex-center">
                <img :src=" path + '/assets/media/illustrations/sketchy-1/5.png'" class="mw-400px">
                <div class="fs-1 fw-bolder text-dark mb-4">No se encontraron registros.</div>
            </div>
        </td>
    </tr>
</template>