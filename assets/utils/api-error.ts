/**
 * The message the API returns for broker errors and refused changes.
 */
export function apiErrorMessage(error: any): string {
  return error?.response?.data?.message ?? 'An error has occurred'
}
