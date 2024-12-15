import {ref} from "vue";

const pageTitle = ref<string>('')
export const usePage = () => {

  const setPageTitle = (title: string) => {
    pageTitle.value = title
  }

  return {
    pageTitle,
    setPageTitle
  }
}
