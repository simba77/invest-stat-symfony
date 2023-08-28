import {ref} from "vue";
import {Account} from "@/models/account";
import axios from "axios";
import useAsync from "@/utils/use-async";

const accounts = ref<Account[]>([])

async function getAccounts() {
  accounts.value = await axios.get('/api/accounts').then((response) => response.data);
}

const {loading, run: asyncGetAccounts} = useAsync(getAccounts)

export default function () {
  const account = ref<Account>();

  // Delete expense with the specific id
  async function deleteAccount(id: number) {
    await axios.post('/api/accounts/delete/' + id).then((response) => response.data);
  }

  async function getAccount(id: number) {
    account.value = await axios.get('/api/deals/' + id).then((response) => response.data);
  }

  const {loading: loadingAccount, run: asyncGetAccount} = useAsync((id: any) => getAccount(id))

  return {
    accounts,
    account,
    loading,
    loadingAccount,
    deleteAccount,
    getAccounts: asyncGetAccounts,
    getAccount: asyncGetAccount,
  }
}
