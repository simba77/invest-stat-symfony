<script setup lang="ts">
import type { Account } from "@/types/account";
import { PencilIcon, XCircleIcon, PlusCircleIcon } from "@heroicons/vue/24/outline";
import { useModal } from "@/composable/useModal";
import ConfirmDeleteAccountModal from "@/components/Account/ConfirmDeleteAccountModal.vue";
import { useNumbers } from "@/composable/useNumbers";

defineProps<{
  account: Account
}>();

const modal = useModal()
const {formatPrice} = useNumbers()

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

<template>
  <div class="flex justify-between mb-2 mt-5 py-3 rounded">
    <div class="">
      <div class="font-extrabold text-lg">
        <router-link :to="{name: 'AccountDetail', params: {id: account.id}}">
          {{ account.name }}
        </router-link>
      </div>
      <div class="text-sm">
        <span class="font-light">Balance:</span> <span>{{ formatPrice(account.balance, '₽') }}</span> / <span>{{ formatPrice(account.usdBalance, '$') }}</span>
        <span class="font-light ml-3">Deposits:</span> <span>{{ formatPrice(account.deposits, '₽') }}</span>
        <span class="font-light ml-3">Current Value:</span> <span>{{ formatPrice(account.currentValue, '₽') }}</span>
        <span class="font-light ml-3">Profit: </span>
        <span :class="[account.fullProfit > 0 ? 'text-green-600' : 'text-red-700']">
          {{ formatPrice(account.fullProfit, '₽') }}
        </span>
      </div>
    </div>
    <div class="flex items-center">
      <router-link
        :to="{name: 'AddAsset', params: {account: account.id}}"
        class="text-gray-300 hover:text-gray-600 mr-2"
        title="Add Asset"
      >
        <plus-circle-icon class="h-5 w-5" />
      </router-link>
      <router-link
        :to="{name: 'EditAccount', params: {id: account.id}}"
        class="text-gray-300 hover:text-gray-600 mr-2"
        title="Edit Account"
      >
        <pencil-icon class="h-5 w-5" />
      </router-link>
      <button
        type="button"
        class="text-gray-300 hover:text-red-500"
        title="Delete Account"
        @click="confirmDeletion(account)"
      >
        <x-circle-icon class="h-5 w-5" />
      </button>
    </div>
  </div>
</template>
