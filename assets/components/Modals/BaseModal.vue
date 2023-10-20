<script setup lang="ts">
import {Dialog, DialogPanel, TransitionChild, TransitionRoot} from '@headlessui/vue';
import {useModal} from "@/composable/useModal";

// convert all state properties to reactive references to be used on view
const {isOpen, view, model, close} = useModal();

defineProps<{
  large?: boolean,
}>()
</script>

<template>
  <TransitionRoot
    as="template"
    :show="isOpen"
  >
    <Dialog
      as="div"
      class="relative z-10"
      @close="close()"
    >
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:w-full"
              :class="[large ? 'w-full md:max-w-7xl' : 'w-full md:max-w-lg']"
            >
              <!-- dynamic components, using model to share values payload -->
              <component
                :is="view"
                v-model="model"
              />
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
