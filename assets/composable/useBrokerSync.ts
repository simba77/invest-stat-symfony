import axios from 'axios'
import type {
  BrokerExpenses,
  BrokerSyncPage,
  BrokerSyncSettingsForm,
  ExternalBrokerAccount,
} from '@/types/brokerSync'

/**
 * The message the API returns for broker errors and refused changes.
 */
export function apiErrorMessage(error: any): string {
  return error?.response?.data?.message ?? 'An error has occurred'
}

export function useBrokerSync() {
  async function getPage(accountId: number): Promise<BrokerSyncPage> {
    return axios.get(`/api/accounts/${accountId}/broker-sync`).then((response) => response.data)
  }

  async function getExternalAccounts(accountId: number, provider: string, token: string): Promise<ExternalBrokerAccount[]> {
    return axios
      .post(`/api/accounts/${accountId}/broker-sync/external-accounts`, {provider, token})
      .then((response) => response.data.items)
  }

  async function save(accountId: number, form: BrokerSyncSettingsForm): Promise<void> {
    await axios.post(`/api/accounts/${accountId}/broker-sync`, form)
  }

  async function unlink(accountId: number): Promise<void> {
    await axios.post(`/api/accounts/${accountId}/broker-sync/delete`)
  }

  async function run(accountId: number): Promise<BrokerSyncPage> {
    return axios.post(`/api/accounts/${accountId}/broker-sync/run`).then((response) => response.data)
  }

  async function getExpenses(accountId: number): Promise<BrokerExpenses> {
    return axios.get(`/api/accounts/${accountId}/broker-sync/expenses`).then((response) => response.data)
  }

  return {
    getPage,
    getExternalAccounts,
    save,
    unlink,
    run,
    getExpenses,
  }
}
