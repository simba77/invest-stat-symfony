<script setup lang="ts">
import PageComponent from '@/components/PageComponent.vue'
import useAccounts from '@/composable/useAccounts'
import {useRoute} from 'vue-router'
import AssetsTableComponent from '@/components/Account/AssetsTableComponent.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import {provide} from 'vue'
import { useNumbers } from "@/composable/useNumbers";

const {params} = useRoute()
const {account: data, getAccount} = useAccounts()
const {formatPrice} = useNumbers()

getAccount(params.id);

provide('accounts', {getAccounts: getAccount})

</script>

<template>
  <page-component :title="data?.account?.name ?? 'Loading...'">
    <template v-if="data">
      <div class="flex justify-between mb-2 py-3 rounded items-center">
        <div class="text-sm">
          <span class="font-light">Balance:</span> <span>{{ formatPrice(data.account.balance, '₽') }}</span> / <span>{{ formatPrice(data.account.usdBalance, '$') }}</span>
          <span class="font-light ml-3">Deposits:</span> <span>{{ formatPrice(data.account.deposits, '₽') }}</span>
          <span class="font-light ml-3">Current Value:</span> <span>{{ formatPrice(data.account.currentValue, '₽') }}</span>
          <span class="font-light ml-3">Profit: </span>
          <span :class="[data.account.fullProfit > 0 ? 'text-green-600' : 'text-red-700']">
            {{ formatPrice(data.account.fullProfit, '₽') }}
          </span>
        </div>
        <div>
          <router-link
            :to="{name: 'AddAsset', params: {account: data.account.id}}"
            class="btn btn-primary"
            title="Add Asset"
          >
            Add
          </router-link>
        </div>
      </div>

      <template
        v-for="(groupedByStatus, groupedByStatusIndex) in data.deals.dealsList"
        :key="groupedByStatusIndex"
      >
        <!-- Если групп блокировки больше одной, выводим название -->
        <template v-if="Object.keys(data.deals.dealsList).length > 1">
          <div class="font-extrabold uppercase text-base mb-4">
            {{ data.deals.statuses[groupedByStatusIndex]['name'] }}
          </div>
        </template>

        <!-- Вывод типа инструмента -->
        <template
          v-for="(groupedByInstrumentType, groupedByInstrumentTypeIndex) in groupedByStatus"
          :key="groupedByInstrumentTypeIndex"
        >
          <div class="font-bold text-neutral-600 mb-4">
            {{ data.deals.instrumentTypes[groupedByInstrumentTypeIndex]['name'] }}
          </div>


          <!-- Выводим группы активов по валютам -->
          <template
            v-for="(groupedByCurrency, groupedByCurrencyIndex) in groupedByInstrumentType"
            :key="groupedByCurrencyIndex"
          >
            <div class="flex items-center">
              <div class="font-bold text-sm mb-4">
                {{ data.deals.currencies[groupedByCurrencyIndex]['name'] }}
              </div>
              <div class="flex-grow mb-4 ml-3 border-b dark:border-zinc-800" />
            </div>

            <div class="w-full overflow-x-auto mb-4">
              <template v-if="Object.keys(groupedByCurrency).length < 1">
                <div class="text-gray-500 text-sm">
                  The List is Empty
                </div>
              </template>
              <template v-else>
                <!-- Выводим таблицу с активами -->
                <assets-table-component
                  :assets="groupedByCurrency"
                  :summary="data.deals.summary[groupedByStatusIndex][groupedByInstrumentTypeIndex][groupedByCurrencyIndex]"
                />
              </template>
            </div>
          </template>
          <!-- // Выводим группы активов по валютам -->
        </template>
        <!-- // Вывод типа инструмента -->
      </template>
    </template>
    <preloader-component v-else />
  </page-component>
</template>
