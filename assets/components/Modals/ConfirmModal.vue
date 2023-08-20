<template>
  <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
    <div class="sm:flex sm:items-start">
      <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
        <exclamation-triangle-icon class="h-6 w-6 text-red-600" aria-hidden="true"/>
      </div>
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">{{ modelValue?.title }}</DialogTitle>
        <div class="mt-2">
          <p class="text-sm text-gray-500" v-html="modelValue?.text"></p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {ExclamationTriangleIcon} from '@heroicons/vue/24/outline'

interface ConfirmModal {
  title: string,
  text: string
}

defineProps<{ modelValue: ConfirmModal }>()

</script>
