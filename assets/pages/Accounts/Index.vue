<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue"
import {computed, provide, ref} from "vue"
import AccountComponent from "@/components/Account/AccountComponent.vue"
import useAccounts from "@/composable/useAccounts"
import {usePage} from "@/composable/usePage";

const {
  getAccounts,
  accounts,
  loading
} = useAccounts()

const {setPageTitle} = usePage()

const openAccounts = computed(() => accounts.value.filter((account) => !account.isClosed))
const closedAccounts = computed(() => accounts.value.filter((account) => account.isClosed))
const showClosed = ref(false)

provide('accounts', {getAccounts})

getAccounts()

setPageTitle('Accounts')
</script>

<template>
  <page-component
    ref="accounts"
    title="Accounts"
  >
    <div class="mb-2">
      <router-link
        :to="{name: 'CreateAccount'}"
        class="btn btn-primary"
      >
        Create Account
      </router-link>
    </div>
    <preloader-component v-if="loading" />
    <template v-if="!loading && accounts">
      <div>
        <div
          v-for="account in openAccounts"
          :key="account.id"
        >
          <account-component :account="account" />
        </div>
      </div>
      <div
        v-if="closedAccounts.length > 0"
        class="mt-3"
      >
        <button
          type="button"
          class="btn btn-link p-0"
          @click="showClosed = !showClosed"
        >
          Closed accounts ({{ closedAccounts.length }})
        </button>
        <div
          v-if="showClosed"
          class="text-muted"
        >
          <div
            v-for="account in closedAccounts"
            :key="account.id"
          >
            <account-component :account="account" />
          </div>
        </div>
      </div>
    </template>
  </page-component>
</template>
