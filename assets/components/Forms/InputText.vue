<script setup lang="ts">
import {computed, reactive} from "vue";
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
  help?: string | number
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
  help: '',
  disabled: false,
  readonly: false,
  required: false,
  inputDelay: 100,
  step: '.0001'
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
    return null
  })
})

const updateModelValue = useDebounceFn(() => {
  emits('update:modelValue', inputParams.value)
}, props.inputDelay)

</script>

<template>
  <div class="flex flex-col gap-2">
    <label :for="inputParams.elementId">{{ label }}</label>
    <InputText
      :id="inputParams.elementId"
      v-model="inputParams.value"
      :placeholder="placeholder"
      :invalid="!!(inputParams.errorMessage && inputParams.errorMessage.length > 0)"
      :pattern="inputParams.pattern"
      :autocomplete="autocomplete"
      :name="name"
      :enterkeyhint="enterKeyHint"
      :type="type"
      :required="required"
      :readonly="readonly"
      :step="step"
      @update:model-value="updateModelValue"
    />
    <Message v-if="help" size="small" severity="secondary" variant="simple">
      <span v-html="help" />
    </Message>
    <div
      v-if="inputParams.errorMessage"
      class="mt-1 text-sm text-red-500"
      v-html="inputParams.errorMessage"
    />
  </div>
</template>
