<script setup lang="ts">
import PageComponent from "../components/PageComponent.vue";
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import { usePortfolio } from "@/composable/usePortfolio";
import AssetsTableComponent from "@/components/Account/AssetsTableComponent.vue";
import {usePage} from "@/composable/usePage";
const {setPageTitle} = usePage()

const {getPortfolio, portfolio} = usePortfolio()

const {loading, run} = useAsync(() => getPortfolio())
run()

setPageTitle("Portfolio")

</script>

<template>
  <page-component title="Portfolio">
    <preloader-component v-if="loading" />
    <div v-else-if="portfolio">
      <template
        v-for="(groupedByStatus, groupedByStatusIndex) in portfolio?.dealsList"
        :key="groupedByStatusIndex"
      >
        <!-- Если групп блокировки больше одной, выводим название -->
        <template v-if="Object.keys(portfolio.dealsList).length > 1">
          <div class="fw-extrabold text-uppercase mb-4">
            {{ portfolio?.statuses[groupedByStatusIndex]['name'] }}
          </div>
        </template>

        <!-- Вывод типа инструмента -->
        <template
          v-for="(groupedByInstrumentType, groupedByInstrumentTypeIndex) in groupedByStatus"
          :key="groupedByInstrumentTypeIndex"
        >
          <div class="fw-bold text-muted mb-4">
            {{ portfolio?.instrumentTypes[groupedByInstrumentTypeIndex]['name'] }}
          </div>


          <!-- Выводим группы активов по валютам -->
          <template
            v-for="(groupedByCurrency, groupedByCurrencyIndex) in groupedByInstrumentType"
            :key="groupedByCurrencyIndex"
          >
            <div class="d-flex align-items-center">
              <div class="fw-bold fz-sm mb-4">
                {{ portfolio?.currencies[groupedByCurrencyIndex]['name'] }}
              </div>
              <div class="flex-grow-1 mb-4 ms-3 border-bottom" />
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
                  :summary="portfolio?.summary[groupedByStatusIndex][groupedByInstrumentTypeIndex][groupedByCurrencyIndex]"
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
