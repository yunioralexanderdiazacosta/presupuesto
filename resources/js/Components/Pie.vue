<template>
<PieChart v-bind="pieChartProps" />
</template>
<script>
import { Chart, registerables } from 'chart.js';
import { PieChart, usePieChart } from 'vue-chart-3';
import { ref, computed, defineComponent } from 'vue';
import { shuffle } from 'lodash';

Chart.register(...registerables);

export default defineComponent({
    props: {
        pieLabels: Array,
        pieDatasets: Array
    },
  	components: {
		PieChart,
  	},
  	setup(props) {
		const chartData = computed(() => ({
		    labels: props.pieLabels,
            datasets: props.pieDatasets
	    }));

		const { pieChartProps, pieChartRef } = usePieChart({
			chartData,



			 options: {
    layout: {
      padding: {
        bottom: 25
      }
    },
    plugins: {
      tooltip: {
        enabled: true,
        callbacks: {
          footer: (ttItem) => {
            let sum = 0;
            let dataArr = ttItem[0].dataset.data;
            dataArr.map(data => {
              sum += Number(data);
            });

            let percentage = (ttItem[0].parsed * 100 / sum).toFixed(2) + '%';
            return `Porcentaje: ${percentage}`;
          }
        }
      },
      /** Imported from a question linked above. 
          Apparently Works for ChartJS V2 **/
      datalabels: {
        formatter: (value, dnct1) => {
          let sum = 0;
          let dataArr = dnct1.chart.data.datasets[0].data;
          dataArr.map(data => {
            sum += Number(data);
          });

          let percentage = (value * 100 / sum).toFixed(2) + '%';
          return percentage;
        },
        color: '#000',
      }
    }
			 }
          
		});

		return {pieChartProps, pieChartRef };
  	},
});

</script>