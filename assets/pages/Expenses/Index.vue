<script setup lang="ts">
import PageComponent from "../../components/PageComponent.vue";
import { Pen, CircleX, CirclePlus } from 'lucide-vue-next';
import StatCard from "@/components/Cards/StatCard.vue";
import {useModal} from "@/composable/useModal";
import DeleteExpenseCategoryModal from "@/components/Expenses/DeleteExpenseCategoryModal.vue";
import {useExpenses} from "@/composable/useExpenses";
import DeleteExpenseModal from "@/components/Expenses/DeleteExpenseModal.vue";
import { useNumbers } from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";

const modal = useModal();
const expenses = useExpenses()
const {formatPrice} = useNumbers()
const {setPageTitle} = usePage()

expenses.getExpenses()
expenses.getSummary()

function confirmDeleteCategory(item: any) {
  modal.open({
    component: DeleteExpenseCategoryModal,
    modelValue: {
      id: item.id,
      title: 'Deletion confirmation',
      text: 'Are you sure you want to delete &quot;<b>' + item.name + '</b>&quot;?',
    }
  })
}

function confirmDeleteExpense(item: any) {
  modal.open({
    component: DeleteExpenseModal,
    modelValue: {
      id: item.id,
      title: 'Deletion confirmation',
      text: 'Are you sure you want to delete &quot;<b>' + item.name + '</b>&quot;?',
    }
  })
}

setPageTitle('Expenses')

</script>

<template>
  <page-component title="Expenses">
    <template v-if="!expenses.loadingSummary.value && expenses.summary.value">
      <div class="fz-xl mb-3">
        Summary
      </div>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 cards-row mb-4">
        <div
          v-for="(card, i) in expenses.summary.value"
          :key="i"
          class="col"
        >
          <stat-card
            :name="card.name"
            :total="card.total"
          />
        </div>
      </div>
    </template>

    <div class="mb-4">
      <router-link
        :to="{name: 'CreateCategory'}"
        class="btn btn-primary"
      >
        Create Category
      </router-link>
    </div>
    <table class="simple-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Sum</th>
          <th class="text-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <template
          v-for="(cat, index) in expenses.expenses.value.items"
          :key="index"
        >
          <tr class="table-subtitle">
            <td colspan="2">
              {{ cat.name }}
            </td>
            <td class="d-flex justify-content-end align-items-center">
              <template v-if="cat.id">
                <router-link
                  :to="{name: 'AddExpense', params: {category: cat.id}}"
                  class="btn btn-link p-0 me-2"
                >
                  <circle-plus :size="20" />
                </router-link>
                <router-link
                  :to="{name: 'EditCategory', params: {id: cat.id}}"
                  class="btn btn-link p-0 me-2"
                >
                  <pen :size="20" />
                </router-link>
                <button
                  type="button"
                  class="btn btn-link btn-link-danger"
                  @click="confirmDeleteCategory(cat)"
                >
                  <circle-x :size="20" />
                </button>
              </template>
            </td>
          </tr>
          <template v-if="cat.expenses?.length > 0">
            <tr
              v-for="(expense, i) in cat.expenses"
              :key="i"
              :class="[expense.isTotal ? 'fw-bold' : '']"
            >
              <td :class="[expense.isSubTotal || expense.isTotal ? 'text-end' : '']">
                {{ expense.name }}
              </td>
              <td>{{ formatPrice(expense.sum, expense.currency) }}</td>
              <td class="table-actions">
                <template v-if="expense.id">
                  <div class="justify-content-end align-items-center show-on-row-hover">
                    <router-link
                      :to="{name: 'EditExpense', params: {id: expense.id, category: cat.id}}"
                      class="btn btn-link p-0 me-2 border-0"
                    >
                      <pen :size="20" />
                    </router-link>
                    <button
                      type="button"
                      class="btn btn-link btn-link-danger"
                      @click="confirmDeleteExpense(expense)"
                    >
                      <circle-x :size="20" />
                    </button>
                  </div>
                </template>
              </td>
            </tr>
          </template>
        </template>
      </tbody>
    </table>
  </page-component>
</template>
