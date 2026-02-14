export type ShowShareResponseDTO = {
  id: number
  name: string
  ticker: string
  logo: string | null
  marketName: string
  currency: string
  price: string
  prevPrice: string
  difference: string
  percent: string
  priceTrend: 'up' | 'down' | 'flat'
  lotSize: string
  isin: string
}
