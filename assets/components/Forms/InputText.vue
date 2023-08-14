<template>
  <div class="">
    <label :for="elementId" class="block text-sm font-medium text-gray-700">{{ label }}</label>
    <input
      :class="[errorMessage ? 'border-red-500' : '', 'mt-1 form-input']"
      :id="elementId"
      :name="name"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      :readonly="readonly"
      :enterkeyhint="enterKeyHint"
      @input="updateModelValue"
      :pattern="type === 'number' ? '[0-9]*' : null"
      step=".0001"
      :autocomplete="autocomplete !== '' ? autocomplete : null"
      v-model="value"
    >
    <div class="mt-1 text-sm text-gray-500" v-if="help" v-html="help"></div>
    <div class="mt-1 text-sm text-red-500" v-if="errorMessage" v-html="errorMessage"></div>
  </div>
</template>

<script lang="ts">
export default {
  name: "InputText",
  emits: ['update:modelValue'],
  props: {
    modelValue: [String, Number],
    label: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
    name: {
      type: String,
      default: '',
    },
    id: {
      type: String,
      default: '',
    },
    type: {
      type: String,
      default: 'text',
    },
    enterKeyHint: {
      type: String,
      default: '',
    },
    autocomplete: {
      type: String,
      default: '',
    },
    error: {
      type: [String, Number, Object],
      default: null,
    },
    help: {
      type: [String, Number],
      default: null,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    required: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      value: this.modelValue,
      elementId: this.id ? this.id : this.name
    }
  },
  computed: {
    errorMessage() {
      return this.error?.violations.filter((item: { propertyPath: any; }) => {
        return item.propertyPath === this.name;
      })
        .map((item: { title: any; }) => {
          return item.title;
        })
        .join('<br>');
    }
  },
  methods: {
    updateModelValue(event: { target: { value: any; }; }) {
      this.$emit('update:modelValue', event.target.value)
    },
  }
}
</script>
