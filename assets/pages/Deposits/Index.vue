<script setup lang="ts">
import PageComponent from "@/components/PageComponent.vue"
import { Pencil, XCircle } from "lucide-vue-next";
import {useDeposits} from '@/composable/useDeposits'
import useAsync from "@/utils/use-async";
import PreloaderComponent from "@/components/Common/PreloaderComponent.vue";
import PaginationComponent from "@/components/Common/PaginationComponent.vue";
import {Deposit} from "@/types/depositAccount";
import {useModal} from "@/composable/useModal";
import DeleteDepositModal from "@/components/Deposits/DeleteDepositModal.vue";
import { useNumbers } from "@/composable/useNumbers";
import {usePage} from "@/composable/usePage";
import {computed, watch} from "vue";
import {useRoute, useRouter} from "vue-router";

const deposits = useDeposits()
const modal = useModal()
const {loading, run: getDeposits} = useAsync(deposits.getDeposits)
const {formatPrice} = useNumbers()
const {setPageTitle} = usePage()
const route = useRoute()
const router = useRouter()

const routePage = computed(() => {
  const value = Number(route.query.page ?? 1)

  if (!Number.isFinite(value) || value < 1) {
    return 1
  }

  return Math.floor(value)
})

watch(routePage, (value) => {
  getDeposits(value).then(() => {
    const actualPage = deposits.deposits.value?.pagination?.page

    if (actualPage && actualPage !== value) {
      router.replace({
        name: 'Deposits',
        query: actualPage > 1 ? {page: String(actualPage)} : {}
      })
    }
  })
}, {immediate: true})

function deleteDeposit(deposit: Deposit) {
  modal.open({
    component: DeleteDepositModal,
    modelValue: {
      id: deposit.id,
      title: 'Delete Confirmation',
      text: 'Are you sure you want to delete the deposit <b>#'+ deposit.id + '</b>?',
    }
  })
}

function changePage(page: number) {
  router.push({
    name: 'Deposits',
    query: page > 1 ? {page: String(page)} : {}
  })
}

setPageTitle('Deposits')

</script>

<template>
  <page-component title="Deposits">
    <div class="mb-4">
      <router-link
        :to="{name: 'DepositCreate'}"
        class="btn btn-primary"
      >
        Add Deposit
      </router-link>
      <router-link
        :to="{name: 'DepositAccounts'}"
        class="btn btn-secondary ms-2"
      >
        Accounts
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
          <th>Date</th>
          <th>Sum</th>
          <th>Type</th>
          <th>Account</th>
          <th class="text-end">
            Actions
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(item, index) in deposits.deposits.value?.items"
          :key="index"
        >
          <td>{{ item.id }}</td>
          <td>{{ item.date }}</td>
          <td>{{ formatPrice(item.sum, '₽') }}</td>
          <td>{{ item.typeName }}</td>
          <td>{{ item.accountName }}</td>
          <td class="table-actions">
            <template v-if="item.id">
              <div class="justify-content-end align-items-center show-on-row-hover">
                <router-link
                  :to="{name: 'DepositEdit', params: {id: item.id}}"
                  class="text-muted hover-opacity me-2"
                  title="Edit"
                >
                  <pencil :size="20" />
                </router-link>

                <button
                  type="button"
                  class="btn btn-link p-0 btn-link-danger"
                  title="Delete"
                  @click="deleteDeposit(item)"
                >
                  <x-circle :size="20" />
                </button>
              </div>
            </template>
          </td>
        </tr>
        <tr v-if="deposits.deposits.value?.items?.length === 0">
          <td
            colspan="6"
            class="text-center"
          >
            The list is empty
          </td>
        </tr>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
      <div class="text-muted small">
        Total: {{ deposits.deposits.value?.pagination?.totalItems ?? 0 }}
      </div>
      <pagination-component
        :page="deposits.deposits.value?.pagination?.page ?? 1"
        :total-pages="deposits.deposits.value?.pagination?.totalPages ?? 1"
        :disabled="loading"
        @change="changePage"
      />
    </div>
  </page-component>
</template>
