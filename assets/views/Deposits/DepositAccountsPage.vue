<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import {XCircleIcon, PencilIcon} from "@heroicons/vue/24/outline"
import {useDepositAccounts} from '@/composable/useDepositAccounts'
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";

const depositAccount = useDepositAccounts()
const {loading, run: getAccounts} = useAsync(() => depositAccount.getAccounts())

getAccounts()
</script>

<template>
  <page-component title="Deposit Accounts">
    <div class="mb-4">
      <router-link
        :to="{name: 'DepositAccountsCreate'}"
        class="btn btn-primary"
      >
        Add Account
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
          <th>Name</th>
          <th class="flex justify-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, index) in depositAccount.accounts.value?.items"
          :key="index"
        >
          <td>{{ item.id }}</td>
          <td>{{ item.name }}</td>
          <td class="table-actions">
            <template v-if="item.id">
              <div class="flex justify-end items-center show-on-row-hover">
                <router-link
                  class="text-gray-300 hover:text-gray-900 mr-3"
                  :to="{name: 'DepositAccountsEdit', params: {id: item.id}}"
                >
                  <pencil-icon class="h-5 w-5" />
                </router-link>
                <button
                  type="button"
                  class="text-gray-300 hover:text-red-500"
                >
                  <x-circle-icon class="h-5 w-5" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="depositAccount.accounts?.value?.items?.length === 0">
          <td
            colspan="3"
            class="text-center"
          >
            The list is empty
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-5">
      <router-link
        :to="{name: 'Deposits'}"
        class="btn btn-secondary"
      >
        Back
      </router-link>
    </div>
  </page-component>
</template>

