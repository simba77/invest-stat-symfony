<script setup lang="ts">
import { LockClosedIcon, BanknotesIcon } from "@heroicons/vue/24/outline";
import type { AssetsGroup } from "@/types/account";
import { useModal } from "@/composable/useModal";
import SellModal from "@/components/Modals/SellModal.vue";
import { useRoute } from "vue-router";
import { useNumbers } from "@/composable/useNumbers";

const modal = useModal()
const route = useRoute()
const {formatPriceWithSign, formatPrice, formatPercent} = useNumbers()

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

function getPercent(resultPrice: number, currentPrice: number) {
  if (currentPrice > 0) {
    return parseFloat(String(Math.abs(resultPrice) / currentPrice * 100)).toFixed(2) + '%';
  }
  return '';
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
      <div>{{ formatPrice(item.buyPrice, item.currency) }}</div>
      <div class="text-xs text-gray-500">
        {{ formatPrice(item.fullBuyPrice, item.currency) }}
      </div>
    </td>
    <td>
      <div>
        {{ formatPrice(item.currentPrice, item.currency) }}
        <span v-tooltip="'Prev price: ' + formatPrice(item.prevPrice, item.currency)" :class="item.dailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
          ({{ item.dailyProfit > 0 ? '+' : '-' }}{{ getPercent(item.dailyProfit, item.prevPrice) }}, {{ formatPrice(Math.abs(item.dailyProfit), item.currency) }})
        </span>
      </div>
      <div class="text-xs text-gray-500">
        {{ formatPrice(item.fullCurrentPrice, item.currency) }}
        <span v-tooltip="'Prev full price: ' + formatPrice(item.fullPrevPrice, item.currency)" :class="item.fullDailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
          ({{ formatPriceWithSign(item.fullDailyProfit, item.currency) }})</span>
      </div>
    </td>
    <td>
      <template v-if="item.targetPrice">
        <div>{{ formatPrice(item.targetPrice, item.currency) }}</div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(item.fullTargetPrice, item.currency) }}
        </div>
      </template>
      <template v-else>
        &mdash;
      </template>
    </td>
    <td :class="[item.profit > 0 ? 'text-green-600' : 'text-red-700']">
      <div>{{ formatPriceWithSign(item.profit, item.currency) }}</div>
      <div class="text-xs">
        ({{ formatPercent(item.profitPercent) }}, {{ formatPrice(item.commission, item.currency) }})
      </div>
    </td>
    <td>
      <template v-if="item.targetProfit !== 0">
        {{ formatPrice(item.targetProfit, item.currency) }}
        <div class="text-xs">
          ({{ formatPrice(item.fullTargetProfit, item.currency) }}, {{ formatPercent(item.targetProfitPercent) }})
        </div>
      </template>
      <template v-else>
        &mdash;
      </template>
    </td>
    <td>{{ formatPercent(item.percent) }}</td>
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
