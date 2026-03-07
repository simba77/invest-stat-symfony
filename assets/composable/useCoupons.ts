import axios from "axios";
import {ref} from "vue";
import {Coupon} from "@/types/coupons";
import {PaginatedResponse} from "@/types/pagination";

const coupons = ref<PaginatedResponse<Coupon>>({
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

export const useCoupons = () => {
  async function getCoupons(page: number = currentPage.value) {
    currentPage.value = page

    coupons.value = await axios.get('/api/coupons', {
      params: {
        page: currentPage.value,
        perPage: perPage.value,
      }
    }).then((response) => response.data)

    if (coupons.value?.pagination?.page) {
      currentPage.value = coupons.value.pagination.page
    }
  }

  function deleteCoupon(id: number) {
    return axios.post('/api/coupons/delete/' + id)
  }

  return {
    coupons,
    currentPage,
    perPage,
    getCoupons,
    deleteCoupon
  }
}
