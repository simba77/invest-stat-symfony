import axios from "axios";
import {ref} from "vue";
import {Dividend} from "@/types/dividends";
import {PaginatedResponse} from "@/types/pagination";

const dividends = ref<PaginatedResponse<Dividend>>({
  items: [],
  pagination: {
    page: 1,
    perPage: 20,
    totalItems: 0,
    totalPages: 1,
    hasPrev: false,
    hasNext: false,
  }
})
const currentPage = ref(1)
const perPage = ref(20)

export const useDividends = () => {
  async function getDividends(page: number = currentPage.value) {
    currentPage.value = page

    dividends.value = await axios.get('/api/dividends', {
      params: {
        page: currentPage.value,
        perPage: perPage.value,
      }
    }).then((response) => response.data)

    if (dividends.value?.pagination?.page) {
      currentPage.value = dividends.value.pagination.page
    }
  }

  function deleteDividend(id: number) {
    return axios.post('/api/dividends/delete/' + id)
  }

  return {
    dividends,
    currentPage,
    perPage,
    getDividends,
    deleteDividend
  }
}
