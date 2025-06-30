<template>
  <div>
    <canvas ref="barChart"></canvas>
  </div>
</template>

<script setup>
import { onMounted, watch, ref } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
  chartData: {
    type: Array,
    default: () => []
  }
});

const barChart = ref(null);
let chartInstance = null;

function renderChart() {
  if (chartInstance) {
    chartInstance.destroy();
  }
  const labels = props.chartData.map(item => item.level1_name);
  const data = props.chartData.map(item => Number(item.total_amount));
  chartInstance = new Chart(barChart.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Monto total',
          data,
          backgroundColor: '#36a2eb',
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { enabled: true }
      },
      scales: {
        x: { title: { display: true, text: 'Nivel 1' } },
        y: { title: { display: true, text: 'Monto total' }, beginAtZero: true }
      },

 plugins: {
    legend: { display: false },
    tooltip: { enabled: true },
    datalabels: {
      anchor: 'start', // Cambia a 'start' para que la etiqueta quede abajo
      align: 'end',   // Cambia a 'end' para que la etiqueta quede en la base de la barra
      color: '#222',
      font: { weight: 'bold', size: 10 },
      formatter: function(value) {
        return value.toLocaleString('es-CL', { maximumFractionDigits: 0 });
      }
    }
  },



    }
  });
}

onMounted(() => {
  renderChart();
});

watch(() => props.chartData, () => {
  renderChart();
}, { deep: true });
</script>

<style scoped>
canvas {
  max-width: 100%;
  height: 320px;
}
</style>
