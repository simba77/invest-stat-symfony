import {ref} from "vue";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {useModal} from "@/composable/useModal";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import {SavingAccount} from '@/models/savingAccount'

export const useSavingAccounts = () => {
  const accounts = ref<{ data: SavingAccount[] }>({data: []})

  async function getAccounts() {
    accounts.value = await axios.get('/api/savings/accounts').then((response) => response.data);
  }

  const {loading, run: asyncGetAccounts} = useAsync(getAccounts)

  // Обработчик модального окна
  const modal = useModal()

  // Delete expense with the specific id
  async function deleteAccount(id: number) {
    await axios.post('/api/savings/accounts/delete/' + id).then((response) => response.data);
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
    loading,
    creating,
    form,
    formErrors,
    loadingForm,
    confirmDeletion,
    create,
    loadForm,
    getAccounts: asyncGetAccounts
  }
}
