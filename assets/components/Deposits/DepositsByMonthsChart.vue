<script setup lang="ts">
import { onMounted, ref } from 'vue'
import * as echarts from 'echarts/core'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import { BarChart } from 'echarts/charts'
import { CanvasRenderer } from 'echarts/renderers'
import type { MonthlyStatItem } from '@/composable/useDepositStats'

const props = defineProps<{ monthlyStats: MonthlyStatItem[] }>()
const chartRef = ref()

echarts.use([TooltipComponent, GridComponent, LegendComponent, BarChart, CanvasRenderer])

function formatRub(value: number): string {
  return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
    .format(value)
    .replace(',', '.') + ' ₽'
}

function formatRubShort(value: number): string {
  return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
    .format(value) + ' ₽'
}

onMounted(() => {
  const myChart = echarts.init(chartRef.value)

  const months = props.monthlyStats.map((r) => r.month)
  const deposits = props.monthlyStats.map((r) => Number(r.deposits))
  const profit = props.monthlyStats.map((r) => Number(r.profit))

  const option = {
    color: ['#4f46e5', '#22c55e'],
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      formatter: (params: { marker: string; seriesName: string; value: number; axisValue: string }[]) => {
        let result = params[0].axisValue + '<br/>'
        params.forEach((p) => {
          result += `${p.marker}${p.seriesName}: <b>${formatRub(p.value)}</b><br/>`
        })
        return result
      },
    },
    legend: {
      data: ['Пополнения', 'Прибыль'],
    },
    xAxis: {
      type: 'category',
      data: months,
      axisTick: { alignWithLabel: true },
      axisLabel: { rotate: 50 },
    },
    yAxis: { type: 'value', axisLabel: { formatter: (v: number) => formatRubShort(v) } },
    grid: {
      left: '2%',
      right: '2%',
      bottom: '2%',
      top: '10%',
      containLabel: true,
    },
    series: [
      { name: 'Пополнения', data: deposits, type: 'bar' },
      { name: 'Прибыль', data: profit, type: 'bar' },
    ],
  }

  myChart.setOption(option)
})
</script>

<template>
  <div>
    <div ref="chartRef" style="width: 100%; height: 500px;" />
  </div>
</template>
