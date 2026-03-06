export interface PaginationMeta {
  page: number
  perPage: number
  totalItems: number
  totalPages: number
  hasPrev: boolean
  hasNext: boolean
}

export interface PaginatedResponse<T> {
  items: T[]
  pagination: PaginationMeta
}
