<script setup lang="ts">
import PageComponent from '@/components/PageComponent.vue'
import useAccounts from '@/composable/useAccounts'
import useAnalytics from "@/composable/useAnalytics";
import AssetsTableComponent from '@/components/Account/AssetsTableComponent.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import {provide} from 'vue'

const {getAccount} = useAccounts()

const {loading, getAssets, assets} = useAnalytics()

getAssets()

provide('accounts', {getAccounts: getAccount})

</script>

<template>
  <page-component title="Analytics">
    <template v-if="!loading">
      <div class="w-full overflow-x-auto mb-4">
        <template v-if="Object.keys(assets).length < 1">
          <div class="text-gray-500 text-sm">
            The List is Empty
          </div>
        </template>
        <template v-else>
          <!-- Выводим таблицу с активами -->
          <assets-table-component v-model="assets" />
        </template>
      </div>
    </template>
    <preloader-component v-else />
  </page-component>
</template>
