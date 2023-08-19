import {ref} from "vue";
import {Account} from "@/models/account";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {useModal} from "@/composable/useModal";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";

const accounts = ref<Account[]>([])

async function getAccounts() {
  accounts.value = await axios.get('/api/accounts').then((response) => response.data);
}

const {loading, run: asyncGetAccounts} = useAsync(getAccounts)

export default function () {
  // Обработчик модального окна
  const modal = useModal()
  const account = ref<Account>();

  // Delete expense with the specific id
  async function deleteAccount(id: number) {
    await axios.post('/api/accounts/delete/' + id).then((response) => response.data);
  }

  function confirmDeletion(item: { name: string, id: number }, completeCallback?: () => void) {
    modal.open(
      ConfirmModal,
      {
        title: 'Deletion confirmation',
        text: 'Are you sure you want to delete &quot;<b>' + item.name + '</b>&quot;?',
      },
      [
        {
          label: 'Confirm',
          classes: ['btn-danger mr-3 md:mr-0 ml-3'],
          callback: async () => {
            if (item.id) {
              await deleteAccount(item.id)
              if (completeCallback) {
                completeCallback();
              }
            }
            modal.close();
          },
        },
        {
          label: 'Cancel',
          classes: ['btn-secondary'],
          callback: () => modal.close(),
        }
      ]
    );
  }

  async function getAccount(id: number) {
    account.value = await axios.get('/api/accounts/show/' + id).then((response) => response.data);
  }

  const {loading: loadingAccount, run: asyncGetAccount} = useAsync((id: any) => getAccount(id))

  return {
    accounts,
    account,
    loading,
    loadingAccount,
    confirmDeletion,
    getAccounts: asyncGetAccounts,
    getAccount: asyncGetAccount,
  }
}
