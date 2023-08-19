import {ref} from "vue";
import {Expenses} from "@/models/expenses";
import axios from "axios";
import useAsync from "@/utils/use-async";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import {useModal} from "@/composable/useModal";

export function useExpenses() {
  // Обработчик модального окна
  const modal = useModal()
  const expenses = ref<Expenses[]>([])
  const summary = ref({})

  /**
   * Получение списка расходов
   */
  async function getExpenses() {
    expenses.value = await axios.get('/api/expenses/list').then((response) => response.data);
  }

  /**
   * Получение суммарной информации
   */
  async function getSummary() {
    summary.value = await axios.get('/api/expenses/summary').then((response) => response.data.summary);
  }

  // Delete expense with the specific id
  async function deleteExpense(id: number) {
    await axios.post('/api/expenses/delete-expense/' + id).then((response) => response.data);
  }

  /**
   * Подтверждение удаления
   * @param item
   * @param completeCallback
   */
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
              await deleteExpense(item.id)
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


  const {loading: loadingExpenses, run: asyncGetExpenses} = useAsync(getExpenses)
  const {loading: loadingSummary, run: asyncGetSummary} = useAsync(getSummary)

  return {
    expenses,
    summary,
    loadingExpenses,
    loadingSummary,
    getExpenses: asyncGetExpenses,
    getSummary: asyncGetSummary,
    confirmDeletion
  }
}
