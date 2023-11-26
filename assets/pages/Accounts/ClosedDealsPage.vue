<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import useAnalytics from "@/composable/useAnalytics";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import ClosedDealsTableComponent from "@/components/Analytics/ClosedDealsTableComponent.vue";

const {closedDeals, getClosedDeals} = useAnalytics()

const {loading, run} = useAsync(() => getClosedDeals())

run()
</script>

<template>
  <page-component title="Closed Deals">
    <preloader-component v-if="loading" />
    <div v-else-if="closedDeals">
      <closed-deals-table-component :assets="closedDeals.deals" />
    </div>
  </page-component>
</template>
