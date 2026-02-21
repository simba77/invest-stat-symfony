<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import { Pen, CircleX } from 'lucide-vue-next';
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {useModal} from "@/composable/useModal";
import {usePage} from "@/composable/usePage";
import useAsync from "@/utils/use-async";
import axios from "axios";
import {ref} from "vue";
import {FutureMultiplier} from "@/types/futures";
import ConfirmDeleteFutureMultiplierModal from "@/components/Instruments/ConfirmDeleteFutureMultiplierModal.vue";

const modal = useModal()
const {setPageTitle} = usePage()


const multipliers = ref<FutureMultiplier[]>([])

const {run, loading} = useAsync(() => axios.get('/api/futures/multipliers')
  .then((response) => {
    multipliers.value = response.data
  })
)

run()

function confirmDelete(item: FutureMultiplier) {
  modal.open({
    component: ConfirmDeleteFutureMultiplierModal,
    modelValue: {
      item,
      onSuccess: () => {
        run()
      }
    }
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
    <preloader-component v-if="loading" />
    <table
      v-else
      class="simple-table"
    >
      <thead>
        <tr>
          <th>Ticker</th>
          <th>Value</th>
          <th class="text-end">
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
              <div class="justify-content-end align-items-center show-on-row-hover">
                <router-link
                  class="btn btn-link p-0 me-2 border-0"
                  :to="{name: 'FuturesMultipliersEdit', params: {id: item.id}}"
                >
                  <pen :size="20" />
                </router-link>
                <button
                  type="button"
                  class="btn btn-link btn-link-danger"
                  @click="confirmDelete(item)"
                >
                  <circle-x :size="20" />
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
