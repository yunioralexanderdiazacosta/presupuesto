<template>
  <div :class="containerClass" :style="containerStyle" ref="chartRef"></div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import * as echarts from 'echarts';

const props = defineProps({
  barLabels: {
    type: Array,
    required: true
  },
  barData: {
    type: Array,
    required: true
  },
  height: {
    type: [String, Number],
    default: 320
  },
  containerClass: {
    type: String,
    default: 'echart-basic-bar-chart-example'
  },
  containerStyle: {
    type: [String, Object],
    default: () => ({ minHeight: '300px' })
  },
  color: {
    type: String,
    default: '#2c7be5'
  }
});

const chartRef = ref(null);
let chartInstance = null;

const formatNumber = (val) => {
  // Si es un objeto tipo {value: ...} (ECharts a veces lo hace), extraer el valor
  if (val && typeof val === 'object' && 'value' in val) {
    val = val.value;
  }
  // Si no es número, intentar convertir
  const num = Number(val);
  if (isNaN(num)) return '';
  return num.toLocaleString('es-CL', { maximumFractionDigits: 0 });
};

const setChart = () => {
  if (!chartRef.value) return;
  if (!chartInstance) {
    chartInstance = echarts.init(chartRef.value);
  }
  const option = {
    color: [props.color],
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#212529',
      borderColor: '#212529',
      textStyle: { color: '#fff' },
      formatter: function(params) {
        const p = params[0];
        return `<strong>${p.name}</strong><br/>${formatNumber(p.value)}`;
      }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: 30,
      containLabel: true
    },
    xAxis: {
      type: 'category',
      data: props.barLabels,
      axisTick: { alignWithLabel: true },
      axisLine: { lineStyle: { color: '#e3e6ed' } },
      axisLabel: { color: '#6c757d', fontWeight: 'bold' }
    },
    yAxis: {
      type: 'value',
      axisLine: { show: false },
      splitLine: { lineStyle: { color: '#f1f3f4' } },
      axisLabel: {
        color: '#6c757d',
        fontWeight: 'bold',
        formatter: formatNumber
      }
    },
    series: [
      {
        name: 'Total',
        type: 'bar',
        barWidth: '50%',
        data: props.barData,
        itemStyle: {
          borderRadius: [12, 12, 0, 0],
          shadowColor: 'rgba(44,123,229,0.15)',
          shadowBlur: 8
        },
        label: {
          show: true,
          position: 'top',
          color: '#2c7be5',
          fontWeight: 'bold',
          formatter: formatNumber
        }
      }
    ]
  };
  chartInstance.setOption(option);
  chartInstance.resize();
};

onMounted(() => {
  setChart();
  window.addEventListener('resize', setChart);
});

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.dispose();
    chartInstance = null;
  }
  window.removeEventListener('resize', setChart);
});

watch(() => [props.barLabels, props.barData], setChart, { deep: true });
</script>

<style scoped>
.echart-basic-bar-chart-example {
  width: 100%;
  min-height: 300px;
}
</style>
