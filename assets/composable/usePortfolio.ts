import axios from "axios";
import { ref } from "vue";
import { AccountData } from "@/types/account";

const portfolio = ref<AccountData>()

export const usePortfolio = () => {

  async function getPortfolio() {
    portfolio.value = await axios.get('/api/deals').then((response) => response.data);
  }

  return {
    getPortfolio,
    portfolio
  }
}
