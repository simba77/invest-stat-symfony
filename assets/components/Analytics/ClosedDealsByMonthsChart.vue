<script setup lang="ts">
import { onMounted, ref } from "vue";
import * as echarts from 'echarts/core';
import { GridComponent, TooltipComponent } from 'echarts/components';
import { BarChart } from 'echarts/charts';
import { CanvasRenderer } from 'echarts/renderers';

const props = defineProps<{profitByMonths: object}>()
const chartRef = ref()

echarts.use([TooltipComponent, GridComponent, BarChart, CanvasRenderer])

onMounted(() => {
  const myChart = echarts.init(chartRef.value)
  const option = {
    color: [
      '#ff4d73'
    ],
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    xAxis: {
      type: 'category',
      data: Object.keys(props.profitByMonths),
      axisTick: {
        alignWithLabel: true
      },
      axisLabel: {
        rotate: 60
      }
    },
    yAxis: {
      type: 'value',
    },
    grid: {
      left: '2%',
      right: '2%',
      bottom: '2%',
      containLabel: true
    },
    series: [
      {
        data: Object.values(props.profitByMonths),
        type: 'bar'
      }
    ]
  }

  myChart.setOption(option)
})

</script>

<template>
  <div>
    <div class="mb-7">
      <div ref="chartRef" style="width: 100%; height: 700px;" />
    </div>
  </div>
</template>

<style scoped lang="scss">

</style>
