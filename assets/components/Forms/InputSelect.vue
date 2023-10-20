<script lang="ts">
export default {
  name: "InputSelect",
  props: {
    modelValue: {
      type: String,
      default: ''
    },
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
    required: {
      type: Boolean,
      default: false,
    },
    displayName: {
      type: String,
      default: 'name',
    },
    fieldValue: {
      type: String,
      default: 'value',
    },
    options: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
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
    getName(option: string) {
      return option[this.displayName];
    },
    getValue(option: string) {
      return option[this.fieldValue];
    },
  }
}
</script>

<template>
  <div>
    <label
      :for="elementId"
      class="block text-sm font-medium text-gray-700"
    >{{ label }}</label>
    <select
      :id="elementId"
      v-model="value"
      :required="required"
      :disabled="disabled"
      :name="name"
      :class="[errorMessage ? 'border-red-500' : '', 'form-select rounded w-full mt-1']"
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
      v-if="errorMessage"
      class="mt-1 text-sm text-red-500"
      v-html="errorMessage"
    />
  </div>
</template>

