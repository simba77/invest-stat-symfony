import useAsync from "@/utils/use-async";
import {ref} from "vue";
import axios from "axios";
import {Investment} from "@/types/investments";
import {PaginatedResponse} from "@/types/pagination";

const investments = ref<PaginatedResponse<Investment>>({
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

export const useInvestments = () => {

  const {loading: loadingInvestments, run: getInvestments} = useAsync(async (page: number = currentPage.value) => {
    currentPage.value = page

    investments.value = await axios.get('/api/investments', {
      params: {
        page: currentPage.value,
        perPage: perPage.value,
      }
    }).then((response) => response.data);

    if (investments.value?.pagination?.page) {
      currentPage.value = investments.value.pagination.page
    }
  })

  const {loading: deleting, run: deleteInvestment} = useAsync(async (id: any) => {
    await axios.post('/api/investments/delete/' + id);
  })

  return {
    loadingInvestments,
    deleting,
    investments,
    currentPage,
    perPage,
    getInvestments,
    deleteInvestment
  }
}
