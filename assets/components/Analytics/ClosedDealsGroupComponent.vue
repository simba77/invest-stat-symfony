<script setup lang="ts">
import {LockClosedIcon} from "@heroicons/vue/24/outline";
import {ClosedDealsGroup} from "@/types/analytics";
import { useNumbers } from "@/composable/useNumbers";

const {formatPrice, formatPriceWithSign, formatPercent} = useNumbers()

const emits = defineEmits<{ showChildren: [] }>()

defineProps<{
  item: ClosedDealsGroup
}>();

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
      <div>{{ formatPrice(item.sellPrice, item.currency) }}</div>
      <div class="text-xs text-gray-500">
        {{ formatPrice(item.fullSellPrice, item.currency) }}
      </div>
    </td>
    <td :class="[item.profit > 0 ? 'text-green-600' : 'text-red-700']">
      <div>{{ formatPriceWithSign(item.profit, item.currency) }}</div>
      <div class="text-xs">
        ({{ formatPercent(item.profitPercent) }}, {{ formatPrice(item.commission, item.currency) }})
      </div>
    </td>
  </tr>
</template>
