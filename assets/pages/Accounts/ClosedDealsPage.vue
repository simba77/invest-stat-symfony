<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import useAnalytics from "@/composable/useAnalytics";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import ClosedDealsTableComponent from "@/components/Analytics/ClosedDealsTableComponent.vue";
import InputDateComponent from "@/components/Forms/InputDateComponent.vue";
import { reactive } from "vue";
import ClosedDealsByMonthsChart from "@/components/Analytics/ClosedDealsByMonthsChart.vue";

const {closedDeals, closedDealsByMonths, getClosedDeals, getClosedDealsByMonths} = useAnalytics()

const filter = reactive({
  startDate: '',
  endDate: '',
})

const {loading, run} = useAsync(() => getClosedDeals(filter))
const {run: closedDealsByMonthsRun} = useAsync(() => getClosedDealsByMonths({}))

run()

closedDealsByMonthsRun()

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
      <closed-deals-table-component
        :assets="closedDeals.deals"
        :summary="closedDeals.summary"
      />
    </div>
  </page-component>
</template>
