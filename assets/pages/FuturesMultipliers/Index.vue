<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import {XCircleIcon, PencilIcon} from "@heroicons/vue/24/outline";
import {useInvestments} from "@/composable/useInvestments";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useModal} from "@/composable/useModal";
import ConfirmDeleteInvestmentModal from "@/components/Investments/ConfirmDeleteInvestmentModal.vue";
import {useNumbers} from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";
import useAsync from "@/utils/use-async";
import axios from "axios";
import {ref} from "vue";

const modal = useModal()
const {setPageTitle} = usePage()

interface FutureMultiplier {
  id: number
  ticker: string
  value: number
}

const multipliers = ref<FutureMultiplier[]>([])

const {run, loading} = useAsync(() => axios.get('/api/futures/multipliers')
  .then((response) => {
    multipliers.value = response.data
  })
)

run()

function confirmDelete(item: FutureMultiplier) {
  modal.open({
    component: ConfirmDeleteInvestmentModal,
    modelValue: item
  })
}

setPageTitle("Futures Multipliers")
</script>

<template>
  <page-component title="Futures Multipliers">
    <div class="mb-4">
      <router-link
        :to="{name: 'FuturesMultipliersCreate'}"
        class="btn btn-primary"
      >
        Add
      </router-link>
    </div>
    <preloader-component v-if="loading"/>
    <table
      v-else
      class="simple-table"
    >
      <thead>
      <tr>
        <th>Ticker</th>
        <th>Value</th>
        <th class="flex justify-end">
          Actions
        </th>
      </tr>
      </thead>
      <tbody>
      <tr
        v-for="(item, index) in multipliers"
        :key="index"
      >
        <td>{{ item.ticker }}</td>
        <td>{{ item.value }}</td>
        <td class="table-actions">
          <template v-if="item.id">
            <div class="flex justify-end items-center show-on-row-hover">
              <router-link
                class="text-gray-300 hover:text-gray-900 mr-3"
                :to="{name: 'FuturesMultipliersEdit', params: {id: item.id}}"
              >
                <pencil-icon class="h-5 w-5"/>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="confirmDelete(item)"
              >
                <x-circle-icon class="h-5 w-5"/>
              </button>
            </div>
          </template>
        </td>
      </tr>
      <tr v-if="multipliers.length < 1">
        <td
          colspan="4"
          class="text-center"
        >
          The list is empty
        </td>
      </tr>
      </tbody>
    </table>
  </page-component>
</template>
