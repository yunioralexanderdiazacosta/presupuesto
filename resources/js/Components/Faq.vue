<template>
  <div class="accordion" id="faqAccordion">
    <div class="accordion-item" v-for="(faq, idx) in faqs" :key="idx">
      <h2 class="accordion-header" :id="'heading' + idx">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          :data-bs-target="'#collapse' + idx" aria-expanded="false" :aria-controls="'collapse' + idx">
          {{ faq.question }}
        </button>
      </h2>
      <div :id="'collapse' + idx" class="accordion-collapse collapse" :aria-labelledby="'heading' + idx"
        data-bs-parent="#faqAccordion">
        <div class="accordion-body">
          <slot name="answer" :faq="faq"><span v-html="faq.answer"></span></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ faqs: Array });
</script>

<style scoped>
.accordion-button {
  font-weight: 500;
}
</style>
