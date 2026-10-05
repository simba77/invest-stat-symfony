import axios from 'axios'
import type {OperationHistoryFilter, OperationHistoryItem} from '@/types/history'
import type {PaginatedResponse} from '@/types/pagination'

export function useOperationsHistory() {
  async function getHistory(filter: OperationHistoryFilter, page: number, perPage: number): Promise<PaginatedResponse<OperationHistoryItem>> {
    return axios
      .get('/api/operations', {
        params: {
          page,
          perPage,
          accountId: filter.accountId ?? undefined,
          category: filter.category ?? undefined,
        },
      })
      .then((response) => response.data)
  }

  return {getHistory}
}
