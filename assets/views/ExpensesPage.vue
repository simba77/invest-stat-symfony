<script setup lang="ts">
import PageComponent from "../components/PageComponent.vue";
import {PencilIcon, XCircleIcon, PlusCircleIcon} from "@heroicons/vue/24/outline";
import StatCard from "@/components/Cards/StatCard.vue";
import {useModal} from "@/composable/useModal";
import DeleteExpenseCategoryModal from "@/components/Expenses/DeleteExpenseCategoryModal.vue";
import {useExpenses} from "@/composable/useExpenses";
import DeleteExpenseModal from "@/components/Expenses/DeleteExpenseModal.vue";

const modal = useModal();
const expenses = useExpenses()

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

</script>

<template>
  <page-component title="Expenses">
    <template v-if="!expenses.loadingSummary.value && expenses.summary.value">
      <div class="text-xl mb-3">Summary</div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4 mb-5">
        <stat-card
          v-for="(card, i) in expenses.summary.value"
          :key="i"
          :name="card.name"
          :total="card.total"
        />
      </div>
    </template>

    <div class="mb-4">
      <router-link :to="{name: 'CreateCategory'}" class="btn btn-primary">Create Category</router-link>
    </div>
    <table class="simple-table white-header">
      <thead>
      <tr>
        <th>Name</th>
        <th>Sum</th>
        <th class="flex justify-end">Actions</th>
      </tr>
      </thead>
      <tbody>
      <template v-for="(cat, index) in expenses.expenses.value.items" :key="index">
        <tr class="table-subtitle">
          <td colspan="2">{{ cat.name }}</td>
          <td class="flex justify-end items-center">
            <template v-if="cat.id">
              <router-link :to="{name: 'AddExpense', params: {category: cat.id}}" class="text-gray-300 hover:text-gray-600 mr-2">
                <plus-circle-icon class="h-5 w-5"/>
              </router-link>
              <router-link :to="{name: 'EditCategory', params: {id: cat.id}}" class="text-gray-300 hover:text-gray-600 mr-2">
                <pencil-icon class="h-5 w-5"/>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="confirmDeleteCategory(cat)"
              >
                <x-circle-icon class="h-5 w-5"/>
              </button>
            </template>
          </td>
        </tr>
        <template v-if="cat.expenses?.length > 0">
          <tr v-for="(expense, i) in cat.expenses" :class="[expense.isTotal ? 'font-bold' : '']" :key="i">
            <td :class="[expense.isSubTotal || expense.isTotal ? 'text-right' : '']">{{ expense.name }}</td>
            <td>{{ new Intl.NumberFormat('ru-RU').format(expense.sum) }} {{ expense.currency }}</td>
            <td class="table-actions">
              <template v-if="expense.id">
                <div class="flex justify-end items-center show-on-row-hover">
                  <router-link :to="{name: 'EditExpense', params: {id: expense.id, category: cat.id}}" class="text-gray-300 hover:text-gray-600 mr-2">
                    <pencil-icon class="h-5 w-5"/>
                  </router-link>
                  <button
                    type="button"
                    class="text-gray-300 hover:text-red-500"
                    @click="confirmDeleteExpense(expense)"
                  >
                    <x-circle-icon class="h-5 w-5"/>
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
