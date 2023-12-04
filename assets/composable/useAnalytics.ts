import { ref } from "vue";
import axios from "axios";
import { ClosedDealsListItem, ClosedDealsSummary } from "@/types/analytics";

const closedDeals = ref<{ deals: ClosedDealsListItem[], summary: ClosedDealsSummary }>()

export default function () {
  async function getClosedDeals(filter: object = {}) {
    closedDeals.value = await axios.get('/api/analytics/closed-deals', {params: filter}).then((response) => response.data);
  }

  return {
    getClosedDeals,
    closedDeals
  }
}
