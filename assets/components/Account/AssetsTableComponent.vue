<script setup lang="ts">
import {type AssetsGroup, AssetsGroupData, GroupSummary} from "@/types/account";
import { useDealsGroup } from "@/composable/useDealsGroup";
import { useNumbers } from "@/composable/useNumbers";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Row from 'primevue/row';
import ColumnGroup from 'primevue/columngroup';
import SellModal from "@/components/Modals/SellModal.vue";
import {useModal} from "@/composable/useModal";
import {useRoute} from "vue-router";
import {BanknotesIcon, LockClosedIcon} from "@heroicons/vue/24/outline";
import AssetsSubTable from "@/components/Account/AssetsSubTable.vue";
const modal = useModal()
const route = useRoute()

const {formatPrice, formatPercent, formatPriceWithSign, getPercent} = useNumbers()

defineProps<{
  assets: { [key: string]: AssetsGroupData },
  summary: GroupSummary,
  hideActions?: boolean
}>()

const dealsGroup = useDealsGroup()

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
  <DataTable
    v-model:expandedRows="dealsGroup.openedGroups.value"
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
    <Column header="Current Price">
      <template #body="{data}">
        <div>
          {{ formatPrice(data.groupData.currentPrice, data.groupData.currency) }}
          <span v-tooltip="'Prev price: ' + formatPrice(data.groupData.prevPrice, data.groupData.currency)" :class="data.groupData.dailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
            ({{ data.groupData.dailyProfit > 0 ? '+' : '-' }}{{ getPercent(data.groupData.dailyProfit, data.groupData.prevPrice) }}, {{ formatPrice(Math.abs(data.groupData.dailyProfit), data.groupData.currency) }})
          </span>
        </div>
        <div class="text-xs text-gray-500">
          {{ formatPrice(data.groupData.fullCurrentPrice, data.groupData.currency) }}
          <span v-tooltip="'Prev full price: ' + formatPrice(data.groupData.fullPrevPrice, data.groupData.currency)" :class="data.groupData.fullDailyProfit > 0 ? 'text-green-600' : 'text-red-700'">
            ({{ formatPriceWithSign(data.groupData.fullDailyProfit, data.groupData.currency) }})</span>
        </div>
      </template>
    </Column>
    <Column header="Target Price">
      <template #body="{data}">
        <template v-if="data.groupData.targetPrice < 0 || data.groupData.targetPrice > 0">
          <div>{{ formatPrice(data.groupData.targetPrice, data.groupData.currency) }}</div>
          <div class="text-xs text-gray-500">
            {{ formatPrice(data.groupData.fullTargetPrice, data.groupData.currency) }}
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
        <div :class="[data.groupData.profit > 0 ? 'text-green-600' : 'text-red-700']">
          <div>{{ formatPriceWithSign(data.groupData.profit, data.groupData.currency) }}</div>
          <div class="text-xs">
            ({{ formatPercent(data.groupData.profitPercent) }}, {{ formatPrice(data.groupData.commission, data.groupData.currency) }})
          </div>
        </div>
      </template>
    </Column>
    <Column header="Target Profit">
      <template #body="{data}">
        <template v-if="data.groupData.targetProfit > 0">
          {{ formatPrice(data.groupData.targetProfit, data.groupData.currency) }}
          <div class="text-xs">
            ({{ formatPrice(data.groupData.fullTargetProfit, data.groupData.currency) }}, {{ formatPercent(data.groupData.targetProfitPercent) }})
          </div>
        </template>
        <template v-else>
          &mdash;
        </template>
      </template>
    </Column>
    <Column header="Percent">
      <template #body="{data}">
        {{ formatPercent(data.groupData.percent) }}
      </template>
    </Column>
    <Column v-if="!hideActions" header="Actions">
      <template #body="{data}">
        <div
          class="text-gray-300 hover:text-gray-600 mr-2 cursor-pointer"
          title="Sell"
          @click.stop="showSellModal(data.groupData)"
        >
          <banknotes-icon class="h-5 w-5 ml-auto" />
        </div>
      </template>
    </Column>

    <ColumnGroup type="footer">
      <Row>
        <Column footer="Subtotal:" colspan="3" class="font-bold" footer-style="text-align:right" />
        <Column class="font-bold">
          <template #footer>
            <div v-if="!summary.isBaseCurrency">
              {{ formatPrice(summary.buyPrice, '$') }}
            </div>
            <div>{{ formatPrice(summary.buyPriceInBaseCurrency, '₽') }}</div>
          </template>
        </Column>
        <Column class="font-bold" colspan="2">
          <template #footer>
            <div v-if="!summary.isBaseCurrency">
              {{ formatPrice(summary.currentPrice, '$') }}
            </div>
            <div>{{ formatPrice(summary.currentPriceInBaseCurrency, '₽') }} </div>
          </template>
        </Column>
        <Column class="font-bold" colspan="4">
          <template #footer>
            <div :class="[summary.profit > 0 ? 'text-green-600' : 'text-red-700']">
              <div v-if="!summary.isBaseCurrency">
                {{ formatPrice(summary.profit, '$') }}
              </div>
              <div>{{ formatPrice(summary.profitInBaseCurrency, '₽') }}</div>
              <div class="text-xs">
                ({{ formatPercent(summary.profitPercent) }})
              </div>
            </div>
          </template>
        </Column>
      </Row>
    </ColumnGroup>

    <template #expansion="slotProps">
      <assets-sub-table :items="slotProps.data.deals" :hide-actions="hideActions" />
    </template>
  </DataTable>
</template>

