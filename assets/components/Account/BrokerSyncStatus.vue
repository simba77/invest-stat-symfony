<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RefreshCw } from 'lucide-vue-next'
import { apiErrorMessage, useBrokerSync } from '@/composable/useBrokerSync'
import type { BrokerSyncSettings } from '@/types/brokerSync'

const props = defineProps<{
  accountId: number
}>()

const emit = defineEmits<{
  (e: 'synced'): void
}>()

const brokerSync = useBrokerSync()
const settings = ref<BrokerSyncSettings | null>(null)
const syncing = ref(false)
const error = ref<string | null>(null)

const statusClass = computed(() => ({
  success: 'text-bg-success',
  warning: 'text-bg-warning',
  failed: 'text-bg-danger',
}[settings.value?.lastSyncStatus ?? 'success']))

const lastSyncedAt = computed(() =>
  settings.value?.lastSyncedAt ? new Date(settings.value.lastSyncedAt).toLocaleString() : 'never'
)

async function load() {
  settings.value = (await brokerSync.getPage(props.accountId)).settings
}

async function sync() {
  syncing.value = true
  error.value = null
  try {
    settings.value = (await brokerSync.run(props.accountId)).settings
    emit('synced')
  } catch (exception: any) {
    error.value = apiErrorMessage(exception)
    await load()
  } finally {
    syncing.value = false
  }
}

onMounted(load)
</script>

<template>
  <div
    v-if="settings"
    class="card mb-3"
  >
    <div class="card-body py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div class="small">
          <span class="fw-light">Synced with</span> {{ settings.externalAccountName }}
          <span class="fw-light ms-3">Last sync:</span> {{ lastSyncedAt }}
          <span
            v-if="settings.lastSyncStatus"
            :class="['badge', 'ms-2', statusClass]"
          >{{ settings.lastSyncStatus }}</span>
          <span
            v-if="!settings.enabled"
            class="badge text-bg-secondary ms-2"
          >automatic sync is off</span>
        </div>
        <button
          class="btn btn-sm btn-outline-primary"
          :disabled="syncing"
          @click="sync"
        >
          <RefreshCw
            :size="14"
            :class="['me-1', { 'spin': syncing }]"
          />
          Sync now
        </button>
      </div>

      <div
        v-if="error"
        class="alert alert-danger small mt-3 mb-0"
      >
        {{ error }}
      </div>
      <div
        v-if="settings.lastSyncMessage"
        class="small text-muted mt-2"
        style="white-space: pre-line"
      >
        {{ settings.lastSyncMessage }}
      </div>

      <table
        v-if="settings.discrepancies.length > 0"
        class="table table-sm small mt-3 mb-0"
      >
        <thead>
          <tr>
            <th>Position differs from the broker</th>
            <th>Broker</th>
            <th>Calculated</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in settings.discrepancies"
            :key="item.name"
          >
            <td>{{ item.name }}</td>
            <td>{{ item.broker }}</td>
            <td>{{ item.calculated }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
