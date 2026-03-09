<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import { Pencil, XCircle } from "lucide-vue-next";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import PaginationComponent from "@/components/Common/PaginationComponent.vue";
import {useModal} from "@/composable/useModal";
import {useNumbers} from "@/composable/useNumbers";
import {computed, watch} from "vue";
import useAsync from "@/utils/use-async";
import {useDividends} from "@/composable/useDividends";
import {Dividend} from "@/types/dividends";
import ConfirmDeleteDividendModal from "@/components/Dividends/ConfirmDeleteDividendModal.vue";
import {usePage} from "@/composable/usePage";
import {useRoute, useRouter} from "vue-router";

const modal = useModal()
const {formatPrice} = useNumbers()

const {dividends, getDividends} = useDividends()
const {setPageTitle} = usePage()
const route = useRoute()
const router = useRouter()

const {loading, run: getItems} = useAsync(getDividends)

const routePage = computed(() => {
  const value = Number(route.query.page ?? 1)

  if (!Number.isFinite(value) || value < 1) {
    return 1
  }

  return Math.floor(value)
})

watch(routePage, (value) => {
  getItems(value).then(() => {
    const actualPage = dividends.value?.pagination?.page

    if (actualPage && actualPage !== value) {
      router.replace({
        name: 'Dividends',
        query: actualPage > 1 ? {page: String(actualPage)} : {}
      })
    }
  })
}, {immediate: true})

function confirmDelete(item: Dividend) {
  modal.open({
    component: ConfirmDeleteDividendModal,
    modelValue: {
      item,
      callback: () => getItems(routePage.value)
    },
  })
}

function changePage(page: number) {
  router.push({
    name: 'Dividends',
    query: page > 1 ? {page: String(page)} : {}
  })
}

setPageTitle("Dividends")
</script>

<template>
  <page-component title="Dividends">
    <div class="mb-4">
      <router-link
        :to="{name: 'DividendCreate'}"
        class="btn btn-primary"
      >
        Add
      </router-link>
    </div>
    <preloader-component v-if="loading" />
    <table
      v-else
      class="simple-table"
    >
      <thead>
        <tr>
          <th>Date</th>
          <th>Sum</th>
          <th>Tax</th>
          <th>Ticker</th>
          <th>Stock Market</th>
          <th>Account</th>
          <th class="text-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(dividend, index) in dividends.items"
          :key="index"
        >
          <td>{{ dividend.date }}</td>
          <td>{{ formatPrice(dividend.amount) }}</td>
          <td>{{ formatPrice(dividend.tax) }}</td>
          <td>{{ dividend.ticker }}</td>
          <td>{{ dividend.stockMarket }}</td>
          <td>{{ dividend.accountName }}</td>
          <td class="table-actions">
            <template v-if="dividend.id">
              <div class="justify-content-end align-items-center show-on-row-hover">
                <router-link
                  :to="{name: 'DividendEdit', params: {id: dividend.id}}"
                  class="text-muted hover-opacity me-2"
                  title="Edit"
                >
                  <pencil :size="20" />
                </router-link>

                <button
                  type="button"
                  class="btn btn-link p-0 btn-link-danger"
                  title="Delete"
                  @click="confirmDelete(dividend)"
                >
                  <x-circle :size="20" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="dividends.items.length < 1">
          <td
            colspan="20"
            class="text-center"
          >
            The list is empty
          </td>
        </tr>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
      <div class="text-muted small">
        Total: {{ dividends.pagination?.totalItems ?? 0 }}
      </div>
      <pagination-component
        :page="dividends.pagination?.page ?? 1"
        :total-pages="dividends.pagination?.totalPages ?? 1"
        :disabled="loading"
        @change="changePage"
      />
    </div>
  </page-component>
</template>
