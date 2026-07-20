<script setup>
const props = defineProps({
    status: {
        type: String,
        required: true,
        validator: (value) => ['pending', 'partial', 'paid', 'annulled'].includes(value)
    }
});

const statusConfig = {
    pending: {
        label: 'Pendiente',
        class: 'bg-danger text-white'
    },
    partial: {
        label: 'Parcial',
        class: 'bg-info text-white'
    },
    paid: {
        label: 'Pagado',
        class: 'bg-success text-white'
    },
    annulled: {
        label: 'Anulada',
        class: 'bg-dark text-white'
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
