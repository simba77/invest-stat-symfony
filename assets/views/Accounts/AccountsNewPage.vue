<template>
  <page-component title="Accounts" ref="accounts">
    <div class="mb-4 mt-3">
      <router-link :to="{name: 'CreateAccount'}" class="btn btn-primary">Create Account</router-link>
    </div>
    <preloader-component v-if="loading"></preloader-component>
    <template v-if="!loading && accounts">
      <div class="mb-6">
        <div v-for="(account, index) in accounts" :key="index">
          <account-component :account="account"></account-component>
        </div>
      </div>
    </template>
  </page-component>
</template>
<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue"
import {provide} from "vue"
import AccountComponent from "@/components/Account/AccountComponent.vue"
import useAccounts from "@/composable/useAccounts"

const {
  getAccounts,
  accounts,
  loading
} = useAccounts()

provide('accounts', {getAccounts})

getAccounts()
</script>
