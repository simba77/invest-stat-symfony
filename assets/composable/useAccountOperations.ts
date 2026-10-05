import axios from 'axios'
import type {ManualOperation} from '@/types/journal'
import type {PaginatedResponse} from '@/types/pagination'

export function useAccountOperations() {
  async function getOperations(accountId: number, page: number, perPage: number): Promise<PaginatedResponse<ManualOperation>> {
    return axios
      .get(`/api/accounts/${accountId}/operations`, {params: {page, perPage}})
      .then((response) => response.data)
  }

  return {getOperations}
}
