import axios from 'axios'
import type {ManualOperation, SaleCorrection} from '@/types/journal'
import type {PaginatedResponse} from '@/types/pagination'

export function useAccountOperations() {
  async function getOperations(accountId: number, page: number, perPage: number): Promise<PaginatedResponse<ManualOperation>> {
    return axios
      .get(`/api/accounts/${accountId}/operations`, {params: {page, perPage}})
      .then((response) => response.data)
  }

  async function cancelOperation(accountId: number, id: number): Promise<void> {
    await axios.post(`/api/accounts/${accountId}/operations/${id}/cancel`)
  }

  async function correctSale(accountId: number, id: number, correction: SaleCorrection): Promise<void> {
    await axios.post(`/api/accounts/${accountId}/operations/${id}/edit`, correction)
  }

  return {getOperations, cancelOperation, correctSale}
}
