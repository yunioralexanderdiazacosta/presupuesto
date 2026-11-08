<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { echartsColors, onThemeChange } from '@/themeColors';

const props = defineProps({
  pieLabels: Array,
  pieDatasets: Array,
  showPercentage: {
    type: Boolean,
    default: false
  }
});

const chartRef = ref(null);
let chartInstance = null;
let offThemeChange = null;

function renderChart() {
  if (chartRef.value && window.echarts) {
    if (!chartInstance) {
      chartInstance = window.echarts.init(chartRef.value);
    }
    const theme = echartsColors();
    chartInstance.setOption({
      tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)',
        backgroundColor: theme.tooltipBg,
        borderColor: theme.tooltipBorder,
        textStyle: { color: theme.tooltipText }
      },
      legend: {
        orient: 'vertical',
        left: 10,
        textStyle: { color: theme.text }
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
            color: theme.text,
            formatter: function(params) {
              // Si showPercentage es true, muestra porcentaje, sino muestra monto
              if (props.showPercentage) {
                return params.name + ': ' + params.percent.toFixed(1) + '%';
              } else {
                return params.name + ': ' + Number(params.value).toLocaleString('es-CL', { maximumFractionDigits: 0 });
              }
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
  offThemeChange = onThemeChange(renderChart);
});

onBeforeUnmount(() => {
  if (offThemeChange) offThemeChange();
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
