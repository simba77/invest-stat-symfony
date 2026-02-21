<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import { Pen, CircleX } from 'lucide-vue-next';
import {useInvestments} from "@/composable/useInvestments";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {Investment} from "@/types/investments";
import {useModal} from "@/composable/useModal";
import ConfirmDeleteInvestmentModal from "@/components/Investments/ConfirmDeleteInvestmentModal.vue";
import { useNumbers } from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";

const {investments, getInvestments, loadingInvestments} = useInvestments()
const modal = useModal()
const {formatPrice} = useNumbers()
const {setPageTitle} = usePage()

getInvestments()

function confirmDelete(item: Investment) {
  modal.open({
    component: ConfirmDeleteInvestmentModal,
    modelValue: item
  })
}

setPageTitle("Investments")
</script>

<template>
  <page-component title="Investments">
    <div class="mb-4">
      <router-link
        :to="{name: 'AddDeposit'}"
        class="btn btn-primary"
      >
        Add
      </router-link>
    </div>
    <preloader-component v-if="loadingInvestments" />
    <table
      v-else
      class="simple-table"
    >
      <thead>
        <tr>
          <th>Date</th>
          <th>Sum</th>
          <th>Account</th>
          <th class="text-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(investment, index) in investments.items"
          :key="index"
        >
          <td>{{ investment.date }}</td>
          <td>{{ formatPrice(investment.sum, investment.currency) }}</td>
          <td>{{ investment.account }}</td>
          <td class="table-actions">
            <template v-if="investment.id">
              <div class="justify-content-end align-items-center show-on-row-hover">
                <router-link
                  class="btn btn-link p-0 me-2 border-0"
                  :to="{name: 'EditDeposit', params: {id: investment.id}}"
                >
                  <pen :size="20" />
                </router-link>
                <button
                  type="button"
                  class="btn btn-link btn-link-danger"
                  @click="confirmDelete(investment)"
                >
                  <circle-x :size="20" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="investments.items.length < 1">
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
