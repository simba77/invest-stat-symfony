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
  // A purchase is deleted through its deal
  canCancel: boolean
  // Only the price and the date of a sale can be corrected
  canEdit: boolean
}

export interface SaleCorrection {
  price: string
  // ATOM, e.g. 2026-03-05T09:00:00+00:00
  executedAt: string
}
