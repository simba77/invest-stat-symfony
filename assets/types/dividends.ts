export interface Dividend {
  id: number
  date: string
  ticker: string
  stockMarket: string
  amount: number
  accountName: string
}

export interface DividendsPage {
  items: Dividend[]
}
