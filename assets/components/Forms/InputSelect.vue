<script setup lang="ts">
import {InputErrors} from "@/types/inputs";
import {computed, reactive} from "vue";

interface InputProps {
  modelValue: number | string,
  label: string
  placeholder: string
  name: string
  id?: string
  error?: InputErrors | null
  help?: string | number
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  displayName?: string
  fieldValue?: string
  options: any[]
}

const props = withDefaults(defineProps<InputProps>(), {
  label: '',
  placeholder: '',
  name: '',
  id: '',
  error: undefined,
  help: '',
  disabled: false,
  readonly: false,
  required: false,
  displayName: 'name',
  fieldValue: 'value'
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
})

const updateModelValue = () => {
  emits('update:modelValue', inputParams.value)
}

function getName(option: any) {
  return option[props.displayName];
}

function getValue(option: any) {
  return option[props.fieldValue];
}

</script>

<template>
  <div>
    <label
      :for="inputParams.elementId"
      class="form-label"
    >{{ label }}</label>
    <select
      :id="inputParams.elementId"
      v-model="inputParams.value"
      :required="required"
      :disabled="disabled"
      :name="name"
      :class="[inputParams.errorMessage ? 'is-invalid' : '', 'form-select mt-1']"
      @change="updateModelValue"
    >
      <option
        v-for="(option, index) in options"
        :key="index"
        :value="getValue(option)"
      >
        {{ getName(option) }}
      </option>
    </select>
    <div
      v-if="help"
      class="mt-1 text-sm text-gray-500"
      v-html="help"
    />
    <div
      v-if="inputParams.errorMessage"
      class="mt-1 text-sm invalid-feedback"
      v-html="inputParams.errorMessage"
    />
  </div>
</template>

