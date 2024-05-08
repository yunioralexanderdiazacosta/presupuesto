<template>
<PieChart v-bind="pieChartProps" />
</template>
<script>
import { Chart, registerables } from 'chart.js';
import { PieChart, usePieChart } from 'vue-chart-3';
import { ref, computed, defineComponent } from 'vue';
import { shuffle } from 'lodash';
import ChartDataLabels from 'chartjs-plugin-datalabels';

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

        var options = {
            plugins: {
                datalabels: {
                    anchor: 'center',
                    formatter: (value, ctx) => {
                        let sum = 0;
                        let dataArr = ctx.chart.data.datasets[0].data;
                        dataArr.map(data => {
                            sum += Number(data);
                        });
                        let percentage = (value*100 / sum).toFixed(1)+"%";
                        return percentage;
                    },
                    color: '#fff',

                    labels: {
                        title: {
                            font: {
                                weight: 'bold',
                                size: 14
                            }
                        },
                    }
                }
            }
        };

		const { pieChartProps, pieChartRef } = usePieChart({
			chartData,
            plugins: [ChartDataLabels],
            options: options
		});

		return {pieChartProps, pieChartRef };
  	},
});

</script>