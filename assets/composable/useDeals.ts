import axios from "axios";

export function useDeals() {
  // Delete deal with the specific id
  async function deleteDeal(id: number) {
    await axios.post('/api/deals/delete/' + id)
  }

  // Sell asset with the specific id
  async function sell(id: number, price: number) {
    await axios.post('/api/assets/sell/' + id, {price: price})
  }

  return {
    sell,
    deleteDeal,
  }
}
