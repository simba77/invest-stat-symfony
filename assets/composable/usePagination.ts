export type PaginationItem = number | 'ellipsis'

function addRange(store: Set<number>, from: number, to: number, totalPages: number): void {
  const start = Math.max(1, from)
  const end = Math.min(totalPages, to)

  for (let page = start; page <= end; page++) {
    store.add(page)
  }
}

export function buildPaginationItems(
  currentPage: number,
  totalPages: number,
  siblingCount = 1,
  boundaryCount = 2
): PaginationItem[] {
  if (totalPages <= 1) {
    return [1]
  }

  const pages = new Set<number>()
  addRange(pages, 1, boundaryCount, totalPages)
  addRange(pages, totalPages - boundaryCount + 1, totalPages, totalPages)
  addRange(pages, currentPage - siblingCount, currentPage + siblingCount, totalPages)

  const sortedPages = Array.from(pages).sort((a, b) => a - b)
  const result: PaginationItem[] = []

  for (let index = 0; index < sortedPages.length; index++) {
    const page = sortedPages[index]
    const previousPage = index > 0 ? sortedPages[index - 1] : null

    if (previousPage !== null) {
      const gap = page - previousPage

      if (gap === 2) {
        result.push(previousPage + 1)
      } else if (gap > 2) {
        result.push('ellipsis')
      }
    }

    result.push(page)
  }

  return result
}
