import {ref} from "vue";
import {Account, AccountData} from "@/types/account";
import axios from "axios";
import useAsync from "@/utils/use-async";

const accounts = ref<Account[]>([])
const account = ref<AccountData>();

async function getAccounts() {
  accounts.value = await axios.get('/api/accounts').then((response) => response.data);
}

const {loading, run: asyncGetAccounts} = useAsync(getAccounts)

export default function () {
  // Delete expense with the specific id
  async function deleteAccount(id: number) {
    await axios.post('/api/accounts/delete/' + id).then((response) => response.data);
  }

  async function closeAccount(id: number) {
    await axios.post('/api/accounts/close/' + id)
  }

  async function reopenAccount(id: number) {
    await axios.post('/api/accounts/reopen/' + id)
  }

  // A closed account is offered only to the record already made in it
  function selectableAccounts(currentId: number | string | null | undefined): Account[] {
    return accounts.value.filter((item) => !item.isClosed || item.id === Number(currentId))
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
    closeAccount,
    reopenAccount,
    selectableAccounts,
    getAccounts: asyncGetAccounts,
    getAccount: asyncGetAccount,
  }
}
