import {ref} from "vue";

const currentTheme = ref(localStorage.getItem("currentTheme") ?? 'light');
export const useTemplate = () => {

  function toggleTheme() {
    currentTheme.value = (currentTheme.value === 'light') ? 'dark' : 'light'
    localStorage.setItem('currentTheme', currentTheme.value)
  }

  return {
    currentTheme,
    toggleTheme
  }
}
