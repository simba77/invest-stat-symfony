<script setup lang="ts">
import { computed, watch, useSlots } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { useTemplate } from "@/composable/useTemplate"

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
  clearable?: boolean
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
  clearable: false,
})

const emits = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
}>()
const slots = useSlots()
const { currentTheme } = useTemplate()

const value = computed({
  get: () => props.modelValue,
  set: (val) => emits('update:modelValue', val),
})

// Классы и идентификаторы
const elementId = computed(() => props.id || props.name)
const hasError = computed(() => !!props.error)
const errorMessage = computed(() => Array.isArray(props.error) ? props.error.join(', ') : props.error)
const hasBefore = computed(() => !!slots.before)
const isDark = computed(() => currentTheme.value === 'dark')

// Классы для обертки и инпута
const inputClasses = computed(() => ({
  disabled: props.disabled,
  'has-before-icon': hasBefore.value,
  'is-invalid': hasError.value,
}))

// Дебаунс обновления v-model
watch(value, useDebounceFn((val) => {
  emits('update:modelValue', val)
}, props.inputDelay))
</script>

<template>
  <div>
    <!-- Label + Help -->
    <label
      v-if="props.label"
      :for="elementId"
      class="fz-sm text-muted d-block mb-1"
    >
      {{ props.label }} <span v-if="props.required" class="text-danger">*</span>
      <v-menu v-if="props.help" placement="auto" class="d-inline-block">
        <span class="badge rounded-pill bg-primary form-help-badge">?</span>
        <template #popper>
          <div class="form-help-text" v-html="props.help" />
        </template>
      </v-menu>
    </label>

    <!-- Datepicker wrapper -->
    <div :class="inputClasses">
      <slot name="before" />
      <vue-date-picker
        v-model="value"
        :class="inputClasses"
        input-class-name="form-control"
        model-type="format"
        :locale="'ru'"
        :format="'dd.MM.yyyy'"
        :enable-time-picker="false"
        :auto-apply="true"
        :placeholder="props.placeholder"
        :name="props.name"
        :disabled="props.disabled"
        :required="props.required"
        :readonly="props.readonly"
        :dark="isDark"
        :clearable="props.clearable"
        :aria-invalid="hasError ? 'true' : 'false'"
        :aria-describedby="hasError ? elementId + '-error' : null"
      />
    </div>

    <!-- Ошибка -->
    <div v-if="hasError" :id="elementId + '-error'" class="invalid-feedback">
      {{ errorMessage }}
    </div>
  </div>
</template>

<style lang="scss" scoped>
.calendar-icon {
  margin: -3px 0 0 8px;
}

.dp__theme_dark {
  --dp-background-color: #000000;
}

.form-help-text {
  padding: 5px 10px;
}
</style>
