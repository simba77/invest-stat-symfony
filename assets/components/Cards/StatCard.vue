<script setup lang="ts">
import {QuestionMarkCircleIcon, ArrowSmallUpIcon, ArrowSmallDownIcon} from "@heroicons/vue/24/solid";

interface CardProps {
  name?: string
  percent?: number
  total?: number | string
  currency?: string
  helpText?: string
  profit?: number
}

withDefaults(defineProps<CardProps>(), {
  name: '',
  percent: undefined,
  total: 0,
  currency: '₽',
  helpText: '',
  profit: undefined
})

</script>

<template>
  <div class="bg-white shadow rounded p-3 md:p-6">
    <div class="text-gray-400 flex items-center">
      <div>{{ name }}</div>
      <div
        v-if="helpText"
        v-tooltip="helpText"
      >
        <question-mark-circle-icon class="h-5 ml-1" />
      </div>
    </div>
    <div class="flex justify-between items-center mt-1">
      <div class="text-lg md:text-3xl font-bold">
        {{ new Intl.NumberFormat('ru-RU').format(Number(total)) }} {{ currency }}
      </div>
      <div
        v-if="percent"
        :class="[percent > 0 ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900', 'rounded-full pr-2 pl-1 flex items-center']"
      >
        <template v-if="percent > 0">
          <arrow-small-up-icon class="h-5 mr-0.5 text-green-500" />
        </template>
        <template v-else>
          <arrow-small-down-icon class="h-5 mr-0.5 text-red-500" />
        </template>
        {{ Math.abs(percent) }}%
      </div>
      <div
        v-if="profit"
        :class="[profit > 0 ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900', 'rounded-full pr-2 pl-1 flex items-center']"
      >
        <template v-if="profit > 0">
          <arrow-small-up-icon class="h-5 mr-0.5 text-green-500" />
        </template>
        <template v-else>
          <arrow-small-down-icon class="h-5 mr-0.5 text-red-500" />
        </template>
        {{ new Intl.NumberFormat('ru-RU').format(profit) }} {{ currency }}
      </div>
    </div>
  </div>
</template>
