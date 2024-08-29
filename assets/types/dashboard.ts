interface SummaryCard {
  name: string
  helpText: string
  percent: number
  dailyChange?: number
  total: number
}

interface DepositAccountCard {
  name: string
  profit: number
  total: number
}

interface StatisticYear {
  year: string
  profit: number
  profitPercent: number
}

export interface Dashboard {
  data: {
    usd: number
    summary: SummaryCard[]
    depositAccounts: DepositAccountCard[]
    statisticByYears: StatisticYear[]
  }
}
