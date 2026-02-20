<script setup lang="ts">
import { HelpCircle, TrendingUp, TrendingDown } from 'lucide-vue-next'
import { computed } from 'vue'
import { useNumbers } from "@/composable/useNumbers"

const { formatPrice, formatPercent } = useNumbers()

interface CardProps {
  name?: string
  percent?: number | string
  total?: number | string
  currency?: string
  helpText?: string
  profit?: number | string | null
  profitHelpText?: string | null
}

const props = withDefaults(defineProps<CardProps>(), {
  name: '',
  percent: undefined,
  total: 0,
  currency: '₽',
  helpText: '',
  profit: undefined,
  profitHelpText: ''
})

const isPositive = computed(() => +(props.profit ?? props.percent ?? 0) > 0)
</script>

<template>
  <div class="card">
    <div class="card-body">
      <div class="text-muted d-flex align-items-center">
        <div>{{ name }}</div>
        <help-circle v-if="helpText" v-tooltip="helpText" :size="16" class="ms-2" />
      </div>
      <div class="mt-1">
        <div class="card-main-number fw-extrabold">
          {{ formatPrice(Number(total), currency) }}
        </div>
      </div>
      <div v-if="profit || percent" class="d-flex fz-sm mt-1">
        <div
          v-if="profit"
          v-tooltip="profitHelpText"
          :class="[isPositive ? 'text-success' : 'text-danger', 'rounded-full pe-1 d-flex align-items-center']"
        >
          <component :is="isPositive ? TrendingUp : TrendingDown" :size="18" class="me-1" />
          {{ formatPrice(+profit, currency) }}
        </div>
        <div
          v-if="percent"
          :class="[isPositive ? 'text-success' : 'text-danger', 'pe-2 d-flex align-items-center']"
        >
          <component :is="isPositive ? TrendingUp : TrendingDown" v-if="!profit" :size="18" class="me-1" />
          <template v-if="profit">
            ({{ formatPercent(+percent) }})
          </template>
          <template v-else>
            {{ formatPercent(+percent) }}
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.card-main-number {
  font-size: 1.875rem;
  line-height: 2.25rem;
}
</style>
