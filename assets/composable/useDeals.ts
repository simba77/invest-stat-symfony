import axios from "axios";
import {SellSecurity} from "@/models/account";

export function useDeals() {
  // Delete deal with the specific id
  async function deleteDeal(id: number) {
    await axios.post('/api/deals/delete/' + id)
  }

  // Sell asset with the specific id
  async function sell(security: SellSecurity) {
    await axios.post('/api/deals/sell', {
      id: security.id,
      accountId: Number(security.accountId),
      ticker: security.ticker,
      price: Number(security.price),
      quantity: Number(security.quantity),
    })
  }

  return {
    sell,
    deleteDeal,
  }
}
