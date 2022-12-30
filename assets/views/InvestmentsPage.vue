<template>
  <page-component title="Investments and Results">
    <div class="mb-4">
      <router-link :to="{name: 'AddDeposit'}" class="btn btn-primary">Add Deposit</router-link>
    </div>
    <table class="simple-table">
      <thead>
      <tr>
        <th>Date</th>
        <th>Sum</th>
        <th>Account</th>
        <th class="flex justify-end">Actions</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(deposit, index) in deposits.data" :key="index">
        <td>{{ deposit.date }}</td>
        <td>{{ helpers.formatPrice(deposit.sum) }} {{ deposit.currency }}</td>
        <td>{{ deposit.account }}</td>
        <td class="table-actions">
          <template v-if="deposit.id">
            <div class="flex justify-end items-center show-on-row-hover">
              <router-link class="text-gray-300 hover:text-gray-900 mr-3" :to="{name: 'EditDeposit', params: {id: deposit.id}}">
                <pencil-icon class="h-5 w-5"></pencil-icon>
              </router-link>
              <button
                type="button"
                class="text-gray-300 hover:text-red-500"
                @click="openConfirmModal(deposit)"
              >
                <x-circle-icon class="h-5 w-5"></x-circle-icon>
              </button>
            </div>
          </template>
        </td>
      </tr>
      <tr v-if="deposits.data?.length < 1">
        <td colspan="3" class="text-center">The list is empty</td>
      </tr>
      </tbody>
    </table>
  </page-component>

  <base-modal ref="deleteConfirmationModal">
    <confirm-modal
      :close="closeModal"
      :confirm="confirmDeletion"
      title="Deletion confirmation"
      text="Are you sure you want to delete it?"
    ></confirm-modal>
  </base-modal>
</template>

<script lang="ts">
import PageComponent from "../components/PageComponent.vue";
import {XCircleIcon, PencilIcon} from "@heroicons/vue/outline";
import axios from "axios";
import helpers from "../helpers";
import BaseModal from "@/components/Modals/BaseModal.vue";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";

export default {
  name: "InvestmentsPage",
  components: {PageComponent, XCircleIcon, PencilIcon, BaseModal, ConfirmModal},
  data() {
    return {
      helpers,
      loading: true,
      deleting: false,
      deleteItem: {
        type: '',
        data: {},
      },
      deposits: {},
    }
  },
  mounted() {
    this.getDeposits();
  },
  methods: {
    openConfirmModal(item: any) {
      this.deleteItem.data = item;
      setTimeout(() => {
        this.$refs.deleteConfirmationModal.openModal();
      });
    },
    closeModal() {
      this.$refs.deleteConfirmationModal.closeModal();
    },
    confirmDeletion() {
      this.deleteDeposit(this.deleteItem.data.id)
        .finally(() => {
          this.closeModal();
        });
    },
    getDeposits() {
      this.loading = true;
      axios.get('/api/investments/deposits')
        .then((response) => {
          this.deposits = response.data;
        })
        .catch(() => {
          alert('An error has occurred');
        })
        .finally(() => {
          this.loading = false;
        })
    },
    deleteDeposit(id: number) {
      this.deleting = true;
      return new Promise((resolve, reject) => {
        axios.post('/api/investments/deposits/delete/' + id)
          .then(() => {
            this.getDeposits();
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
  }
}
</script>
