<script setup lang="ts">
import {useModal} from "@/composable/useModal";
import {Dividend} from "@/types/dividends";
import useAsync from "@/utils/use-async";
import {useDividends} from "@/composable/useDividends";
import {useNumbers} from "@/composable/useNumbers";
import { TriangleAlert } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: {
    item: Dividend,
    callback: () => void
  }
}>()

const modal = useModal()
const {deleteDividend} = useDividends()
const {formatPrice} = useNumbers()

const {loading, run: confirmDelete} = useAsync((id: number) => deleteDividend(id).then(() => {
  modal.close()
  props.modelValue.callback()
}))

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
            Deletion Confirmation
          </h3>
          <div class="text-muted">
            Are you sure you want to delete dividend &quot;<b>{{ modelValue.item.ticker }} - {{ formatPrice(modelValue.item.amount) }}</b>&quot;?
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
        type="button"
        class="btn btn-danger"
        :disabled="loading"
        @click="confirmDelete(modelValue.item.id)"
      >
        Confirm
      </button>
    </div>
  </div>
</template>
