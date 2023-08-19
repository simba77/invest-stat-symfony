import {ref} from "vue";
import axios from "axios";
import useAsync from "@/utils/use-async";
import {useModal} from "@/composable/useModal";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import {Saving} from '@/models/savingAccount'

export const useSavings = () => {
  const savings = ref<{ data: Saving[] }>({data: []})

  const {loading, run: getSavings} = useAsync(async () => {
    savings.value = await axios.get('/api/savings').then((response) => response.data);
  })

  // Обработчик модального окна
  const modal = useModal()

  // Delete expense with the specific id
  async function deleteSaving(id: number) {
    await axios.post('/api/savings/delete/' + id).then((response) => response.data);
  }

  function confirmDeletion(item: { sum: string, id: number } | any, completeCallback?: () => void) {
    modal.open(
      ConfirmModal,
      {
        title: 'Deletion confirmation',
        text: 'Are you sure you want to delete &quot;<b>' + item.sum + '</b>&quot;?',
      },
      [
        {
          label: 'Confirm',
          classes: ['btn-danger mr-3 md:mr-0 ml-3'],
          callback: async () => {
            if (item.id) {
              await deleteSaving(item.id)
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
    savings,
    loading,
    creating,
    form,
    formErrors,
    loadingForm,
    confirmDeletion,
    create,
    loadForm,
    getSavings
  }
}
