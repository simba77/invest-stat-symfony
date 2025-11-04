<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {ExclamationTriangleIcon} from '@heroicons/vue/24/outline'
import {useModal} from "@/composable/useModal";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {FutureMultiplier} from "@/types/futures";
import { Button} from "primevue";

const props = defineProps<{ modelValue: {item: FutureMultiplier, onSuccess: () => void} }>()

const modal = useModal()

const {loading, run: confirmDelete} = useAsync(() => axios.delete('/api/futures/multipliers/delete/' + props.modelValue.item.id)
  .then(() => {
    modal.close()
    props.modelValue.onSuccess()
  })
)

</script>

<template>
  <div>
    <div class="bg-white dark:bg-zinc-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
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
            class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
          >
            Deletion Confirmation
          </DialogTitle>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Are you sure you want to delete &quot;<b>{{ modelValue.item.ticker }}</b>&quot;?
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 dark:bg-zinc-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
      <Button
        type="submit"
        class="btn btn-danger ms-2"
        :loading="loading"
        label="Confirm"
        @click="confirmDelete"
      />
      <Button
        type="submit"
        class="btn btn-secondary ms-2"
        label="Cancel"
        @click="modal.close()"
      />
    </div>
  </div>
</template>
