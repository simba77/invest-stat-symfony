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
  <table class="table table-dark table-hover align-middle" style="min-width: 50rem">
    <thead>
      <tr>
        <th>Name</th>
        <th class="text-end">
          Quantity
        </th>
        <th>
          Buy Price
        </th>
        <th>
          Current Price
        </th>
        <th class="text-end">
          Target Price
        </th>
        <th class="text-end">
          <div class="fw-bold">
            Profit
          </div>
          <span class="text-muted small">(percent, commission)</span>
        </th>
        <th class="text-end">
          Target Profit
        </th>
        <th class="text-end">
          Percent
        </th>
        <th v-if="!hideActions" class="text-end">
          Actions
        </th>
      </tr>
    </thead>

    <tbody>
      <tr
        v-for="data in items"
        :key="data.id"
        class="position-relative"
      >
        <!-- Name -->
        <td>
          <div
            v-tooltip="'Created: ' + data.createdAt"
            class="fw-bold"
          >
            <lock-closed-icon
              v-if="data.isBlocked"
              class="h-3 w-3 me-1"
            />
            {{ data.shortName }}
          </div>
          <div class="text-muted small">
            {{ data.ticker }}
          </div>
        </td>

        <!-- Quantity -->
        <td class="text-end">
          {{ data.quantity }}
        </td>

        <!-- Buy Price -->
        <td class="text-nowrap">
          <div>{{ formatPrice(data.buyPrice, data.currency) }}</div>
          <div class="text-muted small">
            {{ formatPrice(data.fullBuyPrice, data.currency) }}
          </div>
        </td>

        <!-- Current Price -->
        <td>
          <div class="text-nowrap">
            {{ formatPrice(data.currentPrice, data.currency) }}
            <span
              v-tooltip="'Prev price: ' + formatPrice(data.prevPrice, data.currency)"
              :class="data.dailyProfit > 0 ? 'text-success' : 'text-danger'"
            >
              (
              {{ data.dailyProfit > 0 ? '+' : '-' }}
              {{ getPercent(data.dailyProfit, data.prevPrice) }},
              {{ formatPrice(Math.abs(data.dailyProfit), data.currency) }}
              )
            </span>
          </div>

          <div class="text-muted small">
            {{ formatPrice(data.fullCurrentPrice, data.currency) }}
            <span
              v-tooltip="'Prev full price: ' + formatPrice(data.fullPrevPrice, data.currency)"
              :class="data.fullDailyProfit > 0 ? 'text-success' : 'text-danger'"
            >
              ({{ formatPriceWithSign(data.fullDailyProfit, data.currency) }})
            </span>
          </div>
        </td>

        <!-- Target Price -->
        <td class="text-end text-nowrap">
          <template v-if="data.targetPrice !== 0">
            <div>{{ formatPrice(data.targetPrice, data.currency) }}</div>
            <div class="text-muted small">
              {{ formatPrice(data.fullTargetPrice, data.currency) }}
            </div>
          </template>
          <template v-else>
            &mdash;
          </template>
        </td>

        <!-- Profit -->
        <td class="text-end text-nowrap">
          <div :class="data.profit > 0 ? 'text-success' : 'text-danger'">
            <div>{{ formatPriceWithSign(data.profit, data.currency) }}</div>
            <div class="small">
              ({{ formatPercent(data.profitPercent) }},
              {{ formatPrice(data.commission, data.currency) }})
            </div>
          </div>
        </td>

        <!-- Target Profit -->
        <td class="text-end text-nowrap">
          <template v-if="data.targetProfit > 0">
            {{ formatPrice(data.targetProfit, data.currency) }}
            <div class="small">
              (
              {{ formatPrice(data.fullTargetProfit, data.currency) }},
              {{ formatPercent(data.fullTargetProfitPercent) }}
              )
            </div>
          </template>
          <template v-else>
            &mdash;
          </template>
        </td>

        <!-- Percent -->
        <td class="text-end">
          {{ formatPercent(data.percent) }}
        </td>

        <!-- Actions -->
        <td v-if="!hideActions" class="text-end">
          <div class="d-flex justify-content-end gap-2 show-on-row-hover">
            <router-link
              :to="{ name: 'EditAsset', params: { account: data.accountId, id: data.id } }"
              class="text-muted hover-opacity"
              title="Edit"
            >
              <pencil-icon class="h-5 w-5" />
            </router-link>

            <button
              type="button"
              class="btn btn-link p-0 text-muted hover-opacity"
              title="Sell"
              @click.prevent="showSellModal(data)"
            >
              <banknotes-icon class="h-5 w-5" />
            </button>

            <button
              type="button"
              class="btn btn-link p-0 text-danger"
              title="Delete"
              @click="deleteDeal(data)"
            >
              <x-circle-icon class="h-5 w-5" />
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>
