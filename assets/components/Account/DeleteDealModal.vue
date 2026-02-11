<script setup lang="ts">
import {useModal} from "@/composable/useModal";
import useAsync from "@/utils/use-async";
import {useDeals} from "@/composable/useDeals";
import useAccounts from "@/composable/useAccounts";
import {useRoute} from "vue-router";
import { TriangleAlert } from 'lucide-vue-next'

interface ConfirmModal {
  id: number
  title: string
  text: string
}

const props = defineProps<{ modelValue: ConfirmModal }>()

const route = useRoute()
const modal = useModal()
const deals = useDeals()
const accounts = useAccounts()

const {loading, run} = useAsync(() => deals.deleteDeal(props.modelValue.id))

function confirmDelete() {
  run().then(() => {
    modal.close()
    accounts.getAccount(route.params.id)
  })
}

</script>

<template>
  <div class="confirm-delete-modal">
    <!-- Body -->
    <div class="modal-body align-items-start">
      <div class="d-flex align-items-center gap-3">
        <div
          class="d-flex align-items-center justify-content-center rounded-circle bg-red-light"
          style="width: 40px; height: 40px;"
        >
          <triangle-alert class="text-danger" :size="20" />
        </div>
        <div>
          <h3 class="modal-title">
            {{ modelValue.title }}
          </h3>
          <div
            class="text-muted"
            v-html="modelValue.text"
          />
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
        type="button"
        class="btn btn-danger"
        :disabled="loading"
        @click="confirmDelete()"
      >
        Confirm
      </button>
    </div>
  </div>
</template>
