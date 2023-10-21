export interface DepositAccount {
  id: number
  name: string
}

export interface Deposit {
  id: number
  date: string
  accountName: string
  sum: number
  typeName: string
}
