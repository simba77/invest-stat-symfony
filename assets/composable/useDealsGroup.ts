import {ref} from "vue";

export const useDealsGroup = () => {
  const openedGroups = ref<any>({})

  function toggleGroup(ticker: string) {
    console.log(ticker)
    console.log(openedGroups.value)
    openedGroups.value[ticker] = !openedGroups.value[ticker];
  }

  return {
    openedGroups,
    toggleGroup
  }
}
