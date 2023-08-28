<template>
  <div class="flex justify-between mb-2 mt-5 py-3 rounded">
    <div class="">
      <div class="font-extrabold text-lg">
        <router-link :to="{name: 'AccountDetail', params: {id: account.id}}">
          {{ account.name }}
        </router-link>
      </div>
      <div class="text-sm">
        <span class="font-light">Balance:</span> <span>{{ helpers.formatPrice(account.balance) }} ₽</span> / <span>{{ helpers.formatPrice(account.usdBalance) }} $</span>
        <span class="font-light ml-3">Deposits:</span> <span>{{ helpers.formatPrice(account.deposits) }} ₽</span>
        <span class="font-light ml-3">Current Value:</span> <span>{{ helpers.formatPrice(account.currentValue) }} ₽</span>
        <span class="font-light ml-3">Profit: </span>
        <span :class="[account.fullProfit > 0 ? 'text-green-600' : 'text-red-700']">
          {{ helpers.formatPrice(account.fullProfit) }} ₽
        </span>
      </div>
    </div>
    <div class="flex items-center">
      <router-link
        :to="{name: 'AddAsset', params: {account: account.id}}"
        class="text-gray-300 hover:text-gray-600 mr-2"
        title="Add Asset">
        <plus-circle-icon class="h-5 w-5"></plus-circle-icon>
      </router-link>
      <router-link
        :to="{name: 'EditAccount', params: {id: account.id}}"
        class="text-gray-300 hover:text-gray-600 mr-2"
        title="Edit Account"
      >
        <pencil-icon class="h-5 w-5"></pencil-icon>
      </router-link>
      <button
        type="button"
        class="text-gray-300 hover:text-red-500"
        @click="confirmDeletion(account)"
        title="Delete Account"
      >
        <x-circle-icon class="h-5 w-5"></x-circle-icon>
      </button>
    </div>
  </div>
</template>
<script setup lang="ts">
import type {Account} from "@/models/account";
import helpers from "../../helpers";
import {PencilIcon, XCircleIcon, PlusCircleIcon} from "@heroicons/vue/24/outline";
import {useModal} from "@/composable/useModal";
import ConfirmDeleteAccountModal from "@/components/Account/ConfirmDeleteAccountModal.vue";

defineProps<{
  account: Account
}>();

const modal = useModal()

function confirmDeletion(account: Account) {
  modal.open({
    component: ConfirmDeleteAccountModal,
    modelValue: {
      id: account.id,
      title: 'Deletion confirmation',
      text: 'Are you sure you want to delete &quot;<b>' + account.name + '</b>&quot;?',
    },
  })
}

</script>