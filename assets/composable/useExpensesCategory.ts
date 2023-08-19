import {useModal} from "@/composable/useModal";
import axios from "axios";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";

export function useExpensesCategory() {
  // Обработчик модального окна
  const modal = useModal()

  // Delete expense with the specific id
  async function deleteCategory(id: number) {
    await axios.post('/api/expenses/delete-category/' + id).then((response) => response.data);
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
              await deleteCategory(item.id)
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

  return {
    confirmDeletion
  }
}
