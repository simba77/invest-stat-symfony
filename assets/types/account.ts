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

export interface AssetsGroupData {
  groupData: AssetsGroup,
  deals: Deal[]
}

export interface AssetsGroup {
  accountId: number
  ticker: string
  shortName: string
  quantity: number
  buyPrice: number
  prevPrice: number
  fullBuyPrice: number
  currentPrice: number
  fullCurrentPrice: number
  fullPrevPrice: number
  targetPrice: number
  fullTargetPrice: number
  profit: number
  profitPercent: number
  dailyProfit: number
  fullDailyProfit: number
  commission: number
  targetProfit: number
  fullTargetProfit: number
  targetProfitPercent: number
  percent: number
  currency: string
  isBlocked: boolean
  isShort: boolean
}

export interface Deal {
  id: number
  accountId: number
  ticker: string
  shortName: string
  quantity: number
  buyPrice: number
  prevPrice: number
  fullBuyPrice: number
  currentPrice: number
  fullCurrentPrice: number
  targetPrice: number
  fullTargetPrice: number
  fullPrevPrice: number
  profit: number
  profitPercent: number
  dailyProfit: number
  fullDailyProfit: number
  commission: number
  targetProfit: number
  fullTargetProfit: number
  fullTargetProfitPercent: number
  percent: number
  currency: string
  isShort: boolean
  isBlocked: boolean
  createdAt: string
  updatedAt: string
}

export interface GroupSummary {
  buyPrice: number
  buyPriceInBaseCurrency: number
  currentPrice: number
  currentPriceInBaseCurrency: number
  profit: number
  profitInBaseCurrency: number
  profitPercent: number
  isBaseCurrency: boolean
}

export interface AccountData {
  account: Account
  deals: {
    dealsList: object
    statuses: { [keys: string]: { code: string, name: string } }
    instrumentTypes: { [keys: string]: { code: string, name: string } }
    currencies: { [keys: string]: { code: string, name: string } }
    summary: object
  }
}

export interface FullPortfolio {
  dealsList: object
  statuses: { [keys: string]: { code: string, name: string } }
  instrumentTypes: { [keys: string]: { code: string, name: string } }
  currencies: { [keys: string]: { code: string, name: string } }
  summary: object
}

export interface SellSecurity {
  id?: number
  accountId: number
  ticker: string
  name: string
  price: number
  quantity: number
}
