import axios from "axios";
import {SellSecurity} from "@/types/account";

export function useDeals() {
  // Delete deal with the specific id
  async function deleteDeal(id: number) {
    await axios.post('/api/deals/delete/' + id)
  }

  // Sell asset with the specific id
  function sell(security: SellSecurity) {
    return axios.post('/api/deals/sell', {
      id: security.id,
      accountId: Number(security.accountId),
      ticker: security.ticker,
      price: security.price,
      quantity: Number(security.quantity),
    })
  }

  return {
    sell,
    deleteDeal,
  }
}
