import {ref} from "vue";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {DepositAccount} from '@/models/depositAccount'

export const useDepositAccounts = () => {
  const accounts = ref<{ items: DepositAccount[] }>()

  async function getAccounts() {
    accounts.value = await axios.get('/api/deposits/accounts').then((response) => response.data);
  }

  async function deleteAccount(id: number) {
    await axios.post('/api/savings/accounts/delete/' + id).then((response) => response.data);
  }

  // Форма создания/редактирования
  const form = ref({})
  const formErrors = ref({})

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const {loading: creating, run: create} = useAsync(async (item: any, completeCallback?: (response: any) => void) => {
    await axios.post('/api/savings/accounts/create/' + item.id, item)
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
    form.value = await axios.get('/api/savings/accounts/create/' + id).then((response) => response.data)
  })

  return {
    accounts,
    creating,
    form,
    formErrors,
    loadingForm,
    create,
    loadForm,
    deleteAccount,
    getAccounts
  }
}
