<script setup lang="ts">
import {useModal} from "@/composable/useModal";
import {useInvestments} from "@/composable/useInvestments";
import {Investment} from "@/types/investments";
import { TriangleAlert } from 'lucide-vue-next'

const props = defineProps<{ modelValue: Investment }>()

const modal = useModal()
const investments = useInvestments()

function confirmDelete() {
  investments.deleteInvestment(props.modelValue.id)
    .finally(() => {
      modal.close()
      investments.getInvestments()
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
            Deletion Confirmation
          </h3>
          <div class="text-muted">
            Are you sure you want to delete &quot;<b>{{ modelValue.sum }}</b>&quot;?
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
        @click="confirmDelete()"
      >
        Confirm
      </button>
    </div>
  </div>
</template>
