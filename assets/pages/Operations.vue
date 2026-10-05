<script setup lang="ts">
import {computed, ref, watch} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import PageComponent from '@/components/PageComponent.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import PaginationComponent from '@/components/Common/PaginationComponent.vue'
import useAccounts from '@/composable/useAccounts'
import {useNumbers} from '@/composable/useNumbers'
import {useOperationsHistory} from '@/composable/useOperationsHistory'
import useAsync from '@/utils/use-async'
import type {OperationCategory, OperationHistoryFilter, OperationHistoryItem} from '@/types/history'
import type {PaginatedResponse} from '@/types/pagination'

const route = useRoute()
const router = useRouter()
const {accounts, getAccounts} = useAccounts()
const {getHistory} = useOperationsHistory()
const {formatPrice} = useNumbers()

const categories: {code: OperationCategory, name: string}[] = [
  {code: 'trades', name: 'Trades'},
  {code: 'payouts', name: 'Payouts'},
  {code: 'money', name: 'Deposits and cash'},
  {code: 'fees', name: 'Fees and taxes'},
  {code: 'blocks', name: 'Blocks'},
  {code: 'other', name: 'Other'},
]

const history = ref<PaginatedResponse<OperationHistoryItem> | null>(null)

// The page and the filters live in the address, so that a view can be reloaded and shared
const filter = computed<OperationHistoryFilter>(() => ({
  accountId: Number(route.query.accountId) > 0 ? Number(route.query.accountId) : null,
  category: categories.some((item) => item.code === route.query.category) ? route.query.category as OperationCategory : null,
}))
const page = computed(() => Math.max(1, Math.floor(Number(route.query.page ?? 1)) || 1))

const {loading, run: load} = useAsync(async () => {
  history.value = await getHistory(filter.value, page.value, 50)
})

function navigate(changes: {accountId?: number | null, category?: OperationCategory | null, page?: number}) {
  const next = {...filter.value, page: 1, ...changes}
  const query: Record<string, string> = {}
  if (next.accountId) {
    query.accountId = String(next.accountId)
  }
  if (next.category) {
    query.category = next.category
  }
  if (next.page > 1) {
    query.page = String(next.page)
  }
  router.push({name: 'Operations', query})
}

watch(() => route.query, () => load(), {immediate: true})

getAccounts()

const currencySymbols: Record<string, string> = {RUB: '₽', USD: '$', EUR: '€', CNY: '¥'}

function currency(code: string | null): string {
  return code ? (currencySymbols[code] ?? code) : ''
}

function price(item: OperationHistoryItem): string {
  if (item.price === null) {
    return ''
  }
  if (item.priceUnit === 'percent') {
    return Number(item.price) + '%'
  }
  if (item.priceUnit === 'points') {
    return formatPrice(Number(item.price), 'pt')
  }

  return formatPrice(Number(item.price), currency(item.currency))
}

function date(value: string): string {
  return new Date(value).toLocaleString('ru-RU', {dateStyle: 'short', timeStyle: 'short'})
}
</script>

<template>
  <page-component title="Operations">
    <p class="small text-muted">
      The operations of all the accounts, the latest first: trades, blocks and cash of the manual
      accounts with their deposits and payouts, and what the broker reported for the synced ones.
    </p>

    <div class="row g-3 align-items-end mb-4">
      <div class="col-12 col-md-4">
        <label
          for="history-account"
          class="form-label"
        >Account</label>
        <select
          id="history-account"
          class="form-select mt-1"
          :value="filter.accountId ?? ''"
          @change="navigate({accountId: Number(($event.target as HTMLSelectElement).value) || null})"
        >
          <option value="">
            All accounts
          </option>
          <option
            v-for="account in accounts"
            :key="account.id"
            :value="account.id"
          >
            {{ account.name }}{{ account.isClosed ? ' (closed)' : '' }}
          </option>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label
          for="history-category"
          class="form-label"
        >Operations</label>
        <select
          id="history-category"
          class="form-select mt-1"
          :value="filter.category ?? ''"
          @change="navigate({category: (($event.target as HTMLSelectElement).value || null) as OperationCategory | null})"
        >
          <option value="">
            All operations
          </option>
          <option
            v-for="category in categories"
            :key="category.code"
            :value="category.code"
          >
            {{ category.name }}
          </option>
        </select>
      </div>
    </div>

    <preloader-component v-if="loading && !history" />
    <template v-if="history">
      <div
        v-if="history.items.length === 0"
        class="text-muted small"
      >
        No operations
      </div>
      <div
        v-else
        class="table-responsive mb-3"
      >
        <table class="table simple-table table-dark table-hover align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Account</th>
              <th>Operation</th>
              <th>Security</th>
              <th class="text-end">
                Quantity
              </th>
              <th class="text-end">
                Price
              </th>
              <th class="text-end">
                Commission
              </th>
              <th class="text-end">
                Amount
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in history.items"
              :key="item.key"
            >
              <td class="text-nowrap">
                {{ date(item.executedAt) }}
              </td>
              <td>
                <router-link :to="{name: 'AccountDetail', params: {id: item.accountId}}">
                  {{ item.accountName }}
                </router-link>
              </td>
              <td>{{ item.title }}</td>
              <td>
                <template v-if="item.ticker">
                  <div>{{ item.name ?? item.ticker }}</div>
                  <div class="small text-muted">
                    {{ item.ticker }}
                  </div>
                </template>
              </td>
              <td class="text-end">
                {{ item.quantity ?? '' }}
              </td>
              <td class="text-end text-nowrap">
                {{ price(item) }}
              </td>
              <td class="text-end text-nowrap">
                <template v-if="item.commission !== null">
                  {{ formatPrice(Number(item.commission), currency(item.currency)) }}
                </template>
              </td>
              <td
                class="text-end text-nowrap"
                :class="{'text-success': item.category !== 'blocks' && Number(item.amount) > 0}"
              >
                <template v-if="item.amount !== null">
                  {{ formatPrice(Number(item.amount), currency(item.currency)) }}
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination-component
        :page="history.pagination.page"
        :total-pages="history.pagination.totalPages"
        :disabled="loading"
        @change="(value: number) => navigate({page: value})"
      />
    </template>
  </page-component>
</template>
