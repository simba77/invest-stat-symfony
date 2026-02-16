import {Deal} from "@/types/account";
import {ClosedDeal} from "@/types/analytics";

export type PriceTrendEnum = 'up' | 'down' | 'flat'

export type ShowSharePortfolioDTO = {
  quantity: number
  fullPrice: string
  fullProfit: string
  fullProfitPercent: string
  fullProfitTrend: PriceTrendEnum
  averageBuyPrice: string
  portfolioPercent: string
  closedDealsProfit: string
  closedDealsProfitPercent: string
  closedDealsProfitTrend: PriceTrendEnum
  sumOfDividends: string
}

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
  priceTrend: PriceTrendEnum
  lotSize: string
  isin: string
  portfolio: ShowSharePortfolioDTO
  openPositions: Deal[]
  closedPositions: ClosedDeal[]
}
