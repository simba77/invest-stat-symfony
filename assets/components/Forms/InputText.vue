<script setup lang="ts">
import {computed, ref} from "vue";
import {useDebounceFn} from "@vueuse/core";
import {InputErrors} from "@/types/inputs";

interface InputProps {
  modelValue: number | string | null
  label: string
  placeholder: string
  name: string
  id?: string
  type?: string
  error?: InputErrors | null
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  inputDelay?: number
}

const props = withDefaults(defineProps<InputProps>(), {
  id: undefined,
  type: 'text',
  error: undefined,
  inputDelay: 100
})

const emits = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const fieldId = computed(() => props.id || props.name)

const errorMessages = computed(() =>
  props.error?.violations?.filter(v => v.propertyPath === props.name).map(v => v.title) ?? []
)

const inputValue = ref(props.modelValue != null ? String(props.modelValue) : '')

const debouncedEmit = useDebounceFn(
  (value: string | null) => emits('update:modelValue', value),
  props.inputDelay
)

/**
 * Нормализация числового ввода
 * - заменяет запятые на точки
 * - удаляет лишние символы, кроме цифр, точки и минуса
 * - оставляет один минус только в начале
 * - оставляет только одну точку
 */
const normalize = (value: string): string => {
  value = value.replace(/,/g, '.')
  value = value.replace(/[^\d.-]/g, '')

  const isNegative = value.startsWith('-')
  value = value.replace(/-/g, '')
  if (isNegative) value = '-' + value

  const parts = value.split('.')
  if (parts.length > 2) {
    value = parts[0] + '.' + parts.slice(1).join('')
  }

  return value
}

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  let value = target.value

  if (props.type === 'number') {
    value = normalize(value)
    inputValue.value = value
    target.value = value

    // пустое или неполное число -> emit null
    if (!value || value === '-' || value === '.' || value === '-.' || value.endsWith('.')) {
      debouncedEmit(null)
      return
    }
  }

  inputValue.value = value
  debouncedEmit(value)
}
</script>

<template>
  <div class="mb-3">
    <label
      v-if="label"
      :for="fieldId"
      class="form-label"
    >
      {{ label }}
    </label>

    <input
      :id="fieldId"
      :value="inputValue"
      :placeholder="placeholder"
      :class="['form-control', errorMessages.length && 'is-invalid']"
      :type="type === 'number' ? 'text' : type"
      :required="required"
      :readonly="readonly"
      :disabled="disabled"
      :inputmode="type === 'number' ? 'decimal' : undefined"
      @input="onInput"
    >

    <div v-if="$slots.help" class="form-text">
      <slot name="help" />
    </div>

    <div v-if="errorMessages.length" class="invalid-feedback d-block">
      <div v-for="(msg, i) in errorMessages" :key="i">
        {{ msg }}
      </div>
    </div>
  </div>
</template>
