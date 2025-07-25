<script setup>
import Faq from '@/Components/Faq.vue';
const faqs = [
  {
    question: '¿Cómo comenzar?',
    answer: `Para comenzar a utilizar este programa debes partir por lo siguiente:<br>
    <br>
      <ol style="margin-left:1em;">
        <li>Crear razones sociales</li>
        <li>Crear Variedades</li>
        <li>Crear las Especies</li>
        <li>Crear una temporada</li>
        <li>Crear los niveles 3 y 4</li>
        <li>Crear los centros de costos</li>
      </ol>`
  },
  {
    question: '¿Puedo exportar los datos?',
    answer: 'Sí, usa el botón de <b>exportar</b> en la parte superior de la tabla.'
  },
  {
    question: '¿Cómo edito un registro?',
    answer: 'Haz clic en el ícono de <i>lápiz</i> junto al registro que deseas editar.'
  }
];
</script>

<template>
  <div class="container py-4">
    <h2 class="mb-4"><span class="fas fa-question-circle me-2"></span>Preguntas Frecuentes</h2>
    <Faq :faqs="faqs" />
  </div>
</template>
