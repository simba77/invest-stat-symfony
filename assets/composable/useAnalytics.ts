import {ref} from "vue";
import axios from "axios";
import {ClosedDealsListItem} from "@/types/analytics";

const closedDeals = ref<{deals: ClosedDealsListItem[]}>()

export default function () {
  async function getClosedDeals() {
    closedDeals.value = await axios.get('/api/analytics/closed-deals').then((response) => response.data);
  }

  return {
    getClosedDeals,
    closedDeals
  }
}
