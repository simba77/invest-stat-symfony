<script setup lang="ts">
import {QuestionMarkCircleIcon, ArrowSmallUpIcon, ArrowSmallDownIcon} from "@heroicons/vue/24/solid";
import {useNumbers} from "@/composable/useNumbers";
import Card from 'primevue/card';

const {formatPrice, formatPercent} = useNumbers()

interface CardProps {
  name?: string
  percent?: number
  total?: number | string
  currency?: string
  helpText?: string
  profit?: number | null
  profitHelpText?: string | null
}

withDefaults(defineProps<CardProps>(), {
  name: '',
  percent: undefined,
  total: 0,
  currency: '₽',
  helpText: '',
  profit: undefined,
  profitHelpText: ''
})

</script>

<template>
  <Card class="stat-card">
    <template #content>
      <div class="text-gray-400 flex items-center">
        <div>{{ name }}</div>
        <div
          v-if="helpText"
          v-tooltip="helpText"
        >
          <question-mark-circle-icon class="h-5 ml-1" />
        </div>
      </div>
      <div class="mt-1">
        <div class="text-lg md:text-3xl font-extrabold">
          {{ formatPrice(Number(total), currency) }}
        </div>
      </div>
      <div v-if="profit || percent" class="flex text-sm mt-1">
        <div
          v-if="profit"
          v-tooltip="profitHelpText"
          :class="[profit > 0 ? 'text-green-500' : 'text-red-500', 'rounded-full pr-1 flex items-center']"
        >
          <template v-if="profit > 0">
            <arrow-small-up-icon class="h-4 mr-0.5 text-green-500 rotate-45" />
          </template>
          <template v-else>
            <arrow-small-down-icon class="h-4 mr-0.5 text-red-500 -rotate-45" />
          </template>
          {{ formatPrice(profit, currency) }}
        </div>
        <div
          v-if="percent"
          :class="[percent > 0 ? ' text-green-500' : ' text-red-500', 'pr-2 flex items-center']"
        >
          <template v-if="!profit && percent > 0">
            <arrow-small-up-icon class="h-4 mr-0.5 text-green-500 rotate-45" />
          </template>
          <template v-else-if="!profit">
            <arrow-small-down-icon class="h-4 mr-0.5 text-red-500 -rotate-45" />
          </template>
          <template v-if="profit">
            ({{ formatPercent(percent) }})
          </template>
          <template v-else>
            {{ formatPercent(percent) }}
          </template>
        </div>
      </div>
    </template>
  </Card>
</template>

<style lang="scss" scoped>
.stat-card {
  --p-card-body-padding: 1rem 1.25rem;
  --p-card-color: #000;
}

.app-dark {
  .stat-card {
    --p-card-color: #fff;
  }
}
</style>
