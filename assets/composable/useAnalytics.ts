import { ref } from "vue";
import axios from "axios";
import { ClosedDealsListItem, ClosedDealsSummary } from "@/types/analytics";

const closedDeals = ref<{ deals: ClosedDealsListItem[], summary: ClosedDealsSummary }>()
const closedDealsByMonths = ref<{ profitByMonths: number[] }>()

export default function () {
  async function getClosedDeals(filter: object = {}) {
    closedDeals.value = await axios.get('/api/analytics/closed-deals', {params: filter}).then((response) => response.data);
  }

  async function getClosedDealsByMonths(filter: object = {}) {
    closedDealsByMonths.value = await axios.get('/api/analytics/monthly-closed-deals', {params: filter}).then((response) => response.data);
  }

  return {
    getClosedDeals,
    getClosedDealsByMonths,
    closedDeals,
    closedDealsByMonths
  }
}
