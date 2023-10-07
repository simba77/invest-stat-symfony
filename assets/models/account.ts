export interface Account {
  id: number,
  name: string,
  balance: number,
  usdBalance: number,
  deposits: number,
  currentValue: number,
  fullProfit: number,
  blockGroups: BlockGroups[],
}

export interface BlockGroups {
  name: string,
  blocked: boolean,
  items: CurrencyGroup[]
}

export interface CurrencyGroup {
  name: string,
  items: AssetsGroup[]
}

export interface AssetsGroup {
  accountId: number
  ticker: string
  shortName: string
  quantity: number
  buyPrice: number
  fullBuyPrice: number
  currentPrice: number
  fullCurrentPrice: number
  targetPrice: number
  fullTargetPrice: number
  profit: number
  profitPercent: number
  commission: number
  targetProfit: number
  fullTargetProfit: number
  targetProfitPercent: number
  percent: number
  currency: string
  isBlocked: boolean
  isShort: boolean
}

export interface AssetsGroupOld {
  group: boolean,
  id?: number,
  accountId: number,
  ticker: string,
  name: string,
  stockMarket: string,
  blocked: boolean | null,
  quantity: number,
  avgBuyPrice: number,
  fullBuyPrice: number,
  avgTargetPrice: number,
  fullTargetPrice: number,
  currentPrice: number,
  fullCurrentPrice: number,
  profit: number,
  profitPercent: number,
  fullCommission: number,
  targetProfit: number,
  fullTargetProfit: number,
  fullTargetProfitPercent: number,
  groupPercent: number,
  currency: string,
  showItems: boolean,
  isShort: boolean,
  items: [],

  // Поля для Subtotal строк
  isSubTotal: boolean,
  isBaseCurrency: boolean,
  fullBuyPriceConverted: number,
  fullCurrentPriceConverted: number,
  profitConverted: number,

}

export interface Asset {
  id: number,
  createdAt: string,
  updatedAt: string,
  accountId: number,
  ticker: string,
  name: string,
  stockMarket: string,
  blocked: boolean | null,
  quantity: number,
  avgBuyPrice: number,
  fullBuyPrice: number,
  avgTargetPrice: number,
  fullTargetPrice: number,
  currentPrice: number,
  fullCurrentPrice: number,
  profit: number,
  profitPercent: number,
  commission: number,
  targetProfit: number,
  fullTargetProfit: number,
  fullTargetProfitPercent: number,
  groupPercent: number,
  currency: string,
  isShort: boolean,
}

