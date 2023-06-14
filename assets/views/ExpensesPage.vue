<template>
  <page-component title="Expenses">
    <template v-if="stat">
      <div class="text-xl mb-3">Summary</div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4 mb-5">
        <stat-card
          v-for="(card, i) in stat.summary"
          :key="i"
          :name="card.name"
          :help-text="card.helpText ?? null"
          :percent="card.percent ?? null"
          :total="card.total"
        ></stat-card>
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
      <template v-for="(cat, index) in expenses.items" :key="index">
        <template v-if="! cat.isTotal">
          <tr class="table-subtitle">
            <td colspan="2">{{ cat.name }}</td>
            <td class="flex justify-end items-center">
              <template v-if="cat.id">
                <router-link :to="{name: 'AddExpense', params: {category: cat.id}}" class="text-gray-300 hover:text-gray-600 mr-2">
                  <plus-circle-icon class="h-5 w-5"></plus-circle-icon>
                </router-link>
                <router-link :to="{name: 'EditCategory', params: {id: cat.id}}" class="text-gray-300 hover:text-gray-600 mr-2">
                  <pencil-icon class="h-5 w-5"></pencil-icon>
                </router-link>
                <button
                  type="button"
                  class="text-gray-300 hover:text-red-500"
                  @click="openConfirmModal(cat, 'category')"
                >
                  <x-circle-icon class="h-5 w-5"></x-circle-icon>
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
                      <pencil-icon class="h-5 w-5"></pencil-icon>
                    </router-link>
                    <button
                      type="button"
                      class="text-gray-300 hover:text-red-500"
                      @click="openConfirmModal(expense, 'expense')"
                    >
                      <x-circle-icon class="h-5 w-5"></x-circle-icon>
                    </button>
                  </div>
                </template>
              </td>
            </tr>
          </template>
        </template>
        <tr class="font-bold" v-else>
          <td class="text-right">{{ cat.name }}</td>
          <td>{{ new Intl.NumberFormat('ru-RU').format(cat.sum) }} {{ cat.currency }}</td>
        </tr>
      </template>
      </tbody>
    </table>
  </page-component>

  <base-modal ref="deleteConfirmationModal">
    <confirm-modal
      :close="closeModal"
      :confirm="confirmDeletion"
      title="Deletion confirmation"
      :text="'Are you sure you want to delete &quot;<b>'+ deleteItem.data.name +'</b>&quot;?'"
    ></confirm-modal>
  </base-modal>
</template>

<script lang="ts">
import PageComponent from "../components/PageComponent.vue";
import axios from "axios";
import {PencilIcon, XCircleIcon, PlusCircleIcon} from "@heroicons/vue/24/outline";
import BaseModal from "@/components/Modals/BaseModal.vue";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import StatCard from "@/components/Cards/StatCard.vue";

export default {
  name: "ExpensesPage",
  components: {StatCard, ConfirmModal, BaseModal, PageComponent, PencilIcon, XCircleIcon, PlusCircleIcon},
  mounted() {
    this.getItems();
    this.getStat();
  },
  data() {
    return {
      loading: true,
      deleting: false,
      stat: {},
      deleteItem: {
        type: '',
        data: {},
      },
      expenses: {},
    }
  },
  methods: {
    openConfirmModal(item: any, type = 'expense') {
      this.deleteItem.type = type;
      this.deleteItem.data = item;
      setTimeout(() => {
        this.$refs.deleteConfirmationModal.openModal();
      });
    },
    closeModal() {
      this.$refs.deleteConfirmationModal.closeModal();
    },
    confirmDeletion() {
      if (this.deleteItem.type === 'category') {
        this.deleteCategory(this.deleteItem.data.id)
          .finally(() => {
            this.closeModal();
          });
      } else {
        this.deleteExpense(this.deleteItem.data.id)
          .finally(() => {
            this.closeModal();
          });
      }
    },
    getItems() {
      this.loading = true;
      axios.get('/api/expenses')
        .then((response) => {
          this.expenses = response.data;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    },
    getStat() {
      this.loading = true;
      axios.get('/api/expenses/summary')
        .then((response) => {
          this.stat = response.data;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    },
    deleteCategory(id: number) {
      this.deleting = true;
      return new Promise((resolve, reject) => {
        axios.post('/api/expenses/delete-category/' + id)
          .then(() => {
            this.getItems();
            resolve({deleted: true});
          })
          .catch(() => {
            alert('An error has occurred');
            reject('An error has occurred');
          })
          .finally(() => {
            this.deleting = false;
          })
      });
    },
    deleteExpense(id: number) {
      this.deleting = true;
      return new Promise((resolve, reject) => {
        axios.post('/api/expenses/delete-expense/' + id)
          .then(() => {
            this.getItems();
            resolve({deleted: true});
          })
          .catch(() => {
            alert('An error has occurred');
            reject('An error has occurred');
          })
          .finally(() => {
            this.deleting = false;
          })
      });
    }
  }
}
</script>
