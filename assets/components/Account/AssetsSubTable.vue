<script setup lang="ts">
import { LockClosedIcon, PencilIcon, XCircleIcon, BanknotesIcon } from "@heroicons/vue/24/outline";
import type { Deal } from "@/types/account";
import { useModal } from "@/composable/useModal";
import DeleteDealModal from "@/components/Account/DeleteDealModal.vue";
import SellModal from "@/components/Modals/SellModal.vue";
import { useNumbers } from "@/composable/useNumbers";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const {formatPrice, formatPercent, formatPriceWithSign, getPercent} = useNumbers()

defineProps<{
  items: Deal[],
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
  <DataTable :value="items" table-style="min-width: 50rem">
    <Column header="Name">
      <template #body="{data}">
        <div v-tooltip="'Created: ' + data.createdAt" class="font-extrabold">
          <lock-closed-icon
            v-if="data.isBlocked"
            class="h-3 w-3 -mt-1 inline-block"
          />
          {{ data.shortName }}
        </div>
        {{ data.ticker }}
      </template>
    </Column>
    <Column field="quantity" header="Quantity" />
    <Column header="Buy Price">
      <template #body="{data}">
        <div>{{ formatPrice(data.buyPrice, data.currency) }}</div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.fullBuyPrice, data.currency) }}
        </div>
      </template>
    </Column>
    <Column header="Current Price">
      <template #body="{data}">
        <div>
          {{ formatPrice(data.currentPrice, data.currency) }}
          <span v-tooltip="'Prev price: ' + formatPrice(data.prevPrice, data.currency)" :class="data.dailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
            ({{ data.dailyProfit > 0 ? '+' : '-' }}{{ getPercent(data.dailyProfit, data.prevPrice) }}, {{ formatPrice(Math.abs(data.dailyProfit), data.currency) }})
          </span>
        </div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.fullCurrentPrice, data.currency) }}
          <span v-tooltip="'Prev full price: ' + formatPrice(data.fullPrevPrice, data.currency)" :class="data.fullDailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
            ({{ formatPriceWithSign(data.fullDailyProfit, data.currency) }})</span>
        </div>
      </template>
    </Column>
    <Column header="Target Price">
      <template #body="{data}">
        <template v-if="data.targetPrice < 0 || data.targetPrice > 0">
          <div>{{ formatPrice(data.targetPrice, data.currency) }}</div>
          <div class="text-xs text-gray-500">
            {{ formatPrice(data.fullTargetPrice, data.currency) }}
          </div>
        </template>
        <template v-else>
          &mdash;
        </template>
      </template>
    </Column>
    <Column>
      <template #header>
        <div>
          <div class="-mb-1.5 font-bold">
            Profit
          </div>
          <span class="text-xs text-gray-400">(percent, commission)</span>
        </div>
      </template>
      <template #body="{data}">
        <div :class="[data.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div>{{ formatPriceWithSign(data.profit, data.currency) }}</div>
          <div class="text-xs">
            ({{ formatPercent(data.profitPercent) }}, {{ formatPrice(data.commission, data.currency) }})
          </div>
        </div>
      </template>
    </Column>
    <Column header="Target Profit">
      <template #body="{data}">
        <template v-if="data.targetProfit > 0">
          {{ formatPrice(data.targetProfit, data.currency) }}
          <div class="text-xs">
            ({{ formatPrice(data.fullTargetProfit, data.currency) }}, {{ formatPercent(data.fullTargetProfitPercent) }})
          </div>
        </template>
        <template v-else>
          &mdash;
        </template>
      </template>
    </Column>
    <Column header="Percent">
      <template #body="{data}">
        {{ formatPercent(data.percent) }}
      </template>
    </Column>
    <Column v-if="!hideActions" header="Actions">
      <template #body="{data}">
        <div class="">
          <div class="flex justify-end items-center show-on-row-hover">
            <router-link
              :to="{name: 'EditAsset', params: {account: data.accountId, id: data.id}}"
              class="text-gray-300 hover:text-gray-600 mr-2"
              title="Edit"
            >
              <pencil-icon class="h-5 w-5" />
            </router-link>
            <div
              class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
              title="Sell"
              @click.prevent="showSellModal(data)"
            >
              <banknotes-icon class="h-5 w-5" />
            </div>
            <button
              type="button"
              class="text-gray-300 hover:text-red-500"
              title="Delete"
              @click="deleteDeal(data)"
            >
              <x-circle-icon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </template>
    </Column>
  </DataTable>
</template>
