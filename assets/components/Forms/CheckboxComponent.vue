<script setup lang="ts">
import {InputErrors} from "@/types/inputs";
import {computed, reactive} from "vue";

interface InputProps {
  modelValue: number | string | boolean,
  label: string
  name: string
  id?: string
  error?: InputErrors
  help?: string | number
  disabled?: boolean
  readonly?: boolean
  required?: boolean
}

const props = withDefaults(defineProps<InputProps>(), {
  label: '',
  name: '',
  id: '',
  error: undefined,
  help: '',
  disabled: false,
  readonly: false,
  required: false,
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
  })
})

const updateModelValue = () => {
  emits('update:modelValue', inputParams.value)
}

</script>

<template>
  <div>
    <div class="flex items-center">
      <input
        :id="inputParams.elementId"
        v-model="inputParams.value"
        :name="name"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :class="[inputParams.errorMessage ? 'is-invalid' : '']"
        class="form-checkbox"
        type="checkbox"
        @change="updateModelValue"
      >
      <label
        class="form-checkbox-label"
        :for="inputParams.elementId"
        v-text="label"
      />
    </div>
    <span
      v-if="inputParams.errorMessage"
      class="invalid-feedback mt-0"
    >{{ error }}</span>
    <div
      v-if="help"
      class="small text-secondary opacity-75"
    >
      {{ help }}
    </div>
  </div>
</template>
