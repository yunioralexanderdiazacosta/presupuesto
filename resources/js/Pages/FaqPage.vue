<script setup>
import Faq from '@/Components/Faq.vue';
const faqs = [
  { question: '¿Cómo comenzar?', answer: 'Para comenzar a utilizar este programa debes partir por lo siguiente: 1. Crear razones sociales. 2.Crear Variedades. 3.Crear las Especies. 4.Crear una temporada. 5.Crear los niveles 3 y 4. 5.Crear los centros de costos' },
  { question: '¿Puedo exportar los datos?', answer: 'Sí, usa el botón de exportar en la parte superior de la tabla.' },
  { question: '¿Cómo edito un registro?', answer: 'Haz clic en el ícono de lápiz junto al registro que deseas editar.' }
];
</script>

<template>
  <div class="container py-4">
    <h2 class="mb-4"><span class="fas fa-question-circle me-2"></span>Preguntas Frecuentes</h2>
    <Faq :faqs="faqs" />
  </div>
</template>
