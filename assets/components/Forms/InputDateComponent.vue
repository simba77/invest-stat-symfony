<script setup lang="ts">
import { computed, reactive, useSlots } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import {useTemplate} from "@/composable/useTemplate";

const slots = useSlots()
const {currentTheme} = useTemplate()

interface InputProps {
  modelValue: string | number
  label?: string
  placeholder?: string
  name: string
  id?: string
  type?: string
  enterKeyHint?: string
  autocomplete?: string
  error?: string | number | any[] | null
  help?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  inputDelay?: number
  usePhoneFormatter?: boolean
}

const props = withDefaults(defineProps<InputProps>(), {
  modelValue: '',
  label: '',
  placeholder: '',
  name: '',
  id: '',
  type: 'text',
  enterKeyHint: '',
  autocomplete: '',
  error: null,
  help: '',
  disabled: false,
  readonly: false,
  required: false,
  inputDelay: 0,
  usePhoneFormatter: false
})

const emits = defineEmits(['update:modelValue'])

const inputParams = reactive({
  value: props.modelValue,
  elementId: props.id ? props.id : props.name,
  hasError: computed(() => !!props.error),
  hasBefore: computed(() => !!slots.before),
  errorMessage: computed(() => {
    if (Array.isArray(props.error)) {
      return props.error.join(',')
    }
    return props.error
  })
})

const updateModelValue = useDebounceFn(() => {
  emits('update:modelValue', inputParams.value)
}, props.inputDelay)

</script>

<template>
  <div>
    <label v-if="label" class="text-sm font-medium text-gray-700 dark:text-white block mb-1" :for="inputParams.elementId">
      {{ label }} <span v-if="required" class="text-danger">*</span>
      <v-menu v-if="help" placement="auto" class="d-inline-block">
        <span class="badge rounded-pill bg-primary form-help-badge">?</span>
        <template #popper>
          <div class="form-help-text" v-html="help" />
        </template>
      </v-menu>
    </label>
    <div
      :class=" {
        'disabled': disabled,
        'has-before-icon': inputParams.hasBefore,
        'is-invalid': inputParams.hasError,
      }"
    >
      <vue-date-picker
        v-model="inputParams.value"
        :class=" {
          'disabled': disabled,
          'has-before-icon': inputParams.hasBefore,
          'is-invalid': inputParams.hasError,
        }"
        input-class-name="form-input"
        model-type="format"
        :locale="'ru'"
        :format="'dd.MM.yyyy'"
        :enable-time-picker="false"
        :auto-apply="true"
        :placeholder="placeholder"
        :name="name"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :dark="currentTheme === 'dark'"
        @update:model-value="updateModelValue"
      />
    </div>
    <div v-if="inputParams.hasError" class="invalid-feedback" v-text="inputParams.errorMessage" />
  </div>
</template>

<style lang="scss" scoped>
.help {
  margin-top: 0.25rem;
}

.calendar-icon {
  margin: -3px 0 0 8px;
}

.dp__theme_dark {
  --dp-background-color: #000000;
}
</style>
