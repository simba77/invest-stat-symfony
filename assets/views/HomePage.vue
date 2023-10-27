<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue";
import StatCard from "@/components/Cards/StatCard.vue";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {reactive} from "vue";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";

interface SummaryCard {
  name: string
  helpText: string
  percent: number
  total: number
}

interface DepositAccountCard {
  name: string
  profit: number
  total: number
}

interface Dashboard {
  data: {
    usd: number
    summary: SummaryCard[]
    depositAccounts: DepositAccountCard[]
  }
}

const pageData = reactive<Dashboard>({
  data: {
    usd: 0,
    summary: [],
    depositAccounts: []
  }
})

const {loading, run} = useAsync(async () => {
  await axios.get('/api/dashboard')
    .then((response) => {
      pageData.data = response.data;
    })
})

run()

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
          1 USD = {{ pageData.data.usd }}₽
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
        <stat-card
          v-for="(card, i) in pageData.data.summary"
          :key="i"
          :name="card.name"
          :help-text="card.helpText ?? null"
          :percent="card.percent ?? null"
          :total="card.total"
        />
      </div>

      <template v-if="pageData.data.depositAccounts?.length > 0">
        <div class="text-2xl font-extrabold mt-6 mb-3">
          Deposit Accounts
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
          <stat-card
            v-for="(card, i) in pageData.data.depositAccounts"
            :key="i"
            :name="card.name"
            :total="card.total"
            :profit="card.profit"
          />
        </div>
      </template>
    </template>
  </page-component>
</template>

