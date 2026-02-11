<script setup lang="ts">
import {useModal} from "@/composable/useModal";
import {useExpenses} from "@/composable/useExpenses";
import { TriangleAlert } from 'lucide-vue-next'

interface ConfirmModal {
  id: number
  title: string
  text: string
}

const expenses = useExpenses();

const props = defineProps<{ modelValue: ConfirmModal }>()

const modal = useModal()

function confirmDelete() {
  expenses.deleteExpense(props.modelValue.id)
    .finally(() => {
      modal.close()
      expenses.getExpenses()
      expenses.getSummary()
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
        @click="confirmDelete()"
      >
        Confirm
      </button>
    </div>
  </div>
</template>
