export interface BrokerSyncOption {
  code: string
  name: string
}

export interface BrokerSyncDiscrepancy {
  name: string
  broker: string
  calculated: string
}

export interface BrokerSyncSettings {
  provider: string
  externalAccountId: string
  externalAccountName: string
  feeAllocation: string
  enabled: boolean
  lastSyncedAt: string | null
  lastSyncStatus: 'success' | 'warning' | 'failed' | null
  lastSyncMessage: string | null
  discrepancies: BrokerSyncDiscrepancy[]
}

export interface BrokerSyncPage {
  settings: BrokerSyncSettings | null
  providers: BrokerSyncOption[]
  feeAllocations: BrokerSyncOption[]
}

export interface ExternalBrokerAccount {
  id: string
  name: string
  readOnly: boolean
}

export interface BrokerSyncSettingsForm {
  provider: string
  token: string
  externalAccountId: string
  feeAllocation: string
  enabled: boolean
}

export interface BrokerExpense {
  year: number
  type: string
  name: string
  amount: string
}

export interface BrokerExpenses {
  items: BrokerExpense[]
  total: string
}
