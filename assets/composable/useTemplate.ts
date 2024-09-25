import {ref} from "vue";

const currentTheme = ref(localStorage.getItem("currentTheme") ?? 'light');
export const useTemplate = () => {

  function toggleTheme() {
    currentTheme.value = (currentTheme.value === 'light') ? 'dark' : 'light'
    localStorage.setItem('currentTheme', currentTheme.value)
    setCurrentTheme()
  }

  function setCurrentTheme() {
    const html = document.getElementsByTagName('html')[0]
    if (currentTheme.value === 'dark') {
      html.classList.add('app-dark')
    } else {
      html.classList.remove('app-dark')
    }
  }

  return {
    currentTheme,
    toggleTheme,
    setCurrentTheme
  }
}
