import axios from "axios";

export const useDividends = () => {
  function getDividends() {
    return axios.get('/api/dividends')
  }

  function deleteDividend(id: number) {
    return axios.post('/api/dividends/delete/' + id)
  }

  return {
    getDividends,
    deleteDividend
  }
}
