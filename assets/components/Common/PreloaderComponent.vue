<script setup lang="ts">
interface LoadingSpinnerProps {
  text?: string
  size?: 'sm' | 'md' | 'lg'
}

withDefaults(defineProps<LoadingSpinnerProps>(), {
  text: 'Loading...',
  size: 'md'
})
</script>

<template>
  <div class="d-flex align-items-center">
    <div :class="['spinner-border', `spinner-border-${size}`, 'me-3']" role="status">
      <span class="visually-hidden">{{ text }}</span>
    </div>
    <div>{{ text }}</div>
  </div>
</template>
