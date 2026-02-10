<script setup lang="ts">
import { Modal } from 'bootstrap'
import {onMounted, ref} from "vue";
import {useModal} from "@/composable/useModal";
const { init, view, params } = useModal()
const modal = ref<Element | null>(null)

onMounted(() => {
  if (modal.value) {
    // noinspection TypeScriptValidateTypes
    const modalInstance = new Modal(modal.value)
    if ('addEventListener' in modal.value) {
      modal.value.addEventListener('hidden.bs.modal', () => {
        params.isOpen = false
      })
    }
    init(modalInstance)
  }
})
</script>

<template>
  <div ref="modal" class="modal fade" :class="params.classes" tabindex="-1">
    <div class="modal-dialog" :class="{'modal-dialog-centered': params.verticalCentered}">
      <template v-if="params.isOpen">
        <div class="modal-content">
          <!-- dynamic components, using model to share values payload -->
          <component :is="view" v-model="params.modelValue" />
        </div>
      </template>
    </div>
  </div>
</template>
