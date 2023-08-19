<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import {XCircleIcon, PencilIcon} from "@heroicons/vue/outline"
import {useSavingAccounts} from '@/composable/useSavingAccounts'

const savingAccount = useSavingAccounts()

savingAccount.getAccounts()

</script>

<template>
  <page-component title="Saving Accounts">
    <div class="mb-4">
      <router-link :to="{name: 'SavingAccountsCreate'}" class="btn btn-primary">Add Account</router-link>
    </div>
    <table class="simple-table">
      <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th class="flex justify-end">Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(item, index) in savingAccount.accounts.value.data" :key="index">
        <td>{{ item.id }}</td>
        <td>{{ item.name }}</td>
        <td class="table-actions">
          <template v-if="item.id">
            <div class="flex justify-end items-center show-on-row-hover">
              <router-link class="text-gray-300 hover:text-gray-900 mr-3" :to="{name: 'SavingAccountsEdit', params: {id: item.id}}">
                <pencil-icon class="h-5 w-5"></pencil-icon>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="savingAccount.confirmDeletion(item, () => savingAccount.getAccounts())"
              >
                <x-circle-icon class="h-5 w-5"></x-circle-icon>
              </button>
            </div>
          </template>
        </td>
      </tr>
      <tr v-if="savingAccount.accounts.value.data?.length < 1">
        <td colspan="3" class="text-center">The list is empty</td>
      </tr>
      </tbody>
    </table>

    <div class="mt-5">
      <router-link :to="{name: 'Savings'}" class="btn btn-secondary">Back</router-link>
    </div>
  </page-component>
</template>

