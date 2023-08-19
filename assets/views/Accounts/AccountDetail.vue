<template>
  <page-component :title="account?.name ?? 'Loading...'">
    <template v-if="account">
      <div class="flex justify-between mb-2 py-3 rounded items-center">
        <div class="text-sm">
          <span class="font-light">Balance:</span> <span>{{ helpers.formatPrice(account.balance) }} ₽</span> / <span>{{ helpers.formatPrice(account.usdBalance) }} $</span>
          <span class="font-light ml-3">Deposits:</span> <span>{{ helpers.formatPrice(account.deposits) }} ₽</span>
          <span class="font-light ml-3">Current Value:</span> <span>{{ helpers.formatPrice(account.currentValue) }} ₽</span>
          <span class="font-light ml-3">Profit: </span>
          <span :class="[account.fullProfit > 0 ? 'text-green-600' : 'text-red-700']">
              {{ helpers.formatPrice(account.fullProfit) }} ₽
            </span>
        </div>
        <div>
          <router-link
            :to="{name: 'AddAsset', params: {account: account.id}}"
            class="btn btn-primary"
            title="Add Asset">
            Add
          </router-link>
        </div>
      </div>

      <template v-for="(blocTypeGroup, blockTypeGroupIndex) in account.blockGroups" :key="blockTypeGroupIndex">
        <!-- Если групп блокировки больше одной, выводим название -->
        <template v-if="Object.keys(account.blockGroups).length > 1">
          <div class="font-extrabold text-base mb-4">{{ blocTypeGroup.name }}</div>
        </template>

        <!-- Выводим группы активов по валютам -->
        <template v-for="(currencyGroup, currencyGroupIndex) in blocTypeGroup.items" :key="currencyGroupIndex">
          <div class="flex items-center">
            <div class="font-bold text-sm mb-4">{{ currencyGroup.name }}</div>
            <div class="flex-grow mb-4 ml-3 border-b"></div>
          </div>

          <div class="w-full overflow-x-auto mb-4">
            <template v-if="Object.keys(currencyGroup.items).length < 1">
              <div class="text-gray-500 text-sm">The List is Empty</div>
            </template>
            <template v-else>
              <!-- Выводим таблицу с активами -->
              <assets-table-component v-model="currencyGroup.items"></assets-table-component>
            </template>
          </div>
        </template>
      </template>
    </template>
    <preloader-component v-else></preloader-component>
  </page-component>
</template>
<script setup lang="ts">
import PageComponent from '@/components/PageComponent.vue'
import useAccounts from '@/composable/useAccounts'
import {useRoute} from 'vue-router'
import AssetsTableComponent from '@/components/Account/AssetsTableComponent.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import helpers from '@/helpers'
import {provide} from 'vue'

const {params} = useRoute()
const {account, getAccount} = useAccounts()

getAccount(params.id);

provide('accounts', {getAccounts: getAccount})

</script>
