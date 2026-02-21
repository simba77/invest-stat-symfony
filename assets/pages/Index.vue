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
      <div class="d-flex justify-content-between">
        <div class="fz-xl fw-extrabold mb-3">
          Investment Result
        </div>
        <div class="fz-xl mb-3">
          1 USD = {{ pageData.usd }}₽
        </div>
      </div>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 cards-row">
        <div
          v-for="(card, i) in pageData.summary"
          :key="i"
          class="col"
        >
          <stat-card
            class="h-100"
            :name="card.name"
            :help-text="card.helpText ?? null"
            :percent="card.percent ?? null"
            :profit="card.dailyChange ?? null"
            :profit-help-text="card.dailyChange ? 'Daily Profit' : null"
            :total="card.total"
          />
        </div>
      </div>

      <template v-if="Object.keys(pageData.depositAccounts).length > 0">
        <div class="fz-xl fw-extrabold mt-4 mb-3">
          Deposit Accounts
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 cards-row mb-3">
          <div
            v-for="(card, i) in pageData.depositAccounts"
            :key="i"
            class="col"
          >
            <stat-card
              class="h-100"
              :name="card.name"
              :total="card.total"
              :profit="card.profit"
            />
          </div>
        </div>
      </template>

      <template v-if="pageData.statisticByYears">
        <div class="fz-xl fw-extrabold mt-4 mb-4">
          Profit By Years
        </div>
        <table class="table table-dark simple-table">
          <thead>
            <tr>
              <th>Year</th>
              <th>Start Year Profit (1 Jan)</th>
              <th class="text-end">
                Percent
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in pageData.statisticByYears"
              :key="row.year"
            >
              <td>{{ row.year }}</td>
              <td>{{ formatPrice(row.profit) }}</td>
              <td class="text-end">
                {{ formatPercent(row.profitPercent) }}
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </template>
  </page-component>
</template>
