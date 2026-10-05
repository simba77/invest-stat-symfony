<script setup lang="ts">
import {onMounted, ref} from 'vue'
import {Pen, Undo2} from 'lucide-vue-next'
import PaginationComponent from '@/components/Common/PaginationComponent.vue'
import PreloaderComponent from '@/components/Common/PreloaderComponent.vue'
import {useAccountOperations} from '@/composable/useAccountOperations'
import useAccounts from '@/composable/useAccounts'
import {useModal} from '@/composable/useModal'
import CorrectSaleModal from '@/components/Account/CorrectSaleModal.vue'
import {apiErrorMessage} from '@/utils/api-error'
import {useNumbers} from '@/composable/useNumbers'
import useAsync from '@/utils/use-async'
import type {ManualOperation} from '@/types/journal'
import type {PaginatedResponse} from '@/types/pagination'

const props = defineProps<{
  accountId: number
}>()

const {getOperations, cancelOperation} = useAccountOperations()
const accounts = useAccounts()
const modal = useModal()
const {formatPrice} = useNumbers()
const operations = ref<PaginatedResponse<ManualOperation> | null>(null)

const {loading, run: load} = useAsync(async (page: number) => {
  operations.value = await getOperations(props.accountId, page, 20)
})

const currencySymbols: Record<string, string> = {RUB: '₽', USD: '$', EUR: '€', CNY: '¥'}

function currency(code: string | null): string {
  return code ? (currencySymbols[code] ?? code) : ''
}

function title(operation: ManualOperation): string {
  switch (operation.type) {
    case 'buy':
      return 'Buy'
    case 'short':
      return 'Short sale'
    case 'close':
      return 'Close'
    case 'block':
      return 'Block'
    case 'unblock':
      return 'Unblock'
    case 'cash_adjustment':
      return 'Cash adjustment'
    case 'block_cash':
      return Number(operation.amount) < 0 ? 'Unblock cash' : 'Block cash'
  }
}

// A bond is quoted in percent of its nominal, a future in points
function price(operation: ManualOperation, value: string | null): string {
  if (value === null) {
    return ''
  }
  if (operation.instrumentType === 'bond') {
    return Number(value) + '%'
  }
  if (operation.instrumentType === 'future') {
    return formatPrice(Number(value), 'pt')
  }

  return formatPrice(Number(value), currency(operation.currency))
}

function date(value: string): string {
  return new Date(value).toLocaleString('ru-RU', {dateStyle: 'short', timeStyle: 'short'})
}

// The deals and the cash of the account follow from the journal
function reload() {
  load(operations.value?.pagination.page ?? 1)
  accounts.getAccount(props.accountId)
}

async function cancel(operation: ManualOperation) {
  if (!confirm('Cancel "' + title(operation) + '" of ' + date(operation.executedAt) + '? The deals and the cash are rebuilt without it.')) {
    return
  }
  try {
    await cancelOperation(props.accountId, operation.id)
    reload()
  } catch (reason) {
    alert(apiErrorMessage(reason))
  }
}

function edit(operation: ManualOperation) {
  modal.open({
    component: CorrectSaleModal,
    modelValue: {accountId: props.accountId, operation, onSaved: reload},
  })
}

defineExpose({reload})

onMounted(() => load(1))
</script>

<template>
  <div class="mb-4">
    <p class="small text-muted">
      The deals and the cash of the account follow from these operations, the latest first.
      Purchases are corrected or deleted through their deals; a sale can be corrected, and a sale,
      a block or a cash operation cancelled.
    </p>
    <preloader-component v-if="loading && !operations" />
    <template v-if="operations">
      <div
        v-if="operations.items.length === 0"
        class="text-muted small"
      >
        No operations yet
      </div>
      <div
        v-else
        class="table-responsive"
      >
        <table class="table simple-table table-dark table-hover align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Operation</th>
              <th>Security</th>
              <th class="text-end">
                Quantity
              </th>
              <th class="text-end">
                Price
              </th>
              <th class="text-end">
                Commission
              </th>
              <th class="text-end">
                Amount
              </th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="operation in operations.items"
              :key="operation.id"
            >
              <td class="text-nowrap">
                {{ date(operation.executedAt) }}
              </td>
              <td>{{ title(operation) }}</td>
              <td>
                <template v-if="operation.ticker">
                  <div>{{ operation.name ?? operation.ticker }}</div>
                  <div class="small text-muted">
                    {{ operation.ticker }}
                    <template v-if="operation.lotOpenedAt">
                      · lot of {{ date(operation.lotOpenedAt) }} at {{ price(operation, operation.lotPrice) }}
                    </template>
                  </div>
                </template>
              </td>
              <td class="text-end">
                {{ operation.quantity ?? '' }}
              </td>
              <td class="text-end text-nowrap">
                {{ price(operation, operation.price) }}
              </td>
              <td class="text-end text-nowrap">
                <template v-if="operation.commission !== null">
                  {{ formatPrice(Number(operation.commission), currency(operation.currency)) }}
                </template>
              </td>
              <td class="text-end text-nowrap">
                <template v-if="operation.amount !== null">
                  {{ formatPrice(Number(operation.amount), currency(operation.currency)) }}
                </template>
              </td>
              <td class="text-end text-nowrap">
                <button
                  v-if="operation.canEdit"
                  type="button"
                  class="btn btn-link p-0 me-2"
                  title="Correct the price and the date"
                  @click="edit(operation)"
                >
                  <pen :size="18" />
                </button>
                <button
                  v-if="operation.canCancel"
                  type="button"
                  class="btn btn-link-danger p-0"
                  title="Cancel the operation"
                  @click="cancel(operation)"
                >
                  <undo-2 :size="18" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination-component
        :page="operations.pagination.page"
        :total-pages="operations.pagination.totalPages"
        :disabled="loading"
        @change="load"
      />
    </template>
  </div>
</template>
