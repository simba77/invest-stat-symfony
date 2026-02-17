<script setup lang="ts">
import { useDealsGroup } from "@/composable/useDealsGroup";
import { ClosedDealsListItem, ClosedDealsSummary } from "@/types/analytics";
import { useNumbers } from "@/composable/useNumbers";
import { Lock, ChevronDown } from "lucide-vue-next";
import ClosedDealsSubTable from "@/components/Analytics/ClosedDealsSubTable.vue";

const { formatPrice, formatPercent, formatPriceWithSign } = useNumbers();

defineProps<{
  assets: ClosedDealsListItem[],
  summary: ClosedDealsSummary
}>();

const dealsGroup = useDealsGroup();

function toggleGroup(ticker: string) {
  dealsGroup.toggleGroup(ticker);
}

function isGroupOpened(ticker: string) {
  return !!dealsGroup.openedGroups.value[ticker];
}
</script>

<template>
  <table class="table table-dark table-hover align-middle simple-table" style="min-width: 60rem">
    <thead>
      <tr>
        <th style="width: 40px;" />
        <th>Name</th>
        <th class="text-end">
          Quantity
        </th>
        <th>Buy Price</th>
        <th>Sell Price</th>
        <th class="text-end">
          <div class="fw-bold">
            Profit
          </div>
          <small class="text-muted">(percent, commission)</small>
        </th>
      </tr>
    </thead>

    <tbody>
      <template v-for="asset in assets" :key="asset.groupData.ticker">
        <!-- Main row -->
        <tr>
          <td>
            <button
              class="btn btn-sm btn-link p-0 text-muted"
              @click="toggleGroup(asset.groupData.ticker)"
            >
              <chevron-down
                :size="20"
                :class="{ 'rotate-180': isGroupOpened(asset.groupData.ticker) }"
              />
            </button>
          </td>
          <td>
            <div class="d-flex fw-bold align-items-center">
              <lock v-if="asset.groupData.isBlocked" :size="12" class="me-1" />
              {{ asset.groupData.shortName }}
            </div>
            <small class="text-muted">{{ asset.groupData.ticker }}</small>
          </td>
          <td class="text-end">
            {{ asset.groupData.quantity }}
          </td>
          <td>
            <div>{{ formatPrice(asset.groupData.buyPrice, asset.groupData.currency) }}</div>
            <div class="text-xs text-muted">
              {{ formatPrice(asset.groupData.fullBuyPrice, asset.groupData.currency) }}
            </div>
          </td>
          <td>
            <div>{{ formatPrice(asset.groupData.sellPrice, asset.groupData.currency) }}</div>
            <div class="text-xs text-muted">
              {{ formatPrice(asset.groupData.fullSellPrice, asset.groupData.currency) }}
            </div>
          </td>
          <td class="text-end" :class="asset.groupData.profit > 0 ? 'text-success' : 'text-danger'">
            <div>{{ formatPriceWithSign(asset.groupData.profit, asset.groupData.currency) }}</div>
            <div class="text-xs">
              ({{ formatPercent(asset.groupData.profitPercent) }}, {{ formatPrice(asset.groupData.commission, asset.groupData.currency) }})
            </div>
          </td>
        </tr>

        <!-- Expanded row -->
        <tr v-if="isGroupOpened(asset.groupData.ticker)" class="bg-dark-subtle">
          <td colspan="6" class="p-0">
            <closed-deals-sub-table :items="asset.deals" />
          </td>
        </tr>
      </template>
    </tbody>

    <tfoot class="custom-footer">
      <tr class="fw-bold">
        <td colspan="3" class="text-end">
          Total (base currency):
        </td>
        <td>{{ formatPrice(summary.buyPrice, '₽') }}</td>
        <td>{{ formatPrice(summary.sellPrice, '₽') }}</td>
        <td :class="summary.profit > 0 ? 'text-success' : 'text-danger'">
          {{ formatPrice(summary.profit, '₽') }}
          <div class="text-xs">
            ({{ formatPercent(summary.profitPercent) }})
          </div>
        </td>
      </tr>
    </tfoot>
  </table>
</template>
