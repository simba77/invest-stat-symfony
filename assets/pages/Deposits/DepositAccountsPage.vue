<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import { Pencil, XCircle } from "lucide-vue-next";
import {useDepositAccounts} from '@/composable/useDepositAccounts'
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import {DepositAccount} from "@/types/depositAccount";
import {useModal} from "@/composable/useModal";
import DeleteDepositAccountModal from "@/components/Deposits/DeleteDepositAccountModal.vue";

const depositAccount = useDepositAccounts()
const modal = useModal()
const {loading, run: getAccounts} = useAsync(() => depositAccount.getAccounts())

getAccounts()

function deleteAccount(account: DepositAccount) {
  modal.open({
    component: DeleteDepositAccountModal,
    modelValue: {
      id: account.id,
      title: 'Delete Confirmation',
      text: 'Are you sure you want to delete "<b>'+ account.name + '</b>"?',
    }
  })
}

</script>

<template>
  <page-component title="Deposit Accounts">
    <div class="mb-4">
      <router-link
        :to="{name: 'DepositAccountsCreate'}"
        class="btn btn-primary"
      >
        Add Account
      </router-link>
    </div>
    <preloader-component v-if="loading" />
    <table
      v-else
      class="simple-table"
    >
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th class="text-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, index) in depositAccount.accounts.value?.items"
          :key="index"
        >
          <td>{{ item.id }}</td>
          <td>{{ item.name }}</td>
          <td class="table-actions">
            <template v-if="item.id">
              <div class="justify-content-end align-items-center show-on-row-hover">
                <router-link
                  :to="{name: 'DepositAccountsEdit', params: {id: item.id}}"
                  class="text-muted hover-opacity me-2"
                  title="Edit"
                >
                  <pencil :size="20" />
                </router-link>

                <button
                  type="button"
                  class="btn btn-link p-0 btn-link-danger"
                  title="Delete"
                  @click="deleteAccount(item)"
                >
                  <x-circle :size="20" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="depositAccount.accounts?.value?.items?.length === 0">
          <td
            colspan="3"
            class="text-center"
          >
            The list is empty
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4">
      <router-link
        :to="{name: 'Deposits'}"
        class="btn btn-secondary"
      >
        Back
      </router-link>
    </div>
  </page-component>
</template>

