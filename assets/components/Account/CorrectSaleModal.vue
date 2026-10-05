<script setup lang="ts">
import {ref} from 'vue'
import {Pen} from 'lucide-vue-next'
import InputText from '@/components/Forms/InputText.vue'
import {useModal} from '@/composable/useModal'
import {useAccountOperations} from '@/composable/useAccountOperations'
import {apiErrorMessage} from '@/utils/api-error'
import type {ManualOperation} from '@/types/journal'

interface CorrectSale {
  accountId: number
  operation: ManualOperation
  onSaved: () => void
}

const props = defineProps<{ modelValue: CorrectSale }>()

const modal = useModal()
const {correctSale} = useAccountOperations()

// The date as the browser shows it: YYYY-MM-DDTHH:mm in the local time
function localDate(value: string): string {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())

  return date.toISOString().slice(0, 16)
}

const price = ref<string | null>(props.modelValue.operation.price !== null ? String(Number(props.modelValue.operation.price)) : '')
const executedAt = ref<string | null>(localDate(props.modelValue.operation.executedAt))
const saving = ref(false)
const error = ref('')
const validationErrors = ref()

async function save() {
  saving.value = true
  error.value = ''
  validationErrors.value = undefined
  try {
    const date = new Date(executedAt.value ?? '')
    await correctSale(props.modelValue.accountId, props.modelValue.operation.id, {
      price: price.value ?? '',
      executedAt: isNaN(date.getTime()) ? '' : date.toISOString().replace(/\.\d{3}Z$/, '+00:00'),
    })
    modal.close()
    props.modelValue.onSaved()
  } catch (reason: any) {
    if (reason?.response?.status === 422) {
      validationErrors.value = reason.response.data
    } else {
      error.value = apiErrorMessage(reason)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form @submit.prevent="save()">
    <div class="modal-body">
      <div class="d-flex align-items-start gap-3">
        <div
          class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 flex-shrink-0"
          style="width: 40px; height: 40px;"
        >
          <pen
            class="text-primary"
            :size="20"
          />
        </div>
        <div class="flex-grow-1">
          <h3 class="modal-title mb-4 mt-1">
            Correct the sale: {{ modelValue.operation.name ?? modelValue.operation.ticker }}
          </h3>
          <div class="form-stack">
            <input-text
              v-model.trim="price"
              :error="validationErrors"
              :required="true"
              autocomplete="off"
              name="price"
              :label="modelValue.operation.instrumentType === 'bond' ? 'Sell Percent (%)' : 'Sell Price'"
              type="number"
              placeholder="Enter sell price"
            />
            <input-text
              v-model="executedAt"
              :error="validationErrors"
              :required="true"
              class="mt-2"
              name="executedAt"
              label="Date"
              type="datetime-local"
              placeholder="Date of the sale"
            />
            <div
              v-if="error"
              class="text-danger mt-2"
            >
              {{ error }}
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button
        type="button"
        class="btn btn-secondary"
        @click="modal.close()"
      >
        Close
      </button>
      <button
        type="submit"
        class="btn btn-primary"
        :disabled="saving"
      >
        Save
      </button>
    </div>
  </form>
</template>
