<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import InputText from '@/components/Forms/InputText.vue'
import InputSelect from '@/components/Forms/InputSelect.vue'
import CheckboxComponent from '@/components/Forms/CheckboxComponent.vue'
import { apiErrorMessage, useBrokerSync } from '@/composable/useBrokerSync'
import type { BrokerSyncPage, BrokerSyncSettingsForm, ExternalBrokerAccount } from '@/types/brokerSync'
import type { InputErrors } from '@/types/inputs'

const props = defineProps<{
  accountId: number
}>()

const emit = defineEmits<{
  (e: 'changed'): void
}>()

const brokerSync = useBrokerSync()

const page = ref<BrokerSyncPage | null>(null)
const externalAccounts = ref<ExternalBrokerAccount[]>([])
const form = reactive<BrokerSyncSettingsForm>({
  provider: '',
  token: '',
  externalAccountId: '',
  feeAllocation: '',
  enabled: true,
})
const errors = ref<InputErrors | null>(null)
const message = ref<{ type: 'success' | 'danger', text: string } | null>(null)
const loading = ref(false)
const componentKey = ref(0)

const accountOptions = computed(() => {
  const accounts = externalAccounts.value.length > 0
    ? externalAccounts.value
    : (page.value?.settings
      ? [{ id: page.value.settings.externalAccountId, name: page.value.settings.externalAccountName, readOnly: true }]
      : [])

  return accounts.map((account) => ({
    id: account.id,
    label: `${account.name} (${account.id})` + (account.readOnly ? '' : ' — the token can trade'),
  }))
})

const tradingTokenSelected = computed(() =>
  externalAccounts.value.some((account) => account.id === form.externalAccountId && !account.readOnly)
)

async function load() {
  page.value = await brokerSync.getPage(props.accountId)
  const settings = page.value.settings
  form.provider = settings?.provider ?? page.value.providers[0]?.code ?? ''
  form.feeAllocation = settings?.feeAllocation ?? page.value.feeAllocations[0]?.code ?? ''
  form.externalAccountId = settings?.externalAccountId ?? ''
  form.enabled = settings?.enabled ?? true
  form.token = ''
  externalAccounts.value = []
  componentKey.value += 1
}

async function run(action: () => Promise<void>) {
  loading.value = true
  errors.value = null
  message.value = null
  try {
    await action()
  } catch (error: any) {
    if (error.response?.status === 422 && error.response.data?.violations) {
      errors.value = error.response.data
    } else {
      message.value = { type: 'danger', text: apiErrorMessage(error) }
    }
  } finally {
    componentKey.value += 1
    loading.value = false
  }
}

function loadAccounts() {
  return run(async () => {
    externalAccounts.value = await brokerSync.getExternalAccounts(props.accountId, form.provider, form.token)
    if (!externalAccounts.value.some((account) => account.id === form.externalAccountId)) {
      form.externalAccountId = externalAccounts.value[0]?.id ?? ''
    }
  })
}

function save() {
  return run(async () => {
    await brokerSync.save(props.accountId, form)
    await load()
    message.value = { type: 'success', text: 'The settings are saved. Sync the account on its page or wait for the scheduled sync.' }
    emit('changed')
  })
}

function unlink() {
  if (!confirm('Unlink the account from the broker? The synced records stay and become editable by hand.')) {
    return
  }

  return run(async () => {
    await brokerSync.unlink(props.accountId)
    await load()
    message.value = { type: 'success', text: 'The account is unlinked.' }
    emit('changed')
  })
}

onMounted(() => run(load))
</script>

<template>
  <div class="card mt-4">
    <div class="card-body py-4">
      <form
        class="container-fluid"
        @submit.prevent="save"
      >
        <div class="row justify-content-center">
          <div class="col-12 col-md-8 col-lg-8">
            <div class="mb-4">
              <div class="form-title">
                Broker Sync
              </div>
              <p class="form-description">
                Take deals, deposits, dividends, coupons and cash of this account from the broker.
                A read-only API token is enough.
              </p>
            </div>

            <div
              v-if="page?.settings"
              class="alert alert-secondary small"
            >
              Linked to <b>{{ page.settings.externalAccountName }}</b> ({{ page.settings.externalAccountId }}).
              The token is stored encrypted; leave the field empty to keep it.
            </div>
            <div
              v-else
              class="alert alert-warning small"
            >
              Once the account is linked, its deals, deposits, dividends and coupons entered by hand
              are replaced with the broker's records on the first sync.
            </div>

            <div
              v-if="page"
              class="mb-4 form-stack"
            >
              <input-select
                :key="'provider' + componentKey"
                v-model="form.provider"
                :error="errors"
                :options="page.providers"
                name="provider"
                label="Broker"
                field-value="code"
              />

              <input-text
                :key="'token' + componentKey"
                v-model.trim="form.token"
                :error="errors"
                name="token"
                type="password"
                label="API Token"
                :placeholder="page.settings ? 'Leave empty to keep the stored token' : 'Paste the API token'"
              />

              <div>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  :disabled="loading"
                  @click="loadAccounts"
                >
                  Load broker accounts
                </button>
              </div>

              <input-select
                v-if="accountOptions.length > 0"
                :key="'account' + componentKey"
                v-model="form.externalAccountId"
                :error="errors"
                :options="accountOptions"
                name="externalAccountId"
                label="Broker Account"
                display-name="label"
                field-value="id"
              />
              <div
                v-if="tradingTokenSelected"
                class="text-warning small"
              >
                This token can place orders. The sync only reads, but a read-only token is safer.
              </div>

              <input-select
                :key="'fee' + componentKey"
                v-model="form.feeAllocation"
                :error="errors"
                :options="page.feeAllocations"
                name="feeAllocation"
                label="Trade Fees"
                field-value="code"
              />

              <checkbox-component
                :key="'enabled' + componentKey"
                v-model="form.enabled"
                name="enabled"
                label="Sync automatically"
              />
            </div>

            <div
              v-if="message"
              :class="['alert', 'alert-' + message.type, 'small']"
            >
              {{ message.text }}
            </div>

            <hr class="my-4">

            <div class="d-flex align-items-center">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="loading || accountOptions.length === 0"
              >
                <span
                  v-if="loading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                />
                Save Sync Settings
              </button>

              <button
                v-if="page?.settings"
                type="button"
                class="btn btn-outline-danger ms-3"
                :disabled="loading"
                @click="unlink"
              >
                Unlink
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
