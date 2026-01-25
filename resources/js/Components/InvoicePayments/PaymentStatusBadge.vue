<script setup>
const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['pending', 'partial', 'paid'].includes(value)
    }
});

const statusConfig = {
    pending: {
        label: 'Pendiente',
        class: 'badge-danger'
    },
    partial: {
        label: 'Parcial',
        class: 'badge-warning'
    },
    paid: {
        label: 'Pagado',
        class: 'badge-success'
    }
};
</script>

<template>
    <span 
        class="badge" 
        :class="statusConfig[status].class"
    >
        {{ statusConfig[status].label }}
    </span>
</template>
