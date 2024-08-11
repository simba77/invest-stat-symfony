import axios from "axios";

export const useCoupons = () => {
  function getCoupons() {
    return axios.get('/api/coupons')
  }

  function deleteCoupon(id: number) {
    return axios.post('/api/coupons/delete/' + id)
  }

  return {
    getCoupons,
    deleteCoupon
  }
}
