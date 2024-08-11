export interface Coupon {
  id: number
  date: string
  ticker: string
  stockMarket: string
  amount: number
  accountName: string
}

export interface CouponsPage {
  items: Coupon[]
}
