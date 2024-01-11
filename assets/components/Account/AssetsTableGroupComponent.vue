<script setup lang="ts">
import {LockClosedIcon, BanknotesIcon} from "@heroicons/vue/24/outline";
import helpers from "../../helpers";
import type {AssetsGroup} from "@/types/account";
import {useModal} from "@/composable/useModal";
import SellModal from "@/components/Modals/SellModal.vue";
import {useRoute} from "vue-router";

const modal = useModal()
const route = useRoute()

function formatProfit(asset: { profit: number; currency: string; }) {
  return (asset.profit > 0 ? '+' : '-') + ' ' + helpers.formatPrice(Math.abs(asset.profit)) + ' ' + asset.currency;
}

const emits = defineEmits<{ showChildren: [] }>()

defineProps<{
  item: AssetsGroup,
  hideActions?: boolean
}>();

function showSellModal(item: AssetsGroup) {
  modal.open({
    component: SellModal,
    modelValue: {
      accountId: route.params.id,
      ticker: item.ticker,
      name: item.shortName,
      price: item.currentPrice,
      quantity: ''
    }
  })
}

</script>

<template>
  <tr
    class="tr-clickable"
    @click="emits('showChildren')"
  >
    <td class="underline">
      <div class="font-extrabold">
        <lock-closed-icon
          v-if="item.isBlocked"
          class="h-3 w-3 inline-block"
        />
        {{ item.shortName }}
      </div>
      <div class="text-gray-500">
        <span class="text-xs">{{ item.ticker }}</span>
        <span
          v-if="item.isShort"
          class="bg-red-200 text-red-900 rounded-full inline-flex pr-2 pl-2 items-center ml-2"
        >short</span>
      </div>
    </td>
    <td>{{ item.quantity }}</td>
    <td>
      <div>{{ helpers.formatPrice(item.buyPrice) }} {{ item.currency }}</div>
      <div class="text-xs text-gray-500">
        {{ helpers.formatPrice(item.fullBuyPrice) }} {{ item.currency }}
      </div>
    </td>
    <td>
      <div>
        {{ helpers.formatPrice(item.currentPrice) }} {{ item.currency }}
        <span v-tooltip="'Prev price: ' + helpers.formatPrice(item.prevPrice) +' '+ item.currency" :class="item.dailyProfit > 0 ? 'text-green-600' : 'text-red-700'">({{ item.dailyProfit > 0 ? '+' : '' }}{{ item.dailyProfit }}{{ item.currency }})</span>
      </div>
      <div class="text-xs text-gray-500">
        {{ helpers.formatPrice(item.fullCurrentPrice) }} {{ item.currency }}
        <span v-tooltip="'Prev full price: ' + helpers.formatPrice(item.fullPrevPrice) +' '+ item.currency" :class="item.fullDailyProfit > 0 ? 'text-green-600' : 'text-red-700'">({{ item.fullDailyProfit > 0 ? '+' : '' }}{{ item.fullDailyProfit }}{{ item.currency }})</span>
      </div>
    </td>
    <td>
      <template v-if="item.targetPrice">
        <div>{{ helpers.formatPrice(item.targetPrice) }} {{ item.currency }}</div>
        <div class="text-xs text-gray-500">
          {{ helpers.formatPrice(item.fullTargetPrice) }} {{ item.currency }}
        </div>
      </template>
      <template v-else>
        &mdash;
      </template>
    </td>
    <td :class="[item.profit > 0 ? 'text-green-600' : 'text-red-700']">
      <div>{{ formatProfit(item) }}</div>
      <div class="text-xs">
        ({{ item.profitPercent }}%, {{ item.commission }} {{ item.currency }})
      </div>
    </td>
    <td>
      <template v-if="item.targetProfit !== 0">
        {{ item.targetProfit }} {{ item.currency }}
        <div class="text-xs">
          ({{ helpers.formatPrice(item.fullTargetProfit) }} {{ item.currency }}, {{ item.targetProfitPercent }}%)
        </div>
      </template>
      <template v-else>
        &mdash;
      </template>
    </td>
    <td>{{ item.percent }}%</td>
    <td v-if="!hideActions" class="table-actions">
      <div class="flex justify-end items-center show-on-row-hover">
        <div
          class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
          title="Sell"
          @click.stop="showSellModal(item)"
        >
          <banknotes-icon class="h-5 w-5" />
        </div>
      </div>
    </td>
  </tr>
</template>
