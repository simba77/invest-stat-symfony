<script setup lang="ts">
import PageComponent from "../components/PageComponent.vue";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import InputDateComponent from "@/components/Forms/InputDateComponent.vue";
import {reactive, ref} from "vue";
import ClosedDealsByMonthsChart from "@/components/Analytics/ClosedDealsByMonthsChart.vue";
import ClosedDealsTable from "@/components/Analytics/ClosedDealsTable.vue";
import {usePage} from "@/composable/usePage";
import {ClosedDealsListItem, ClosedDealsSummary} from "@/types/analytics";
import axios from "axios";

const {setPageTitle} = usePage()

const closedDeals = ref<{ deals: ClosedDealsListItem[], summary: ClosedDealsSummary }>()
const closedDealsByMonths = ref<{ profitByMonths: number[] }>()

function startOfCurrentMonth(): string {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), 1)

  const day = String(start.getDate()).padStart(2, '0')
  const month = String(start.getMonth() + 1).padStart(2, '0')
  const year = start.getFullYear()

  return `${day}.${month}.${year}`
}

const filter = reactive({
  startDate: startOfCurrentMonth(),
  endDate: '',
})

const {loading, run} = useAsync(
  () => axios.get('/api/analytics/closed-deals', {params: filter})
  .then((response) => {
    closedDeals.value = response.data
  })
)

const {run: closedDealsByMonthsRun} = useAsync(
  () => axios.get('/api/analytics/monthly-closed-deals')
    .then((response) => {
      closedDealsByMonths.value = response.data
    })
)

run()
closedDealsByMonthsRun()

setPageTitle("Closed Deals")

</script>

<template>
  <page-component title="Closed Deals">
    <div v-if="closedDealsByMonths?.profitByMonths">
      <closed-deals-by-months-chart :profit-by-months="closedDealsByMonths?.profitByMonths" />
    </div>

    <div class="mb-6 grid grid-flow-col auto-cols-max gap-4">
      <div>
        <input-date-component v-model="filter.startDate" name="startDate" label="Start Date" @update:model-value="run()" />
      </div>
      <div>
        <input-date-component v-model="filter.endDate" name="endDate" label="End Date" @update:model-value="run()" />
      </div>
    </div>

    <preloader-component v-if="loading" />
    <div v-else-if="closedDeals">
      <closed-deals-table
        :assets="closedDeals.deals"
        :summary="closedDeals.summary"
      />
    </div>
  </page-component>
</template>
