<script setup lang="ts">
import type { Account } from "@/types/account";
import { Pen, CircleX, CirclePlus, Archive, ArchiveRestore } from 'lucide-vue-next';
import { useModal } from "@/composable/useModal";
import ConfirmDeleteAccountModal from "@/components/Account/ConfirmDeleteAccountModal.vue";
import { useNumbers } from "@/composable/useNumbers";
import useAccounts from "@/composable/useAccounts";
import useAsync from "@/utils/use-async";

defineProps<{
  account: Account
}>();

const modal = useModal()
const {formatPrice} = useNumbers()
const accounts = useAccounts()

const {loading: closing, run: setClosed} = useAsync(async (account: Account, close: boolean) => {
  if (close) {
    await accounts.closeAccount(account.id)
  } else {
    await accounts.reopenAccount(account.id)
  }
  await accounts.getAccounts()
})

function close(account: Account) {
  if (confirm('Close "' + account.name + '"? It leaves the lists and the forms; its records stay in the statistics.')) {
    setClosed(account, true)
  }
}

function confirmDeletion(account: Account) {
  modal.open({
    component: ConfirmDeleteAccountModal,
    modelValue: {
      id: account.id,
      title: 'Deletion confirmation',
      text: 'Are you sure you want to delete &quot;<b>' + account.name + '</b>&quot;? Only an empty account can be deleted.',
    },
  })
}

</script>

<template>
  <div class="d-flex justify-content-between mb-2 py-3">
    <div class="">
      <div class="fw-extrabold fz-lg">
        <router-link :to="{name: 'AccountDetail', params: {id: account.id}}">
          {{ account.name }}
        </router-link>
      </div>
      <div class="fz-sm">
        <span class="fw-light">Balance:</span> <span>{{ formatPrice(account.balance, '₽') }}</span> / <span>{{ formatPrice(account.usdBalance, '$') }}</span>
        <span class="fw-light ms-3">Deposits:</span> <span>{{ formatPrice(account.deposits, '₽') }}</span>
        <span class="fw-light ms-3">Current Value:</span> <span>{{ formatPrice(account.currentValue, '₽') }}</span>
        <span class="fw-light ms-3">Profit: </span>
        <span :class="[account.fullProfit > 0 ? 'text-success' : 'text-danger']">
          {{ formatPrice(account.fullProfit, '₽') }}
        </span>
      </div>
    </div>
    <div class="d-flex align-items-center">
      <router-link
        v-if="!account.isClosed"
        :to="{name: 'AddAsset', params: {account: account.id}}"
        class="btn btn-link p-0 me-2"
        title="Add Asset"
      >
        <circle-plus :size="20" />
      </router-link>
      <router-link
        :to="{name: 'EditAccount', params: {id: account.id}}"
        class="btn btn-link p-0 me-2"
        title="Edit Account"
      >
        <pen :size="20" />
      </router-link>
      <button
        v-if="account.isClosed"
        type="button"
        class="btn btn-link p-0 me-2"
        title="Reopen Account"
        :disabled="closing"
        @click="setClosed(account, false)"
      >
        <archive-restore :size="20" />
      </button>
      <button
        v-else-if="!account.isSynced"
        type="button"
        class="btn btn-link p-0 me-2"
        title="Close Account"
        :disabled="closing"
        @click="close(account)"
      >
        <archive :size="20" />
      </button>
      <button
        type="button"
        class="btn btn-link-danger p-0"
        title="Delete Account"
        @click="confirmDeletion(account)"
      >
        <circle-x :size="20" />
      </button>
    </div>
  </div>
</template>
