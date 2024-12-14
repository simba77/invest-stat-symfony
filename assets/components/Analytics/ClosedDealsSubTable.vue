<script setup lang="ts">
import { LockClosedIcon } from "@heroicons/vue/24/outline";
import type { Deal } from "@/types/account";
import { useNumbers } from "@/composable/useNumbers";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const {formatPrice, formatPercent, formatPriceWithSign} = useNumbers()

defineProps<{
  items: Deal[]
}>();

</script>

<template>
  <DataTable :value="items" table-style="min-width: 50rem">
    <Column header="Name">
      <template #body="{data}">
        <div class="font-extrabold">
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
    <Column header="Sell Price">
      <template #body="{data}">
        <div>{{ formatPrice(data.sellPrice, data.currency) }}</div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.fullSellPrice, data.currency) }}
        </div>
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
  </DataTable>
</template>
