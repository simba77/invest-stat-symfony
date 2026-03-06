<script setup lang="ts">
import {computed} from 'vue'
import {ChevronLeft, ChevronRight} from 'lucide-vue-next'
import {buildPaginationItems, PaginationItem} from '@/composable/usePagination'

const props = withDefaults(defineProps<{
  page: number
  totalPages: number
  disabled?: boolean
}>(), {
  disabled: false,
})

const emit = defineEmits<{
  (event: 'change', page: number): void
}>()

const items = computed<PaginationItem[]>(() => buildPaginationItems(props.page, props.totalPages))

function changePage(page: number): void {
  if (props.disabled || page < 1 || page > props.totalPages || page === props.page) {
    return
  }

  emit('change', page)
}
</script>

<template>
  <nav
    v-if="totalPages > 1"
    aria-label="Pagination"
  >
    <ul class="pagination mb-0">
      <li
        class="page-item"
        :class="{ disabled: disabled || page <= 1 }"
      >
        <button
          type="button"
          class="page-link"
          aria-label="Previous page"
          @click="changePage(page - 1)"
        >
          <chevron-left :size="16" />
        </button>
      </li>

      <li
        v-for="(item, index) in items"
        :key="`${item}-${index}`"
        class="page-item"
        :class="{ active: item === page, disabled: item === 'ellipsis' || disabled }"
      >
        <span
          v-if="item === 'ellipsis'"
          class="page-link"
        >...</span>
        <button
          v-else
          type="button"
          class="page-link"
          @click="changePage(item)"
        >
          {{ item }}
        </button>
      </li>

      <li
        class="page-item"
        :class="{ disabled: disabled || page >= totalPages }"
      >
        <button
          type="button"
          class="page-link"
          aria-label="Next page"
          @click="changePage(page + 1)"
        >
          <chevron-right :size="16" />
        </button>
      </li>
    </ul>
  </nav>
</template>
