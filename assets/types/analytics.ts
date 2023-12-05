export interface ClosedDealsListItem {
  groupData: ClosedDealsGroup
  deals: ClosedDeal[]
}

export interface ClosedDealsSummary {
  buyPrice: number
  sellPrice: number
  profit: number
  profitPercent: number
}

export interface ClosedDealsGroup {
  ticker: string
  shortName: string
  quantity: number
  buyPrice: number
  fullBuyPrice: number
  sellPrice: number
  fullSellPrice: number
  profit: number
  profitPercent: number
  commission: number
  currency: string
  isShort: boolean
  isBlocked: boolean
}

export interface ClosedDeal {
  id: number
  accountId: number
  ticker: string
  shortName: string
  quantity: number
  buyPrice: number
  fullBuyPrice: number
  sellPrice: number
  fullSellPrice: number
  profit: number
  profitPercent: number
  commission: number
  currency: string
  isShort: boolean
  isBlocked: boolean
  createdAt: string
  updatedAt: string
  closingDate: string
}
