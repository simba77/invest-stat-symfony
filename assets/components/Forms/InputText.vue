<script setup lang="ts">
import {computed, reactive} from "vue";
import {useDebounceFn} from "@vueuse/core";
import {InputErrors} from "@/types/inputs";

interface InputProps {
  modelValue: number | string,
  label: string
  placeholder: string
  name: string
  id?: string
  type?: string
  enterKeyHint?: string
  autocomplete?: string
  error?: InputErrors
  help?: string | number
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  inputDelay?: number
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
  help: '',
  disabled: false,
  readonly: false,
  required: false,
  inputDelay: 100
})

const emits = defineEmits(['update:modelValue'])

const inputParams = reactive({
  value: props.modelValue,
  elementId: props.id ? props.id : props.name,
  errorMessage: computed(() => {
    return props.error?.violations
      .filter((item) => {
        return item.propertyPath === props.name;
      })
      .map((item) => {
        return item.title;
      })
      .join('<br>');
  }),
  pattern: computed(() => {
    if (props.type === 'number') {
      return '[0-9]*'
    }
    return undefined
  })
})

const updateModelValue = useDebounceFn(() => {
  emits('update:modelValue', inputParams.value)
}, props.inputDelay)

</script>

<template>
  <div class="">
    <label
      :for="inputParams.elementId"
      class="block text-sm font-medium text-gray-700"
    >{{ label }}</label>
    <input
      :id="inputParams.elementId"
      v-model="inputParams.value"
      :class="[inputParams.errorMessage ? 'border-red-500' : '', 'mt-1 form-input']"
      :name="name"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      :readonly="readonly"
      :enterkeyhint="enterKeyHint"
      :pattern="inputParams.pattern"
      step=".0001"
      :autocomplete="autocomplete !== '' ? autocomplete : undefined"
      @input="updateModelValue"
    >
    <div
      v-if="help"
      class="mt-1 text-sm text-gray-500"
      v-html="help"
    />
    <div
      v-if="inputParams.errorMessage"
      class="mt-1 text-sm text-red-500"
      v-html="inputParams.errorMessage"
    />
  </div>
</template>
