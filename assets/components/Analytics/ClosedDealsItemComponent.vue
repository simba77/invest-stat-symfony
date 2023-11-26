<script setup lang="ts">
import {LockClosedIcon} from "@heroicons/vue/24/outline";
import helpers from "../../helpers";
import {ClosedDeal} from "@/types/analytics";

function formatProfit(asset: { profit: number; currency: string; }) {
  return (asset.profit > 0 ? '+' : '-') + ' ' + helpers.formatPrice(Math.abs(asset.profit)) + ' ' + asset.currency;
}

defineProps<{
  item: ClosedDeal,
}>();
</script>

<template>
  <tr>
    <td v-tooltip="{html: true, content: 'Created At: ' + item.createdAt + '<br>Updated At: ' + item.createdAt}">
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
      <div>{{ helpers.formatPrice(item.sellPrice) }} {{ item.currency }}</div>
      <div class="text-xs text-gray-500">
        {{ helpers.formatPrice(item.fullSellPrice) }} {{ item.currency }}
      </div>
    </td>

    <td :class="[item.profit > 0 ? 'text-green-600' : 'text-red-700']">
      <div>{{ formatProfit(item) }}</div>
      <div class="text-xs">
        ({{ item.profitPercent }}%, {{ item.commission }} {{ item.currency }})
      </div>
    </td>
  </tr>
</template>
