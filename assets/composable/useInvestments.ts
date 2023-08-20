import useAsync from "@/utils/use-async";
import {ref} from "vue";
import axios from "axios";
import {Investment} from "@/models/investments";

const investments = ref<Investment[]>([])

export const useInvestments = () => {

  const {loading: loadingInvestments, run: getInvestments} = useAsync(async () => {
    investments.value = await axios.get('/api/investments').then((response) => response.data);
  })

  const {loading: deleting, run: deleteInvestment} = useAsync(async (id: any) => {
    await axios.post('/api/investments/delete/' + id);
  })

  return {
    loadingInvestments,
    deleting,
    investments,
    getInvestments,
    deleteInvestment
  }
}
