<script setup lang="ts">
import { Chart } from 'chart.js/auto'
import { onMounted, ref } from "vue";

const props = defineProps<{profitByMonths: number[]}>()
const chartRef = ref()

onMounted(() => {
    new Chart(
      chartRef.value,
      {
        type: 'bar',
        data: {
          labels: Object.keys(props.profitByMonths),
          datasets: [
            {
              label: 'Profit by Months',
              data: props.profitByMonths,
              backgroundColor: '#ff4d73'
            }
          ]
        }
      }
    );
})

</script>

<template>
  <div>
    <div class="mb-7">
      <canvas ref="chartRef" />
    </div>
  </div>
</template>

<style scoped lang="scss">

</style>
