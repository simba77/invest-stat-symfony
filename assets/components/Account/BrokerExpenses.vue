<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useBrokerSync } from '@/composable/useBrokerSync'
import { useNumbers } from '@/composable/useNumbers'
import type { BrokerExpenses } from '@/types/brokerSync'

const props = defineProps<{
  accountId: number
}>()

const brokerSync = useBrokerSync()
const { formatPrice } = useNumbers()
const expenses = ref<BrokerExpenses | null>(null)

async function load() {
  expenses.value = await brokerSync.getExpenses(props.accountId)
}

defineExpose({ load })

onMounted(load)
</script>

<template>
  <div
    v-if="expenses && expenses.items.length > 0"
    class="mb-4"
  >
    <div class="d-flex align-items-center mb-3">
      <div class="fw-bold text-muted">
        Account Expenses
      </div>
      <div class="flex-grow-1 ms-3 border-bottom" />
    </div>
    <p class="small text-muted">
      Fees and taxes the broker charged outside of trades. Trade commissions are in the deals.
    </p>
    <table class="table simple-table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th>Year</th>
          <th>Expense</th>
          <th class="text-end">
            Amount
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="item in expenses.items"
          :key="item.year + item.type"
        >
          <td>{{ item.year }}</td>
          <td>{{ item.name }}</td>
          <td class="text-end">
            {{ formatPrice(Number(item.amount)) }}
          </td>
        </tr>
      </tbody>
      <tfoot class="custom-footer">
        <tr class="fw-bold">
          <td colspan="2" class="text-end">
            Total:
          </td>
          <td class="text-end">
            {{ formatPrice(Number(expenses.total)) }}
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>
