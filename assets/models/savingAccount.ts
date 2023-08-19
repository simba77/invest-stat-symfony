export interface SavingAccount {
  id: number
  name: string
}

export interface Saving {
  id: number
  date: string
  account: SavingAccount
  sum: number
  type: string
  currency: string
}
