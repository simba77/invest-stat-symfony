<script setup lang="ts">
import {useDealsGroup} from "@/composable/useDealsGroup";
import {ClosedDealsListItem, ClosedDealsSummary} from "@/types/analytics";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Row from 'primevue/row';
import ColumnGroup from 'primevue/columngroup';
import { useNumbers } from "@/composable/useNumbers";
import {LockClosedIcon} from "@heroicons/vue/24/outline";
import ClosedDealsSubTable from "@/components/Analytics/ClosedDealsSubTable.vue";

const {formatPrice, formatPercent, formatPriceWithSign} = useNumbers()

defineProps<{ assets: ClosedDealsListItem[], summary: ClosedDealsSummary }>()

const dealsGroup = useDealsGroup()

</script>

<template>
  <DataTable
    v-model:expanded-rows="dealsGroup.openedGroups.value"
    :value="assets"
    data-key="groupData.ticker"
    table-style="min-width: 60rem"
  >
    <Column expander style="width: 0" />
    <Column header="Name">
      <template #body="{data}">
        <div class="font-extrabold">
          <lock-closed-icon
            v-if="data.groupData.isBlocked"
            class="h-3 w-3 -mt-1 inline-block"
          />
          {{ data.groupData.shortName }}
        </div>
        {{ data.groupData.ticker }}
      </template>
    </Column>
    <Column field="groupData.quantity" header="Quantity" />
    <Column header="Buy Price">
      <template #body="{data}">
        <div>{{ formatPrice(data.groupData.buyPrice, data.groupData.currency) }}</div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.groupData.fullBuyPrice, data.groupData.currency) }}
        </div>
      </template>
    </Column>
    <Column header="Sell Price">
      <template #body="{data}">
        <div>{{ formatPrice(data.groupData.sellPrice, data.groupData.currency) }}</div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.groupData.fullSellPrice, data.groupData.currency) }}
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
        <div :class="[data.groupData.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div>{{ formatPriceWithSign(data.groupData.profit, data.groupData.currency) }}</div>
          <div class="text-xs">
            ({{ formatPercent(data.groupData.profitPercent) }}, {{ formatPrice(data.groupData.commission, data.groupData.currency) }})
          </div>
        </div>
      </template>
    </Column>

    <ColumnGroup type="footer">
      <Row>
        <Column footer="Total (base currency):" :colspan="3" class="font-bold" footer-style="text-align:right" />
        <Column class="font-bold">
          <template #footer>
            <div>{{ formatPrice(summary.buyPrice, '₽') }}</div>
          </template>
        </Column>
        <Column class="font-bold">
          <template #footer>
            <div>{{ formatPrice(summary.sellPrice, '₽') }} </div>
          </template>
        </Column>
        <Column class="font-bold">
          <template #footer>
            <div :class="[summary.profit > 0 ? 'text-green-600' : 'text-red-700']">
              <div>{{ formatPrice(summary.profit, '₽') }}</div>
              <div class="text-xs">
                ({{ formatPercent(summary.profitPercent) }})
              </div>
            </div>
          </template>
        </Column>
      </Row>
    </ColumnGroup>

    <template #expansion="slotProps">
      <closed-deals-sub-table :items="slotProps.data.deals" />
    </template>
  </DataTable>
</template>

