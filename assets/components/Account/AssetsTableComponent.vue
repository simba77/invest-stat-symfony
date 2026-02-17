<script setup lang="ts">
import { Lock, Banknote, ArrowRight, ChevronDown } from "lucide-vue-next";
import type { AssetsGroup, AssetsGroupData, GroupSummary } from "@/types/account";
import { useDealsGroup } from "@/composable/useDealsGroup";
import { useNumbers } from "@/composable/useNumbers";
import SellModal from "@/components/Modals/SellModal.vue";
import { useModal } from "@/composable/useModal";
import { useRoute } from "vue-router";
import AssetsSubTable from "@/components/Account/AssetsSubTable.vue";

const modal = useModal();
const route = useRoute();
const dealsGroup = useDealsGroup();

const { formatPrice, formatPercent, formatPriceWithSign, getPercent } = useNumbers();

defineProps<{
  assets: { [key: string]: AssetsGroupData },
  summary: GroupSummary,
  hideActions?: boolean
}>();

function toggleGroup(ticker: string) {
  const opened = dealsGroup.openedGroups.value;
  opened[ticker] = !opened[ticker];
}

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
  });
}
</script>

<template>
  <table class="table simple-table table-dark table-hover align-middle" style="min-width: 60rem">
    <thead>
      <tr>
        <th style="width: 40px;" />
        <th>Name</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Target Price</th>
        <th>
          <div class="fw-bold">
            Profit
          </div>
          <small class="text-muted">(percent, commission)</small>
        </th>
        <th>Target Profit</th>
        <th>Percent</th>
        <th v-if="!hideActions" class="text-end">
          Actions
        </th>
      </tr>
    </thead>

    <tbody>
      <template v-for="group in Object.values(assets)" :key="group.groupData.ticker">
        <!-- Main row -->
        <tr>
          <td>
            <button
              class="btn btn-sm btn-link p-0 text-muted"
              @click="toggleGroup(group.groupData.ticker)"
            >
              <ChevronDown
                :size="20"
                :class="{ 'rotate-180': dealsGroup.openedGroups.value[group.groupData.ticker] }"
              />
            </button>
          </td>

          <!-- Name -->
          <td>
            <div class="fw-bold d-flex align-items-center">
              <Lock
                v-if="group.groupData.isBlocked"
                :size="12"
                class="me-1"
              />
              <router-link
                :to="`/instruments/show/${group.groupData.instrumentType}/${group.groupData.instrumentId}`"
              >
                {{ group.groupData.shortName }}
              </router-link>
            </div>
            <small class="text-muted">
              {{ group.groupData.ticker }}
            </small>
          </td>

          <td>{{ group.groupData.quantity }}</td>

          <!-- Price -->
          <td>
            <div class="d-flex align-items-center">
              {{ formatPrice(group.groupData.buyPrice, group.groupData.currency) }}
              <ArrowRight :size="14" class="mx-2 text-muted" />
              <div class="text-nowrap">
                {{ formatPrice(group.groupData.currentPrice, group.groupData.currency) }}
                <span v-tooltip="'Prev price: ' + formatPrice(group.groupData.prevPrice, group.groupData.currency)" :class="group.groupData.dailyProfit > 0 ? 'text-success' : 'text-danger'">
                  ({{ group.groupData.dailyProfit > 0 ? '+' : '-' }}{{ getPercent(group.groupData.dailyProfit, group.groupData.prevPrice) }}, {{ formatPrice(Math.abs(group.groupData.dailyProfit), group.groupData.currency) }})
                </span>
              </div>
            </div>

            <small class="d-flex align-items-center text-muted">
              {{ formatPrice(group.groupData.fullBuyPrice, group.groupData.currency) }}
              <ArrowRight :size="12" class="mx-2 text-muted" />
              <div class="text-nowrap">
                {{ formatPrice(group.groupData.fullCurrentPrice, group.groupData.currency) }}
                <span v-tooltip="'Prev full price: ' + formatPrice(group.groupData.fullPrevPrice, group.groupData.currency)" :class="group.groupData.fullDailyProfit > 0 ? 'text-success' : 'text-danger'">
                  ({{ formatPriceWithSign(group.groupData.fullDailyProfit, group.groupData.currency) }})</span>
              </div>
            </small>
          </td>

          <!-- Target Price -->
          <td>
            <template v-if="group.groupData.targetPrice < 0 || group.groupData.targetPrice > 0">
              <div>{{ formatPrice(group.groupData.targetPrice, group.groupData.currency) }}</div>
              <div class="text-xs text-muted">
                {{ formatPrice(group.groupData.fullTargetPrice, group.groupData.currency) }}
              </div>
            </template>
            <template v-else>
              &mdash;
            </template>
          </td>

          <!-- Profit -->
          <td>
            <div :class="group.groupData.profit > 0 ? 'text-success' : 'text-danger'">
              {{ formatPriceWithSign(group.groupData.profit, group.groupData.currency) }}
              <div class="small">
                ({{ formatPercent(group.groupData.profitPercent) }}, {{ formatPrice(group.groupData.commission, group.groupData.currency) }})
              </div>
            </div>
          </td>

          <!-- Target Profit -->
          <td>
            <template v-if="group.groupData.targetProfit > 0">
              {{ formatPrice(group.groupData.targetProfit, group.groupData.currency) }}
              <div class="text-xs">
                ({{ formatPrice(group.groupData.fullTargetProfit, group.groupData.currency) }}, {{ formatPercent(group.groupData.targetProfitPercent) }})
              </div>
            </template>
            <template v-else>
              &mdash;
            </template>
          </td>

          <!-- Percent -->
          <td>
            {{ formatPercent(group.groupData.percent) }}
          </td>

          <!-- Actions -->
          <td v-if="!hideActions" class="text-end">
            <button
              class="btn btn-sm btn-link text-muted"
              @click.stop="showSellModal(group.groupData)"
            >
              <Banknote :size="20" />
            </button>
          </td>
        </tr>

        <!-- Expanded row -->
        <tr
          v-if="dealsGroup.openedGroups.value[group.groupData.ticker]"
          class="bg-dark-subtle"
        >
          <td colspan="9" class="p-0">
            <assets-sub-table
              class="mb-0 sub-table"
              :items="group.deals"
              :hide-actions="hideActions"
            />
          </td>
        </tr>
      </template>
    </tbody>

    <!-- Footer -->
    <tfoot class="custom-footer">
      <tr class="fw-bold">
        <td colspan="3" class="text-end">
          Subtotal:
        </td>
        <td>
          {{ formatPrice(summary.buyPriceInBaseCurrency, '₽') }}
        </td>
        <td colspan="2">
          {{ formatPrice(summary.currentPriceInBaseCurrency, '₽') }}
        </td>
        <td
          colspan="3"
          :class="summary.profit > 0 ? 'text-success' : 'text-danger'"
        >
          {{ formatPrice(summary.profitInBaseCurrency, '₽') }}
          <div class="small">
            ({{ formatPercent(summary.profitPercent) }})
          </div>
        </td>
      </tr>
    </tfoot>
  </table>
</template>


