import {ref} from "vue";
import {AssetsGroup} from "@/models/account";
import axios from "axios";
import useAsync from "@/utils/use-async";

const assets = ref<AssetsGroup[]>([])

export default function () {
  async function getAssets() {
    assets.value = await axios.get('/api/analytics').then((response) => response.data);
  }

  const {loading, run: asyncGetAssets} = useAsync(getAssets)

  return {
    assets,
    loading,
    getAssets: asyncGetAssets
  }
}
