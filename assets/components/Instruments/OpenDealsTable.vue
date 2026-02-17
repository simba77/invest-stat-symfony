<script setup lang="ts">
import {LockClosedIcon, PencilIcon, XCircleIcon, BanknotesIcon} from "@heroicons/vue/24/outline";
import type {Deal} from "@/types/account";
import {useModal} from "@/composable/useModal";
import DeleteDealModal from "@/components/Account/DeleteDealModal.vue";
import SellModal from "@/components/Modals/SellModal.vue";
import {useNumbers} from "@/composable/useNumbers";
import {MoveRight} from 'lucide-vue-next';

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
  <table class="table simple-table table-dark table-hover align-middle" style="min-width: 50rem">
    <thead>
      <tr>
        <th>Дата открытия</th>
        <th>Счет</th>
        <th class="text-end">
          Кол-во
        </th>
        <th>
          Цена
        </th>
        <th class="text-end">
          <div class="fw-bold">
            Прибыль
          </div>
          <span class="text-muted small">(процент, комиссия)</span>
        </th>
        <th class="text-end">
          Цель
        </th>
        <th class="text-end">
          Целевая прибыль
        </th>
        <th v-if="!hideActions" class="text-end">
          Действия
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
          <lock-closed-icon
            v-if="data.isBlocked"
            class="h-3 w-3 me-1"
          />
          <div>{{ data.createdAt }}</div>
        </td>

        <!-- Account name -->
        <td>{{ data.accountName }}</td>

        <!-- Quantity -->
        <td class="text-end">
          {{ data.quantity }}
        </td>

        <!-- Buy Price -->
        <td>
          <div class="d-flex align-items-center">
            <div class="text-nowrap">
              {{ formatPrice(data.buyPrice, data.currency) }}
            </div>
            <div class="px-2">
              <move-right :size="15" />
            </div>
            <div class="text-nowrap">
              {{ formatPrice(data.currentPrice, data.currency) }}
            </div>
          </div>
          <div class="d-flex align-items-center">
            <div class="text-xs text-muted">
              {{ formatPrice(data.fullBuyPrice, data.currency) }}
            </div>
            <div class="px-2">
              <move-right :size="12" />
            </div>
            <div class="text-xs text-muted">
              {{ formatPrice(data.fullCurrentPrice, data.currency) }}
            </div>
          </div>
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
