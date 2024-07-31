<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {ExclamationTriangleIcon} from '@heroicons/vue/24/outline'
import {useModal} from "@/composable/useModal";
import {Dividend} from "@/types/dividends";
import useAsync from "@/utils/use-async";
import {useDividends} from "@/composable/useDividends";
import {useNumbers} from "@/composable/useNumbers";

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
  <div>
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
          <exclamation-triangle-icon
            class="h-6 w-6 text-red-600"
            aria-hidden="true"
          />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <DialogTitle
            as="h3"
            class="text-lg leading-6 font-medium text-gray-900"
          >
            Deletion Confirmation
          </DialogTitle>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Are you sure you want to delete dividend &quot;<b>{{ modelValue.item.ticker }} - {{ formatPrice(modelValue.item.amount) }}</b>&quot;?
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
      <button
        class="btn btn-danger mr-3 md:mr-0 ml-3"
        type="button"
        :disabled="loading"
        @click="confirmDelete(modelValue.item.id)"
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
  </div>
</template>
