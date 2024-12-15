<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import {XCircleIcon, PencilIcon} from "@heroicons/vue/24/outline";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useModal} from "@/composable/useModal";
import {useNumbers} from "@/composable/useNumbers";
import {ref} from "vue";
import useAsync from "@/utils/use-async";
import {useDividends} from "@/composable/useDividends";
import {Dividend, DividendsPage} from "@/types/dividends";
import ConfirmDeleteDividendModal from "@/components/Dividends/ConfirmDeleteDividendModal.vue";
import {usePage} from "@/composable/usePage";

const modal = useModal()
const {formatPrice} = useNumbers()

const {getDividends} = useDividends()
const {setPageTitle} = usePage()

const dividends = ref<DividendsPage>({items: []})
const {loading, run: getItems} = useAsync(() => getDividends().then((response) => {
  dividends.value = response.data
}))

getItems()

function confirmDelete(item: Dividend) {
  modal.open({
    component: ConfirmDeleteDividendModal,
    modelValue: {
      item,
      callback: () => getItems()
    },
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
          <th>Ticker</th>
          <th>Stock Market</th>
          <th>Account</th>
          <th class="flex justify-end">
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
          <td>{{ dividend.ticker }}</td>
          <td>{{ dividend.stockMarket }}</td>
          <td>{{ dividend.accountName }}</td>
          <td class="table-actions">
            <template v-if="dividend.id">
              <div class="flex justify-end items-center show-on-row-hover">
                <router-link
                  class="text-gray-300 hover:text-gray-900 mr-3"
                  :to="{name: 'DividendEdit', params: {id: dividend.id}}"
                >
                  <pencil-icon class="h-5 w-5" />
                </router-link>
                <button
                  type="button"
                  class="text-gray-300 hover:text-red-500"
                  @click="confirmDelete(dividend)"
                >
                  <x-circle-icon class="h-5 w-5" />
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
  </page-component>
</template>
