<script setup lang="ts">
import { Lock } from "lucide-vue-next";
import type { Deal } from "@/types/account";
import { useNumbers } from "@/composable/useNumbers";

const { formatPrice, formatPercent, formatPriceWithSign } = useNumbers();

defineProps<{
  items: Deal[]
}>();
</script>

<template>
  <table class="table table-dark table-hover align-middle mb-0 sub-table" style="min-width: 50rem">
    <thead>
      <tr>
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
      <tr v-for="item in items" :key="item.id">
        <!-- Name -->
        <td>
          <div class="d-flex fw-bold align-items-center">
            <lock v-if="item.isBlocked" :size="12" class="me-1" />
            {{ item.shortName }}
          </div>
          <small class="text-muted">{{ item.ticker }}</small>
        </td>

        <!-- Quantity -->
        <td class="text-end">
          {{ item.quantity }}
        </td>

        <!-- Buy Price -->
        <td>
          <div>{{ formatPrice(item.buyPrice, item.currency) }}</div>
          <div class="text-xs text-muted">
            {{ formatPrice(item.fullBuyPrice, item.currency) }}
          </div>
        </td>

        <!-- Sell Price -->
        <td>
          <div>{{ formatPrice(item.sellPrice, item.currency) }}</div>
          <div class="text-xs text-muted">
            {{ formatPrice(item.fullSellPrice, item.currency) }}
          </div>
        </td>

        <!-- Profit -->
        <td class="text-end">
          <div :class="item.profit > 0 ? 'text-success' : 'text-danger'">
            <div>{{ formatPriceWithSign(item.profit, item.currency) }}</div>
            <div class="text-xs">
              ({{ formatPercent(item.profitPercent) }}, {{ formatPrice(item.commission, item.currency) }})
            </div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>
