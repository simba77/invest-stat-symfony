export interface Dividend {
  id: number
  date: string
  ticker: string
  amount: number
  accountName: string
}

export interface DividendsPage {
  items: Dividend[]
}
