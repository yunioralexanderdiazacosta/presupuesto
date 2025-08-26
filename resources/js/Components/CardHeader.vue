<template>
  <div class="card-header">
    <div class="row flex-between-center">
      <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
        <h3 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
          <slot name="icon"><i class="fas fa-people-carry text-primary me-2"></i></slot>{{ title }}
        </h3>
      </div>
      <div class="col">
        <slot name="actions"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true }
})
</script>
