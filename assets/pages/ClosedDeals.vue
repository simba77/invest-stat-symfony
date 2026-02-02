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
import Button from 'primevue/button';

const {setPageTitle} = usePage()

const closedDeals = ref<{ deals: ClosedDealsListItem[], summary: ClosedDealsSummary }>()
const closedDealsByMonths = ref<{ profitByMonths: number[] }>()

function formatDate(date: Date): string {
  const d = String(date.getDate()).padStart(2, '0')
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const y = date.getFullYear()
  return `${d}.${m}.${y}`
}

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

function endOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0)
}

const currentMonth = ref(startOfMonth(new Date()))

const filter = reactive({
  startDate: formatDate(startOfMonth(currentMonth.value)),
  endDate: formatDate(endOfMonth(currentMonth.value)),
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


function applyMonth(date: Date) {
  filter.startDate = formatDate(startOfMonth(date))
  filter.endDate = formatDate(endOfMonth(date))
  run()
}

function prevMonth() {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() - 1,
    1
  )
  applyMonth(currentMonth.value)
}

function nextMonth() {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() + 1,
    1
  )
  applyMonth(currentMonth.value)
}

</script>

<template>
  <page-component title="Closed Deals">
    <div v-if="closedDealsByMonths?.profitByMonths">
      <closed-deals-by-months-chart :profit-by-months="closedDealsByMonths?.profitByMonths" />
    </div>

    <div class="mb-6 grid gap-4 items-end grid-flow-row sm:grid-flow-row md:grid-flow-col md:auto-cols-max">
      <!-- Start Date -->
      <div class="w-full md:w-auto">
        <input-date-component
          v-model="filter.startDate"
          name="startDate"
          label="Start Date"
          @update:model-value="run()"
        />
      </div>

      <!-- End Date -->
      <div class="w-full md:w-auto">
        <input-date-component
          v-model="filter.endDate"
          name="endDate"
          label="End Date"
          @update:model-value="run()"
        />
      </div>

      <!-- Month navigation -->
      <div class="flex items-center gap-2 w-full md:w-auto justify-start md:justify-center">
        <Button
          label="←"
          raised
          rounded
          variant="outlined"
          @click="prevMonth"
        />
        <div class="font-medium">
          {{ currentMonth.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' }) }}
        </div>
        <Button
          label="→"
          raised
          rounded
          variant="outlined"
          @click="nextMonth"
        />
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
