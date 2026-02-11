<script setup lang="ts">
import { Banknote } from 'lucide-vue-next'
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
  <form class="sell-modal" @submit.prevent="sell()">
    <div class="modal-body">
      <div class="d-flex align-items-start gap-3">
        <div
          class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 flex-shrink-0"
          style="width: 40px; height: 40px;"
        >
          <Banknote class="text-success" :size="22" />
        </div>
        <div class="flex-grow">
          <h3 class="modal-title mb-4 mt-1">
            Sell Asset: {{ modelValue.name }}
          </h3>
          <div class="form-stack">
            <input-text
              v-model.trim="security.price"
              :error="validationErrors"
              :required="true"
              autocomplete="off"
              name="price"
              label="Sell Price"
              type="number"
              placeholder="Enter an account name"
            />
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
    </div>
    <!-- Footer -->
    <div class="modal-footer">
      <button
        type="button"
        class="btn btn-secondary"
        @click="modal.close()"
      >
        Cancel
      </button>

      <button
        type="submit"
        class="btn btn-danger"
      >
        Confirm
      </button>
    </div>
  </form>
</template>
<style scoped>
.form-stack {
  min-height: 100px;
}
</style>

