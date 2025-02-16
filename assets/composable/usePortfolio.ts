import axios from "axios";
import { ref } from "vue";
import {FullPortfolio} from "@/types/account";

const portfolio = ref<FullPortfolio>()

export const usePortfolio = () => {

  async function getPortfolio() {
    portfolio.value = await axios.get('/api/portfolio').then((response) => response.data);
  }

  return {
    getPortfolio,
    portfolio
  }
}
