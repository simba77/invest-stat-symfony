<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import {XCircleIcon, PencilIcon} from "@heroicons/vue/outline"
import {useSavings} from '@/composable/useSavings'
import helpers from '@/helpers'

const savings = useSavings()

savings.getSavings()

</script>

<template>
  <page-component title="Savings">
    <div class="mb-4">
      <router-link :to="{name: 'SavingCreate'}" class="btn btn-primary">Add Deposit</router-link>
      <router-link :to="{name: 'SavingAccounts'}" class="btn btn-secondary ml-2">Accounts</router-link>
    </div>
    <table class="simple-table">
      <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Sum</th>
        <th>Type</th>
        <th>Account</th>
        <th class="flex justify-end">Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(item, index) in savings.savings.value.data" :key="index">
        <td>{{ item.id }}</td>
        <td>{{ item.date }}</td>
        <td>{{ helpers.formatPrice(item.sum) }} {{ item.currency }}</td>
        <td>{{ item.type }}</td>
        <td>{{ item.account.name }}</td>
        <td class="table-actions">
          <template v-if="item.id">
            <div class="flex justify-end items-center show-on-row-hover">
              <router-link class="text-gray-300 hover:text-gray-900 mr-3" :to="{name: 'SavingEdit', params: {id: item.id}}">
                <pencil-icon class="h-5 w-5"></pencil-icon>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="savings.confirmDeletion(item, () => savings.getSavings())"
              >
                <x-circle-icon class="h-5 w-5"></x-circle-icon>
              </button>
            </div>
          </template>
        </td>
      </tr>
      <tr v-if="savings.savings.value.data?.length < 1">
        <td colspan="6" class="text-center">The list is empty</td>
      </tr>
      </tbody>
    </table>
  </page-component>
</template>

