<template>
  <page-component title="Investments">
    <div class="mb-4">
      <router-link :to="{name: 'AddDeposit'}" class="btn btn-primary">Add</router-link>
    </div>
    <preloader-component v-if="investments.loadingInvestments.value"/>
    <table v-else class="simple-table">
      <thead>
      <tr>
        <th>Date</th>
        <th>Sum</th>
        <th>Account</th>
        <th class="flex justify-end">Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(investment, index) in investments.investments.value.items" :key="index">
        <td>{{ investment.date }}</td>
        <td>{{ helpers.formatPrice(investment.sum) }} {{ investment.currency }}</td>
        <td>{{ investment.account }}</td>
        <td class="table-actions">
          <template v-if="investment.id">
            <div class="flex justify-end items-center show-on-row-hover">
              <router-link class="text-gray-300 hover:text-gray-900 mr-3" :to="{name: 'EditDeposit', params: {id: investment.id}}">
                <pencil-icon class="h-5 w-5"></pencil-icon>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="confirmDelete(investment)"
              >
                <x-circle-icon class="h-5 w-5"></x-circle-icon>
              </button>
            </div>
          </template>
        </td>
      </tr>
      <tr v-if="investments.investments.value?.length < 1">
        <td colspan="4" class="text-center">The list is empty</td>
      </tr>
      </tbody>
    </table>
  </page-component>
</template>

<script setup lang="ts">
import PageComponent from "../components/PageComponent.vue";
import {XCircleIcon, PencilIcon} from "@heroicons/vue/24/outline";
import helpers from "../helpers";
import {useInvestments} from "@/composable/useInvestments";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {Investment} from "@/models/investments";
import {useModal} from "@/composable/useModal";
import ConfirmDeleteInvestmentModal from "@/components/Investments/ConfirmDeleteInvestmentModal.vue";

const investments = useInvestments()
const modal = useModal()

investments.getInvestments()

function confirmDelete(item: Investment) {
  modal.open({
    component: ConfirmDeleteInvestmentModal,
    modelValue: item
  })
}

</script>
