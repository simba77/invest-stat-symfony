<script lang="ts">
export default {
  name: "CheckboxComponent",
  props: {
    modelValue: {
      type: [String, Number, Boolean],
      default: ''
    },
    label: {
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
      type: [String, Number],
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
  emits: ['update:modelValue'],
  data() {
    return {
      value: this.modelValue,
      elementId: this.id ? this.id : this.name
    }
  },
  computed: {
    hasError() {
      return !!this.error;
    },
  },
  methods: {
    updateModelValue() {
      this.$emit('update:modelValue', this.value);
    }
  }
}
</script>

<template>
  <div>
    <div class="flex items-center">
      <input
        :id="elementId"
        v-model="value"
        :name="name"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :class="[hasError ? 'is-invalid' : '']"
        class="form-checkbox"
        type="checkbox"
        @change="updateModelValue"
      >
      <label
        class="form-checkbox-label"
        :for="elementId"
        v-text="label"
      />
    </div>
    <span
      v-if="hasError"
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
