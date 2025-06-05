<template>
  <div>
    <h1>Clima en {{ city }}</h1>
    <form @submit.prevent="fetchWeather">
      <input v-model="inputCity" placeholder="Ingresa ciudad" />
      <button>Buscar</button>
    </form>

    <div v-if="weather">
      <p>Temperatura: {{ weather.current.temp_c }} °C</p>
      <p>Condición: {{ weather.current.condition.text }}</p>
      <img :src="weather.current.condition.icon" alt="icon" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  weather: Object,
  city: String
})

const inputCity = ref(props.city)

function fetchWeather() {
  router.get('/weather', { city: inputCity.value })
}
</script>
