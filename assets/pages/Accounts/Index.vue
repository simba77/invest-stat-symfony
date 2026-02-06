<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue"
import {provide} from "vue"
import AccountComponent from "@/components/Account/AccountComponent.vue"
import useAccounts from "@/composable/useAccounts"
import {usePage} from "@/composable/usePage";

const {
  getAccounts,
  accounts,
  loading
} = useAccounts()

const {setPageTitle} = usePage()

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
      <div class="mb-6">
        <div
          v-for="(account, index) in accounts"
          :key="index"
        >
          <account-component :account="account" />
        </div>
      </div>
    </template>
  </page-component>
</template>
