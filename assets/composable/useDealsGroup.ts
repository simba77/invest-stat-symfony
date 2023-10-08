import {ref} from "vue";

export const useDealsGroup = () => {
  const openedGroups = ref<any>({})

  function toggleGroup(ticker: string) {
    openedGroups.value[ticker] = !openedGroups.value[ticker];
  }

  return {
    openedGroups,
    toggleGroup
  }
}
