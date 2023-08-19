<template>
  <div>
    <div v-for="(item, index) in fields" :key="index" class="mb-3">
      <template v-if="item && item.type && !item.isHidden">
        <div v-if="item.type === 'text' || item.type === 'password' || item.type === 'number' || item.type === 'date'">
          <!-- Text or number input -->
          <input-text
            v-model="item.value"
            class="mb-2"
            :label="item.label"
            :type="item.type"
            :name="item.name"
            :format="item?.format"
            :placeholder="item.placeholder"
            :help="item.help ?? ''"
            :error="errors ? errors[item.name] ?? '' : ''"
            @change="updated"
          />
        </div>


        <div v-else-if="item.type === 'select'">
          <!-- Select -->
          <input-select
            v-model="item.value"
            class="mb-2"
            :label="item.label"
            :name="item.name"
            :placeholder="item.placeholder"
            :options="item.items"
            :error="errors ? errors[item.name] ?? '' : ''"
            :help="item.help ?? ''"
            @change="updated"
          />
        </div>

        <div v-else-if="item.type === 'checkbox'">
          <!-- Checkbox -->
          <checkbox-component
            v-model="item.value"
            class="mb-2"
            :label="item.label"
            :name="item.name"
            :error="errors ? errors[item.name] ?? '' : ''"
            :help="item.help ?? ''"
            @change="updated"
          />
        </div>
      </template>
    </div>
  </div>
</template>
<script setup lang="ts">
import InputText from "@/components/Forms/InputText.vue";
import CheckboxComponent from "@/components/Forms/CheckboxComponent.vue";
import {reactive} from "vue";
import InputSelect from '@/components/Forms/InputSelect.vue'

const props = defineProps<{
  modelValue: object,
  errors?: object | []
}>()

const emits = defineEmits(['update:modelValue'])
const fields = reactive(props.modelValue);

function updated() {
  emits('update:modelValue', fields);
}
</script>
