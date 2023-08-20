<template>
  <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
    <div class="sm:flex sm:items-start">
      <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
        <cash-icon class="h-6 w-6 text-green-600" aria-hidden="true"/>
      </div>
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">Sell Asset: {{ asset.name }}</DialogTitle>
        <div class="mt-2">
          <input-text
            v-model="asset.price"
            class="mb-3"
            name="name"
            label="Sell Price"
            placeholder="Enter an account name"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {DialogTitle} from '@headlessui/vue'
import {CashIcon} from '@heroicons/vue/outline'
import InputText from "@/components/Forms/InputText.vue";
import {computed} from "vue";

interface SellModal {
  name: string,
  price: number
}

const props = defineProps<{ modelValue: SellModal }>()
const emit = defineEmits(['update:modelValue'])
const asset = computed<SellModal>({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

</script>
