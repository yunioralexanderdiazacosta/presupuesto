<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
  pieLabels: Array,
  pieDatasets: Array
});

const chartRef = ref(null);
let chartInstance = null;

function renderChart() {
  if (chartRef.value && window.echarts) {
    if (!chartInstance) {
      chartInstance = window.echarts.init(chartRef.value);
    }
    chartInstance.setOption({
      tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)'
      },
      legend: {
        orient: 'vertical',
        left: 10
      },
      series: [
        {
          name: 'Totales',
          type: 'pie',
          radius: ['40%', '70%'],
          avoidLabelOverlap: false,
          label: {
            show: true,
            position: 'outside',
            alignTo: 'edge',
            formatter: function(params) {
              // Formatea el valor con separador de miles
              return params.name + ': ' + Number(params.value).toLocaleString('es-CL', { maximumFractionDigits: 0 });
            }
          },
          labelLine: {
            show: true
          },
          data: (Array.isArray(props.pieLabels) && Array.isArray(props.pieDatasets) && props.pieDatasets[0]?.data)
            ? props.pieLabels.map((label, i) => ({ name: label, value: props.pieDatasets[0].data[i] }))
            : []
        }
      ]
    });
    chartInstance.resize();
  }
}

onMounted(() => {
  nextTick(() => {
    renderChart();
    window.addEventListener('resize', () => chartInstance && chartInstance.resize());
  });
});

watch(() => [props.pieLabels, props.pieDatasets], () => {
  renderChart();
});
</script>

<template>
  <div ref="chartRef" class="echart-pie-label-align-chart" style="min-height: 320px;" data-echart-responsive="true"></div>
</template>

<style scoped>
.echart-pie-label-align-chart {
  width: 100%;
}
</style>
