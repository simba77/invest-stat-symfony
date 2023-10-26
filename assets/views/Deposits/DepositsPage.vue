<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import {XCircleIcon, PencilIcon} from "@heroicons/vue/24/outline"
import {useDeposits} from '@/composable/useDeposits'
import helpers from '@/helpers'
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {Deposit, DepositAccount} from "@/models/depositAccount";
import {useModal} from "@/composable/useModal";
import DeleteDepositModal from "@/components/Deposits/DeleteDepositModal.vue";

const deposits = useDeposits()
const modal = useModal()
const {loading, run: getDeposits} = useAsync(() => deposits.getDeposits())

getDeposits()

function deleteDeposit(deposit: Deposit) {
  modal.open({
    component: DeleteDepositModal,
    modelValue: {
      id: deposit.id,
      title: 'Delete Confirmation',
      text: 'Are you sure you want to delete the deposit <b>#'+ deposit.id + '</b>?',
    }
  })
}

</script>

<template>
  <page-component title="Deposits">
    <div class="mb-4">
      <router-link
        :to="{name: 'DepositCreate'}"
        class="btn btn-primary"
      >
        Add Deposit
      </router-link>
      <router-link
        :to="{name: 'DepositAccounts'}"
        class="btn btn-secondary ml-2"
      >
        Accounts
      </router-link>
    </div>
    <preloader-component v-if="loading" />
    <table
      v-else
      class="simple-table"
    >
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Sum</th>
          <th>Type</th>
          <th>Account</th>
          <th class="flex justify-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, index) in deposits.deposits.value?.items"
          :key="index"
        >
          <td>{{ item.id }}</td>
          <td>{{ item.date }}</td>
          <td>{{ helpers.formatPrice(item.sum) }} ₽</td>
          <td>{{ item.typeName }}</td>
          <td>{{ item.accountName }}</td>
          <td class="table-actions">
            <template v-if="item.id">
              <div class="flex justify-end items-center show-on-row-hover">
                <router-link
                  class="text-gray-300 hover:text-gray-900 mr-3"
                  :to="{name: 'DepositEdit', params: {id: item.id}}"
                >
                  <pencil-icon class="h-5 w-5" />
                </router-link>
                <button
                  type="button"
                  class="text-gray-300 hover:text-red-500"
                  @click="deleteDeposit(item)"
                >
                  <x-circle-icon class="h-5 w-5" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="deposits.deposits.value?.items?.length === 0">
          <td
            colspan="6"
            class="text-center"
          >
            The list is empty
          </td>
        </tr>
      </tbody>
    </table>
  </page-component>
</template>

