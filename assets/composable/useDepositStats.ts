import { ref } from 'vue'
import axios from 'axios'

export interface MonthlyStatItem {
  month: string
  deposits: string
  profit: string
}

export interface AccountStatItem {
  id: number
  name: string
  balance: string
  profit: string
  grossInvested: string
  profitPercent: string
  annualizedPercent: string
}

export interface DepositStats {
  summary: {
    balance: string
    profit: string
    profitPercent: string
    annualizedPercent: string
  }
  monthlyStats: MonthlyStatItem[]
  accounts: AccountStatItem[]
}

const stats = ref<DepositStats | null>(null)

export const useDepositStats = () => {
  async function getStats() {
    stats.value = await axios.get('/api/deposits/stats').then((r) => r.data)
  }

  return { stats, getStats }
}
