<template>
  <div class="bg-white shadow rounded p-3 md:p-6">
    <div class="text-gray-400 flex items-center">
      <div>{{ name }}</div>
      <div v-if="helpText" v-tooltip="helpText">
        <question-mark-circle-icon class="h-5 ml-1"></question-mark-circle-icon>
      </div>
    </div>
    <div class="flex justify-between items-center mt-1">
      <div class="text-lg md:text-3xl font-bold">{{ new Intl.NumberFormat('ru-RU').format(total) }} {{ currency }}</div>
      <div
        :class="[percent > 0 ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900', 'rounded-full pr-2 pl-1 flex items-center']"
        v-if="percent"
      >
        <template v-if="percent > 0">
          <arrow-sm-up-icon class="h-5 mr-0.5 text-green-500"></arrow-sm-up-icon>
        </template>
        <template v-else>
          <arrow-sm-down-icon class="h-5 mr-0.5 text-red-500"></arrow-sm-down-icon>
        </template>
        {{ Math.abs(percent) }}%
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {QuestionMarkCircleIcon, ArrowSmUpIcon, ArrowSmDownIcon} from "@heroicons/vue/solid";

export default {
  name: "StatCard",
  components: {QuestionMarkCircleIcon, ArrowSmUpIcon, ArrowSmDownIcon},
  props: {
    name: {
      type: String,
      default: '',
    },
    percent: {
      type: Number,
      default: null,
    },
    total: {
      type: [Number, String],
      default: 0,
    },
    currency: {
      type: String,
      default: '₽',
    },
    helpText: {
      type: String,
      default: null,
    }
  }
}
</script>
