export interface Violation {
  propertyPath: string
  title: string
}

export interface InputErrors {
  violations: Violation[]
}
