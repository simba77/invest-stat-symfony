import useAsync from "@/utils/use-async";
import axios from "axios";
import {Asset, AssetsGroup} from "@/models/account";
import ConfirmModal from "@/components/Modals/ConfirmModal.vue";
import {useModal} from "@/composable/useModal";

export function useAssets() {
  // Обработчик модального окна
  const modal = useModal()

  // Delete asset with the specific id
  async function deleteAsset(id: number) {
    await axios.post('/api/assets/delete/' + id).then((response) => response.data);
  }

  // Sell asset with the specific id
  async function sellAsset(id: number, price: number) {
    await axios.post('/api/assets/sell/' + id, {price: price}).then((response) => response.data);
  }

  /**
   * Подтверждение удаления
   * @param item
   * @param completeCallback
   */
  function confirmDeletion(item: Asset | AssetsGroup, completeCallback?: () => void) {
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
              await deleteAsset(item.id)
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

  const {loading, run: asyncDeleteAsset} = useAsync((id: any) => deleteAsset(id))
  const {run: asyncSellAsset} = useAsync((id: any, price: any) => sellAsset(id, price))
  return {
    loading,
    confirmDeletion,
    sellAsset: asyncSellAsset,
    deleteAsset: asyncDeleteAsset,
  }
}
