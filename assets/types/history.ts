export type OperationCategory = 'trades' | 'payouts' | 'money' | 'fees' | 'blocks' | 'other'

/**
 * An operation of any account: from the journal of a manual account, its deposits and payouts,
 * or what the broker reported for a synced account.
 */
export interface OperationHistoryItem {
  key: string
  source: 'journal' | 'broker' | 'deposit' | 'dividend' | 'coupon'
  accountId: number
  accountName: string
  executedAt: string
  type: string
  title: string
  category: OperationCategory
  ticker: string | null
  name: string | null
  quantity: number | null
  price: string | null
  // A bond in the journal is quoted in percent of its nominal, a future in points
  priceUnit: 'money' | 'percent' | 'points'
  commission: string | null
  // The money the operation brought or took, when the source knows it
  amount: string | null
  currency: string | null
}

export interface OperationHistoryFilter {
  accountId: number | null
  category: OperationCategory | null
}
