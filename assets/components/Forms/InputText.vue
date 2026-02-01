<script setup lang="ts">
import {computed} from "vue";
import {useDebounceFn} from "@vueuse/core";
import {InputErrors} from "@/types/inputs";
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

interface InputProps {
  modelValue: number | string | null,
  label: string
  placeholder: string
  name: string
  id?: string
  type?: string
  enterKeyHint?: string
  autocomplete?: string
  error?: InputErrors | null
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  inputDelay?: number
  step?: string
}

const props = withDefaults(defineProps<InputProps>(), {
  label: '',
  placeholder: '',
  name: '',
  id: '',
  type: 'text',
  enterKeyHint: '',
  autocomplete: '',
  error: undefined,
  disabled: false,
  readonly: false,
  required: false,
  inputDelay: 100,
  step: '.0001'
})

const emits = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const model = computed({
  get: () => props.modelValue,
  set: (value) => {
    updateModelValue(value)
  }
})

const elementId = computed(() => props.id || props.name)
const errorMessages = computed(
  () => props.error?.violations
    .filter(v => v.propertyPath === props.name)
    .map(v => v.title) ?? []
)

const inputType = computed(() =>
  props.type === 'number' ? 'text' : props.type
)

const inputMode = computed(() =>
  props.type === 'number' ? 'decimal' : undefined
)

const updateModelValue = useDebounceFn((value: string | number | null) => {
  emits('update:modelValue', value)
}, props.inputDelay)

const normalizeNumberInput = (value: string): string => {
  let result = value
    .replace(/\s+/g, '')
    .replace(/,/g, '.')
    .replace(/[^\d.-]/g, '')

  const isNegative = result.startsWith('-')

  result = result.replace(/-/g, '')

  const [int, ...rest] = result.split('.')
  const fraction = rest.join('')

  return (isNegative ? '-' : '') + int + (fraction ? '.' + fraction : '')
}

const onInput = (event: Event) => {
  if (props.type !== 'number') {
    return
  }

  const target = event.target as HTMLInputElement
  const normalized = normalizeNumberInput(target.value)

  if (normalized !== target.value) {
    target.value = normalized
  }

  model.value = normalized
}

const onPaste = (event: ClipboardEvent) => {
  if (props.type !== 'number') {
    return
  }

  const pasted = event.clipboardData?.getData('text')
  if (!pasted) {
    return
  }

  event.preventDefault()
  model.value = normalizeNumberInput(pasted)
}

</script>

<template>
  <div class="flex flex-col gap-2">
    <label v-if="label" :for="elementId">{{ label }}</label>
    <InputText
      :id="elementId"
      v-model="model"
      :placeholder="placeholder"
      :invalid="errorMessages.length > 0"
      :autocomplete="autocomplete"
      :name="name"
      :enterkeyhint="enterKeyHint"
      :type="inputType"
      :required="required"
      :readonly="readonly"
      :step="type === 'number' ? step : undefined"
      :inputmode="inputMode"
      :disabled="disabled"
      @input="onInput"
      @paste="onPaste"
    />
    <Message v-if="$slots.help" size="small" severity="secondary" variant="simple">
      <slot name="help" />
    </Message>
    <ul v-if="errorMessages.length" class="text-sm text-red-500">
      <li v-for="(msg, i) in errorMessages" :key="i">
        {{ msg }}
      </li>
    </ul>
  </div>
</template>
