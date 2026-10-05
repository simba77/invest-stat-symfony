export type ManualOperationType = 'buy' | 'short' | 'close' | 'block' | 'unblock' | 'cash_adjustment' | 'block_cash'

/**
 * An entry of the journal of a manual account.
 */
export interface ManualOperation {
  id: number
  type: ManualOperationType
  executedAt: string
  ticker: string | null
  name: string | null
  instrumentType: 'share' | 'bond' | 'future' | null
  quantity: number | null
  // As the security is quoted: money for a share, percent of the nominal for a bond, points for a future
  price: string | null
  commission: string | null
  amount: string | null
  currency: string | null
  lotOpenedAt: string | null
  lotPrice: string | null
}
