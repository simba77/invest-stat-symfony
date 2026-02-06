<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import StatCard from "@/components/Cards/StatCard.vue";
import useAsync from "@/utils/use-async";
import {ref} from "vue";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useNumbers} from "@/composable/useNumbers";
import {useDashboard} from "@/composable/useDashboard";
import {Dashboard} from "@/types/dashboard";
import {usePage} from "@/composable/usePage";

const {formatPrice, formatPercent} = useNumbers()
const {getDashboard} = useDashboard()
const {setPageTitle} = usePage()

const pageData = ref<Dashboard>({
  usd: 0,
  summary: [],
  depositAccounts: [],
  statisticByYears: []
})

const {loading, run} = useAsync(() => getDashboard().then((response) => {
  pageData.value = response.data
}))

run()

setPageTitle('')

</script>

<template>
  <page-component title="Dashboard">
    <preloader-component v-if="loading" />
    <template v-else>
      <div class="flex justify-between">
        <div class="text-xl mb-3">
          Investment Result
        </div>
        <div class="text-xl mb-3">
          1 USD = {{ pageData.usd }}₽
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
        <stat-card
          v-for="(card, i) in pageData.summary"
          :key="i"
          :name="card.name"
          :help-text="card.helpText ?? null"
          :percent="card.percent ?? null"
          :profit="card.dailyChange ?? null"
          :profit-help-text="card.dailyChange ? 'Daily Profit' : null"
          :total="card.total"
        />
      </div>

      <template v-if="Object.keys(pageData.depositAccounts).length > 0">
        <div class="text-2xl font-extrabold mt-6 mb-3">
          Deposit Accounts
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
          <stat-card
            v-for="(card, i) in pageData.depositAccounts"
            :key="i"
            :name="card.name"
            :total="card.total"
            :profit="card.profit"
          />
        </div>
      </template>

      <template v-if="pageData.statisticByYears">
        <div class="text-2xl font-extrabold mt-6 mb-7">
          Profit By Years
        </div>
        <table class="table table-dark">
          <thead>
            <tr>
              <th>Year</th>
              <th>Start Year Profit (1 Jan)</th>
              <th class="text-end">Percent</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in pageData.statisticByYears"
              :key="row.year"
            >
              <td>{{ row.year }}</td>
              <td>{{ formatPrice(row.profit) }}</td>
              <td class="text-end">{{ formatPercent(row.profitPercent) }}</td>
            </tr>
          </tbody>
        </table>
      </template>
    </template>
  </page-component>
</template>

