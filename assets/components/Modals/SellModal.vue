<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {BanknotesIcon} from '@heroicons/vue/24/outline'
import InputText from "@/components/Forms/InputText.vue";
import {useModal} from "@/composable/useModal";
import {ref} from "vue";
import {useDeals} from "@/composable/useDeals";
import {SellSecurity} from "@/types/account";
import useAsync from "@/utils/use-async";
import useAccounts from "@/composable/useAccounts";

const modal = useModal()
const deals = useDeals()
const accounts = useAccounts()

const props = defineProps<{ modelValue: SellSecurity }>()
const security = ref<SellSecurity>(props.modelValue)

const {run: sellSecurity, validationErrors} = useAsync(() => deals.sell(security.value))

function sell() {
  sellSecurity()
    .then(() => {
      modal.close()
      accounts.getAccount(security.value.accountId)
    })
}
</script>

<template>
  <form @submit.prevent="sell()">
    <div class="bg-white dark:bg-zinc-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
          <banknotes-icon
            class="h-6 w-6 text-green-600"
            aria-hidden="true"
          />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-grow">
          <DialogTitle
            as="h3"
            class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
          >
            Sell Asset: {{ modelValue.name }}
          </DialogTitle>
          <div class="mt-2">
            <input-text
              v-model.trim="security.price"
              :error="validationErrors"
              :required="true"
              autocomplete="off"
              class="mb-3"
              name="price"
              label="Sell Price"
              placeholder="Enter an account name"
            />
          </div>
          <div
            v-if="!security.id"
            class="mt-2"
          >
            <input-text
              v-model="security.quantity"
              :error="validationErrors"
              :required="true"
              type="number"
              class="mb-3"
              name="quantity"
              label="Quantity"
              autocomplete="off"
              placeholder="Enter the Quantity to Sell"
            />
          </div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 dark:bg-zinc-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
      <button
        class="btn btn-danger mr-3 md:mr-0 ml-3"
        type="submit"
      >
        Confirm
      </button>
      <button
        class="btn btn-secondary"
        type="button"
        @click="modal.close()"
      >
        Cancel
      </button>
    </div>
  </form>
</template>


