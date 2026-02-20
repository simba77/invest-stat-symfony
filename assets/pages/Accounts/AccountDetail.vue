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
      <div class="d-flex justify-content-between align-items-center mb-3 rounded">
        <div>
          <span class="fw-light">Balance:</span> <span>{{ formatPrice(data.account.balance, '₽') }}</span> / <span>{{ formatPrice(data.account.usdBalance, '$') }}</span>
          <span class="fw-light ms-3">Deposits:</span> <span>{{ formatPrice(data.account.deposits, '₽') }}</span>
          <span class="fw-light ms-3">Current Value:</span> <span>{{ formatPrice(data.account.currentValue, '₽') }}</span>
          <span class="fw-light ms-3">Profit: </span>
          <span :class="[data.account.fullProfit > 0 ? 'text-success' : 'text-danger']">
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
          <div class="fw-bold text-uppercase mb-2">
            {{ data.deals.statuses[groupedByStatusIndex]['name'] }}
          </div>
        </template>

        <!-- Вывод типа инструмента -->
        <template
          v-for="(groupedByInstrumentType, groupedByInstrumentTypeIndex) in groupedByStatus"
          :key="groupedByInstrumentTypeIndex"
        >
          <div class="fw-bold text-muted mb-2">
            {{ data.deals.instrumentTypes[groupedByInstrumentTypeIndex]['name'] }}
          </div>


          <!-- Выводим группы активов по валютам -->
          <template
            v-for="(groupedByCurrency, groupedByCurrencyIndex) in groupedByInstrumentType"
            :key="groupedByCurrencyIndex"
          >
            <div class="d-flex align-items-center mb-4">
              <div class="fw-bold small">
                {{ data.deals.currencies[groupedByCurrencyIndex]['name'] }}
              </div>
              <div class="flex-grow-1 ms-3 border-bottom" />
            </div>

            <div class="table-responsive mb-4">
              <template v-if="Object.keys(groupedByCurrency).length < 1">
                <div class="text-muted small">
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
