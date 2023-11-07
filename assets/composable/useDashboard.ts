import {ref} from "vue";
import axios from "axios";
import {Dashboard} from "@/types/dashboard";
import useAsync from "@/utils/use-async";

export function useDashboard() {

  const dashboard = ref<Dashboard>({
    data: {},
    brokers: [],
    summary: [],
    usd: 0,
  })

  async function getDashboard() {
    dashboard.value = await axios.get('/api/dashboard').then((response) => response.data);
  }

  const {loading, run: asyncGetDashboard} = useAsync(getDashboard)

  return {
    dashboard,
    loading,
    getDashboard: asyncGetDashboard
  }
}
