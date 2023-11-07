import {ref} from "vue";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {Deposit} from '@/types/depositAccount'

const deposits = ref<{ items: Deposit[] }>()

export const useDeposits = () => {
  async function getDeposits() {
    deposits.value = await axios.get('/api/deposits').then((response) => response.data);
  }

  async function deleteDeposit(id: number) {
    await axios.post('/api/deposits/delete/' + id);
  }

  // Форма создания/редактирования
  const form = ref({})
  const formErrors = ref({})

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const {loading: creating, run: create} = useAsync(async (item: any, completeCallback?: (response: any) => void) => {
    await axios.post('/api/savings/create/' + item.id, item)
      .then((response) => {
        if (completeCallback) {
          completeCallback(response)
        }
      })
      .catch((e) => {
        if (e.response.status === 422) {
          formErrors.value = e.response.data.errors;
        } else {
          throw e;
        }
      })
  })

  const {loading: loadingForm, run: loadForm} = useAsync(async (id: any) => {
    form.value = await axios.get('/api/savings/create/' + id).then((response) => response.data)
  })

  return {
    deposits,
    creating,
    form,
    formErrors,
    loadingForm,
    create,
    loadForm,
    getDeposits,
    deleteDeposit
  }
}
