import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import {createApp} from 'vue';
import {createPinia} from 'pinia';
import router from './router';
import AppComponent from "./App.vue";
import {authStore} from "./stores/authStore";
import FloatingVue from 'floating-vue';
import './scss/app.scss';
import 'floating-vue/dist/style.css';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import {definePreset} from "@primevue/themes";

const app = createApp(AppComponent)
app.use(createPinia())

app.use(FloatingVue)

const themePreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '{indigo.50}',
      100: '{indigo.100}',
      200: '{indigo.200}',
      300: '{indigo.300}',
      400: '{indigo.400}',
      500: '{indigo.500}',
      600: '{indigo.600}',
      700: '{indigo.700}',
      800: '{indigo.800}',
      900: '{indigo.900}',
      950: '{indigo.950}'
    }
  }
})

app.use(PrimeVue, {
  theme: {
    preset: themePreset,
    options: {
      cssLayer: {
        name: 'primevue',
        order: 'tailwind-base, primevue, tailwind-utilities',
      },
      darkModeSelector: '.app-dark',
    },
  }
})

// Check auth
router.beforeEach((to) => {
  const auth = authStore();
  if (to.meta.requiresAuth && !auth.userData) {
    return '/login'
  }

  // Redirect authorized users from guest pages
  if (to.meta.onlyGuests && auth.userData) {
    return '/'
  }
})

const auth = authStore();

// Checking auth and mount app
auth.checkAuth()
  .finally(() => {
    app.use(router)
    app.mount('#app')
  });
