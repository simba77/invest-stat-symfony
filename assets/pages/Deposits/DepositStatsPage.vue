<script setup lang="ts">
import PageComponent from '@/components/PageComponent.vue'
import StatCard from '@/components/Cards/StatCard.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import DepositsByMonthsChart from '@/components/Deposits/DepositsByMonthsChart.vue'
import { useDepositStats } from '@/composable/useDepositStats'
import useAsync from '@/utils/use-async'
import { useNumbers } from '@/composable/useNumbers'
import { usePage } from '@/composable/usePage'

const { stats, getStats } = useDepositStats()
const { loading, run } = useAsync(getStats)
const { formatPrice, formatPercent } = useNumbers()
const { setPageTitle } = usePage()

setPageTitle('Deposit Stats')
run()
</script>

<template>
  <page-component title="Deposit Stats">
    <preloader-component v-if="loading" />
    <template v-else-if="stats">
      <!-- Summary cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <stat-card
            name="Текущий баланс"
            :total="stats.summary.balance"
          />
        </div>
        <div class="col-md-4">
          <stat-card
            name="Прибыль за всё время"
            :total="stats.summary.profit"
            :profit="stats.summary.profit"
          />
        </div>
        <div class="col-md-4">
          <stat-card
            name="Доходность в год."
            :total="0"
            :percent="stats.summary.annualizedPercent"
          />
        </div>
      </div>

      <!-- Monthly chart -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">
            Статистика по месяцам
          </h5>
          <deposits-by-months-chart
            v-if="stats.monthlyStats.length"
            :monthly-stats="stats.monthlyStats"
          />
          <div v-else class="text-muted">
            Нет данных
          </div>
        </div>
      </div>

      <!-- Accounts table -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">
            По счетам
          </h5>
          <table class="simple-table">
            <thead>
              <tr>
                <th>Счёт</th>
                <th class="text-end">Вложено</th>
                <th class="text-end">Прибыль</th>
                <th class="text-end">Баланс</th>
                <th class="text-end">% итог</th>
                <th class="text-end">% годовых</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="account in stats.accounts"
                :key="account.id"
              >
                <td>
                  {{ account.name }}
                  <span v-if="Number(account.balance) === 0" class="text-muted small ms-1">(закрыт)</span>
                </td>
                <td class="text-end">
                  {{ formatPrice(Number(account.grossInvested)) }}
                </td>
                <td
                  class="text-end"
                  :class="Number(account.profit) > 0 ? 'text-success' : ''"
                >
                  {{ formatPrice(Number(account.profit)) }}
                </td>
                <td class="text-end text-muted">
                  {{ formatPrice(Number(account.balance)) }}
                </td>
                <td
                  class="text-end"
                  :class="Number(account.profitPercent) > 0 ? 'text-success' : ''"
                >
                  {{ formatPercent(Number(account.profitPercent)) }}
                </td>
                <td
                  class="text-end"
                  :class="Number(account.annualizedPercent) > 0 ? 'text-success' : ''"
                >
                  {{ formatPercent(Number(account.annualizedPercent)) }}
                </td>
              </tr>
              <tr v-if="stats.accounts.length === 0">
                <td colspan="6" class="text-center">
                  Нет данных
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div class="mt-4">
      <router-link :to="{ name: 'Deposits' }" class="btn btn-secondary">
        Назад
      </router-link>
    </div>
  </page-component>
</template>
