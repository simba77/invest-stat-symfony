<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import { usePortfolio } from "@/composable/usePortfolio";
import AssetsTableComponent from "@/components/Account/AssetsTableComponent.vue";

const {getPortfolio, portfolio} = usePortfolio()

const {loading, run} = useAsync(() => getPortfolio())
run()

</script>

<template>
  <page-component title="Portfolio">
    <preloader-component v-if="loading" />
    <div v-else-if="portfolio?.deals">
      <template
        v-for="(groupedByStatus, groupedByStatusIndex) in portfolio?.deals.dealsList"
        :key="groupedByStatusIndex"
      >
        <!-- Если групп блокировки больше одной, выводим название -->
        <template v-if="Object.keys(portfolio.deals.dealsList).length > 1">
          <div class="font-extrabold uppercase text-base mb-4">
            {{ portfolio?.deals.statuses[groupedByStatusIndex]['name'] }}
          </div>
        </template>

        <!-- Вывод типа инструмента -->
        <template
          v-for="(groupedByInstrumentType, groupedByInstrumentTypeIndex) in groupedByStatus"
          :key="groupedByInstrumentTypeIndex"
        >
          <div class="font-bold text-neutral-600 mb-4">
            {{ portfolio?.deals.instrumentTypes[groupedByInstrumentTypeIndex]['name'] }}
          </div>


          <!-- Выводим группы активов по валютам -->
          <template
            v-for="(groupedByCurrency, groupedByCurrencyIndex) in groupedByInstrumentType"
            :key="groupedByCurrencyIndex"
          >
            <div class="flex items-center">
              <div class="font-bold text-sm mb-4">
                {{ portfolio?.deals.currencies[groupedByCurrencyIndex]['name'] }}
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
                  :hide-actions="true"
                  :assets="groupedByCurrency"
                  :summary="portfolio?.deals.summary[groupedByStatusIndex][groupedByInstrumentTypeIndex][groupedByCurrencyIndex]"
                />
              </template>
            </div>
          </template>
          <!-- // Выводим группы активов по валютам -->
        </template>
        <!-- // Вывод типа инструмента -->
      </template>
    </div>
  </page-component>
</template>
