<script setup lang="ts">
import { LockClosedIcon, PencilIcon, XCircleIcon, BanknotesIcon } from "@heroicons/vue/24/outline";
import type { Deal } from "@/types/account";
import { useModal } from "@/composable/useModal";
import DeleteDealModal from "@/components/Account/DeleteDealModal.vue";
import SellModal from "@/components/Modals/SellModal.vue";
import { useNumbers } from "@/composable/useNumbers";

const {formatPriceWithSign, formatPrice, formatPercent} = useNumbers()

defineProps<{
  item: Deal,
  hideActions?: boolean
}>();

const modal = useModal();

function deleteDeal(deal: Deal) {
  modal.open({
    component: DeleteDealModal,
    modelValue: {
      id: deal.id,
      title: 'Deletion confirmation',
      text: 'Are you sure you want to delete &quot;<b>' + deal.shortName + '</b>&quot;?',
    }
  })
}

function showSellModal(item: Deal) {
  modal.open({
    component: SellModal,
    modelValue: {
      id: item.id,
      accountId: item.accountId,
      ticker: item.ticker,
      name: item.shortName,
      price: item.currentPrice,
      quantity: item.quantity
    }
  })
}

</script>

<template>
  <tr>
    <td v-tooltip="{html: true, content: 'Created: ' + item.createdAt + '<br>Updated: ' + item.updatedAt}">
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
      </div>
      <div class="text-xs text-gray-500">
        {{ formatPrice(item.fullCurrentPrice, item.currency) }}
        <span v-if="item.fullDailyProfit != 0" :class="{'text-green-600': item.fullDailyProfit > 0, 'text-red-700': item.fullDailyProfit < 0}">
          ({{ formatPrice(item.fullDailyProfit, item.currency) }})
        </span>
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
    <td :class="{'text-green-600': item.profit > 0, 'text-red-700': item.profit < 0}">
      <div>{{ formatPriceWithSign(item.profit, item.currency) }}</div>
      <div class="text-xs">
        ({{ formatPercent(item.profitPercent) }}, {{ formatPrice(item.commission, item.currency) }})
      </div>
    </td>
    <td>
      <template v-if="item.targetProfit !== 0">
        {{ formatPrice(item.targetProfit, item.currency) }}
        <div class="text-xs">
          ({{ formatPrice(item.fullTargetProfit, item.currency) }}, {{ formatPercent(item.fullTargetProfitPercent) }})
        </div>
      </template>
      <template v-else>
        &mdash;
      </template>
    </td>
    <td>{{ formatPercent(item.percent) }}</td>
    <td v-if="!hideActions" class="table-actions">
      <div class="flex justify-end items-center show-on-row-hover">
        <router-link
          :to="{name: 'EditAsset', params: {account: item.accountId, id: item.id}}"
          class="text-gray-300 hover:text-gray-600 mr-2"
          title="Edit"
        >
          <pencil-icon class="h-5 w-5" />
        </router-link>
        <div
          class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
          title="Sell"
          @click.prevent="showSellModal(item)"
        >
          <banknotes-icon class="h-5 w-5" />
        </div>
        <button
          type="button"
          class="text-gray-300 hover:text-red-500"
          title="Delete"
          @click="deleteDeal(item)"
        >
          <x-circle-icon class="h-5 w-5" />
        </button>
      </div>
    </td>
  </tr>
</template>
