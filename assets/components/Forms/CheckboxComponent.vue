<script setup lang="ts">
import { computed } from 'vue'
import type { InputErrors } from '@/types/inputs'

interface InputCheckboxProps {
  modelValue: boolean
  label: string
  name: string
  id?: string
  error?: InputErrors
  help?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
}

const props = withDefaults(defineProps<InputCheckboxProps>(), {
  id: '',
  error: undefined,
  help: '',
  disabled: false,
  readonly: false,
  required: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const elementId = computed(() => props.id || props.name)

const errorMessage = computed(() => {
  return props.error?.violations
    .filter((item) => item.propertyPath === props.name)
    .map((item) => item.title)
    .join(', ')
})

const hasError = computed(() => !!errorMessage.value)
</script>

<template>
  <div class="form-check">
    <input
      :id="elementId"
      :checked="modelValue"
      :name="name"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :aria-invalid="hasError"
      :aria-describedby="hasError ? `${elementId}-feedback` : undefined"
      class="form-check-input"
      :class="{ 'is-invalid': hasError }"
      type="checkbox"
      @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    >
    <label :for="elementId" class="form-check-label">
      {{ label }}
    </label>
    <div
      v-if="hasError"
      :id="`${elementId}-feedback`"
      class="invalid-feedback d-block"
    >
      {{ errorMessage }}
    </div>
    <div v-if="help" class="form-text">
      {{ help }}
    </div>
  </div>
</template>
